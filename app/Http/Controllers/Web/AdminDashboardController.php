<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\MobilePayment;
use App\Models\Purchase;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $stats = [
            'users' => [
                'total' => User::count(),
                'today' => User::whereDate('created_at', $today)->count(),
                'this_month' => User::where('created_at', '>=', $thisMonth)->count(),
                'vip' => User::where('is_vip', true)->where('vip_expires_at', '>', now())->count(),
            ],
            'content' => [
                'total_dramas' => Drama::count(),
                'published_dramas' => Drama::published()->count(),
                'total_episodes' => Episode::count(),
                'published_episodes' => Episode::published()->count(),
            ],
            'payments' => [
                'today_revenue' => MobilePayment::completed()->whereDate('created_at', $today)->sum('amount'),
                'month_revenue' => MobilePayment::completed()->where('created_at', '>=', $thisMonth)->sum('amount'),
                'total_revenue' => MobilePayment::completed()->sum('amount'),
                'pending' => MobilePayment::pending()->count(),
            ],
            'subscriptions' => [
                'active' => Subscription::active()->count(),
                'new_today' => Subscription::whereDate('created_at', $today)->count(),
            ],
        ];

        $recentDramas = Drama::with('category')->latest()->limit(5)->get();
        $recentUsers = User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']);

        return view('admin.dashboard', compact('stats', 'recentDramas', 'recentUsers'));
    }
}
