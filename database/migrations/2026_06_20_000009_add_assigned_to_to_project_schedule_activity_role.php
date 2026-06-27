<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allow each responsible role on a schedule activity to have its own assigned
 * user (Architect→John, Civil Engineer→Mary, …). The activity-level assigned_to
 * stays as the overall/primary assignee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_schedule_activity_role', function (Blueprint $table) {
            if (!Schema::hasColumn('project_schedule_activity_role', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('role_id');
                $table->foreign('assigned_to')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_schedule_activity_role', function (Blueprint $table) {
            if (Schema::hasColumn('project_schedule_activity_role', 'assigned_to')) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            }
        });
    }
};
