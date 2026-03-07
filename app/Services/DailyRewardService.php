<?php

namespace App\Services;

use App\Models\DailyReward;
use App\Models\User;
use Carbon\Carbon;

class DailyRewardService
{
    // Coins per streak day (day => coins)
    protected array $rewardSchedule = [
        1 => 5,
        2 => 10,
        3 => 15,
        4 => 20,
        5 => 25,
        6 => 30,
        7 => 50, // bonus on day 7
    ];

    public function __construct(
        protected CoinService $coinService
    ) {}

    /**
     * Claim daily reward for the user.
     */
    public function claim(User $user): DailyReward
    {
        $today = Carbon::today();

        // Check if already claimed today
        $todayReward = DailyReward::where('user_id', $user->id)
            ->where('reward_date', $today)
            ->first();

        if ($todayReward) {
            throw new \Exception('Daily reward already claimed today.');
        }

        // Get yesterday's reward for streak calculation
        $yesterdayReward = DailyReward::where('user_id', $user->id)
            ->where('reward_date', $today->copy()->subDay())
            ->first();

        $streakDay = $yesterdayReward
            ? ($yesterdayReward->streak_day % 7) + 1
            : 1;

        $coinsEarned = $this->rewardSchedule[$streakDay] ?? 5;

        // VIP users get double daily rewards
        if ($user->isVipActive()) {
            $coinsEarned *= 2;
        }

        // Credit coins
        $this->coinService->credit(
            $user,
            $coinsEarned,
            'daily_reward',
            "Daily reward - Day {$streakDay} streak",
            null,
            ['streak_day' => $streakDay]
        );

        return DailyReward::create([
            'user_id' => $user->id,
            'reward_date' => $today,
            'coins_earned' => $coinsEarned,
            'streak_day' => $streakDay,
        ]);
    }

    /**
     * Get the user's current streak info.
     */
    public function getStreakInfo(User $user): array
    {
        $today = Carbon::today();

        $todayReward = DailyReward::where('user_id', $user->id)
            ->where('reward_date', $today)
            ->first();

        $lastReward = DailyReward::where('user_id', $user->id)
            ->orderByDesc('reward_date')
            ->first();

        $currentStreak = $lastReward ? $lastReward->streak_day : 0;
        $canClaim = !$todayReward;

        $nextDay = $canClaim
            ? ($lastReward && $lastReward->reward_date->isYesterday()
                ? ($lastReward->streak_day % 7) + 1
                : 1)
            : null;

        return [
            'current_streak' => $currentStreak,
            'can_claim' => $canClaim,
            'claimed_today' => (bool) $todayReward,
            'next_reward_day' => $nextDay,
            'next_reward_coins' => $nextDay ? ($this->rewardSchedule[$nextDay] ?? 5) : null,
            'reward_schedule' => $this->rewardSchedule,
        ];
    }
}
