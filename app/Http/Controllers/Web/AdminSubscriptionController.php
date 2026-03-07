<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminSubscriptionController extends Controller
{
    // ── Subscription Plans ──────────────────────────────

    public function plans()
    {
        $plans = SubscriptionPlan::withCount([
            'subscriptions',
            'subscriptions as active_count' => function ($q) {
                $q->whereIn('status', ['active', 'cancelled'])->where('ends_at', '>', now());
            },
        ])->orderBy('sort_order')->get();

        return view('admin.subscriptions.plans', compact('plans'));
    }

    public function createPlan()
    {
        return view('admin.subscriptions.create-plan');
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'interval' => 'required|in:weekly,monthly,yearly',
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'coin_bonus' => 'nullable|integer|min:0',
            'daily_coin_bonus' => 'nullable|integer|min:0',
            'is_popular' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_popular'] = $request->boolean('is_popular');
        $data['is_active'] = $request->boolean('is_active', true);

        // Parse features from textarea (one per line)
        if (!empty($data['features'])) {
            $data['features'] = array_filter(array_map('trim', explode("\n", $data['features'])));
        } else {
            $data['features'] = [];
        }

        SubscriptionPlan::create($data);

        return redirect()->route('admin.subscriptions.plans')->with('success', 'Subscription plan created.');
    }

    public function editPlan(SubscriptionPlan $plan)
    {
        return view('admin.subscriptions.edit-plan', compact('plan'));
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'interval' => 'required|in:weekly,monthly,yearly',
            'duration_days' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'coin_bonus' => 'nullable|integer|min:0',
            'daily_coin_bonus' => 'nullable|integer|min:0',
            'is_popular' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer',
            'features' => 'nullable|string',
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['is_popular'] = $request->boolean('is_popular');
        $data['is_active'] = $request->boolean('is_active');

        if (!empty($data['features'])) {
            $data['features'] = array_filter(array_map('trim', explode("\n", $data['features'])));
        } else {
            $data['features'] = [];
        }

        $plan->update($data);

        return redirect()->route('admin.subscriptions.plans')->with('success', 'Plan updated.');
    }

    public function destroyPlan(SubscriptionPlan $plan)
    {
        if ($plan->subscriptions()->whereIn('status', ['active', 'cancelled'])->where('ends_at', '>', now())->exists()) {
            return back()->with('error', 'Cannot delete plan with active subscriptions.');
        }

        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }

    // ── Subscriptions ──────────────────────────────────

    public function subscriptions(Request $request)
    {
        $query = Subscription::with(['user:id,name,email', 'plan:id,name,interval,price']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('plan_id')) {
            $query->where('subscription_plan_id', $request->plan_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest()->paginate(25)->withQueryString();
        $plans = SubscriptionPlan::orderBy('sort_order')->get(['id', 'name']);

        $activeCount = Subscription::active()->count();
        $totalRevenue = Subscription::where('status', '!=', 'refunded')->sum('amount_paid');

        return view('admin.subscriptions.index', compact('subscriptions', 'plans', 'activeCount', 'totalRevenue'));
    }

    public function cancelSubscription(Request $request, Subscription $subscription)
    {
        if (!$subscription->isActive()) {
            return back()->with('error', 'Subscription is not active.');
        }

        $reason = $request->input('reason', 'Cancelled by admin');
        app(SubscriptionService::class)->cancel($subscription, $reason);

        return back()->with('success', 'Subscription cancelled.');
    }
}
