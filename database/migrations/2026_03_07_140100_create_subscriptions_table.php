<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->cascadeOnDelete();
            $table->string('order_id')->unique()->nullable();       // payment gateway order
            $table->string('transaction_id')->nullable();           // payment gateway txn
            $table->string('payment_provider')->nullable();         // stripe, apple, google
            $table->string('store_transaction_id')->nullable();     // App Store / Play Store txn
            $table->decimal('amount_paid', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', [
                'pending',       // payment initiated
                'active',        // currently valid
                'expired',       // past end_date
                'cancelled',     // user cancelled (still active until end_date)
                'refunded',      // payment refunded
            ])->default('pending');
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('renewed_at')->nullable();            // last renewal timestamp
            $table->string('cancellation_reason')->nullable();
            $table->json('payment_meta')->nullable();               // raw gateway response
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('ends_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
