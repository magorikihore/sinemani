<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();              // internal ref e.g. PAY-XXXX
            $table->string('phone', 15);                        // 255XXXXXXXXX
            $table->enum('operator', ['mpesa', 'tigopesa', 'airtelmoney', 'halopesa']);
            $table->decimal('amount', 15, 2);                   // TZS amount
            $table->string('currency', 3)->default('TZS');

            // What this payment is for
            $table->enum('payment_type', ['coin_purchase', 'subscription']);
            $table->unsignedBigInteger('payable_id')->nullable();   // coin_package_id or subscription_plan_id
            $table->string('payable_type')->nullable();             // model class

            // Gateway tracking
            $table->string('gateway_reference')->nullable();        // payin.co.tz reference
            $table->string('gateway_transaction_id')->nullable();   // operator transaction ID
            $table->enum('status', [
                'pending',      // USSD push sent, waiting for user
                'processing',   // user confirmed, processing
                'completed',    // payment successful
                'failed',       // payment failed
                'cancelled',    // user cancelled USSD
                'expired',      // timed out
            ])->default('pending');

            $table->string('failure_reason')->nullable();
            $table->json('gateway_response')->nullable();           // raw callback data
            $table->json('push_response')->nullable();              // raw push response

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['reference']);
            $table->index(['gateway_reference']);
            $table->index(['phone', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_payments');
    }
};
