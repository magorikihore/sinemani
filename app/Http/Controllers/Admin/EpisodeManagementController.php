<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEpisodeRequest;
use App\Jobs\ProcessVideoJob;
use App\Models\Drama;
use App\Models\Episode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EpisodeManagementController extends Controller
{
    /**
     * List episodes for a drama.
     */
    public function index(Request $request, Drama $drama): JsonResponse
    {
        $episodes = $drama->episodes()
            ->orderBy('season_number')
            ->orderBy('episode_number')
            ->paginate($request->input('per_page', 50));

        return $this->paginated($episodes);
    }

    /**
     * Create a new episode.
     */
    public function store(StoreEpisodeRequest $request, Drama $drama): JsonResponse
    {
        $data = $request->validated();
        $data['drama_id'] = $drama->id;
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')
                ->store("dramas/{$drama->id}/thumbnails", 'public');
        }

        // Handle video upload
        if ($request->hasFile('video')) {
            $videoFile = $request->file('video');
            $data['video_path'] = $videoFile->store("dramas/{$drama->id}/videos", 'local');
            $data['file_size'] = $videoFile->getSize();
            $data['status'] = 'processing'; // Will need to be processed for HLS

            // Dispatch video processing job
        }

        $episode = Episode::create($data);

        if (isset($videoFile)) {
            ProcessVideoJob::dispatch($episode->id);
        }

        // Update drama episode counts
        $drama->update([
            'total_episodes' => $drama->episodes()->count(),
            'published_episodes' => $drama->episodes()->published()->count(),
        ]);

        return $this->created($episode, 'Episode created successfully');
    }

    /**
     * Get a single episode.
     */
    public function show(Drama $drama, Episode $episode): JsonResponse
    {
        return $this->success($episode);
    }

    /**
     * Update an episode.
     */
    public function update(Request $request, Drama $drama, Episode $episode): JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'thumbnail' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'video' => ['sometimes', 'nullable', 'file', 'mimes:mp4,mov,avi,mkv', 'max:512000'],
            'video_url' => ['sometimes', 'nullable', 'url'],
            'hls_url' => ['sometimes', 'nullable', 'url'],
            'episode_number' => ['sometimes', 'integer', 'min:1'],
            'season_number' => ['sometimes', 'integer', 'min:1'],
            'duration' => ['sometimes', 'integer', 'min:0'],
            'is_free' => ['sometimes', 'boolean'],
            'coin_price' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:draft,processing,published,failed'],
        ]);

        if ($request->hasFile('thumbnail')) {
            if ($episode->thumbnail) {
                Storage::disk('public')->delete($episode->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')
                ->store("dramas/{$drama->id}/thumbnails", 'public');
        }

        if ($request->hasFile('video')) {
            if ($episode->video_path) {
                Storage::delete($episode->video_path);
            }
            $videoFile = $request->file('video');
            $data['video_path'] = $videoFile->store("dramas/{$drama->id}/videos", 'local');
            $data['file_size'] = $videoFile->getSize();
            $data['status'] = 'processing';
        }

        // If publishing for the first time
        if (isset($data['status']) && $data['status'] === 'published' && !$episode->published_at) {
            $data['published_at'] = now();
        }

        $episode->update($data);

        // Update drama counts
        $drama->update([
            'total_episodes' => $drama->episodes()->count(),
            'published_episodes' => $drama->episodes()->published()->count(),
        ]);

        return $this->success($episode, 'Episode updated successfully');
    }

    /**
     * Delete an episode (soft delete).
     */
    public function destroy(Drama $drama, Episode $episode): JsonResponse
    {
        $episode->delete();

        $drama->update([
            'total_episodes' => $drama->episodes()->count(),
            'published_episodes' => $drama->episodes()->published()->count(),
        ]);

        return $this->noContent('Episode deleted successfully');
    }

    /**
     * Reorder episodes.
     */
    public function reorder(Request $request, Drama $drama): JsonResponse
    {
        $request->validate([
            'episodes' => ['required', 'array'],
            'episodes.*.id' => ['required', 'exists:episodes,id'],
            'episodes.*.episode_number' => ['required', 'integer', 'min:1'],
        ]);

        foreach ($request->episodes as $item) {
            Episode::where('id', $item['id'])
                ->where('drama_id', $drama->id)
                ->update(['episode_number' => $item['episode_number']]);
        }

        return $this->success(null, 'Episodes reordered');
    }
}
