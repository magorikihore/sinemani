<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('phone')->unique()->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('avatar');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->text('bio')->nullable()->after('date_of_birth');
            $table->string('provider')->nullable()->after('bio'); // google, facebook, apple
            $table->string('provider_id')->nullable()->after('provider');
            $table->string('provider_token')->nullable()->after('provider_id');
            $table->integer('coin_balance')->default(0)->after('provider_token');
            $table->string('language')->default('en')->after('coin_balance');
            $table->string('country')->nullable()->after('language');
            $table->boolean('is_active')->default(true)->after('country');
            $table->boolean('is_vip')->default(false)->after('is_active');
            $table->timestamp('vip_expires_at')->nullable()->after('is_vip');
            $table->string('fcm_token')->nullable()->after('vip_expires_at');
            $table->timestamp('last_login_at')->nullable()->after('fcm_token');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username', 'phone', 'avatar', 'gender', 'date_of_birth', 'bio',
                'provider', 'provider_id', 'provider_token', 'coin_balance',
                'language', 'country', 'is_active', 'is_vip', 'vip_expires_at',
                'fcm_token', 'last_login_at',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
