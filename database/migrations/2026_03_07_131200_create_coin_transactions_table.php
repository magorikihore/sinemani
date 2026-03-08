<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique(); // unique transaction reference
            $table->enum('type', ['credit', 'debit']);
            $table->integer('amount'); // positive integer
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->enum('source', [
                'purchase',       // bought coins
                'episode_unlock', // spent on episode
                'daily_reward',   // free daily coins
                'referral',       // referral bonus
                'admin_grant',    // admin gave coins
                'admin_deduct',   // admin removed coins
                'refund',         // refund
                'vip_reward',     // VIP daily bonus
                'ad_reward',      // watched ad
                'signup_bonus',   // new user bonus
                'subscription',   // subscription bonus
            ]);
            $table->string('description')->nullable();
            $table->morphs('transactionable'); // polymorphic relation
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
