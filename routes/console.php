<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\SubscriptionService;
use App\Services\MobilePaymentService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Scheduled Tasks ────────────────────────────────────────────────

// Expire overdue subscriptions daily at midnight
Schedule::call(function () {
    $count = app(SubscriptionService::class)->expireOverdueSubscriptions();
    logger("Expired {$count} overdue subscriptions");
})->daily()->name('expire-subscriptions')->withoutOverlapping();

// Poll gateway for pending payments that may have missed callbacks
// (handles cases where gateway auth-service fails to forward callbacks)
Schedule::call(function () {
    $paymentService = app(MobilePaymentService::class);
    $pending = \App\Models\MobilePayment::whereIn('status', ['pending', 'processing'])
        ->whereNotNull('gateway_reference')
        ->where('created_at', '>', now()->subHours(24))
        ->oldest()
        ->limit(20)
        ->get();

    $polled = 0;
    foreach ($pending as $payment) {
        $paymentService->pollGatewayStatus($payment);
        $polled++;
    }

    if ($polled > 0) {
        logger("Polled {$polled} pending payments for status updates");
    }
})->everyTwoMinutes()->name('poll-pending-payments')->withoutOverlapping();

// Clean up expired/abandoned payment records (older than 24 hours)
Schedule::call(function () {
    $count = \App\Models\MobilePayment::where('status', 'pending')
        ->where('created_at', '<', now()->subHours(24))
        ->update(['status' => 'expired']);
    logger("Expired {$count} abandoned payments");
})->dailyAt('01:00')->name('expire-abandoned-payments')->withoutOverlapping();

// Prune stale Sanctum tokens (older than 30 days)
Schedule::command('sanctum:prune-expired --hours=720')
    ->daily()
    ->name('prune-expired-tokens');
