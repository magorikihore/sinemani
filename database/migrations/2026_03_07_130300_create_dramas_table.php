<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dramas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('trailer_url')->nullable();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['draft', 'published', 'completed', 'suspended'])->default('draft');
            $table->enum('content_rating', ['G', 'PG', 'PG-13', 'R', 'NC-17'])->default('PG-13');
            $table->string('language')->default('en');
            $table->string('country')->nullable();
            $table->integer('release_year')->nullable();
            $table->string('director')->nullable();
            $table->json('cast')->nullable();
            $table->integer('total_episodes')->default(0);
            $table->integer('published_episodes')->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->bigInteger('like_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_new_release')->default(false);
            $table->boolean('is_free')->default(false);
            $table->integer('coin_price')->default(0); // price per episode in coins
            $table->integer('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'is_featured']);
            $table->index(['status', 'is_trending']);
            $table->index('view_count');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dramas');
    }
};
