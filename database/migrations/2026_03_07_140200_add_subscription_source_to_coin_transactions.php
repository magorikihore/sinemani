<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't enforce ENUM — source is stored as TEXT, so no alter needed.
            return;
        }

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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

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
