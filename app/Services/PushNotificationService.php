<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    /**
     * Send push notification to a single user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        $token = $user->expo_push_token;
        if (empty($token)) {
            return false;
        }

        return $this->send([$token], $title, $body, $data);
    }

    /**
     * Send push notification to multiple users.
     */
    public function sendToUsers($users, string $title, string $body, array $data = []): int
    {
        $tokens = collect($users)
            ->pluck('expo_push_token')
            ->filter()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return 0;
        }

        // Expo API accepts batches of 100
        $chunks = array_chunk($tokens, 100);
        $sent = 0;

        foreach ($chunks as $chunk) {
            if ($this->send($chunk, $title, $body, $data)) {
                $sent += count($chunk);
            }
        }

        return $sent;
    }

    /**
     * Send push notification via Expo Push API.
     */
    protected function send(array $tokens, string $title, string $body, array $data = []): bool
    {
        $messages = array_map(function ($token) use ($title, $body, $data) {
            return [
                'to' => $token,
                'sound' => 'default',
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ];
        }, $tokens);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post(self::EXPO_PUSH_URL, $messages);

            if (!$response->successful()) {
                Log::error('Expo Push failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Expo Push exception', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Send new episode notification to all users who watched this drama.
     */
    public function notifyNewEpisode(int $dramaId, string $dramaTitle, int $episodeNumber): int
    {
        $users = User::whereHas('watchHistories', function ($q) use ($dramaId) {
            $q->where('drama_id', $dramaId);
        })
            ->whereNotNull('expo_push_token')
            ->get();

        return $this->sendToUsers(
            $users,
            'New Episode!',
            "Episode {$episodeNumber} of \"{$dramaTitle}\" is now available!",
            ['type' => 'new_episode', 'drama_id' => $dramaId]
        );
    }

    /**
     * Send daily reward reminder to users who haven't claimed today.
     */
    public function sendDailyRewardReminder(): int
    {
        $users = User::whereNotNull('expo_push_token')
            ->whereDoesntHave('dailyRewards', function ($q) {
                $q->where('reward_date', today());
            })
            ->get();

        return $this->sendToUsers(
            $users,
            '🎁 Daily Reward Ready!',
            'Claim your free coins today and keep your streak going!',
            ['type' => 'daily_reward']
        );
    }

    /**
     * Send promotional push notification to all users.
     */
    public function sendPromotion(string $title, string $body, array $data = []): int
    {
        $users = User::whereNotNull('expo_push_token')->get();

        return $this->sendToUsers($users, $title, $body, $data);
    }
}
