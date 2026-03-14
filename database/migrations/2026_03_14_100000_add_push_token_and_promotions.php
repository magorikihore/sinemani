<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add expo push token to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('expo_push_token')->nullable()->after('fcm_token');
        });

        // Promotions / offer popups
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('action_type'); // subscription, coin_store, drama, url, daily_reward
            $table->string('action_value')->nullable(); // plan_id, drama_id, url, etc.
            $table->string('button_text')->default('Check it out');
            $table->string('position')->default('popup'); // popup, banner
            $table->boolean('is_active')->default(true);
            $table->boolean('show_once_per_day')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'position', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('expo_push_token');
        });

        Schema::dropIfExists('promotions');
    }
};
