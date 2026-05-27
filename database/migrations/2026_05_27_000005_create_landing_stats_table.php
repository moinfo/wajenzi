<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing CMS — Stats (hero metrics: "120+ Flagship Projects", etc.).
 * `value` is a free-text string ("120+", "4.9"); `label` is multilingual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_stats', function (Blueprint $table) {
            $table->id();
            $table->string('value');                       // "120+", "4.9"
            $table->json('label');                          // multilingual
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_stats');
    }
};
