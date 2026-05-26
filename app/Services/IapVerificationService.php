<?php

namespace App\Services;

use App\Models\CoinPackage;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Verifies and grants consumable In-App Purchases (coin packages) bought
 * through Google Play Billing or Apple StoreKit.
 *
 * Subscriptions are handled in SubscriptionService::verifyStoreReceipt.
 */
class IapVerificationService
{
    public function __construct(
        protected CoinService $coinService,
    ) {}

    /**
     * Verify a coin (consumable) IAP and credit coins to the user.
     *
     * @param  array<string,mixed>|null  $extra
     */
    public function verifyCoinPurchase(
        User $user,
        string $provider,
        string $productId,
        string $receiptOrToken,
        ?string $transactionId = null,
        ?array $extra = null,
    ): CoinTransaction {
        $package = CoinPackage::where('store_product_id', $productId)
            ->active()
            ->firstOrFail();

        // Idempotency: if we already credited this transaction, return existing.
        if ($transactionId) {
            $existing = CoinTransaction::where('metadata->store_transaction_id', $transactionId)
                ->where('source', 'purchase')
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $verification = match ($provider) {
            'google' => $this->verifyGoogleProduct($productId, $receiptOrToken),
            'apple' => $this->verifyAppleReceipt($receiptOrToken, $productId),
            default => throw new \InvalidArgumentException("Unsupported provider: {$provider}"),
        };

        if (! ($verification['valid'] ?? false)) {
            throw new \RuntimeException($verification['error'] ?? 'IAP verification failed');
        }

        return DB::transaction(function () use ($user, $package, $provider, $transactionId, $verification, $extra) {
            $totalCoins = $package->coins + $package->bonus_coins;

            return $this->coinService->credit(
                $user,
                $totalCoins,
                'purchase',
                "Purchased {$package->name} via " . strtoupper($provider),
                $package,
                array_merge($extra ?? [], [
                    'provider' => $provider,
                    'product_id' => $package->store_product_id,
                    'package_id' => $package->id,
                    'store_transaction_id' => $transactionId ?? ($verification['transaction_id'] ?? null),
                    'verified_at' => now()->toISOString(),
                ]),
            );
        });
    }

    /**
     * Verify a Google Play consumable purchase via Play Developer API.
     */
    private function verifyGoogleProduct(string $productId, string $purchaseToken): array
    {
        $packageName = config('services.google.play_package_name');
        $serviceAccountKey = config('services.google.play_service_account_key');

        if (empty($packageName) || empty($serviceAccountKey)) {
            return ['valid' => false, 'error' => 'Google Play verification not configured'];
        }

        try {
            $accessToken = $this->getGoogleAccessToken($serviceAccountKey);
            if (! $accessToken) {
                return ['valid' => false, 'error' => 'Failed to obtain Google access token'];
            }

            $url = "https://androidpublisher.googleapis.com/androidpublisher/v3/applications/{$packageName}/purchases/products/{$productId}/tokens/{$purchaseToken}";

            $response = Http::withToken($accessToken)->timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning('Google Play product verification failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return ['valid' => false, 'error' => 'Google Play verification failed'];
            }

            $data = $response->json();

            // purchaseState: 0 = purchased, 1 = cancelled, 2 = pending
            $purchaseState = $data['purchaseState'] ?? -1;
            if ($purchaseState !== 0) {
                return ['valid' => false, 'error' => "Invalid purchaseState: {$purchaseState}"];
            }

            // Reject consumed-already? consumptionState 0=yet to consume, 1=consumed
            // We accept both — client consumes after server credits.

            return [
                'valid' => true,
                'transaction_id' => $data['orderId'] ?? null,
                'raw' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Google Play product verification error', ['error' => $e->getMessage()]);
            return ['valid' => false, 'error' => 'Google Play verification error'];
        }
    }

    private function verifyAppleReceipt(string $receipt, string $productId): array
    {
        $sharedSecret = config('services.apple.shared_secret', '');

        $payload = [
            'receipt-data' => $receipt,
            'password' => $sharedSecret,
            'exclude-old-transactions' => true,
        ];

        $urls = [
            'https://buy.itunes.apple.com/verifyReceipt',
            'https://sandbox.itunes.apple.com/verifyReceipt',
        ];

        foreach ($urls as $url) {
            try {
                $response = Http::timeout(30)->post($url, $payload);
                if (! $response->successful()) {
                    continue;
                }

                $data = $response->json();
                $status = $data['status'] ?? -1;

                // 21007 = sandbox receipt sent to production, try sandbox
                if ($status === 21007) {
                    continue;
                }

                if ($status !== 0) {
                    return ['valid' => false, 'error' => "Apple status {$status}"];
                }

                $inApp = $data['receipt']['in_app'] ?? [];
                foreach ($inApp as $item) {
                    if (($item['product_id'] ?? null) === $productId) {
                        return [
                            'valid' => true,
                            'transaction_id' => $item['transaction_id'] ?? null,
                            'raw' => $item,
                        ];
                    }
                }
                return ['valid' => false, 'error' => 'Product not found in receipt'];
            } catch (\Exception $e) {
                Log::error('Apple receipt verification error', ['error' => $e->getMessage()]);
            }
        }

        return ['valid' => false, 'error' => 'Apple verification failed'];
    }

    private function getGoogleAccessToken(string $serviceAccountKeyPath): ?string
    {
        try {
            if (! file_exists($serviceAccountKeyPath)) {
                Log::error('Google service account key not found', ['path' => $serviceAccountKeyPath]);
                return null;
            }

            $keyData = json_decode(file_get_contents($serviceAccountKeyPath), true);

            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $now = time();
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $keyData['client_email'],
                'scope' => 'https://www.googleapis.com/auth/androidpublisher',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signatureInput = "{$header}.{$claims}";
            $privateKey = openssl_pkey_get_private($keyData['private_key']);
            openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

            $jwt = "{$signatureInput}." . $this->base64UrlEncode($signature);

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

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
