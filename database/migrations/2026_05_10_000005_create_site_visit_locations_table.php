<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('site_visit_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('base_cost_tzs', 12, 2)->default(150000);
            $table->decimal('preset_travel_tzs', 12, 2)->default(0)->comment('Suggested travel cost to pre-fill form');
            $table->decimal('preset_local_tzs', 12, 2)->default(0)->comment('Suggested local transport');
            $table->decimal('preset_allowance_tzs', 12, 2)->default(0)->comment('Suggested daily allowance');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('site_visit_locations'); }
};
