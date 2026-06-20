<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns project_site_visits from a single-approval document into a 6-stage
 * workflow (initiation → billing → assignment → confirmation → reporting →
 * integration → completed). The new `stage` column drives the state machine;
 * the legacy `status` enum is left intact for backward-compat and is the
 * source for the one-time backfill below.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            if (!Schema::hasColumn('project_site_visits', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('id');
            }
            if (!Schema::hasColumn('project_site_visits', 'stage')) {
                $table->string('stage', 30)->default('initiation')->index()->after('status');
            }
            if (!Schema::hasColumn('project_site_visits', 'phone_number')) {
                $table->string('phone_number', 40)->nullable()->after('description');
            }

            // Stage 2 — Billing & Invoice (lightweight, stored on the visit)
            if (!Schema::hasColumn('project_site_visits', 'invoice_amount')) {
                $table->decimal('invoice_amount', 15, 2)->nullable();
                $table->string('invoice_number')->nullable();
                $table->unsignedBigInteger('billed_by')->nullable();
                $table->timestamp('payment_confirmed_at')->nullable();
                $table->unsignedBigInteger('payment_confirmed_by')->nullable();
            }

            // Stage 3 — Assignment
            if (!Schema::hasColumn('project_site_visits', 'architect_id')) {
                $table->unsignedBigInteger('architect_id')->nullable();
                $table->unsignedBigInteger('site_engineer_id')->nullable();   // Civil Engineer role
                $table->unsignedBigInteger('site_supervisor_id')->nullable();
                $table->unsignedBigInteger('assigned_by')->nullable();
                $table->timestamp('assigned_at')->nullable();
            }

            // Stage 4 — Confirmation & Scheduling
            if (!Schema::hasColumn('project_site_visits', 'team_confirmed_at')) {
                $table->timestamp('team_confirmed_at')->nullable();
                $table->unsignedBigInteger('team_confirmed_by')->nullable();
            }

            // Stage 5 — Uploading & Reporting
            if (!Schema::hasColumn('project_site_visits', 'report_path')) {
                $table->string('report_path')->nullable();
                $table->string('report_name')->nullable();
                $table->text('report_notes')->nullable();
                $table->timestamp('report_uploaded_at')->nullable();
                $table->unsignedBigInteger('report_uploaded_by')->nullable();
            }

            // Stage 6 — Integration with Project Schedule (Survey Stage)
            if (!Schema::hasColumn('project_site_visits', 'schedule_activity_id')) {
                $table->unsignedBigInteger('schedule_activity_id')->nullable();
                $table->unsignedBigInteger('schedule_attachment_id')->nullable();
                $table->timestamp('integrated_at')->nullable();
            }

            // Cancel path
            if (!Schema::hasColumn('project_site_visits', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->text('cancel_reason')->nullable();
            }
        });

        // Backfill stage from the legacy status enum for existing rows.
        DB::statement("UPDATE project_site_visits SET stage = CASE
            WHEN status IN ('CREATED', 'PENDING') THEN 'initiation'
            WHEN status IN ('APPROVED', 'PAID', 'COMPLETED') THEN 'completed'
            WHEN status = 'REJECTED' THEN 'cancelled'
            ELSE 'initiation' END
            WHERE stage IS NULL OR stage = 'initiation'");

        // Give existing rows a reference number (SV-{year}-{padded id}).
        DB::statement("UPDATE project_site_visits
            SET reference_number = CONCAT('SV-', YEAR(COALESCE(created_at, NOW())), '-', LPAD(id, 4, '0'))
            WHERE reference_number IS NULL OR reference_number = ''");

        // Copy client phone numbers where the visit is linked to a client.
        DB::statement("UPDATE project_site_visits v
            JOIN project_clients c ON c.id = v.client_id
            SET v.phone_number = c.phone_number
            WHERE (v.phone_number IS NULL OR v.phone_number = '') AND c.phone_number IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('project_site_visits', function (Blueprint $table) {
            foreach ([
                'reference_number', 'stage', 'phone_number',
                'invoice_amount', 'invoice_number', 'billed_by', 'payment_confirmed_at', 'payment_confirmed_by',
                'architect_id', 'site_engineer_id', 'site_supervisor_id', 'assigned_by', 'assigned_at',
                'team_confirmed_at', 'team_confirmed_by',
                'report_path', 'report_name', 'report_notes', 'report_uploaded_at', 'report_uploaded_by',
                'schedule_activity_id', 'schedule_attachment_id', 'integrated_at',
                'cancelled_at', 'cancelled_by', 'cancel_reason',
            ] as $column) {
                if (Schema::hasColumn('project_site_visits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
