<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing CMS — Awards. Translatable text stored as JSON (en-first, resolved
 * per-request via ?lang=). Single image per award.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_awards', function (Blueprint $table) {
            $table->id();
            $table->string('year')->nullable();
            $table->json('title');
            $table->json('subtitle')->nullable();
            $table->json('organization')->nullable();
            $table->json('description')->nullable();
            $table->string('image')->nullable();          // /storage/landing/awards/x.jpg
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_awards');
    }
};
