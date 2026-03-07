<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
            // If user already has an active subscription, extend from its end date
            $activeSubscription = $this->getActiveSubscription($user);
            $startsAt = $activeSubscription && $activeSubscription->ends_at->isFuture()
                ? $activeSubscription->ends_at
                : now();

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
     * Placeholder — integrate with actual store APIs.
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

        // TODO: Verify receipt with Apple/Google
        // For now, trust the receipt and create subscription
        return $this->subscribe(
            $user,
            $plan,
            $provider,
            null,
            $receipt,
            ['receipt' => $receipt, 'product_id' => $productId],
        );
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
