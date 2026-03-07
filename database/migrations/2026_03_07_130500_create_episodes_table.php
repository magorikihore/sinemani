<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('drama_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('video_url')->nullable();
            $table->string('video_path')->nullable(); // local storage path
            $table->string('hls_url')->nullable(); // HLS streaming URL
            $table->integer('duration')->default(0); // in seconds
            $table->bigInteger('file_size')->default(0); // in bytes
            $table->string('resolution')->nullable(); // 720p, 1080p, etc
            $table->integer('episode_number');
            $table->integer('season_number')->default(1);
            $table->boolean('is_free')->default(false);
            $table->integer('coin_price')->default(0); // override drama price
            $table->enum('status', ['draft', 'processing', 'published', 'failed'])->default('draft');
            $table->bigInteger('view_count')->default(0);
            $table->bigInteger('like_count')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['drama_id', 'episode_number', 'season_number']);
            $table->index(['drama_id', 'status']);
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
