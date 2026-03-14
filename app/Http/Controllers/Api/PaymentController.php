<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinPackage;
use App\Models\MobilePayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\CoinService;
use App\Services\MobilePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected MobilePaymentService $paymentService,
        protected CoinService $coinService
    ) {}

    /**
     * Resolve authenticated user or find/create from phone number.
     * If the user is a guest (no auth), auto-register from phone.
     */
    protected function resolveUser(Request $request): array
    {
        $authUser = $request->user();

        if ($authUser) {
            return ['user' => $authUser, 'token' => null, 'is_new' => false];
        }

        // Guest flow — find or create user from phone
        $phone = $this->paymentService->normalizePhone($request->phone);

        $user = User::where('phone', $phone)
            ->orWhere('phone', $request->phone)
            ->first();

        $isNew = false;

        if (!$user) {
            // Auto-register with phone number
            $user = User::create([
                'name' => $request->input('name', 'User ' . substr($phone, -4)),
                'phone' => $phone,
                'password' => bcrypt(Str::random(16)), // random password
                'is_active' => true,
            ]);

            $user->assignRole('user');

            // Grant signup bonus
            $signupBonus = (int) config('dramabox.signup_bonus_coins', 50);
            if ($signupBonus > 0) {
                $this->coinService->credit(
                    $user,
                    $signupBonus,
                    'signup_bonus',
                    'Welcome bonus coins!'
                );
                $user->refresh();
            }

            $isNew = true;
        }

        if (!$user->is_active) {
            throw new \RuntimeException('This account has been suspended.');
        }

        // Create a Sanctum token so the guest is now logged in
        $token = $user->createToken('mobile-payment')->plainTextToken;

        return ['user' => $user, 'token' => $token, 'is_new' => $isNew];
    }

    /**
     * Initiate coin package purchase via mobile money.
     * Works for both authenticated users and guests (phone-only).
     */
    public function purchaseCoins(Request $request): JsonResponse
    {
        $request->validate([
            'package_id' => ['required', 'exists:coin_packages,id'],
            'phone' => ['required', 'string', 'min:9', 'max:15'],
            'name' => ['sometimes', 'string', 'max:100'],
        ]);

        try {
            $resolved = $this->resolveUser($request);
            $user = $resolved['user'];
        } catch (\RuntimeException $e) {
            return $this->error('This account has been suspended. Please contact support.', 403, null, 'ACCOUNT_SUSPENDED');
        }

        $package = CoinPackage::active()->findOrFail($request->package_id);

        try {
            $payment = $this->paymentService->initiate(
                user: $user,
                phone: $request->phone,
                operator: null,
                amount: $package->price,
                paymentType: 'coin_purchase',
                payableId: $package->id,
            );

            if ($payment->status === 'failed') {
                return $this->error($payment->failure_reason ?? 'Payment could not be processed. Please try again.', 422, null, 'PAYMENT_FAILED');
            }

            $response = [
                'payment' => $this->formatPayment($payment),
                'message' => 'USSD push sent to your phone. Please complete the payment.',
                'poll_url' => "/api/v1/payments/{$payment->reference}/status",
                'poll_interval' => 5,
                'should_continue_polling' => true,
            ];

            // If guest, include auth token and user info
            if ($resolved['token']) {
                $response['token'] = $resolved['token'];
                $response['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'coin_balance' => $user->coin_balance,
                    'is_new_account' => $resolved['is_new'],
                ];
            }

            return $this->created($response, 'Payment initiated');
        } catch (\InvalidArgumentException $e) {
            return $this->error('Please check your phone number and try again.', 422, null, 'INVALID_PHONE');
        } catch (\Exception $e) {
            Log::error('Payment initiation failed', ['error' => $e->getMessage()]);
            return $this->error('We couldn\'t process your payment right now. Please try again.', 500, null, 'PAYMENT_ERROR');
        }
    }

    /**
     * Initiate subscription purchase via mobile money.
     * Works for both authenticated users and guests (phone-only).
     */
    public function purchaseSubscription(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => ['required', 'exists:subscription_plans,id'],
            'phone' => ['required', 'string', 'min:9', 'max:15'],
            'name' => ['sometimes', 'string', 'max:100'],
        ]);

        try {
            $resolved = $this->resolveUser($request);
            $user = $resolved['user'];
        } catch (\RuntimeException $e) {
            return $this->error('This account has been suspended. Please contact support.', 403, null, 'ACCOUNT_SUSPENDED');
        }

        $plan = SubscriptionPlan::active()->findOrFail($request->plan_id);

        // Prevent duplicate subscription purchase if user already has an active one
        $activeSubscription = \App\Models\Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->first();

        if ($activeSubscription) {
            $daysRemaining = (int) now()->diffInDays($activeSubscription->ends_at, false);
            return $this->error(
                "You already have an active subscription ({$activeSubscription->plan->name}) with {$daysRemaining} days remaining. Please wait for it to expire or cancel it first.",
                422,
                null,
                'SUBSCRIPTION_ALREADY_ACTIVE'
            );
        }

        // Also check for any pending subscription payment to avoid double charges
        $pendingPayment = MobilePayment::where('user_id', $user->id)
            ->where('payment_type', 'subscription')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if ($pendingPayment) {
            return $this->error(
                'You already have a pending subscription payment. Please complete or wait for it to expire before trying again.',
                422,
                null,
                'PAYMENT_PENDING'
            );
        }

        try {
            $payment = $this->paymentService->initiate(
                user: $user,
                phone: $request->phone,
                operator: null,
                amount: $plan->price,
                paymentType: 'subscription',
                payableId: $plan->id,
            );

            if ($payment->status === 'failed') {
                return $this->error($payment->failure_reason ?? 'Payment could not be processed. Please try again.', 422, null, 'PAYMENT_FAILED');
            }

            $response = [
                'payment' => $this->formatPayment($payment),
                'message' => 'USSD push sent to your phone. Please complete the payment.',
                'poll_url' => "/api/v1/payments/{$payment->reference}/status",
                'poll_interval' => 5,
                'should_continue_polling' => true,
            ];

            // If guest, include auth token and user info
            if ($resolved['token']) {
                $response['token'] = $resolved['token'];
                $response['user'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'is_vip' => $user->isVipActive(),
                    'is_new_account' => $resolved['is_new'],
                ];
            }

            return $this->created($response, 'Payment initiated');
        } catch (\InvalidArgumentException $e) {
            return $this->error('Please check your phone number and try again.', 422, null, 'INVALID_PHONE');
        } catch (\Exception $e) {
            Log::error('Subscription payment failed', ['error' => $e->getMessage()]);
            return $this->error('We couldn\'t process your payment right now. Please try again.', 500, null, 'PAYMENT_ERROR');
        }
    }

    /**
     * Check payment status.
     * Authenticated users see their own; guests look up by reference only.
     * Actively polls the gateway if the payment is still pending.
     */
    public function status(Request $request, string $reference): JsonResponse
    {
        $query = MobilePayment::where('reference', $reference);

        // If authenticated, scope to that user
        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        }

        $payment = $query->first();

        if (!$payment) {
            return $this->notFound('Payment not found');
        }

        // Actively poll the gateway if payment is still pending/processing
        if ($payment->isPending() && $payment->gateway_reference) {
            $this->paymentService->pollGatewayStatus($payment);
            $payment->refresh();
        }

        // Auto-expire payments that exceeded the expiry window
        if ($payment->isPending() && $payment->expires_at && $payment->expires_at->isPast()) {
            $payment->markFailed('Payment expired');
            $payment->refresh();
        }

        $data = $this->formatPayment($payment);

        // If completed, include result details
        if ($payment->isCompleted()) {
            $user = User::find($payment->user_id);
            $data['coin_balance'] = $user->coin_balance;
            $data['is_vip'] = $user->isVipActive();
        }

        // Tell the app whether to keep polling and how often
        $data['should_continue_polling'] = $payment->isPending();
        $data['poll_interval'] = $payment->isPending() ? 5 : 0;

        return $this->success($data);
    }

    /**
     * Get user's mobile payment history.
     */
    public function history(Request $request): JsonResponse
    {
        $payments = $this->paymentService->getUserPayments(
            $request->user(),
            $request->input('per_page', 20)
        );

        return $this->paginated($payments);
    }

    /**
     * Payment gateway callback (no auth — called by payin.co.tz).
     *
     * Expected payload:
     * {
     *   "request_ref": "PAY-A1B2C3D4E5F6",
     *   "type": "collection",
     *   "status": "completed",
     *   "amount": 10000,
     *   "charge": 200,
     *   "phone": "255712345678",
     *   "operator": "mpesa",
     *   "operator_ref": "MPESA123456",
     *   "reference": "PAY-A1B2C3D4E5F6",
     *   "completed_at": "2026-01-15T10:30:45.000000Z"
     * }
     */
    public function callback(Request $request): JsonResponse
    {
        Log::info('MobilePayment callback received', [
            'payload' => $request->all(),
            'ip' => $request->ip(),
            'signature_headers' => array_filter([
                'X-Signature' => $request->header('X-Signature'),
                'X-Webhook-Signature' => $request->header('X-Webhook-Signature'),
                'X-Payin-Signature' => $request->header('X-Payin-Signature'),
            ]),
        ]);

        // Verify webhook signature if configured
        if (!$this->paymentService->verifyWebhookSignature($request)) {
            Log::warning('MobilePayment callback: invalid signature', [
                'ip' => $request->ip(),
                'payload' => $request->all(),
            ]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        try {
            $payment = $this->paymentService->handleCallback($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Callback processed',
                'reference' => $payment->reference,
                'status' => $payment->status,
            ]);
        } catch (\RuntimeException $e) {
            Log::error('MobilePayment callback error', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment reference not found.',
                'error_code' => 'PAYMENT_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            Log::error('MobilePayment callback exception', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal error processing callback.',
                'error_code' => 'SERVER_ERROR',
            ], 500);
        }
    }

    /**
     * Format payment for API response.
     */
    private function formatPayment(MobilePayment $payment): array
    {
        return [
            'reference' => $payment->reference,
            'phone' => $payment->phone,
            'operator' => $payment->operator,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_type' => $payment->payment_type,
            'status' => $payment->status,
            'gateway_reference' => $payment->gateway_reference,
            'failure_reason' => $payment->failure_reason,
            'completed_at' => $payment->completed_at?->toISOString(),
            'created_at' => $payment->created_at->toISOString(),
        ];
    }
}
