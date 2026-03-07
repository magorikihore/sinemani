<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\MobilePayment;
use App\Models\Subscription;
use App\Services\CoinService;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['subscriptions', 'comments', 'ratings']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('vip')) {
            if ($request->vip === 'yes') {
                $query->where('is_vip', true)->where('vip_expires_at', '>', now());
            } else {
                $query->where(function ($q) {
                    $q->where('is_vip', false)->orWhereNull('vip_expires_at')->orWhere('vip_expires_at', '<=', now());
                });
            }
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load('roles');

        $stats = [
            'episodes_watched' => $user->watchHistories()->count(),
            'episodes_unlocked' => $user->episodeUnlocks()->count(),
            'coins_spent' => $user->coinTransactions()->where('type', 'debit')->sum('amount'),
            'comments_count' => $user->comments()->count(),
            'ratings_count' => $user->ratings()->count(),
        ];

        $recentPayments = MobilePayment::where('user_id', $user->id)->latest()->limit(10)->get();
        $activeSubscription = $user->activeSubscription;
        $coinTransactions = $user->coinTransactions()->latest()->limit(20)->get();

        return view('admin.users.show', compact('user', 'stats', 'recentPayments', 'activeSubscription', 'coinTransactions'));
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activated' : 'banned';

        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function toggleVip(Request $request, User $user)
    {
        if ($user->is_vip) {
            $user->update(['is_vip' => false, 'vip_expires_at' => null]);
            return back()->with('success', "VIP removed from {$user->name}.");
        }

        $request->validate(['days' => 'required|integer|min:1|max:365']);
        $user->update([
            'is_vip' => true,
            'vip_expires_at' => now()->addDays($request->days),
        ]);

        return back()->with('success', "VIP granted to {$user->name} for {$request->days} days.");
    }

    public function grantCoins(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1|max:100000',
            'reason' => 'required|string|max:255',
        ]);

        app(CoinService::class)->credit($user, $request->amount, 'admin_grant', $request->reason);

        return back()->with('success', "{$request->amount} coins granted to {$user->name}. New balance: {$user->fresh()->coin_balance}");
    }

    public function deductCoins(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        if ($user->coin_balance < $request->amount) {
            return back()->with('error', "User only has {$user->coin_balance} coins.");
        }

        app(CoinService::class)->debit($user, $request->amount, 'admin_deduct', $request->reason);

        return back()->with('success', "{$request->amount} coins deducted from {$user->name}. New balance: {$user->fresh()->coin_balance}");
    }
}
