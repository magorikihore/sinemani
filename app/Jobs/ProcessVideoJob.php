<?php

namespace App\Jobs;

use App\Models\Episode;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Process;

class ProcessVideoJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 1800; // 30 minutes

    public function __construct(
        protected int $episodeId
    ) {}

    public function handle(): void
    {
        $episode = Episode::find($this->episodeId);

        if (!$episode || !$episode->video_path) {
            Log::warning('ProcessVideoJob: Episode or video not found', ['id' => $this->episodeId]);
            return;
        }

        Log::info('ProcessVideoJob: Starting', [
            'episode_id' => $episode->id,
            'video_path' => $episode->video_path,
        ]);

        try {
            $inputPath = Storage::path($episode->video_path);

            if (!file_exists($inputPath)) {
                throw new \RuntimeException("Video file not found: {$inputPath}");
            }

            // Create HLS output directory
            $dramaId = $episode->drama_id;
            $hlsDir = "dramas/{$dramaId}/hls/ep-{$episode->episode_number}";
            $hlsFullDir = Storage::disk('public')->path($hlsDir);

            if (!is_dir($hlsFullDir)) {
                mkdir($hlsFullDir, 0755, true);
            }

            $playlistPath = "{$hlsFullDir}/playlist.m3u8";

            // Probe duration if not set
            if (!$episode->duration) {
                $duration = $this->probeDuration($inputPath);
                if ($duration > 0) {
                    $episode->update(['duration' => $duration]);
                }
            }

            // Transcode to HLS using FFmpeg
            $ffmpegCmd = implode(' ', [
                'ffmpeg',
                '-i', escapeshellarg($inputPath),
                '-codec: copy',   // Copy streams (fast, no re-encoding)
                '-start_number', '0',
                '-hls_time', '10',
                '-hls_list_size', '0',
                '-hls_segment_filename', escapeshellarg("{$hlsFullDir}/segment_%03d.ts"),
                '-f', 'hls',
                escapeshellarg($playlistPath),
                '-y',
                '2>&1',
            ]);

            $result = Process::timeout($this->timeout)->run($ffmpegCmd);

            if (!$result->successful()) {
                throw new \RuntimeException("FFmpeg failed: " . $result->errorOutput());
            }

            if (!file_exists($playlistPath)) {
                throw new \RuntimeException("HLS playlist was not created");
            }

            // Update episode with HLS URL and mark as published
            $episode->update([
                'hls_url' => $hlsDir . '/playlist.m3u8',
                'status' => 'published',
                'published_at' => $episode->published_at ?? now(),
            ]);

            // Update drama published episode count
            $episode->drama->update([
                'published_episodes' => $episode->drama->episodes()->published()->count(),
            ]);

            Log::info('ProcessVideoJob: Completed', [
                'episode_id' => $episode->id,
                'hls_url' => $episode->hls_url,
            ]);
        } catch (\Exception $e) {
            Log::error('ProcessVideoJob: Failed', [
                'episode_id' => $episode->id,
                'error' => $e->getMessage(),
            ]);

            $episode->update(['status' => 'failed']);

            throw $e; // Re-throw for retry
        }
    }

    /**
     * Probe video duration using FFprobe.
     */
    private function probeDuration(string $path): int
    {
        try {
            $result = Process::timeout(30)->run(
                'ffprobe -v quiet -show_entries format=duration -of csv=p=0 ' . escapeshellarg($path)
            );

            if ($result->successful()) {
                return (int) round((float) trim($result->output()));
            }
        } catch (\Exception $e) {
            Log::warning('ProcessVideoJob: ffprobe failed', ['error' => $e->getMessage()]);
        }

        return 0;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessVideoJob: Permanently failed', [
            'episode_id' => $this->episodeId,
            'error' => $exception->getMessage(),
        ]);

        Episode::where('id', $this->episodeId)->update(['status' => 'failed']);
    }
}
