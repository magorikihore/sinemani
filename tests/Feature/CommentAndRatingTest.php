<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentAndRatingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Drama $drama;
    protected Episode $episode;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('user');

        $category = Category::create(['name' => 'Drama', 'slug' => 'drama', 'is_active' => true]);
        $this->drama = Drama::create([
            'title' => 'Test Drama',
            'slug' => 'test-drama',
            'synopsis' => 'Test',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $this->episode = Episode::create([
            'drama_id' => $this->drama->id,
            'title' => 'Episode 1',
            'slug' => 'test-ep-1',
            'episode_number' => 1,
            'season_number' => 1,
            'duration' => 300,
            'is_free' => true,
            'coin_price' => 0,
            'status' => 'published',
            'video_url' => 'https://example.com/video.mp4',
            'published_at' => now(),
        ]);
    }

    public function test_can_post_comment(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$this->episode->id}/comments", [
                'body' => 'Great episode!',
            ]);

        $response->assertStatus(201);
    }

    public function test_can_get_comments(): void
    {
        $response = $this->getJson("/api/v1/episodes/{$this->episode->id}/comments");

        $response->assertOk();
    }

    public function test_comment_requires_body(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$this->episode->id}/comments", []);

        $response->assertStatus(422);
    }

    public function test_can_rate_drama(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/dramas/{$this->drama->id}/rate", [
                'score' => 5,
            ]);

        $response->assertOk();
    }

    public function test_rating_must_be_valid(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/dramas/{$this->drama->id}/rate", [
                'score' => 10,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_get_ratings(): void
    {
        $response = $this->getJson("/api/v1/dramas/{$this->drama->id}/ratings");

        $response->assertOk();
    }

    public function test_can_toggle_drama_like(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/dramas/{$this->drama->id}/like");

        $response->assertOk();
    }

    public function test_can_toggle_episode_like(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/episodes/{$this->episode->id}/like");

        $response->assertOk();
    }
}
