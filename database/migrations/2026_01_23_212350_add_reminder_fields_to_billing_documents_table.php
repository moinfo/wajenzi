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
        Schema::table('billing_documents', function (Blueprint $table) {
            // Add fields for invoice reminder tracking
            if (!Schema::hasColumn('billing_documents', 'reminder_count')) {
                $table->integer('reminder_count')->default(0)->after('last_reminder_sent_at');
            }
            if (!Schema::hasColumn('billing_documents', 'original_due_date')) {
                $table->date('original_due_date')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('billing_documents', 'rescheduled_at')) {
                $table->timestamp('rescheduled_at')->nullable()->after('original_due_date');
            }
            if (!Schema::hasColumn('billing_documents', 'rescheduled_by')) {
                $table->unsignedBigInteger('rescheduled_by')->nullable()->after('rescheduled_at');
            }
            if (!Schema::hasColumn('billing_documents', 'reschedule_reason')) {
                $table->text('reschedule_reason')->nullable()->after('rescheduled_by');
            }
            if (!Schema::hasColumn('billing_documents', 'attended_at')) {
                $table->timestamp('attended_at')->nullable()->after('reschedule_reason');
            }
            if (!Schema::hasColumn('billing_documents', 'attended_by')) {
                $table->unsignedBigInteger('attended_by')->nullable()->after('attended_at');
            }
            if (!Schema::hasColumn('billing_documents', 'attendance_notes')) {
                $table->text('attendance_notes')->nullable()->after('attended_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_documents', function (Blueprint $table) {
            $columns = [
                'reminder_count',
                'original_due_date',
                'rescheduled_at',
                'rescheduled_by',
                'reschedule_reason',
                'attended_at',
                'attended_by',
                'attendance_notes'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('billing_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
