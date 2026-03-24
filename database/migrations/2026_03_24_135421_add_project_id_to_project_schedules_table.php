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
        Schema::table('project_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')->nullable()->after('lead_id');
            $table->index('project_id');
        });

        // Make lead_id nullable so schedules can be created from projects directly
        Schema::table('project_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable()->change();
        });

        // Also make lead_id nullable in project_assignments for project-based schedules
        Schema::table('project_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('lead_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_schedules', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
