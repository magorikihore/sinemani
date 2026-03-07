<?php

namespace App\Services;

use App\Models\Episode;
use App\Models\EpisodeUnlock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EpisodeUnlockService
{
    public function __construct(
        protected CoinService $coinService
    ) {}

    /**
     * Unlock an episode for a user by spending coins.
     */
    public function unlock(User $user, Episode $episode): EpisodeUnlock
    {
        // Check if already unlocked
        $existing = EpisodeUnlock::where('user_id', $user->id)
            ->where('episode_id', $episode->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        // Free episodes or VIP users don't need to pay
        if ($episode->is_free || $episode->drama->is_free || $user->isVipActive()) {
            return EpisodeUnlock::create([
                'user_id' => $user->id,
                'episode_id' => $episode->id,
                'coins_spent' => 0,
            ]);
        }

        $price = $episode->getEffectivePrice();

        return DB::transaction(function () use ($user, $episode, $price) {
            // Debit coins
            $this->coinService->debit(
                $user,
                $price,
                'episode_unlock',
                "Unlocked: {$episode->drama->title} - Episode {$episode->episode_number}",
                $episode
            );

            // Create unlock record
            return EpisodeUnlock::create([
                'user_id' => $user->id,
                'episode_id' => $episode->id,
                'coins_spent' => $price,
            ]);
        });
    }

    /**
     * Check if a user has unlocked an episode.
     */
    public function isUnlocked(User $user, Episode $episode): bool
    {
        if ($episode->is_free || $episode->drama->is_free || $user->isVipActive()) {
            return true;
        }

        return EpisodeUnlock::where('user_id', $user->id)
            ->where('episode_id', $episode->id)
            ->exists();
    }
}
