<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update coin_packages currency default and existing rows
        DB::table('coin_packages')->update(['currency' => 'TZS']);
        DB::statement("ALTER TABLE coin_packages ALTER COLUMN currency SET DEFAULT 'TZS'");

        // Update subscription_plans currency default and existing rows
        DB::table('subscription_plans')->update(['currency' => 'TZS']);

        // Update purchases currency default
        DB::statement("ALTER TABLE purchases ALTER COLUMN currency SET DEFAULT 'TZS'");

        // Update subscriptions currency default
        DB::statement("ALTER TABLE subscriptions ALTER COLUMN currency SET DEFAULT 'TZS'");

        // Update coin_packages prices to TZS equivalents
        // Starter Pack: $0.99 → 2,500 TZS
        DB::table('coin_packages')->where('name', 'Starter Pack')->update(['price' => 2500]);
        // Basic Pack: $4.99 → 12,500 TZS
        DB::table('coin_packages')->where('name', 'Basic Pack')->update(['price' => 12500]);
        // Popular Pack: $9.99 → 25,000 TZS
        DB::table('coin_packages')->where('name', 'Popular Pack')->update(['price' => 25000]);
        // Premium Pack: $19.99 → 50,000 TZS
        DB::table('coin_packages')->where('name', 'Premium Pack')->update(['price' => 50000]);
        // Ultimate Pack: $49.99 → 125,000 TZS
        DB::table('coin_packages')->where('name', 'Ultimate Pack')->update(['price' => 125000]);
        // VIP Pack: $99.99 → 250,000 TZS
        DB::table('coin_packages')->where('name', 'VIP Pack')->update(['price' => 250000]);

        // Update subscription plan prices to TZS
        // Weekly: $2.99 → 7,500 TZS
        DB::table('subscription_plans')->where('slug', 'weekly-pass')->update([
            'price' => 7500,
            'original_price' => null,
            'currency' => 'TZS',
        ]);
        // Monthly: $9.99 → 25,000 TZS
        DB::table('subscription_plans')->where('slug', 'monthly-vip')->update([
            'price' => 25000,
            'original_price' => 30000,
            'currency' => 'TZS',
        ]);
        // Yearly: $79.99 → 200,000 TZS
        DB::table('subscription_plans')->where('slug', 'yearly-premium')->update([
            'price' => 200000,
            'original_price' => 300000,
            'currency' => 'TZS',
        ]);

        // Add payment gateway settings
        DB::table('app_settings')->insertOrIgnore([
            ['key' => 'payment_gateway_url', 'value' => 'https://api.payin.co.tz/api/v1', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_gateway_api_key', 'value' => '', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_gateway_api_secret', 'value' => '', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_callback_url', 'value' => '', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_gateway_timeout', 'value' => '30', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('app_settings')->whereIn('key', [
            'payment_gateway_url', 'payment_gateway_api_key', 'payment_gateway_api_secret',
            'payment_callback_url', 'payment_gateway_timeout',
        ])->delete();
    }
};
