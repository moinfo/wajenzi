<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Activity templates can carry several responsible roles, mirroring schedule
 * activities. project_activity_templates.role_id stays as the primary role; this
 * pivot holds the full set that generated activities inherit.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_activity_template_role')) {
            Schema::create('project_activity_template_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('template_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();

                $table->unique(['template_id', 'role_id']);
                $table->foreign('template_id')->references('id')->on('project_activity_templates')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        }

        // Backfill the existing single role as a responsible role.
        DB::statement("
            INSERT IGNORE INTO project_activity_template_role (template_id, role_id, created_at, updated_at)
            SELECT id, role_id, NOW(), NOW()
            FROM project_activity_templates
            WHERE role_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('project_activity_template_role');
    }
};
