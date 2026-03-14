<?php
require '/var/www/dramabox/vendor/autoload.php';
$app = require_once '/var/www/dramabox/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check recent payments with full details
$rows = App\Models\MobilePayment::orderByDesc('id')->take(10)->get();
foreach($rows as $r) {
    echo "=== Payment #{$r->id} ===\n";
    echo "Reference: {$r->reference}\n";
    echo "Gateway Ref: {$r->gateway_reference}\n";
    echo "Status: {$r->status}\n";
    echo "Amount: {$r->amount}\n";
    echo "Phone: {$r->phone}\n";
    echo "Operator: {$r->operator}\n";
    echo "Push Response: " . json_encode($r->push_response) . "\n";
    echo "Gateway Response: " . json_encode($r->gateway_response) . "\n";
    echo "Failure: {$r->failure_reason}\n";
    echo "Created: {$r->created_at}\n";
    echo "Completed: {$r->completed_at}\n\n";
}

// Check callback URL setting
$callbackUrl = App\Models\AppSetting::where('key', 'payment_callback_url')->first();
echo "=== Callback URL Setting ===\n";
echo $callbackUrl ? $callbackUrl->value : 'NOT SET';
echo "\n";

// Check gateway URL
$gatewayUrl = App\Models\AppSetting::where('key', 'payment_gateway_url')->first();
echo "Gateway URL: " . ($gatewayUrl ? $gatewayUrl->value : 'NOT SET') . "\n";

// Check API keys
$apiKey = App\Models\AppSetting::where('key', 'payment_gateway_api_key')->first();
echo "API Key: " . ($apiKey ? $apiKey->value : 'NOT SET') . "\n";

$apiSecret = App\Models\AppSetting::where('key', 'payment_gateway_api_secret')->first();
echo "API Secret: " . ($apiSecret ? substr($apiSecret->value, 0, 10) . '...' : 'NOT SET') . "\n";
