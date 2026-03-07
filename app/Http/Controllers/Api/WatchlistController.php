<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    /**
     * Get user's watchlist.
     */
    public function index(Request $request): JsonResponse
    {
        $dramas = $request->user()
            ->watchlist()
            ->with('category')
            ->orderByDesc('watchlists.created_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($dramas);
    }

    /**
     * Add drama to watchlist.
     */
    public function store(Request $request, Drama $drama): JsonResponse
    {
        $user = $request->user();

        if ($user->watchlist()->where('drama_id', $drama->id)->exists()) {
            return $this->success(null, 'Already in watchlist');
        }

        $user->watchlist()->attach($drama->id);

        return $this->created(null, 'Added to watchlist');
    }

    /**
     * Remove drama from watchlist.
     */
    public function destroy(Request $request, Drama $drama): JsonResponse
    {
        $request->user()->watchlist()->detach($drama->id);

        return $this->noContent('Removed from watchlist');
    }

    /**
     * Check if drama is in watchlist.
     */
    public function check(Request $request, Drama $drama): JsonResponse
    {
        $inWatchlist = $request->user()->watchlist()
            ->where('drama_id', $drama->id)
            ->exists();

        return $this->success(['in_watchlist' => $inWatchlist]);
    }
}
