<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        protected CoinService $coinService
    ) {}

    /**
     * List users with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%")
                  ->orWhere('username', 'ilike', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_vip')) {
            $query->where('is_vip', $request->boolean('is_vip'));
        }

        $users = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($users);
    }

    /**
     * Get user details.
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles');

        $data = $user->toArray();
        $data['stats'] = [
            'total_watch_time' => $user->watchHistories()->sum('progress'),
            'episodes_watched' => $user->watchHistories()->count(),
            'episodes_unlocked' => $user->episodeUnlocks()->count(),
            'coins_spent' => $user->coinTransactions()->where('type', 'debit')->sum('amount'),
            'coins_purchased' => $user->purchases()->completed()->sum('coins_granted'),
            'total_purchases' => $user->purchases()->completed()->sum('amount'),
            'comments_count' => $user->comments()->count(),
        ];

        return $this->success($data);
    }

    /**
     * Update user (ban/unban, toggle VIP, etc.).
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'is_vip' => ['sometimes', 'boolean'],
            'vip_expires_at' => ['sometimes', 'nullable', 'date'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', "unique:users,email,{$user->id}"],
        ]);

        $user->update($data);

        return $this->success($user->fresh(), 'User updated');
    }

    /**
     * Grant coins to user.
     */
    public function grantCoins(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:100000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->coinService->credit(
            $user,
            $request->amount,
            'admin_grant',
            $request->reason
        );

        return $this->success([
            'new_balance' => $user->fresh()->coin_balance,
        ], 'Coins granted successfully');
    }

    /**
     * Deduct coins from user.
     */
    public function deductCoins(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->coinService->debit(
                $user,
                $request->amount,
                'admin_deduct',
                $request->reason
            );

            return $this->success([
                'new_balance' => $user->fresh()->coin_balance,
            ], 'Coins deducted successfully');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }
}
