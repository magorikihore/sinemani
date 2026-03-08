<?php

namespace Tests\Feature;

use App\Models\CoinPackage;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create([
            'is_active' => true,
            'coin_balance' => 100,
        ]);
        $this->user->assignRole('user');
    }

    public function test_can_get_coin_balance(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/coins/balance');

        $response->assertOk()
            ->assertJsonPath('data.balance', 100);
    }

    public function test_can_get_coin_packages(): void
    {
        CoinPackage::create([
            'name' => 'Starter',
            'coins' => 100,
            'bonus_coins' => 0,
            'price' => 1000,
            'currency' => 'TZS',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/v1/coins/packages');

        $response->assertOk();
    }

    public function test_can_get_coin_transactions(): void
    {
        $coinService = app(CoinService::class);
        $coinService->credit($this->user, 50, 'admin_grant', 'Test credit');

        $response = $this->actingAs($this->user)->getJson('/api/v1/coins/transactions');

        $response->assertOk();
    }

    public function test_can_claim_daily_reward(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/v1/coins/daily-reward');

        // Should succeed on first claim
        $response->assertOk();
    }

    public function test_cannot_claim_daily_reward_twice(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/coins/daily-reward');
        $response = $this->actingAs($this->user)->postJson('/api/v1/coins/daily-reward');

        // Second claim should fail
        $response->assertStatus(422);
    }

    public function test_can_get_daily_reward_info(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/coins/daily-reward/info');

        $response->assertOk();
    }

    public function test_coin_service_credit(): void
    {
        $coinService = app(CoinService::class);
        $coinService->credit($this->user, 50, 'admin_grant', 'Test credit');

        $this->user->refresh();
        $this->assertEquals(150, $this->user->coin_balance);
    }

    public function test_coin_service_debit(): void
    {
        $coinService = app(CoinService::class);
        $coinService->debit($this->user, 30, 'episode_unlock', 'Test debit');

        $this->user->refresh();
        $this->assertEquals(70, $this->user->coin_balance);
    }

    public function test_coin_service_debit_insufficient_balance(): void
    {
        $coinService = app(CoinService::class);

        $this->expectException(\App\Exceptions\InsufficientCoinsException::class);
        $coinService->debit($this->user, 200, 'episode_unlock', 'Should fail');
    }

    public function test_unauthenticated_cannot_access_coins(): void
    {
        $response = $this->getJson('/api/v1/coins/balance');

        $response->assertStatus(401);
    }
}
