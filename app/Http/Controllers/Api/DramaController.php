<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Drama;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DramaController extends Controller
{
    /**
     * List dramas with filtering, sorting, and search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Drama::published()->with(['category', 'tags']);

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('synopsis', 'like', "%{$search}%")
                  ->orWhere('director', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter by tag
        if ($tagId = $request->input('tag_id')) {
            $query->whereHas('tags', fn($q) => $q->where('tags.id', $tagId));
        }

        // Filter by content rating
        if ($rating = $request->input('content_rating')) {
            $query->where('content_rating', $rating);
        }

        // Filter by language
        if ($language = $request->input('language')) {
            $query->where('language', $language);
        }

        // Filter free only
        if ($request->boolean('free_only')) {
            $query->where('is_free', true);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['published_at', 'view_count', 'rating', 'title', 'created_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        $dramas = $query->paginate($request->input('per_page', 20));

        return $this->paginated($dramas);
    }

    /**
     * Get a single drama with episodes.
     */
    public function show(Request $request, Drama $drama): JsonResponse
    {
        if ($drama->status !== 'published') {
            return $this->notFound();
        }

        $drama->load(['category', 'tags', 'episodes' => function ($q) {
            $q->published()->orderBy('season_number')->orderBy('episode_number');
        }]);

        // Resolve user from Bearer token even on public route
        $user = $request->user() ?? $request->user('sanctum');
        if (!$user && $request->bearerToken()) {
            $token = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            if ($token) {
                $user = $token->tokenable;
            }
        }
        $data = $drama->toArray();

        // Add user-specific data if authenticated
        if ($user) {
            $data['is_in_watchlist'] = $user->watchlist()->where('drama_id', $drama->id)->exists();
            $data['user_rating'] = $drama->ratings()->where('user_id', $user->id)->first();
            $data['is_liked'] = $drama->likes()->where('user_id', $user->id)->exists();

            // Mark which episodes are unlocked
            $unlockedEpisodeIds = $user->episodeUnlocks()
                ->whereIn('episode_id', $drama->episodes->pluck('id'))
                ->pluck('episode_id')
                ->toArray();

            $data['episodes'] = $drama->episodes->map(function ($episode) use ($unlockedEpisodeIds, $user, $drama) {
                $ep = $episode->toArray();
                $ep['is_unlocked'] = $episode->is_free || $drama->is_free || $user->isVipActive()
                    || in_array($episode->id, $unlockedEpisodeIds);
                return $ep;
            });
        }

        return $this->success($data);
    }

    /**
     * Get featured dramas.
     */
    public function featured(): JsonResponse
    {
        $dramas = Drama::published()
            ->featured()
            ->with(['category'])
            ->orderByDesc('sort_order')
            ->limit(20)
            ->get();

        return $this->success($dramas);
    }

    /**
     * Get trending dramas.
     */
    public function trending(): JsonResponse
    {
        $dramas = Drama::published()
            ->trending()
            ->with(['category'])
            ->orderByDesc('view_count')
            ->limit(20)
            ->get();

        return $this->success($dramas);
    }

    /**
     * Get new releases.
     */
    public function newReleases(): JsonResponse
    {
        $dramas = Drama::published()
            ->newRelease()
            ->with(['category'])
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        return $this->success($dramas);
    }

    /**
     * Get home page data (banners + curated lists).
     */
    public function home(): JsonResponse
    {
        $banners = Banner::active()->orderBy('sort_order')->get();

        // Populate banner images from linked drama if banner has no image
        $dramaIds = $banners->where('link_type', 'drama')->pluck('link_value')->filter()->map(fn($v) => (int) $v);
        $dramaImages = Drama::whereIn('id', $dramaIds)->pluck('banner_image', 'id');
        $dramaCoverImages = Drama::whereIn('id', $dramaIds)->pluck('cover_image', 'id');
        $banners->transform(function ($banner) use ($dramaImages, $dramaCoverImages) {
            if (empty($banner->image) && $banner->link_type === 'drama') {
                $did = (int) $banner->link_value;
                $banner->image = $dramaImages[$did] ?? $dramaCoverImages[$did] ?? '';
            }
            return $banner;
        });

        $featured = Drama::published()->featured()
            ->with('category')->orderByDesc('sort_order')->limit(10)->get();

        $trending = Drama::published()->trending()
            ->with('category')->orderByDesc('view_count')->limit(10)->get();

        $newReleases = Drama::published()->newRelease()
            ->with('category')->orderByDesc('published_at')->limit(10)->get();

        $recentlyUpdated = Drama::published()
            ->with('category')->orderByDesc('updated_at')->limit(10)->get();

        $topRated = Drama::published()
            ->with('category')
            ->where('rating_count', '>=', 5)
            ->orderByDesc('rating')
            ->limit(10)
            ->get();

        return $this->success([
            'banners' => $banners,
            'featured' => $featured,
            'trending' => $trending,
            'new_releases' => $newReleases,
            'recently_updated' => $recentlyUpdated,
            'top_rated' => $topRated,
        ]);
    }
}
