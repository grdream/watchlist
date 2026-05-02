<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('slug');
            $table->enum('type', ['drama', 'movie', 'anime', 'tv_series', 'anime_movie', 'other']);
            $table->text('description')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('backdrop_url')->nullable();
            $table->string('tmdb_id')->nullable();
            $table->string('jikan_id')->nullable();
            $table->string('imdb_id')->nullable();
            $table->enum('status', ['watching', 'completed', 'on_hold', 'dropped', 'plan_to_watch'])->default('plan_to_watch');
            $table->string('country')->nullable();
            $table->string('language')->nullable();
            $table->integer('total_episodes')->nullable();
            $table->json('genres')->nullable();
            $table->string('rating')->nullable();
            $table->string('year')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['user_id', 'status']);
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shows');
    }
};
