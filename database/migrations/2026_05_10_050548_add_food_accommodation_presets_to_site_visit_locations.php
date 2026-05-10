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
        Schema::table('site_visit_locations', function (Blueprint $table) {
            $table->decimal('preset_food_tzs', 12, 2)->default(0)->after('preset_allowance_tzs');
            $table->decimal('preset_accommodation_tzs', 12, 2)->default(0)->after('preset_food_tzs');
        });
    }

    public function down(): void
    {
        Schema::table('site_visit_locations', function (Blueprint $table) {
            $table->dropColumn(['preset_food_tzs', 'preset_accommodation_tzs']);
        });
    }
};
