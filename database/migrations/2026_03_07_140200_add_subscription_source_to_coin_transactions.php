<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE coin_transactions MODIFY COLUMN source ENUM(
            'purchase',
            'episode_unlock',
            'daily_reward',
            'referral',
            'admin_grant',
            'admin_deduct',
            'refund',
            'vip_reward',
            'ad_reward',
            'signup_bonus',
            'subscription'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE coin_transactions MODIFY COLUMN source ENUM(
            'purchase',
            'episode_unlock',
            'daily_reward',
            'referral',
            'admin_grant',
            'admin_deduct',
            'refund',
            'vip_reward',
            'ad_reward',
            'signup_bonus'
        ) NOT NULL");
    }
};
