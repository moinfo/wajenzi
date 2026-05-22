<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track whether an architect_bonus_task is being auto-synced from a project_schedule.
 *
 *   auto_synced     — when true, the BonusScheduleSyncService keeps this task's
 *                     dates/revisions in sync as the linked schedule progresses.
 *                     Cleared if an admin scores/edits the task manually.
 *   last_synced_at  — diagnostic timestamp of the last sync run.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('architect_bonus_tasks', function (Blueprint $table) {
            $table->boolean('auto_synced')->default(false)->after('project_schedule_id');
            $table->timestamp('last_synced_at')->nullable()->after('auto_synced');
        });
    }

    public function down(): void
    {
        Schema::table('architect_bonus_tasks', function (Blueprint $table) {
            $table->dropColumn(['auto_synced', 'last_synced_at']);
        });
    }
};
