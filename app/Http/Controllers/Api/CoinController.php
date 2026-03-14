<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoinPackage;
use App\Services\CoinService;
use App\Services\DailyRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoinController extends Controller
{
    public function __construct(
        protected CoinService $coinService,
        protected DailyRewardService $dailyRewardService
    ) {}

    /**
     * Get user's coin balance and transaction history.
     */
    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'balance' => $user->coin_balance,
            'is_vip' => $user->isVipActive(),
        ]);
    }

    /**
     * Get transaction history.
     */
    public function transactions(Request $request): JsonResponse
    {
        $transactions = $this->coinService->getHistory(
            $request->user(),
            $request->input('per_page', 20)
        );

        return $this->paginated($transactions);
    }

    /**
     * Get available coin packages.
     */
    public function packages(): JsonResponse
    {
        $packages = CoinPackage::active()
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return $this->success($packages);
    }

    /**
     * Claim daily reward.
     */
    public function claimDailyReward(Request $request): JsonResponse
    {
        try {
            $reward = $this->dailyRewardService->claim($request->user());

            return $this->success([
                'reward' => $reward,
                'coin_balance' => $request->user()->fresh()->coin_balance,
            ], 'Daily reward claimed!');
        } catch (\Exception $e) {
            return $this->error('You have already claimed your daily reward today. Come back tomorrow!', 422, null, 'DAILY_REWARD_CLAIMED');
        }
    }

    /**
     * Get daily reward streak info.
     */
    public function dailyRewardInfo(Request $request): JsonResponse
    {
        $info = $this->dailyRewardService->getStreakInfo($request->user());

        return $this->success($info);
    }

    /**
     * Reward coins for watching an ad.
     */
    public function adReward(Request $request): JsonResponse
    {
        $request->validate([
            'ad_network' => ['required', 'string'],
            'ad_unit_id' => ['required', 'string'],
        ]);

        $adRewardCoins = (int) config('dramabox.ad_reward_coins', 5);

        $this->coinService->credit(
            $request->user(),
            $adRewardCoins,
            'ad_reward',
            'Watched ad reward',
            null,
            [
                'ad_network' => $request->ad_network,
                'ad_unit_id' => $request->ad_unit_id,
            ]
        );

        return $this->success([
            'coins_earned' => $adRewardCoins,
            'coin_balance' => $request->user()->fresh()->coin_balance,
        ], 'Ad reward credited');
    }
}
