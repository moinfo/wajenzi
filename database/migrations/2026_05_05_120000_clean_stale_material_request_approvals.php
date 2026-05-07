<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $type = 'App\\Models\\ProjectMaterialRequest';

        // Orphans: rows whose target request no longer exists.
        DB::table('process_approval_statuses')
            ->where('approvable_type', $type)
            ->whereNotIn('approvable_id', function ($q) {
                $q->select('id')->from('project_material_requests');
            })
            ->delete();

        DB::table('process_approvals')
            ->where('approvable_type', $type)
            ->whereNotIn('approvable_id', function ($q) {
                $q->select('id')->from('project_material_requests');
            })
            ->delete();

        // Duplicates per approvable_id: keep the newest row (highest id) only.
        $duplicates = DB::table('process_approval_statuses')
            ->where('approvable_type', $type)
            ->select('approvable_id')
            ->groupBy('approvable_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('approvable_id');

        foreach ($duplicates as $approvableId) {
            $keepId = DB::table('process_approval_statuses')
                ->where('approvable_type', $type)
                ->where('approvable_id', $approvableId)
                ->max('id');

            DB::table('process_approval_statuses')
                ->where('approvable_type', $type)
                ->where('approvable_id', $approvableId)
                ->where('id', '<>', $keepId)
                ->delete();
        }

        $approvalDups = DB::table('process_approvals')
            ->where('approvable_type', $type)
            ->select('approvable_id')
            ->groupBy('approvable_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('approvable_id');

        foreach ($approvalDups as $approvableId) {
            $request = DB::table('project_material_requests')->where('id', $approvableId)->first();
            if (!$request) {
                continue;
            }
            // Drop any process_approvals row created before the request itself —
            // it belongs to a previous record that reused this id.
            DB::table('process_approvals')
                ->where('approvable_type', $type)
                ->where('approvable_id', $approvableId)
                ->where('created_at', '<', $request->created_at)
                ->delete();
        }
    }

    public function down(): void
    {
        // Data cleanup; not reversible.
    }
};
