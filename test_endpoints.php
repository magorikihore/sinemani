<?php
require '/var/www/dramabox/vendor/autoload.php';
$app = require_once '/var/www/dramabox/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$gatewayUrl = App\Models\AppSetting::getValue('payment_gateway_url');
$apiKey = App\Models\AppSetting::getValue('payment_gateway_api_key');
$apiSecret = App\Models\AppSetting::getValue('payment_gateway_api_secret');

$gatewayRef = 'PAYALUWTV0GDNGH';
$internalRef = 'PAY-P1PVBLXXVR0J';

// Try different endpoint patterns
$endpoints = [
    "{$gatewayUrl}/collection/{$gatewayRef}",
    "{$gatewayUrl}/collection/status/{$gatewayRef}",
    "{$gatewayUrl}/collections/{$gatewayRef}",
    "{$gatewayUrl}/transaction/{$gatewayRef}",
    "{$gatewayUrl}/transactions/{$gatewayRef}",
    "{$gatewayUrl}/status/{$gatewayRef}",
    "{$gatewayUrl}/collection/{$internalRef}",
    "{$gatewayUrl}/collection?reference={$internalRef}",
    "{$gatewayUrl}/collection?request_ref={$gatewayRef}",
];

foreach ($endpoints as $url) {
    try {
        $response = Illuminate\Support\Facades\Http::timeout(10)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => $apiKey,
                'X-API-Secret' => $apiSecret,
            ])
            ->get($url);

        echo "GET {$url}\n  => HTTP {$response->status()}\n";
        if ($response->status() < 404) {
            echo "  => Body: " . substr($response->body(), 0, 300) . "\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "GET {$url}\n  => ERROR: {$e->getMessage()}\n\n";
    }
}
