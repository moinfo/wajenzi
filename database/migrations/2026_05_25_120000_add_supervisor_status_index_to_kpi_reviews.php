<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Speed up "awaiting me" scope in KpiController::scopeAwaitingFor, which filters
 * by (status='self_submitted' AND supervisor_id=?) for the supervisor branch.
 * Without this composite, the query falls back to the single-column status index
 * and post-filters on supervisor_id.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('kpi_reviews', function (Blueprint $table) {
            $table->index(['supervisor_id', 'status'], 'kpi_reviews_supervisor_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_reviews', function (Blueprint $table) {
            $table->dropIndex('kpi_reviews_supervisor_status_idx');
        });
    }
};
