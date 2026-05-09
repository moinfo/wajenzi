<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_structural_designs', function (Blueprint $table) {
            $table->text('schedule_description')->nullable()->after('notes');
            $table->date('schedule_planned_start')->nullable()->after('schedule_description');
            $table->date('schedule_planned_end')->nullable()->after('schedule_planned_start');
            // not_submitted / submitted / approved / rejected
            $table->string('schedule_status', 20)->default('not_submitted')->after('schedule_planned_end');
            $table->timestamp('schedule_submitted_at')->nullable()->after('schedule_status');
            $table->timestamp('schedule_approved_at')->nullable()->after('schedule_submitted_at');
            $table->unsignedBigInteger('schedule_approved_by')->nullable()->after('schedule_approved_at');
            $table->text('schedule_rejection_notes')->nullable()->after('schedule_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('project_structural_designs', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_description', 'schedule_planned_start', 'schedule_planned_end',
                'schedule_status', 'schedule_submitted_at', 'schedule_approved_at',
                'schedule_approved_by', 'schedule_rejection_notes',
            ]);
        });
    }
};
