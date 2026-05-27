<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing CMS — Posters (home-screen promotional banners). Image is uploaded;
 * an optional tap target is a link URL or a YouTube URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_posters', function (Blueprint $table) {
            $table->id();
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->string('image');                       // /storage/landing/posters/x.png
            $table->string('link_url')->nullable();        // optional tap target
            $table->string('youtube_url')->nullable();     // optional video
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_posters');
    }
};
