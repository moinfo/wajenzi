<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labor_requests', function (Blueprint $table) {
            $table->dropForeign(['construction_phase_id']);
            $table->foreign('construction_phase_id')->references('id')->on('project_boq_sections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('labor_requests', function (Blueprint $table) {
            $table->dropForeign(['construction_phase_id']);
            $table->foreign('construction_phase_id')->references('id')->on('project_construction_phases')->nullOnDelete();
        });
    }
};
