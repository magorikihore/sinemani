<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createPublishedDrama(array $overrides = []): Drama
    {
        $category = Category::firstOrCreate(
            ['slug' => 'romance'],
            ['name' => 'Romance', 'is_active' => true]
        );

        return Drama::create(array_merge([
            'title' => 'Test Drama',
            'slug' => 'test-drama-' . uniqid(),
            'synopsis' => 'A test drama synopsis.',
            'category_id' => $category->id,
            'status' => 'published',
            'total_episodes' => 5,
            'published_episodes' => 5,
            'published_at' => now(),
        ], $overrides));
    }

    private function createEpisode(Drama $drama, array $overrides = []): Episode
    {
        return Episode::create(array_merge([
            'drama_id' => $drama->id,
            'title' => 'Episode 1',
            'slug' => 'ep-' . uniqid(),
            'episode_number' => 1,
            'season_number' => 1,
            'duration' => 300,
            'is_free' => true,
            'coin_price' => 0,
            'status' => 'published',
            'video_url' => 'https://example.com/video.mp4',
            'published_at' => now(),
        ], $overrides));
    }

    public function test_can_list_dramas(): void
    {
        $this->createPublishedDrama(['title' => 'Drama A']);
        $this->createPublishedDrama(['title' => 'Drama B', 'slug' => 'drama-b']);

        $response = $this->getJson('/api/v1/dramas');

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_can_get_single_drama(): void
    {
        $drama = $this->createPublishedDrama();
        $this->createEpisode($drama);

        $response = $this->getJson("/api/v1/dramas/{$drama->id}");

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_can_get_featured_dramas(): void
    {
        $this->createPublishedDrama(['is_featured' => true]);

        $response = $this->getJson('/api/v1/dramas/featured');

        $response->assertOk();
    }

    public function test_can_get_trending_dramas(): void
    {
        $this->createPublishedDrama(['is_trending' => true]);

        $response = $this->getJson('/api/v1/dramas/trending');

        $response->assertOk();
    }

    public function test_can_get_new_releases(): void
    {
        $this->createPublishedDrama(['is_new_release' => true]);

        $response = $this->getJson('/api/v1/dramas/new-releases');

        $response->assertOk();
    }

    public function test_can_get_home_page(): void
    {
        $response = $this->getJson('/api/v1/home');

        $response->assertOk();
    }

    public function test_can_list_categories(): void
    {
        Category::create(['name' => 'Action', 'slug' => 'action', 'is_active' => true]);

        $response = $this->getJson('/api/v1/categories');

        $response->assertOk();
    }

    public function test_can_get_episode_details(): void
    {
        $drama = $this->createPublishedDrama();
        $episode = $this->createEpisode($drama);

        $response = $this->getJson("/api/v1/episodes/{$episode->id}");

        $response->assertOk();
    }

    public function test_can_get_next_episode(): void
    {
        $drama = $this->createPublishedDrama();
        $ep1 = $this->createEpisode($drama, ['episode_number' => 1]);
        $ep2 = $this->createEpisode($drama, [
            'episode_number' => 2,
            'title' => 'Episode 2',
            'slug' => 'ep2-' . uniqid(),
        ]);

        $response = $this->getJson("/api/v1/episodes/{$ep1->id}/next");

        $response->assertOk();
    }
}
