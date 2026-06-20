<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ProjectSiteVisit no longer uses the RingleSoft Approvable mechanism (it is
 * driven by the new `stage` state machine instead). This removes the now-orphaned
 * approval rows for the model so they cannot "adopt" future visits that reuse an id.
 * The legacy approval_levels config rows (document type 11) are intentionally left
 * untouched — only the UI stops reading them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $type = 'App\\Models\\ProjectSiteVisit';

        if (Schema::hasTable('process_approvals')) {
            DB::table('process_approvals')->where('approvable_type', $type)->delete();
        }

        if (Schema::hasTable('process_approval_statuses')) {
            DB::table('process_approval_statuses')->where('approvable_type', $type)->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: the approval history cannot be reconstructed.
    }
};
