<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function __construct(
        protected CoinService $coinService
    ) {}

    /**
     * Subscribe a user to a plan.
     */
    public function subscribe(
        User $user,
        SubscriptionPlan $plan,
        string $paymentProvider = 'manual',
        ?string $transactionId = null,
        ?string $storeTransactionId = null,
        ?array $paymentMeta = null,
    ): Subscription {
        return DB::transaction(function () use ($user, $plan, $paymentProvider, $transactionId, $storeTransactionId, $paymentMeta) {
            $activeSubscription = $this->getActiveSubscription($user);

            if ($activeSubscription && $activeSubscription->subscription_plan_id !== $plan->id) {
                // Upgrading/switching plan — cancel the old one and start new plan from now
                $this->cancel($activeSubscription, 'Switched to ' . $plan->name);
                $startsAt = now();
            } elseif ($activeSubscription && $activeSubscription->ends_at->isFuture()) {
                // Same plan renewal — extend from current end date
                $startsAt = $activeSubscription->ends_at;
            } else {
                $startsAt = now();
            }

            $endsAt = $startsAt->copy()->addDays($plan->duration_days);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'subscription_plan_id' => $plan->id,
                'order_id' => 'SUB-' . strtoupper(Str::random(12)),
                'transaction_id' => $transactionId,
                'payment_provider' => $paymentProvider,
                'store_transaction_id' => $storeTransactionId,
                'amount_paid' => $plan->price,
                'currency' => $plan->currency,
                'status' => 'active',
                'auto_renew' => true,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'payment_meta' => $paymentMeta,
            ]);

            // Update user VIP status
            $this->syncUserVipStatus($user);

            // Grant subscription bonus coins
            if ($plan->coin_bonus > 0) {
                $this->coinService->credit(
                    $user,
                    $plan->coin_bonus,
                    'subscription',
                    "Subscription bonus: {$plan->name}",
                    $subscription
                );
            }

            return $subscription;
        });
    }

    /**
     * Cancel a subscription (remains active until end_date).
     */
    public function cancel(Subscription $subscription, ?string $reason = null): Subscription
    {
        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        return $subscription;
    }

    /**
     * Renew an existing subscription.
     */
    public function renew(
        Subscription $subscription,
        ?string $transactionId = null,
        ?string $storeTransactionId = null,
        ?array $paymentMeta = null,
    ): Subscription {
        $plan = $subscription->plan;

        return $this->subscribe(
            $subscription->user,
            $plan,
            $subscription->payment_provider ?? 'manual',
            $transactionId,
            $storeTransactionId,
            $paymentMeta,
        );
    }

    /**
     * Process a refund.
     */
    public function refund(Subscription $subscription): Subscription
    {
        $subscription->update([
            'status' => 'refunded',
            'auto_renew' => false,
        ]);

        $this->syncUserVipStatus($subscription->user);

        return $subscription;
    }

    /**
     * Verify and process a store receipt (Apple/Google).
     */
    public function verifyStoreReceipt(
        User $user,
        string $provider,
        string $receipt,
        string $productId,
    ): Subscription {
        // Find the plan by store product ID
        $plan = SubscriptionPlan::where('store_product_id', $productId)
            ->active()
            ->firstOrFail();

        // Verify receipt with respective store
        $verification = match ($provider) {
            'apple' => $this->verifyAppleReceipt($receipt, $productId),
            'google' => $this->verifyGoogleReceipt($receipt, $productId),
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
        };

        if (!$verification['valid']) {
            throw new \RuntimeException($verification['error'] ?? 'Receipt verification failed');
        }

        return $this->subscribe(
            $user,
            $plan,
            $provider,
            $verification['transaction_id'] ?? null,
            $receipt,
            [
                'receipt' => substr($receipt, 0, 100) . '...',
                'product_id' => $productId,
                'store_verified' => true,
                'verification_time' => now()->toISOString(),
            ],
        );
    }

    /**
     * Verify Apple App Store receipt.
     */
    private function verifyAppleReceipt(string $receipt, string $productId): array
    {
        $sharedSecret = config('services.apple.shared_secret', '');

        $payload = [
            'receipt-data' => $receipt,
            'password' => $sharedSecret,
            'exclude-old-transactions' => true,
        ];

        // Try production first, then sandbox
        $urls = [
            'https://buy.itunes.apple.com/verifyReceipt',
            'https://sandbox.itunes.apple.com/verifyReceipt',
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(30)->post($url, $payload);

                if (!$response->successful()) {
                    continue;
                }

                $data = $response->json();
                $status = $data['status'] ?? -1;

                // Status 21007 means sandbox receipt sent to production — retry with sandbox
                if ($status === 21007) {
                    continue;
                }

                if ($status !== 0) {
                    Log::warning('Apple receipt verification failed', ['status' => $status]);
                    return ['valid' => false, 'error' => "Apple verification status: {$status}"];
                }

                // Find matching in-app purchase
                $latestReceipt = $data['latest_receipt_info'] ?? $data['receipt']['in_app'] ?? [];
                $matchingPurchase = null;

                foreach ($latestReceipt as $purchase) {
                    if (($purchase['product_id'] ?? '') === $productId) {
                        $matchingPurchase = $purchase;
                        break;
                    }
                }

                if (!$matchingPurchase) {
                    return ['valid' => false, 'error' => 'Product not found in receipt'];
                }

                return [
                    'valid' => true,
                    'transaction_id' => $matchingPurchase['transaction_id'] ?? null,
                    'product_id' => $productId,
                ];
            } catch (\Exception $e) {
                Log::error('Apple receipt verification error', ['url' => $url, 'error' => $e->getMessage()]);
                continue;
            }
        }

        return ['valid' => false, 'error' => 'Could not verify receipt with Apple'];
    }

    /**
     * Verify Google Play receipt via Google Play Developer API.
     */
    private function verifyGoogleReceipt(string $purchaseToken, string $productId): array
    {
        $packageName = config('services.google.play_package_name', '');
        $serviceAccountKey = config('services.google.play_service_account_key', '');

        if (empty($packageName) || empty($serviceAccountKey)) {
            Log::error('Google Play verification: missing configuration');
            return ['valid' => false, 'error' => 'Google Play verification not configured'];
        }

        try {
            // Get access token from service account
            $accessToken = $this->getGoogleAccessToken($serviceAccountKey);

            if (!$accessToken) {
                return ['valid' => false, 'error' => 'Failed to obtain Google access token'];
            }

            $url = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{$packageName}/purchases/subscriptions/{$productId}/tokens/{$purchaseToken}";

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Google Play verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['valid' => false, 'error' => 'Google Play verification failed'];
            }

            $data = $response->json();

            // Check payment state (0=pending, 1=received, 2=trial, 3=deferred)
            $paymentState = $data['paymentState'] ?? -1;
            if (!in_array($paymentState, [1, 2], true)) {
                return ['valid' => false, 'error' => "Invalid payment state: {$paymentState}"];
            }

            return [
                'valid' => true,
                'transaction_id' => $data['orderId'] ?? null,
                'product_id' => $productId,
            ];
        } catch (\Exception $e) {
            Log::error('Google Play verification error', ['error' => $e->getMessage()]);
            return ['valid' => false, 'error' => 'Google Play verification error'];
        }
    }

    /**
     * Get Google OAuth2 access token from service account credentials.
     */
    private function getGoogleAccessToken(string $serviceAccountKeyPath): ?string
    {
        try {
            if (!file_exists($serviceAccountKeyPath)) {
                Log::error('Google service account key file not found', ['path' => $serviceAccountKeyPath]);
                return null;
            }

            $keyData = json_decode(file_get_contents($serviceAccountKeyPath), true);

            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now = time();
            $claims = base64_encode(json_encode([
                'iss' => $keyData['client_email'],
                'scope' => 'https://www.googleapis.com/auth/androidpublisher',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signatureInput = "{$header}.{$claims}";
            $privateKey = openssl_pkey_get_private($keyData['private_key']);
            openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

            $jwt = "{$signatureInput}." . base64_encode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Google OAuth token exchange failed', ['body' => $response->body()]);
            return null;
        } catch (\Exception $e) {
            Log::error('Google OAuth error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Get user's current active subscription.
     */
    public function getActiveSubscription(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->active()
            ->with('plan')
            ->latest('ends_at')
            ->first();
    }

    /**
     * Get all subscriptions for a user.
     */
    public function getUserSubscriptions(User $user)
    {
        return Subscription::where('user_id', $user->id)
            ->with('plan')
            ->latest()
            ->paginate(20);
    }

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(User $user): bool
    {
        return Subscription::where('user_id', $user->id)
            ->active()
            ->exists();
    }

    /**
     * Sync user's is_vip and vip_expires_at based on active subscriptions.
     */
    public function syncUserVipStatus(User $user): void
    {
        $activeSubscription = $this->getActiveSubscription($user);

        if ($activeSubscription) {
            $user->update([
                'is_vip' => true,
                'vip_expires_at' => $activeSubscription->ends_at,
            ]);
        } else {
            $user->update([
                'is_vip' => false,
                'vip_expires_at' => null,
            ]);
        }
    }

    /**
     * Expire all overdue subscriptions (run via scheduler).
     */
    public function expireOverdueSubscriptions(): int
    {
        return Subscription::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->where('auto_renew', false)
            ->update(['status' => 'expired']);
    }
}
