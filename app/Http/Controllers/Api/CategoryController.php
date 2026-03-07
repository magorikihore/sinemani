<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * List all active categories.
     */
    public function index(): JsonResponse
    {
        $categories = Category::active()
            ->withCount(['dramas' => fn($q) => $q->published()])
            ->orderBy('sort_order')
            ->get();

        return $this->success($categories);
    }

    /**
     * Get category with its dramas.
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['dramas' => function ($q) {
            $q->published()->with('tags')->orderByDesc('published_at')->limit(50);
        }]);

        return $this->success($category);
    }
}
