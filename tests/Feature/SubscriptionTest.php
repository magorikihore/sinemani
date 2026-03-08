<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionTest extends TestCase
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

    private function createPlan(array $overrides = []): SubscriptionPlan
    {
        return SubscriptionPlan::create(array_merge([
            'name' => 'Monthly VIP',
            'slug' => 'monthly-vip',
            'interval' => 'monthly',
            'duration_days' => 30,
            'price' => 10000,
            'currency' => 'TZS',
            'coin_bonus' => 50,
            'is_active' => true,
        ], $overrides));
    }

    public function test_can_get_subscription_plans(): void
    {
        $this->createPlan();

        $response = $this->actingAs($this->user)->getJson('/api/v1/subscriptions/plans');

        $response->assertOk();
    }

    public function test_can_get_current_subscription(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/subscriptions/current');

        $response->assertOk();
    }

    public function test_subscription_service_creates_subscription(): void
    {
        $plan = $this->createPlan();
        $service = app(SubscriptionService::class);

        $subscription = $service->subscribe($this->user, $plan, 'manual');

        $this->user->refresh();
        $this->assertTrue($this->user->is_vip);
        $this->assertNotNull($this->user->vip_expires_at);
        $this->assertEquals('active', $subscription->status);
    }

    public function test_subscription_grants_bonus_coins(): void
    {
        $plan = $this->createPlan(['coin_bonus' => 100]);
        $service = app(SubscriptionService::class);

        $initialBalance = $this->user->coin_balance;
        $service->subscribe($this->user, $plan, 'manual');

        $this->user->refresh();
        $this->assertEquals($initialBalance + 100, $this->user->coin_balance);
    }

    public function test_subscription_can_be_cancelled(): void
    {
        $plan = $this->createPlan();
        $service = app(SubscriptionService::class);

        $subscription = $service->subscribe($this->user, $plan, 'manual');
        $service->cancel($subscription, 'Testing');

        $subscription->refresh();
        $this->assertEquals('cancelled', $subscription->status);
        $this->assertFalse($subscription->auto_renew);
    }

    public function test_subscription_extends_from_existing(): void
    {
        $plan = $this->createPlan(['duration_days' => 30]);
        $service = app(SubscriptionService::class);

        $first = $service->subscribe($this->user, $plan, 'manual');
        $second = $service->subscribe($this->user, $plan, 'manual');

        // Second subscription should start from end of first
        $this->assertTrue($second->starts_at->gte($first->ends_at->subSecond()));
    }

    public function test_expire_overdue_subscriptions(): void
    {
        $plan = $this->createPlan();
        $service = app(SubscriptionService::class);

        $subscription = $service->subscribe($this->user, $plan, 'manual');

        // Force expiry
        $subscription->update([
            'ends_at' => now()->subDay(),
            'auto_renew' => false,
        ]);

        $expired = $service->expireOverdueSubscriptions();
        $this->assertEquals(1, $expired);
    }
}
