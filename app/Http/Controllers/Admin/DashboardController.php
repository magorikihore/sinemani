<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\MobilePayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics.
     */
    public function index(): JsonResponse
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return $this->success([
            'users' => [
                'total' => User::count(),
                'today' => User::whereDate('created_at', $today)->count(),
                'this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                'active_vip' => User::where('is_vip', true)->where('vip_expires_at', '>', now())->count(),
            ],
            'content' => [
                'total_dramas' => Drama::count(),
                'published_dramas' => Drama::published()->count(),
                'total_episodes' => Episode::count(),
                'published_episodes' => Episode::published()->count(),
            ],
            'revenue' => [
                'today' => Purchase::completed()->whereDate('created_at', $today)->sum('amount'),
                'this_month' => Purchase::completed()->where('created_at', '>=', $thisMonth)->sum('amount'),
                'total' => Purchase::completed()->sum('amount'),
            ],
            'mobile_payments' => [
                'today_count' => MobilePayment::completed()->whereDate('created_at', $today)->count(),
                'today_revenue' => MobilePayment::completed()->whereDate('created_at', $today)->sum('amount'),
                'month_revenue' => MobilePayment::completed()->where('created_at', '>=', $thisMonth)->sum('amount'),
                'pending' => MobilePayment::pending()->count(),
            ],
            'subscriptions' => [
                'active' => Subscription::active()->count(),
                'new_today' => Subscription::whereDate('created_at', $today)->count(),
                'new_this_month' => Subscription::where('created_at', '>=', $thisMonth)->count(),
                'revenue_this_month' => Subscription::where('status', '!=', 'refunded')
                    ->where('created_at', '>=', $thisMonth)->sum('amount_paid'),
            ],
            'engagement' => [
                'total_views' => Drama::sum('view_count'),
                'coins_in_circulation' => User::sum('coin_balance'),
                'coins_spent_today' => CoinTransaction::where('type', 'debit')
                    ->whereDate('created_at', $today)->sum('amount'),
            ],
        ]);
    }

    /**
     * Get recent activity.
     */
    public function recentActivity(): JsonResponse
    {
        $recentUsers = User::orderByDesc('created_at')->limit(10)->get(['id', 'name', 'email', 'created_at']);
        $recentPurchases = Purchase::with('user:id,name,email')
            ->orderByDesc('created_at')->limit(10)->get();

        return $this->success([
            'recent_users' => $recentUsers,
            'recent_purchases' => $recentPurchases,
        ]);
    }
}
