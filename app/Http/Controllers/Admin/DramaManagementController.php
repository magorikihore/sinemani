<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDramaRequest;
use App\Http\Requests\Admin\UpdateDramaRequest;
use App\Models\Drama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DramaManagementController extends Controller
{
    /**
     * List all dramas (including non-published) with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Drama::with(['category', 'tags']);

        if ($search = $request->input('search')) {
            $query->where('title', 'ilike', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        $dramas = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($dramas);
    }

    /**
     * Create a new drama.
     */
    public function store(StoreDramaRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        // Handle image uploads
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('dramas/covers', 'public');
        }

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')->store('dramas/banners', 'public');
        }

        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $drama = Drama::create($data);

        if (!empty($tags)) {
            $drama->tags()->sync($tags);
        }

        $drama->load(['category', 'tags']);

        return $this->created($drama, 'Drama created successfully');
    }

    /**
     * Get a single drama.
     */
    public function show(Drama $drama): JsonResponse
    {
        $drama->load(['category', 'tags', 'episodes']);

        return $this->success($drama);
    }

    /**
     * Update a drama.
     */
    public function update(UpdateDramaRequest $request, Drama $drama): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('cover_image')) {
            if ($drama->cover_image) {
                Storage::disk('public')->delete($drama->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('dramas/covers', 'public');
        }

        if ($request->hasFile('banner_image')) {
            if ($drama->banner_image) {
                Storage::disk('public')->delete($drama->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')->store('dramas/banners', 'public');
        }

        if (isset($data['tags'])) {
            $drama->tags()->sync($data['tags']);
            unset($data['tags']);
        }

        // If publishing for the first time, set published_at
        if (isset($data['status']) && $data['status'] === 'published' && !$drama->published_at) {
            $data['published_at'] = now();
        }

        $drama->update($data);
        $drama->load(['category', 'tags']);

        return $this->success($drama, 'Drama updated successfully');
    }

    /**
     * Delete a drama (soft delete).
     */
    public function destroy(Drama $drama): JsonResponse
    {
        $drama->delete();

        return $this->noContent('Drama deleted successfully');
    }

    /**
     * Bulk update drama status.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['exists:dramas,id'],
            'status' => ['required', 'in:draft,published,completed,suspended'],
        ]);

        Drama::whereIn('id', $request->ids)->update(['status' => $request->status]);

        return $this->success(null, 'Drama status updated');
    }
}
