<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow a schedule activity to be the responsibility of several roles
 * (e.g. Survey → Architect + Civil Engineer + QS). project_schedule_activities.role_id
 * is kept as the primary role; this pivot holds the full set used for visibility/badges.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('project_schedule_activity_role')) {
            Schema::create('project_schedule_activity_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('activity_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();

                $table->unique(['activity_id', 'role_id']);
                $table->foreign('activity_id')->references('id')->on('project_schedule_activities')->cascadeOnDelete();
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        }

        // Backfill: each activity's existing primary role becomes a responsible role.
        DB::statement("
            INSERT IGNORE INTO project_schedule_activity_role (activity_id, role_id, created_at, updated_at)
            SELECT id, role_id, NOW(), NOW()
            FROM project_schedule_activities
            WHERE role_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('project_schedule_activity_role');
    }
};
