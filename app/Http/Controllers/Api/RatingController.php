<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Drama;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    /**
     * Rate a drama (create or update).
     */
    public function store(StoreRatingRequest $request, Drama $drama): JsonResponse
    {
        $rating = Rating::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'drama_id' => $drama->id,
            ],
            [
                'score' => $request->score,
                'review' => $request->review,
            ]
        );

        // Recalculate drama rating
        $drama->updateRating();

        return $this->success([
            'rating' => $rating,
            'drama_rating' => $drama->fresh()->rating,
            'drama_rating_count' => $drama->fresh()->rating_count,
        ], 'Rating submitted');
    }

    /**
     * Get ratings for a drama.
     */
    public function index(Request $request, Drama $drama): JsonResponse
    {
        $ratings = Rating::where('drama_id', $drama->id)
            ->with('user:id,name,username,avatar')
            ->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($ratings);
    }

    /**
     * Delete rating.
     */
    public function destroy(Request $request, Drama $drama): JsonResponse
    {
        Rating::where('user_id', $request->user()->id)
            ->where('drama_id', $drama->id)
            ->delete();

        $drama->updateRating();

        return $this->noContent('Rating deleted');
    }
}
