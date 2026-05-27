<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing CMS — Portfolio.
 *
 * Dedicated marketing/showcase tables, intentionally separate from the
 * operational `projects` table so confidential client/contract data is never
 * exposed through the public (unauthenticated) landing endpoints.
 *
 * Translatable columns are stored as JSON ({"en": "...", "sw": null, ...}) and
 * resolved per-request via a `lang` query param with an English fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_projects', function (Blueprint $table) {
            $table->id();
            $table->json('title');                              // multilingual
            $table->json('category')->nullable();               // multilingual — shown as the pill/badge ("3D Design")
            $table->json('description')->nullable();            // multilingual
            $table->decimal('price_tzs', 18, 2)->nullable();
            $table->decimal('price_usd', 18, 2)->nullable();
            $table->string('youtube_url')->nullable();          // video link (uploaded to YouTube, pasted here)
            $table->string('model_3d_url')->nullable();         // 3D tour link (Sketchfab/Matterport/YouTube)
            $table->unsignedInteger('likes_count')->default(0); // cached count of landing_project_likes
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('landing_project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_project_id')->constrained('landing_projects')->cascadeOnDelete();
            $table->string('file');                             // e.g. /storage/landing/portfolio/x.png
            $table->string('file_name')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('landing_project_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_project_id')->constrained('landing_projects')->cascadeOnDelete();
            $table->json('label');                              // multilingual chip text ("Bedrooms")
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('landing_project_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_project_id')->constrained('landing_projects')->cascadeOnDelete();
            $table->string('device_id');                        // anonymous visitor identity (no auth on landing)
            $table->timestamps();
            $table->unique(['landing_project_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_project_likes');
        Schema::dropIfExists('landing_project_amenities');
        Schema::dropIfExists('landing_project_images');
        Schema::dropIfExists('landing_projects');
    }
};