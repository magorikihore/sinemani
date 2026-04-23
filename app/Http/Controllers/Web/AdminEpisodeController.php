<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\EpisodeSubtitle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminEpisodeController extends Controller
{
    public function create(Drama $drama)
    {
        $nextEpisode = $drama->episodes()->max('episode_number') + 1;
        $nextSeason = $drama->episodes()->max('season_number') ?: 1;

        return view('admin.episodes.create', compact('drama', 'nextEpisode', 'nextSeason'));
    }

    public function store(Request $request, Drama $drama)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'episode_number' => ['required', 'integer', 'min:1'],
            'season_number' => ['required', 'integer', 'min:1'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['sometimes'],
            'coin_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published'],
            'video_url' => ['nullable', 'url'],
            'hls_url' => ['nullable', 'url'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,mkv,webm', 'max:512000'],
        ]);

        $data['drama_id'] = $drama->id;
        $data['slug'] = Str::slug($data['title']) . '-' . Str::random(6);
        $data['is_free'] = $request->boolean('is_free');
        $data['duration'] = $data['duration'] ?? 0;

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $request->file('thumbnail')
                ->store("dramas/{$drama->id}/thumbnails", 'public');
        }

        if ($request->hasFile('video')) {
            $videoFile = $request->file('video');
            $data['video_path'] = $videoFile->store("dramas/{$drama->id}/videos", 'public');
            $data['file_size'] = $videoFile->getSize();
        }

        if (isset($data['status']) && $data['status'] === 'published') {
            $data['published_at'] = now();
        }

        Episode::create($data);

        // Update drama counts
        $drama->update([
            'total_episodes' => $drama->episodes()->count(),
            'published_episodes' => $drama->episodes()->where('status', 'published')->count(),
        ]);

        return redirect()->route('admin.dramas.show', $drama)
            ->with('success', "Episode \"{$data['title']}\" added successfully.");
    }

    public function edit(Drama $drama, Episode $episode)
    {
        $episode->load('subtitles');
        return view('admin.episodes.edit', compact('drama', 'episode'));
    }

    public function update(Request $request, Drama $drama, Episode $episode)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'episode_number' => ['required', 'integer', 'min:1'],
            'season_number' => ['required', 'integer', 'min:1'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'is_free' => ['sometimes'],
            'coin_price' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,processing,published,failed'],
            'video_url' => ['nullable', 'url'],
            'hls_url' => ['nullable', 'url'],
            'thumbnail' => ['nullable', 'image', 'max:5120'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,mkv,webm', 'max:512000'],
            'subtitles' => ['nullable', 'array'],
            'subtitles.*.language' => ['required_with:subtitles.*.file', 'string', 'max:10'],
            'subtitles.*.label' => ['nullable', 'string', 'max:64'],
            'subtitles.*.file' => ['required_with:subtitles.*.language', 'file', 'mimetypes:text/plain,text/vtt,application/x-subrip,application/octet-stream', 'max:2048'],
            'delete_subtitles' => ['nullable', 'array'],
            'delete_subtitles.*' => ['integer'],
        ]);

        $data['is_free'] = $request->boolean('is_free');
        $data['duration'] = $data['duration'] ?? $episode->duration ?? 0;

        if ($request->hasFile('thumbnail')) {
            if ($episode->thumbnail) {
                Storage::disk('public')->delete($episode->thumbnail);
            }
            $data['thumbnail'] = $request->file('thumbnail')
                ->store("dramas/{$drama->id}/thumbnails", 'public');
        }

        if ($request->hasFile('video')) {
            if ($episode->video_path) {
                Storage::disk('public')->delete($episode->video_path);
            }
            $videoFile = $request->file('video');
            $data['video_path'] = $videoFile->store("dramas/{$drama->id}/videos", 'public');
            $data['file_size'] = $videoFile->getSize();
        }

        if (isset($data['status']) && $data['status'] === 'published' && !$episode->published_at) {
            $data['published_at'] = now();
        }

        $episode->update($data);

        // Delete selected subtitles
        if ($request->filled('delete_subtitles')) {
            $toDelete = EpisodeSubtitle::whereIn('id', $request->input('delete_subtitles'))
                ->where('episode_id', $episode->id)
                ->get();
            foreach ($toDelete as $sub) {
                if ($sub->file_path && !str_starts_with($sub->file_path, 'http')) {
                    Storage::disk('public')->delete($sub->file_path);
                }
                $sub->delete();
            }
        }

        // Add new subtitle uploads
        if ($request->has('subtitles')) {
            foreach ($request->input('subtitles', []) as $idx => $sub) {
                if (!$request->hasFile("subtitles.{$idx}.file")) {
                    continue;
                }
                $file = $request->file("subtitles.{$idx}.file");
                $ext = strtolower($file->getClientOriginalExtension() ?: 'srt');
                $format = in_array($ext, ['srt', 'vtt']) ? $ext : 'srt';
                $path = $file->store("dramas/{$drama->id}/subtitles", 'public');
                EpisodeSubtitle::create([
                    'episode_id' => $episode->id,
                    'language' => $sub['language'],
                    'label' => $sub['label'] ?? null,
                    'file_path' => $path,
                    'format' => $format,
                ]);
            }
        }

        $drama->update([
            'total_episodes' => $drama->episodes()->count(),
            'published_episodes' => $drama->episodes()->where('status', 'published')->count(),
        ]);

        return redirect()->route('admin.dramas.show', $drama)
            ->with('success', "Episode \"{$episode->title}\" updated.");
    }

    public function destroy(Drama $drama, Episode $episode)
    {
        $title = $episode->title;

        if ($episode->thumbnail) {
            Storage::disk('public')->delete($episode->thumbnail);
        }
        if ($episode->video_path) {
            Storage::disk('public')->delete($episode->video_path);
        }
        foreach ($episode->subtitles as $sub) {
            if ($sub->file_path && !str_starts_with($sub->file_path, 'http')) {
                Storage::disk('public')->delete($sub->file_path);
            }
        }

        $episode->delete();

        $drama->update([
            'total_episodes' => $drama->episodes()->count(),
            'published_episodes' => $drama->episodes()->where('status', 'published')->count(),
        ]);

        return redirect()->route('admin.dramas.show', $drama)
            ->with('success', "Episode \"{$title}\" deleted.");
    }
}
