<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $type = 'App\\Models\\ImprestRequest';

        $orphanStatusIds = DB::table('process_approval_statuses as s')
            ->leftJoin('imprest_requests as i', 'i.id', '=', 's.approvable_id')
            ->where('s.approvable_type', $type)
            ->whereNull('i.id')
            ->pluck('s.id')
            ->all();

        $orphanApprovalIds = DB::table('process_approvals as a')
            ->leftJoin('imprest_requests as i', 'i.id', '=', 'a.approvable_id')
            ->where('a.approvable_type', $type)
            ->whereNull('i.id')
            ->pluck('a.id')
            ->all();

        $staleStatusIds = DB::table('process_approval_statuses as s')
            ->join('imprest_requests as i', 'i.id', '=', 's.approvable_id')
            ->where('s.approvable_type', $type)
            ->whereColumn('s.created_at', '<', 'i.created_at')
            ->pluck('s.id')
            ->all();

        $staleApprovalIds = DB::table('process_approvals as a')
            ->join('imprest_requests as i', 'i.id', '=', 'a.approvable_id')
            ->where('a.approvable_type', $type)
            ->whereColumn('a.created_at', '<', 'i.created_at')
            ->pluck('a.id')
            ->all();

        $statusIdsToDelete   = array_unique(array_merge($orphanStatusIds, $staleStatusIds));
        $approvalIdsToDelete = array_unique(array_merge($orphanApprovalIds, $staleApprovalIds));

        if (!empty($approvalIdsToDelete)) {
            DB::table('process_approvals')->whereIn('id', $approvalIdsToDelete)->delete();
        }

        if (!empty($statusIdsToDelete)) {
            DB::table('process_approval_statuses')->whereIn('id', $statusIdsToDelete)->delete();
        }
    }

    public function down(): void
    {
        // Cleanup of orphaned/stale approval rows is irreversible by design.
    }
};
