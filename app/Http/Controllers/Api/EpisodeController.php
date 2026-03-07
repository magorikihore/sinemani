<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateWatchProgressRequest;
use App\Models\Episode;
use App\Models\WatchHistory;
use App\Services\EpisodeUnlockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    public function __construct(
        protected EpisodeUnlockService $unlockService
    ) {}

    /**
     * Get episode details (for playing).
     */
    public function show(Request $request, Episode $episode): JsonResponse
    {
        if ($episode->status !== 'published') {
            return $this->notFound();
        }

        $episode->load('drama');
        // Resolve user from Bearer token even on public route
        $user = $request->user() ?? $request->user('sanctum');
        if (!$user && $request->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                $user = $token->tokenable;
            }
        }

        $data = $episode->toArray();
        $data['drama_title'] = $episode->drama->title;

        if ($user) {
            $isUnlocked = $this->unlockService->isUnlocked($user, $episode);
            $data['is_unlocked'] = $isUnlocked;
            $data['effective_price'] = $episode->getEffectivePrice();
            $data['is_liked'] = $episode->likes()->where('user_id', $user->id)->exists();

            // Get watch progress
            $history = WatchHistory::where('user_id', $user->id)
                ->where('episode_id', $episode->id)
                ->first();

            $data['watch_progress'] = $history ? [
                'progress' => $history->progress,
                'duration' => $history->duration,
                'completed' => $history->completed,
            ] : null;

            // If unlocked, provide streaming URL
            if ($isUnlocked) {
                $data['stream_url'] = $episode->hls_url ?? $episode->video_url;
            } else {
                // Don't expose video URLs for locked episodes
                unset($data['video_url'], $data['video_path'], $data['hls_url']);
            }
        } else {
            $data['is_unlocked'] = $episode->is_free || $episode->drama->is_free;
            if (!$data['is_unlocked']) {
                unset($data['video_url'], $data['video_path'], $data['hls_url']);
            }
        }

        // Increment view count
        $episode->incrementViewCount();

        return $this->success($data);
    }

    /**
     * Unlock an episode with coins.
     */
    public function unlock(Request $request, Episode $episode): JsonResponse
    {
        $user = $request->user();

        try {
            $unlock = $this->unlockService->unlock($user, $episode);

            return $this->success([
                'unlock' => $unlock,
                'coin_balance' => $user->fresh()->coin_balance,
                'stream_url' => $episode->hls_url ?? $episode->video_url,
            ], 'Episode unlocked successfully');
        } catch (\App\Exceptions\InsufficientCoinsException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Update watch progress.
     */
    public function updateProgress(UpdateWatchProgressRequest $request, Episode $episode): JsonResponse
    {
        $user = $request->user();

        $history = WatchHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'episode_id' => $episode->id,
            ],
            [
                'drama_id' => $episode->drama_id,
                'progress' => $request->progress,
                'duration' => $request->duration,
                'completed' => $request->progress >= ($request->duration * 0.9), // 90% = completed
            ]
        );

        return $this->success($history, 'Progress updated');
    }

    /**
     * Get next episode in the drama.
     */
    public function next(Episode $episode): JsonResponse
    {
        $nextEpisode = Episode::where('drama_id', $episode->drama_id)
            ->where('season_number', $episode->season_number)
            ->where('episode_number', '>', $episode->episode_number)
            ->published()
            ->orderBy('episode_number')
            ->first();

        // If no more episodes in current season, check next season
        if (!$nextEpisode) {
            $nextEpisode = Episode::where('drama_id', $episode->drama_id)
                ->where('season_number', '>', $episode->season_number)
                ->published()
                ->orderBy('season_number')
                ->orderBy('episode_number')
                ->first();
        }

        if (!$nextEpisode) {
            return $this->success(null, 'No more episodes');
        }

        return $this->success($nextEpisode);
    }
}
