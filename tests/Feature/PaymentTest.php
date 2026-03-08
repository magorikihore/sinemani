<?php

namespace Tests\Feature;

use App\Models\CoinPackage;
use App\Models\User;
use App\Services\MobilePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create([
            'is_active' => true,
            'coin_balance' => 0,
        ]);
        $this->user->assignRole('user');
    }

    public function test_normalize_phone_from_local(): void
    {
        $service = app(MobilePaymentService::class);
        $this->assertEquals('255712345678', $service->normalizePhone('0712345678'));
    }

    public function test_normalize_phone_from_international(): void
    {
        $service = app(MobilePaymentService::class);
        $this->assertEquals('255712345678', $service->normalizePhone('+255712345678'));
    }

    public function test_normalize_phone_already_normalized(): void
    {
        $service = app(MobilePaymentService::class);
        $this->assertEquals('255712345678', $service->normalizePhone('255712345678'));
    }

    public function test_normalize_phone_invalid(): void
    {
        $service = app(MobilePaymentService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->normalizePhone('12345');
    }

    public function test_payment_status_returns_not_found(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/payments/NON-EXISTENT/status');

        $response->assertStatus(404);
    }

    public function test_payment_history_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/payments/history');

        $response->assertStatus(401);
    }

    public function test_payment_history_returns_empty(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/v1/payments/history');

        $response->assertOk();
    }

    public function test_purchase_coins_validates_input(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/payments/purchase-coins', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['package_id', 'phone']);
    }

    public function test_callback_rejects_invalid_signature(): void
    {
        // Set up app settings to require a signature
        \App\Models\AppSetting::setValue('payment_gateway_api_secret', 'test-secret');
        \App\Models\AppSetting::setValue('payment_gateway_allowed_ips', '192.168.1.1');

        $response = $this->postJson('/api/payments/callback', [
            'reference' => 'PAY-TEST123',
            'status' => 'completed',
        ]);

        $response->assertStatus(403);
    }
}
