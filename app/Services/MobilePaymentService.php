<?php

namespace App\Services;

use App\Models\CoinPackage;
use App\Models\MobilePayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\AppSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MobilePaymentService
{
    public function __construct(
        protected CoinService $coinService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Initiate a mobile payment (push USSD to user's phone).
     */
    public function initiate(
        User $user,
        string $phone,
        ?string $operator,
        float $amount,
        string $paymentType,
        int $payableId,
    ): MobilePayment {
        // Normalize phone number to 255XXXXXXXXX
        $phone = $this->normalizePhone($phone);

        // Auto-detect operator from phone prefix if not provided
        $operator = $operator ?: $this->detectOperator($phone);

        // Validate operator
        $this->validateOperator($operator);

        // Resolve payable model
        $payableType = match ($paymentType) {
            'coin_purchase' => CoinPackage::class,
            'subscription' => SubscriptionPlan::class,
            default => throw new \InvalidArgumentException("Invalid payment type: {$paymentType}"),
        };

        // Generate unique internal reference
        $reference = 'PAY-' . strtoupper(Str::random(12));

        // Create payment record
        $payment = MobilePayment::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'phone' => $phone,
            'operator' => $operator,
            'amount' => $amount,
            'currency' => 'TZS',
            'payment_type' => $paymentType,
            'payable_id' => $payableId,
            'payable_type' => $payableType,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
        ]);

        // Push to payment gateway
        $gatewayResponse = $this->pushToGateway($phone, $amount, $operator, $reference);

        // Update payment with gateway response
        $payment->update([
            'gateway_reference' => $gatewayResponse['request_ref'] ?? null,
            'push_response' => $gatewayResponse,
        ]);

        // If gateway returned an error, mark as failed
        if (isset($gatewayResponse['error']) || ($gatewayResponse['status'] ?? '') === 'error') {
            $payment->markFailed(
                $gatewayResponse['message'] ?? 'Gateway push failed',
                $gatewayResponse
            );
        }

        return $payment;
    }

    /**
     * Push collection request to payin.co.tz gateway.
     */
    protected function pushToGateway(string $phone, float $amount, string $operator, string $reference): array
    {
        $gatewayUrl = $this->getGatewayConfig('payment_gateway_url');
        $apiKey = $this->getGatewayConfig('payment_gateway_api_key');
        $apiSecret = $this->getGatewayConfig('payment_gateway_api_secret');
        $timeout = (int) ($this->getGatewayConfig('payment_gateway_timeout') ?: 30);

        if (empty($apiKey) || empty($apiSecret)) {
            Log::error('MobilePayment: Gateway API key/secret not configured');
            return ['error' => true, 'message' => 'Payment gateway not configured'];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-API-Key' => $apiKey,
                    'X-API-Secret' => $apiSecret,
                ])
                ->post("{$gatewayUrl}/collection", [
                    'phone' => $phone,
                    'amount' => (int) $amount,
                    'operator' => $operator,
                    'reference' => $reference,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('MobilePayment: Gateway returned error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'reference' => $reference,
            ]);

            return [
                'error' => true,
                'message' => 'Gateway error: ' . ($response->json('message') ?? $response->body()),
                'status_code' => $response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('MobilePayment: Gateway request failed', [
                'reference' => $reference,
                'exception' => $e->getMessage(),
            ]);

            return [
                'error' => true,
                'message' => 'Connection to payment gateway failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature from payment gateway.
     *
     * Checks HMAC-SHA256 signature sent in the X-Signature header.
     * Also validates source IP if PAYMENT_GATEWAY_IPS is configured.
     */
    public function verifyWebhookSignature(\Illuminate\Http\Request $request): bool
    {
        $secret = $this->getGatewayConfig('payment_gateway_api_secret');

        // If no secret is configured, skip verification (dev/testing mode)
        if (empty($secret)) {
            Log::warning('MobilePayment: Webhook signature verification skipped — no API secret configured');
            return true;
        }

        // Check IP whitelist if configured
        $allowedIps = $this->getGatewayConfig('payment_gateway_allowed_ips');
        if (!empty($allowedIps)) {
            $ips = array_map('trim', explode(',', $allowedIps));
            if (!in_array($request->ip(), $ips)) {
                Log::warning('MobilePayment: Callback from unauthorized IP', ['ip' => $request->ip()]);
                return false;
            }
        }

        // Verify HMAC-SHA256 signature
        $signature = $request->header('X-Signature') ?? $request->header('X-Webhook-Signature');
        if (empty($signature)) {
            Log::warning('MobilePayment: No webhook signature header found');
            return false;
        }

        $payload = $request->getContent();
        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    /**
     * Handle callback from payment gateway.
     *
     * Expected callback payload:
     * {
     *   "request_ref": "PAY-A1B2C3D4E5F6",
     *   "type": "collection",
     *   "status": "completed",
     *   "amount": 10000,
     *   "charge": 200,
     *   "phone": "255712345678",
     *   "operator": "mpesa",
     *   "operator_ref": "MPESA123456",
     *   "reference": "ORDER-001",
     *   "completed_at": "2026-01-15T10:30:45.000000Z"
     * }
     */
    public function handleCallback(array $data): MobilePayment
    {
        $requestRef = $data['request_ref'] ?? null;
        $reference = $data['reference'] ?? null;

        // Find payment by gateway reference or internal reference
        $payment = MobilePayment::where('gateway_reference', $requestRef)
            ->orWhere('reference', $reference)
            ->first();

        if (!$payment) {
            Log::warning('MobilePayment: Callback for unknown payment', $data);
            throw new \RuntimeException('Payment not found for reference: ' . ($requestRef ?? $reference));
        }

        // Already processed — idempotent
        if ($payment->isCompleted()) {
            return $payment;
        }

        $status = strtolower($data['status'] ?? 'failed');

        if ($status === 'completed') {
            return $this->processSuccessfulPayment($payment, $data);
        }

        // Failed / cancelled / expired
        $payment->markFailed(
            $data['failure_reason'] ?? "Payment {$status}",
            $data
        );

        return $payment;
    }

    /**
     * Process a successful payment — grant coins or activate subscription.
     */
    protected function processSuccessfulPayment(MobilePayment $payment, array $callbackData): MobilePayment
    {
        return DB::transaction(function () use ($payment, $callbackData) {
            $operatorRef = $callbackData['operator_ref'] ?? '';
            $payment->markCompleted($operatorRef, $callbackData);

            $user = $payment->user;

            if ($payment->payment_type === 'coin_purchase') {
                $this->fulfillCoinPurchase($payment, $user);
            } elseif ($payment->payment_type === 'subscription') {
                $this->fulfillSubscription($payment, $user);
            }

            return $payment;
        });
    }

    /**
     * Grant coins for a coin package purchase.
     */
    protected function fulfillCoinPurchase(MobilePayment $payment, User $user): void
    {
        $package = CoinPackage::find($payment->payable_id);

        if (!$package) {
            Log::error('MobilePayment: CoinPackage not found', ['id' => $payment->payable_id]);
            return;
        }

        $totalCoins = $package->coins + $package->bonus_coins;

        $this->coinService->credit(
            $user,
            $totalCoins,
            'purchase',
            "Purchased {$package->name} via {$payment->operator}",
            $payment,
        );

        // Also create a Purchase record for consistency
        \App\Models\Purchase::create([
            'user_id' => $user->id,
            'coin_package_id' => $package->id,
            'order_id' => $payment->reference,
            'payment_provider' => $payment->operator,
            'provider_transaction_id' => $payment->gateway_transaction_id,
            'amount' => $payment->amount,
            'currency' => 'TZS',
            'coins_granted' => $totalCoins,
            'status' => 'completed',
            'metadata' => [
                'mobile_payment_id' => $payment->id,
                'phone' => $payment->phone,
                'operator' => $payment->operator,
            ],
        ]);
    }

    /**
     * Activate subscription after payment.
     */
    protected function fulfillSubscription(MobilePayment $payment, User $user): void
    {
        $plan = SubscriptionPlan::find($payment->payable_id);

        if (!$plan) {
            Log::error('MobilePayment: SubscriptionPlan not found', ['id' => $payment->payable_id]);
            return;
        }

        $this->subscriptionService->subscribe(
            $user,
            $plan,
            $payment->operator,
            $payment->gateway_transaction_id,
            null,
            [
                'mobile_payment_id' => $payment->id,
                'phone' => $payment->phone,
                'operator' => $payment->operator,
            ],
        );
    }

    /**
     * Get payment status (for polling from frontend).
     */
    public function getStatus(string $reference): ?MobilePayment
    {
        return MobilePayment::where('reference', $reference)->first();
    }

    /**
     * Get user's payment history.
     */
    public function getUserPayments(User $user, int $perPage = 20)
    {
        return MobilePayment::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Normalize phone to 255XXXXXXXXX format.
     */
    public function normalizePhone(string $phone): string
    {
        // Remove spaces, dashes, plus sign
        $phone = preg_replace('/[\s\-\+]/', '', $phone);

        // 0XXXXXXXXX → 255XXXXXXXXX
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            $phone = '255' . substr($phone, 1);
        }

        // +255XXXXXXXXX → 255XXXXXXXXX (already handled by removing +)

        if (!preg_match('/^255\d{9}$/', $phone)) {
            throw new \InvalidArgumentException('Invalid phone number format. Use 255XXXXXXXXX or 0XXXXXXXXX');
        }

        return $phone;
    }

    /**
     * Detect mobile money operator from Tanzanian phone number prefix.
     *
     * Vodacom (M-Pesa):     074, 075, 076
     * Tigo (TigoPesa):       071, 065, 067
     * Airtel (Airtel Money): 068, 069, 078
     * Halotel (HaloPesa):    062
     *
     * Phone must be in 255XXXXXXXXX format.
     */
    protected function detectOperator(string $phone): string
    {
        // Extract the 2-digit prefix after country code (255XX...)
        // e.g. 255712345678 → "71", 255652345678 → "65"
        $prefix = substr($phone, 3, 2); // positions 3-4 after "255"

        return match ($prefix) {
            // Vodacom (M-Pesa): 074x, 075x, 076x
            '74', '75', '76' => 'mpesa',

            // Tigo (TigoPesa): 071x, 065x, 067x
            '71', '65', '67' => 'tigopesa',

            // Airtel (Airtel Money): 068x, 069x, 078x
            '68', '69', '78' => 'airtelmoney',

            // Halotel (HaloPesa): 062x
            '62' => 'halopesa',

            default => throw new \InvalidArgumentException(
                "Cannot detect operator for phone number starting with 0{$prefix}. "
                . 'Supported prefixes: Vodacom (074/075/076), Tigo (071/065/067), Airtel (068/069/078), Halotel (062)'
            ),
        };
    }

    /**
     * Validate mobile money operator.
     */
    protected function validateOperator(string $operator): void
    {
        $allowed = ['mpesa', 'tigopesa', 'airtelmoney', 'halopesa'];
        if (!in_array($operator, $allowed)) {
            throw new \InvalidArgumentException(
                "Invalid operator: {$operator}. Allowed: " . implode(', ', $allowed)
            );
        }
    }

    /**
     * Get gateway config from app_settings table.
     */
    protected function getGatewayConfig(string $key): ?string
    {
        return AppSetting::getValue($key);
    }
}
