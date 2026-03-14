<?php

namespace App\Services;

use App\Models\User;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CoinService
{
    /**
     * Credit coins to a user's balance.
     */
    public function credit(
        User $user,
        int $amount,
        string $source,
        string $description = '',
        $transactionable = null,
        array $metadata = []
    ): CoinTransaction {
        return DB::transaction(function () use ($user, $amount, $source, $description, $transactionable, $metadata) {
            // Lock user row for update to prevent race conditions
            $user = User::lockForUpdate()->find($user->id);

            $balanceBefore = $user->coin_balance;
            $balanceAfter = $balanceBefore + $amount;

            $user->update(['coin_balance' => $balanceAfter]);

            return CoinTransaction::create([
                'user_id' => $user->id,
                'reference' => $this->generateReference(),
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source' => $source,
                'description' => $description,
                'transactionable_id' => $transactionable?->id ?? 0,
                'transactionable_type' => $transactionable ? get_class($transactionable) : User::class,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Debit coins from a user's balance.
     */
    public function debit(
        User $user,
        int $amount,
        string $source,
        string $description = '',
        $transactionable = null,
        array $metadata = []
    ): CoinTransaction {
        return DB::transaction(function () use ($user, $amount, $source, $description, $transactionable, $metadata) {
            $user = User::lockForUpdate()->find($user->id);

            if ($user->coin_balance < $amount) {
                throw new \App\Exceptions\InsufficientCoinsException($amount, $user->coin_balance);
            }

            $balanceBefore = $user->coin_balance;
            $balanceAfter = $balanceBefore - $amount;

            $user->update(['coin_balance' => $balanceAfter]);

            return CoinTransaction::create([
                'user_id' => $user->id,
                'reference' => $this->generateReference(),
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'source' => $source,
                'description' => $description,
                'transactionable_id' => $transactionable?->id ?? 0,
                'transactionable_type' => $transactionable ? get_class($transactionable) : User::class,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Get user's transaction history.
     */
    public function getHistory(User $user, int $perPage = 20)
    {
        return $user->coinTransactions()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Generate a unique transaction reference.
     */
    private function generateReference(): string
    {
        return 'TXN-' . strtoupper(Str::random(16));
    }
}
