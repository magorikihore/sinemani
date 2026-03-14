<?php
require '/var/www/dramabox/vendor/autoload.php';
$app = require_once '/var/www/dramabox/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(App\Services\MobilePaymentService::class);

// Test polling for Payment #7 which is stuck at pending
$payment = $service->getStatus('PAY-P1PVBLXXVR0J');

echo "Reference: {$payment->reference}\n";
echo "Gateway Ref: {$payment->gateway_reference}\n";
echo "Status: {$payment->status}\n";
echo "Failure: {$payment->failure_reason}\n";
echo "Completed: {$payment->completed_at}\n";
echo "Gateway Response: " . json_encode($payment->gateway_response) . "\n";
