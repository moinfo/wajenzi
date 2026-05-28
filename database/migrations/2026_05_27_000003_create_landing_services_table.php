<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Landing CMS — Services. Translatable text stored as JSON (en-first, resolved
 * via ?lang=). `features` is a JSON array of (English-first) chip strings.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_services', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('short_description')->nullable();
            $table->json('full_description')->nullable();
            $table->string('image')->nullable();          // /storage/landing/services/x.png
            $table->json('features')->nullable();          // ["Cost Estimation", ...]
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_services');
    }
};
