<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episode_subtitles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('episode_id')->constrained()->cascadeOnDelete();
            $table->string('language', 10); // e.g. en, sw, fr
            $table->string('label')->nullable(); // display label, e.g. "English", "Kiswahili"
            $table->string('file_path'); // storage path on public disk
            $table->string('format', 8)->default('srt'); // srt | vtt
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['episode_id', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_subtitles');
    }
};
