<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WatchHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchHistoryController extends Controller
{
    /**
     * Get user's watch history.
     */
    public function index(Request $request): JsonResponse
    {
        $histories = WatchHistory::where('user_id', $request->user()->id)
            ->whereHas('drama')
            ->whereHas('episode')
            ->with(['drama:id,title,cover_image,slug', 'episode:id,title,episode_number,season_number,thumbnail'])
            ->orderByDesc('updated_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($histories);
    }

    /**
     * Get continue watching list (unfinished episodes).
     */
    public function continueWatching(Request $request): JsonResponse
    {
        $histories = WatchHistory::where('user_id', $request->user()->id)
            ->where('completed', false)
            ->whereHas('drama')
            ->whereHas('episode')
            ->with(['drama:id,title,cover_image,slug', 'episode:id,title,episode_number,season_number,thumbnail,duration'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return $this->success($histories);
    }

    /**
     * Delete a watch history entry.
     */
    public function destroy(Request $request, WatchHistory $watchHistory): JsonResponse
    {
        if ($watchHistory->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $watchHistory->delete();

        return $this->noContent('Watch history entry deleted');
    }

    /**
     * Clear all watch history.
     */
    public function clearAll(Request $request): JsonResponse
    {
        WatchHistory::where('user_id', $request->user()->id)->delete();

        return $this->noContent('Watch history cleared');
    }
}
