<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Banner;
use App\Models\CoinPackage;
use App\Models\AppSetting;
use App\Models\MobilePayment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentManagementController extends Controller
{
    // ── Categories ─────────────────────────────────────

    public function categories(): JsonResponse
    {
        $categories = Category::withCount('dramas')->orderBy('sort_order')->get();
        return $this->success($categories);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'image', 'max:1024'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        if ($request->hasFile('icon')) {
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        $category = Category::create($data);

        return $this->created($category);
    }

    public function updateCategory(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'icon' => ['sometimes', 'nullable', 'image', 'max:1024'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        if ($request->hasFile('icon')) {
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $request->file('icon')->store('categories', 'public');
        }

        $category->update($data);

        return $this->success($category->fresh());
    }

    public function destroyCategory(Category $category): JsonResponse
    {
        if ($category->dramas()->exists()) {
            return $this->error('Cannot delete category with associated dramas.', 422);
        }

        $category->delete();

        return $this->noContent('Category deleted');
    }

    // ── Tags ───────────────────────────────────────────

    public function tags(): JsonResponse
    {
        $tags = Tag::withCount('dramas')->orderBy('name')->get();
        return $this->success($tags);
    }

    public function storeTag(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        $tag = Tag::create($data);

        return $this->created($tag);
    }

    public function updateTag(Request $request, Tag $tag): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $tag->update($data);

        return $this->success($tag->fresh());
    }

    public function destroyTag(Tag $tag): JsonResponse
    {
        $tag->dramas()->detach();
        $tag->delete();

        return $this->noContent('Tag deleted');
    }

    // ── Banners ────────────────────────────────────────

    public function banners(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order')->get();
        return $this->success($banners);
    }

    public function storeBanner(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', 'image', 'max:5120'],
            'link_type' => ['nullable', 'string', 'in:drama,url,category'],
            'link_value' => ['nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ]);

        $data['image'] = $request->file('image')->store('banners', 'public');

        $banner = Banner::create($data);

        return $this->created($banner);
    }

    public function updateBanner(Request $request, Banner $banner): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'image' => ['sometimes', 'image', 'max:5120'],
            'link_type' => ['sometimes', 'nullable', 'string', 'in:drama,url,category'],
            'link_value' => ['sometimes', 'nullable', 'string'],
            'sort_order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($request->hasFile('image')) {
            Storage::disk('public')->delete($banner->image);
            $data['image'] = $request->file('image')->store('banners', 'public');
        }

        $banner->update($data);

        return $this->success($banner->fresh());
    }

    public function destroyBanner(Banner $banner): JsonResponse
    {
        Storage::disk('public')->delete($banner->image);
        $banner->delete();

        return $this->noContent('Banner deleted');
    }

    // ── Coin Packages ──────────────────────────────────

    public function coinPackages(): JsonResponse
    {
        $packages = CoinPackage::orderBy('sort_order')->get();
        return $this->success($packages);
    }

    public function storeCoinPackage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'coins' => ['required', 'integer', 'min:1'],
            'bonus_coins' => ['sometimes', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'store_product_id' => ['nullable', 'string'],
            'is_popular' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $package = CoinPackage::create($data);

        return $this->created($package);
    }

    public function updateCoinPackage(Request $request, CoinPackage $coinPackage): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'coins' => ['sometimes', 'integer', 'min:1'],
            'bonus_coins' => ['sometimes', 'integer', 'min:0'],
            'price' => ['sometimes', 'numeric', 'min:0.01'],
            'currency' => ['sometimes', 'string', 'max:3'],
            'store_product_id' => ['sometimes', 'nullable', 'string'],
            'is_popular' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
        ]);

        $coinPackage->update($data);

        return $this->success($coinPackage->fresh());
    }

    public function destroyCoinPackage(CoinPackage $coinPackage): JsonResponse
    {
        $coinPackage->delete();

        return $this->noContent('Coin package deleted');
    }

    // ── App Settings ───────────────────────────────────

    public function settings(): JsonResponse
    {
        $settings = AppSetting::orderBy('group')->orderBy('key')->get();
        return $this->success($settings);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.key' => ['required', 'string'],
            'settings.*.value' => ['nullable', 'string'],
            'settings.*.group' => ['sometimes', 'string'],
        ]);

        foreach ($request->settings as $setting) {
            AppSetting::setValue(
                $setting['key'],
                $setting['value'],
                $setting['group'] ?? 'general'
            );
        }

        return $this->success(null, 'Settings updated');
    }

    // ── Subscription Plans ─────────────────────────────

    public function subscriptionPlans(): JsonResponse
    {
        $plans = SubscriptionPlan::withCount(['subscriptions', 'subscriptions as active_subscriptions_count' => function ($q) {
            $q->whereIn('status', ['active', 'cancelled'])->where('ends_at', '>', now());
        }])
            ->orderBy('sort_order')
            ->get();

        return $this->success($plans);
    }

    public function storeSubscriptionPlan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'interval' => ['required', 'in:weekly,monthly,yearly'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'store_product_id' => ['nullable', 'string', 'max:255'],
            'coin_bonus' => ['sometimes', 'integer', 'min:0'],
            'daily_coin_bonus' => ['sometimes', 'integer', 'min:0'],
            'is_popular' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:255'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        $plan = SubscriptionPlan::create($data);

        return $this->created($plan);
    }

    public function updateSubscriptionPlan(Request $request, SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'interval' => ['sometimes', 'in:weekly,monthly,yearly'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'original_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'store_product_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'coin_bonus' => ['sometimes', 'integer', 'min:0'],
            'daily_coin_bonus' => ['sometimes', 'integer', 'min:0'],
            'is_popular' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer'],
            'features' => ['sometimes', 'nullable', 'array'],
            'features.*' => ['string', 'max:255'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $subscriptionPlan->update($data);

        return $this->success($subscriptionPlan->fresh());
    }

    public function destroySubscriptionPlan(SubscriptionPlan $subscriptionPlan): JsonResponse
    {
        if ($subscriptionPlan->subscriptions()->active()->exists()) {
            return $this->error('Cannot delete plan with active subscriptions', 422);
        }

        $subscriptionPlan->delete();

        return $this->noContent('Subscription plan deleted');
    }

    // ── Subscription Management ────────────────────────

    public function subscriptions(Request $request): JsonResponse
    {
        $query = Subscription::with(['user:id,name,email', 'plan:id,name,interval']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_id')) {
            $query->where('subscription_plan_id', $request->plan_id);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $subscriptions = $query->latest()->paginate($request->input('per_page', 20));

        return $this->paginated($subscriptions);
    }

    public function showSubscription(Subscription $subscription): JsonResponse
    {
        $subscription->load(['user:id,name,email,is_vip,vip_expires_at', 'plan']);

        return $this->success($subscription);
    }

    public function cancelSubscription(Request $request, Subscription $subscription): JsonResponse
    {
        if (!$subscription->isActive()) {
            return $this->error('Subscription is not active', 422);
        }

        $reason = $request->input('reason', 'Cancelled by admin');
        app(SubscriptionService::class)->cancel($subscription, $reason);

        return $this->success($subscription->fresh()->load('plan'), 'Subscription cancelled');
    }

    public function refundSubscription(Subscription $subscription): JsonResponse
    {
        if ($subscription->status === 'refunded') {
            return $this->error('Subscription is already refunded', 422);
        }

        app(SubscriptionService::class)->refund($subscription);

        return $this->success($subscription->fresh()->load('plan'), 'Subscription refunded');
    }

    // ── Mobile Payments Management ───────────────────

    public function payments(Request $request): JsonResponse
    {
        $query = MobilePayment::with('user:id,name,email,phone');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('operator')) {
            $query->where('operator', $request->operator);
        }
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->payment_type);
        }
        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }
        if ($request->filled('reference')) {
            $query->where('reference', $request->reference);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate($request->input('per_page', 20));

        return $this->paginated($payments);
    }

    public function showPayment(MobilePayment $mobilePayment): JsonResponse
    {
        $mobilePayment->load('user:id,name,email,phone');

        return $this->success($mobilePayment);
    }

    public function paymentStats(): JsonResponse
    {
        $today = \Carbon\Carbon::today();
        $thisMonth = \Carbon\Carbon::now()->startOfMonth();

        return $this->success([
            'today' => [
                'total' => MobilePayment::whereDate('created_at', $today)->count(),
                'completed' => MobilePayment::completed()->whereDate('created_at', $today)->count(),
                'failed' => MobilePayment::where('status', 'failed')->whereDate('created_at', $today)->count(),
                'revenue' => MobilePayment::completed()->whereDate('created_at', $today)->sum('amount'),
            ],
            'this_month' => [
                'total' => MobilePayment::where('created_at', '>=', $thisMonth)->count(),
                'completed' => MobilePayment::completed()->where('created_at', '>=', $thisMonth)->count(),
                'failed' => MobilePayment::where('status', 'failed')->where('created_at', '>=', $thisMonth)->count(),
                'revenue' => MobilePayment::completed()->where('created_at', '>=', $thisMonth)->sum('amount'),
            ],
            'all_time' => [
                'total' => MobilePayment::count(),
                'completed' => MobilePayment::completed()->count(),
                'revenue' => MobilePayment::completed()->sum('amount'),
            ],
            'by_operator' => MobilePayment::completed()
                ->selectRaw('operator, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('operator')
                ->get(),
            'by_type' => MobilePayment::completed()
                ->selectRaw('payment_type, COUNT(*) as count, SUM(amount) as total_amount')
                ->groupBy('payment_type')
                ->get(),
        ]);
    }

    // ── Payment Gateway Configuration ───────────────

    public function paymentGateway(): JsonResponse
    {
        $keys = [
            'payment_gateway_url',
            'payment_gateway_api_key',
            'payment_gateway_api_secret',
            'payment_callback_url',
            'payment_gateway_timeout',
        ];

        $settings = AppSetting::whereIn('key', $keys)->get()->keyBy('key');

        // Mask the secret for display
        $secret = $settings->get('payment_gateway_api_secret');
        $maskedSecret = $secret && $secret->value
            ? str_repeat('*', max(0, strlen($secret->value) - 4)) . substr($secret->value, -4)
            : '';

        return $this->success([
            'gateway_url' => $settings->get('payment_gateway_url')?->value ?? '',
            'api_key' => $settings->get('payment_gateway_api_key')?->value ?? '',
            'api_secret_masked' => $maskedSecret,
            'callback_url' => $settings->get('payment_callback_url')?->value ?? '',
            'timeout' => $settings->get('payment_gateway_timeout')?->value ?? '30',
        ]);
    }

    public function updatePaymentGateway(Request $request): JsonResponse
    {
        $data = $request->validate([
            'gateway_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'api_key' => ['sometimes', 'nullable', 'string', 'max:500'],
            'api_secret' => ['sometimes', 'nullable', 'string', 'max:500'],
            'callback_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'timeout' => ['sometimes', 'integer', 'min:5', 'max:120'],
        ]);

        $mapping = [
            'gateway_url' => 'payment_gateway_url',
            'api_key' => 'payment_gateway_api_key',
            'api_secret' => 'payment_gateway_api_secret',
            'callback_url' => 'payment_callback_url',
            'timeout' => 'payment_gateway_timeout',
        ];

        foreach ($data as $field => $value) {
            if (array_key_exists($field, $mapping)) {
                AppSetting::setValue($mapping[$field], (string) $value, 'payment');
            }
        }

        return $this->success(null, 'Payment gateway settings updated');
    }
}
