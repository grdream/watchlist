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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained()->onDelete('cascade');
            $table->integer('season_no')->nullable();
            $table->integer('episode_no');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->dateTime('air_datetime');
            $table->integer('duration_minutes')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('youtube_link')->nullable();
            $table->boolean('is_aired')->default(false);
            $table->boolean('notified')->default(false);
            $table->timestamps();
            
            $table->index(['show_id', 'is_aired']);
            $table->index(['show_id', 'notified']);
            $table->index('air_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
