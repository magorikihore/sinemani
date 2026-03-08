<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create([
            'is_active' => true,
            'password' => 'password123',
        ]);
        $this->user->assignRole('user');
    }

    public function test_can_update_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/profile', [
                'name' => 'Updated Name',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_change_password(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'password123',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertOk();
    }

    public function test_password_change_requires_current_password(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword456',
                'password_confirmation' => 'newpassword456',
            ]);

        $response->assertStatus(422);
    }

    public function test_can_delete_account(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v1/profile', [
                'password' => 'password123',
            ]);

        $response->assertOk();
        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
    }

    public function test_can_update_fcm_token(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/v1/auth/fcm-token', [
                'fcm_token' => 'test-fcm-token-123',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'fcm_token' => 'test-fcm-token-123',
        ]);
    }
}
