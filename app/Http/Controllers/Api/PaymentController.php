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
            return $this->error($e->getMessage(), 403);
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

            $response = [
                'payment' => $this->formatPayment($payment),
                'message' => 'USSD push sent to your phone. Please complete the payment.',
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
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Payment initiation failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to initiate payment. Please try again.', 500);
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
            return $this->error($e->getMessage(), 403);
        }

        $plan = SubscriptionPlan::active()->findOrFail($request->plan_id);

        try {
            $payment = $this->paymentService->initiate(
                user: $user,
                phone: $request->phone,
                operator: null,
                amount: $plan->price,
                paymentType: 'subscription',
                payableId: $plan->id,
            );

            $response = [
                'payment' => $this->formatPayment($payment),
                'message' => 'USSD push sent to your phone. Please complete the payment.',
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
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            Log::error('Subscription payment failed', ['error' => $e->getMessage()]);
            return $this->error('Failed to initiate payment. Please try again.', 500);
        }
    }

    /**
     * Check payment status.
     * Authenticated users see their own; guests look up by reference only.
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

        $data = $this->formatPayment($payment);

        // If completed, include result details
        if ($payment->isCompleted()) {
            $user = User::find($payment->user_id);
            $data['coin_balance'] = $user->coin_balance;
            $data['is_vip'] = $user->isVipActive();
        }

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
        Log::info('MobilePayment callback received', $request->all());

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
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            Log::error('MobilePayment callback exception', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal error processing callback',
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
