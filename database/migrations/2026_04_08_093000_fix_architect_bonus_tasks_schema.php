<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('architect_bonus_tasks')) {
            return;
        }

        DB::statement('
            CREATE TABLE architect_bonus_tasks_repaired (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                task_number VARCHAR(255) NOT NULL UNIQUE,
                project_name VARCHAR(255) NOT NULL,
                architect_id BIGINT UNSIGNED NOT NULL,
                project_budget DECIMAL(15,2) NOT NULL,
                lead_id BIGINT UNSIGNED NULL,
                project_schedule_id BIGINT UNSIGNED NULL,
                start_date DATE NOT NULL,
                scheduled_completion_date DATE NOT NULL,
                actual_completion_date DATE NULL,
                max_units INT NOT NULL,
                design_quality_score DECIMAL(3,2) NULL,
                client_revisions INT NULL,
                schedule_performance DECIMAL(4,3) NULL,
                client_approval_efficiency DECIMAL(4,3) NULL,
                performance_score DECIMAL(4,3) NULL,
                final_units DECIMAL(6,2) NULL,
                bonus_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                status ENUM(\'pending\', \'in_progress\', \'completed\', \'scored\', \'paid\', \'no_bonus\') NOT NULL DEFAULT \'pending\',
                notes TEXT NULL,
                created_by BIGINT UNSIGNED NOT NULL,
                scored_by BIGINT UNSIGNED NULL,
                scored_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL
            )
        ');

        DB::statement('
            INSERT INTO architect_bonus_tasks_repaired (
                task_number,
                project_name,
                architect_id,
                project_budget,
                lead_id,
                project_schedule_id,
                start_date,
                scheduled_completion_date,
                actual_completion_date,
                max_units,
                design_quality_score,
                client_revisions,
                schedule_performance,
                client_approval_efficiency,
                performance_score,
                final_units,
                bonus_amount,
                status,
                notes,
                created_by,
                scored_by,
                scored_at,
                created_at,
                updated_at
            )
            SELECT
                task_number,
                project_name,
                architect_id,
                project_budget,
                lead_id,
                project_schedule_id,
                start_date,
                scheduled_completion_date,
                actual_completion_date,
                max_units,
                design_quality_score,
                client_revisions,
                schedule_performance,
                client_approval_efficiency,
                performance_score,
                final_units,
                bonus_amount,
                status,
                notes,
                created_by,
                scored_by,
                scored_at,
                created_at,
                updated_at
            FROM architect_bonus_tasks
            ORDER BY
                CASE WHEN id IS NULL OR id = 0 THEN 1 ELSE 0 END,
                id,
                created_at,
                updated_at
        ');

        Schema::drop('architect_bonus_tasks');
        Schema::rename('architect_bonus_tasks_repaired', 'architect_bonus_tasks');
    }

    public function down(): void
    {
        // Intentionally left empty because the previous schema was invalid.
    }
};
