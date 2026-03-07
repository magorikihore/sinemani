<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coin_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_id')->unique();
            $table->string('payment_provider'); // stripe, apple, google
            $table->string('provider_transaction_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');
            $table->integer('coins_granted')->default(0);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('receipt_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('provider_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
