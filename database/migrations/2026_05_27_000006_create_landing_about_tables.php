<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing CMS — About. Three tables backing the mobile "About" screen:
 *   - landing_about        : singleton row (founded year, story, mission, vision, contact)
 *   - landing_values       : the company core values list
 *   - landing_team_members : the leadership team list
 * All translatable text is stored as JSON (en-first, resolved via ?lang=).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_about', function (Blueprint $table) {
            $table->id();
            $table->string('founded_year')->nullable();
            $table->json('tagline')->nullable();
            $table->json('story')->nullable();
            $table->json('mission')->nullable();
            $table->json('vision')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('working_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('landing_values', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('landing_team_members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('role');
            $table->json('bio')->nullable();
            $table->string('image')->nullable();          // /storage/landing/team/x.png
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_team_members');
        Schema::dropIfExists('landing_values');
        Schema::dropIfExists('landing_about');
    }
};
