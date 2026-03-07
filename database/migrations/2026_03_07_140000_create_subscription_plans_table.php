<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Weekly", "Monthly", "Yearly"
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->enum('interval', ['weekly', 'monthly', 'yearly']);
            $table->integer('duration_days');                 // 7, 30, 365
            $table->decimal('price', 10, 2);
            $table->decimal('original_price', 10, 2)->nullable(); // strikethrough price
            $table->string('currency', 3)->default('USD');
            $table->string('store_product_id')->nullable();  // App Store / Google Play product ID
            $table->integer('coin_bonus')->default(0);       // bonus coins on subscribe
            $table->integer('daily_coin_bonus')->default(0); // daily bonus coins for active subs
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('features')->nullable();            // ["All episodes free", "No ads", ...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
