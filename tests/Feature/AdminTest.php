<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Drama;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create(['is_active' => true, 'coin_balance' => 50]);
        $this->regularUser->assignRole('user');
    }

    public function test_admin_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/dashboard');

        $response->assertOk();
    }

    public function test_regular_user_cannot_access_dashboard(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_users(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/users');

        $response->assertOk();
    }

    public function test_admin_can_view_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/users/{$this->regularUser->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $this->regularUser->id);
    }

    public function test_admin_can_update_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/admin/users/{$this->regularUser->id}", [
                'is_active' => false,
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->regularUser->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_grant_coins(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$this->regularUser->id}/grant-coins", [
                'amount' => 100,
                'reason' => 'Test grant',
            ]);

        $response->assertOk();
        $this->regularUser->refresh();
        $this->assertEquals(150, $this->regularUser->coin_balance);
    }

    public function test_admin_can_deduct_coins(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/users/{$this->regularUser->id}/deduct-coins", [
                'amount' => 30,
                'reason' => 'Test deduction',
            ]);

        $response->assertOk();
        $this->regularUser->refresh();
        $this->assertEquals(20, $this->regularUser->coin_balance);
    }

    public function test_admin_can_create_drama(): void
    {
        $category = Category::create(['name' => 'Action', 'slug' => 'action', 'is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/dramas', [
                'title' => 'New Admin Drama',
                'synopsis' => 'A drama created by admin.',
                'category_id' => $category->id,
                'status' => 'draft',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('dramas', ['title' => 'New Admin Drama']);
    }

    public function test_admin_can_list_dramas(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/dramas');

        $response->assertOk();
    }

    public function test_admin_can_manage_categories(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/categories', [
                'name' => 'New Category',
                'slug' => 'new-category',
            ]);

        $response->assertStatus(201);
    }

    public function test_admin_can_manage_tags(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/admin/tags', [
                'name' => 'New Tag',
                'slug' => 'new-tag',
            ]);

        $response->assertStatus(201);
    }

    public function test_unauthenticated_cannot_access_admin(): void
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }
}
