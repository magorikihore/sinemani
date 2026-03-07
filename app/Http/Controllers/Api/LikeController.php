<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Toggle like on a drama.
     */
    public function toggleDramaLike(Request $request, Drama $drama): JsonResponse
    {
        return $this->toggleLike($request->user(), $drama);
    }

    /**
     * Toggle like on an episode.
     */
    public function toggleEpisodeLike(Request $request, Episode $episode): JsonResponse
    {
        return $this->toggleLike($request->user(), $episode);
    }

    private function toggleLike($user, $model): JsonResponse
    {
        $like = Like::where('user_id', $user->id)
            ->where('likeable_id', $model->id)
            ->where('likeable_type', get_class($model))
            ->first();

        if ($like) {
            $like->delete();
            $model->decrement('like_count');
            return $this->success(['liked' => false, 'like_count' => $model->fresh()->like_count], 'Unliked');
        }

        Like::create([
            'user_id' => $user->id,
            'likeable_id' => $model->id,
            'likeable_type' => get_class($model),
        ]);

        $model->increment('like_count');

        return $this->success(['liked' => true, 'like_count' => $model->fresh()->like_count], 'Liked');
    }
}
