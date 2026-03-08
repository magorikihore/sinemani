<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WatchlistTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Drama $drama;

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
    }

    public function test_can_add_to_watchlist(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/watchlist/{$this->drama->id}");

        $response->assertStatus(201);
        $this->assertDatabaseHas('watchlists', [
            'user_id' => $this->user->id,
            'drama_id' => $this->drama->id,
        ]);
    }

    public function test_can_get_watchlist(): void
    {
        $this->user->watchlist()->attach($this->drama->id);

        $response = $this->actingAs($this->user)->getJson('/api/v1/watchlist');

        $response->assertOk();
    }

    public function test_can_remove_from_watchlist(): void
    {
        $this->user->watchlist()->attach($this->drama->id);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/watchlist/{$this->drama->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('watchlists', [
            'user_id' => $this->user->id,
            'drama_id' => $this->drama->id,
        ]);
    }

    public function test_can_check_watchlist_status(): void
    {
        $this->user->watchlist()->attach($this->drama->id);

        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/watchlist/{$this->drama->id}/check");

        $response->assertOk()
            ->assertJsonPath('data.in_watchlist', true);
    }

    public function test_unauthenticated_cannot_access_watchlist(): void
    {
        $response = $this->getJson('/api/v1/watchlist');

        $response->assertStatus(401);
    }
}
