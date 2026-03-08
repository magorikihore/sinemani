<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EpisodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Drama $drama;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create([
            'is_active' => true,
            'coin_balance' => 100,
        ]);
        $this->user->assignRole('user');

        $category = Category::create(['name' => 'Action', 'slug' => 'action', 'is_active' => true]);
        $this->drama = Drama::create([
            'title' => 'Test Drama',
            'slug' => 'test-drama',
            'synopsis' => 'Test',
            'category_id' => $category->id,
            'status' => 'published',
            'coin_price' => 10,
            'published_at' => now(),
        ]);
    }

    private function createEpisode(array $overrides = []): Episode
    {
        return Episode::create(array_merge([
            'drama_id' => $this->drama->id,
            'title' => 'Episode 1',
            'slug' => 'test-ep-' . uniqid(),
            'episode_number' => 1,
            'season_number' => 1,
            'duration' => 300,
            'is_free' => false,
            'coin_price' => 10,
            'status' => 'published',
            'video_url' => 'https://example.com/video.mp4',
            'published_at' => now(),
        ], $overrides));
    }

    public function test_can_unlock_episode_with_coins(): void
    {
        $episode = $this->createEpisode();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$episode->id}/unlock");

        $response->assertOk();

        $this->user->refresh();
        $this->assertEquals(90, $this->user->coin_balance);

        $this->assertDatabaseHas('episode_unlocks', [
            'user_id' => $this->user->id,
            'episode_id' => $episode->id,
        ]);
    }

    public function test_cannot_unlock_without_sufficient_coins(): void
    {
        $this->user->update(['coin_balance' => 0]);
        $episode = $this->createEpisode(['coin_price' => 50]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$episode->id}/unlock");

        $response->assertStatus(422);
    }

    public function test_free_episode_does_not_need_unlock(): void
    {
        $episode = $this->createEpisode(['is_free' => true, 'coin_price' => 0]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$episode->id}/unlock");

        $response->assertOk();

        // Balance should not change
        $this->user->refresh();
        $this->assertEquals(100, $this->user->coin_balance);
    }

    public function test_can_update_watch_progress(): void
    {
        $episode = $this->createEpisode(['is_free' => true]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$episode->id}/progress", [
                'progress' => 120,
                'duration' => 300,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('watch_histories', [
            'user_id' => $this->user->id,
            'episode_id' => $episode->id,
        ]);
    }

    public function test_can_get_watch_history(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/watch-history');

        $response->assertOk();
    }

    public function test_can_get_continue_watching(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/continue-watching');

        $response->assertOk();
    }

    public function test_can_clear_watch_history(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/v1/watch-history');

        $response->assertOk();
    }
}
