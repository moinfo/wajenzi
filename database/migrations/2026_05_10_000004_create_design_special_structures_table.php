<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('design_special_structures', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate_tzs_per_sqm', 12, 2);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('design_special_structures'); }
};
