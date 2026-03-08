<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Drama;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('user');
    }

    public function test_can_get_notifications(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications');

        $response->assertOk();
    }

    public function test_can_get_unread_count(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/notifications/unread-count');

        $response->assertOk();
    }

    public function test_can_mark_all_as_read(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/v1/notifications/read-all');

        $response->assertOk();
    }

    public function test_can_submit_report(): void
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);
        $drama = Drama::create([
            'title' => 'Reported Drama',
            'slug' => 'reported-drama',
            'synopsis' => 'Test',
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/v1/reports', [
            'reportable_type' => 'drama',
            'reportable_id' => $drama->id,
            'reason' => 'inappropriate',
            'description' => 'This content contains inappropriate material.',
        ]);

        $response->assertStatus(201)->assertJsonPath('success', true);
    }

    public function test_notifications_require_auth(): void
    {
        $response = $this->getJson('/api/v1/notifications');

        $response->assertStatus(401);
    }
}
