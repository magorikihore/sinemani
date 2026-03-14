<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * List all available subscription plans.
     */
    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::active()
            ->orderBy('sort_order')
            ->get()
            ->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'interval' => $plan->interval,
                    'duration_days' => $plan->duration_days,
                    'price' => $plan->price,
                    'original_price' => $plan->original_price,
                    'currency' => $plan->currency,
                    'store_product_id' => $plan->store_product_id,
                    'coin_bonus' => $plan->coin_bonus,
                    'daily_coin_bonus' => $plan->daily_coin_bonus,
                    'is_popular' => $plan->is_popular,
                    'features' => $plan->features,
                    'savings_percent' => $plan->savings_percent,
                    'price_per_day' => $plan->price_per_day,
                ];
            });

        return $this->success($plans);
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        $user = $request->user();
        $plan = SubscriptionPlan::active()->findOrFail($request->plan_id);

        // Prevent duplicate subscription purchase
        if ($this->subscriptionService->hasActiveSubscription($user)) {
            $active = $this->subscriptionService->getActiveSubscription($user);
            $daysRemaining = (int) now()->diffInDays($active->ends_at, false);
            return $this->error(
                "You already have an active subscription ({$active->plan->name}) with {$daysRemaining} days remaining. Please wait for it to expire or cancel it first.",
                422,
                null,
                'SUBSCRIPTION_ALREADY_ACTIVE'
            );
        }

        // For store purchases, verify receipt
        if (in_array($request->payment_provider, ['apple', 'google']) && $request->receipt) {
            $subscription = $this->subscriptionService->verifyStoreReceipt(
                $user,
                $request->payment_provider,
                $request->receipt,
                $plan->store_product_id,
            );
        } else {
            $subscription = $this->subscriptionService->subscribe(
                $user,
                $plan,
                $request->payment_provider,
                $request->transaction_id,
                $request->store_transaction_id,
            );
        }

        $subscription->load('plan');

        return $this->created([
            'subscription' => $this->formatSubscription($subscription),
            'coin_balance' => $user->fresh()->coin_balance,
        ], 'Subscription activated successfully');
    }

    /**
     * Get current active subscription.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (!$subscription) {
            return $this->success([
                'has_subscription' => false,
                'subscription' => null,
            ]);
        }

        return $this->success([
            'has_subscription' => true,
            'subscription' => $this->formatSubscription($subscription),
        ]);
    }

    /**
     * Get subscription history.
     */
    public function history(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getUserSubscriptions($request->user());

        return $this->paginated($subscriptions);
    }

    /**
     * Cancel current subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $this->subscriptionService->getActiveSubscription($user);

        if (!$subscription) {
            return $this->error('You don\'t have an active subscription to cancel.', 404, null, 'NO_ACTIVE_SUBSCRIPTION');
        }

        if ($subscription->isCancelled()) {
            return $this->error('Your subscription has already been cancelled.', 422, null, 'ALREADY_CANCELLED');
        }

        $reason = $request->input('reason');
        $this->subscriptionService->cancel($subscription, $reason);

        return $this->success([
            'subscription' => $this->formatSubscription($subscription->fresh()->load('plan')),
            'message' => 'Your subscription has been cancelled. You can still access content until ' . $subscription->ends_at->format('M d, Y'),
        ], 'Subscription cancelled');
    }

    /**
     * Restore a cancelled subscription (re-enable auto-renew).
     */
    public function restore(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->where('ends_at', '>', now())
            ->latest()
            ->first();

        if (!$subscription) {
            return $this->error('No cancelled subscription found to restore.', 404, null, 'NO_CANCELLED_SUBSCRIPTION');
        }

        $subscription->update([
            'status' => 'active',
            'auto_renew' => true,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        return $this->success(
            $this->formatSubscription($subscription->fresh()->load('plan')),
            'Subscription restored successfully'
        );
    }

    /**
     * Verify a store receipt (Apple/Google webhook or client-side).
     */
    public function verifyReceipt(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => ['required', 'in:apple,google'],
            'receipt' => ['required', 'string'],
            'product_id' => ['required', 'string'],
        ]);

        $user = $request->user();

        try {
            $subscription = $this->subscriptionService->verifyStoreReceipt(
                $user,
                $request->provider,
                $request->receipt,
                $request->product_id,
            );

            $subscription->load('plan');

            return $this->success([
                'subscription' => $this->formatSubscription($subscription),
                'coin_balance' => $user->fresh()->coin_balance,
            ], 'Receipt verified and subscription activated');
        } catch (\Exception $e) {
            return $this->error('We couldn\'t verify your purchase receipt. Please try again or contact support.', 422, null, 'RECEIPT_VERIFICATION_FAILED');
        }
    }

    /**
     * Format subscription for API response.
     */
    private function formatSubscription(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'plan' => [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'interval' => $subscription->plan->interval,
                'features' => $subscription->plan->features,
            ],
            'status' => $subscription->status,
            'auto_renew' => $subscription->auto_renew,
            'amount_paid' => $subscription->amount_paid,
            'currency' => $subscription->currency,
            'starts_at' => $subscription->starts_at->toISOString(),
            'ends_at' => $subscription->ends_at->toISOString(),
            'days_remaining' => $subscription->daysRemaining(),
            'is_active' => $subscription->isActive(),
            'cancelled_at' => $subscription->cancelled_at?->toISOString(),
            'created_at' => $subscription->created_at->toISOString(),
        ];
    }
}
