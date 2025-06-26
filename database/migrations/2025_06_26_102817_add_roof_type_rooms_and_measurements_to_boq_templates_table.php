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
        Schema::table('boq_templates', function (Blueprint $table) {
            $table->enum('roof_type', ['pitched_roof', 'hidden_roof', 'concrete_roof'])->nullable()->after('building_type_id');
            $table->enum('no_of_rooms', ['1', '2', '3', '4', '5+'])->nullable()->after('roof_type');
            $table->decimal('square_metre', 10, 2)->nullable()->after('no_of_rooms');
            $table->decimal('run_metre', 10, 2)->nullable()->after('square_metre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boq_templates', function (Blueprint $table) {
            $table->dropColumn(['roof_type', 'no_of_rooms', 'square_metre', 'run_metre']);
        });
    }
};
