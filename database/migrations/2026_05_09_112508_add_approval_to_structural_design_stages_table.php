<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_structural_design_stages', function (Blueprint $table) {
            // Per-stage approval: engineer submits each stage individually
            // pending / submitted / approved / rejected
            $table->string('approval_status', 20)->default('pending')->after('status');
            $table->timestamp('submitted_at')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('approved_by');
            $table->text('rejection_notes')->nullable()->after('rejected_at');
        });
    }

    public function down(): void
    {
        Schema::table('project_structural_design_stages', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status', 'submitted_at', 'approved_at',
                'approved_by', 'rejected_at', 'rejection_notes',
            ]);
        });
    }
};
