<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $target = 'App\\Models\\ProjectSiteVisit';

        // Find the misconfigured flow that pointed at the non-existent SiteVisit model
        $flow = DB::table('process_approval_flows')
            ->where('approvable_type', 'App\\Models\\SiteVisit')
            ->orWhere('id', 12)
            ->first();

        if (!$flow) {
            return;
        }

        DB::table('process_approval_flows')
            ->where('id', $flow->id)
            ->update([
                'name'            => 'ProjectSiteVisit',
                'approvable_type' => $target,
                'updated_at'      => now(),
            ]);

        // Build the steps payload that orphan status rows are missing
        $steps = DB::table('process_approval_flow_steps as s')
            ->leftJoin('roles as r', 'r.id', '=', 's.role_id')
            ->where('s.process_approval_flow_id', $flow->id)
            ->orderBy('s.order')
            ->get(['s.id', 's.action', 's.role_id', 'r.name as role_name', 's.active']);

        $stepsArray = $steps->map(fn($s) => [
            'id'                       => $s->id,
            'action'                   => $s->action,
            'process_approval_id'      => null,
            'role_id'                  => $s->role_id,
            'role_name'                => $s->role_name,
            'process_approval_action'  => null,
            'active'                   => (bool) $s->active,
        ])->all();

        $stepsJson = json_encode($stepsArray);

        // Repair existing ProjectSiteVisit status rows that were created without steps
        DB::table('process_approval_statuses')
            ->where('approvable_type', $target)
            ->where(function ($q) {
                $q->where('steps', '[]')->orWhereNull('steps');
            })
            ->update([
                'steps'      => $stepsJson,
                'status'     => 'Submitted',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No-op: pointing the flow back at the bogus SiteVisit model would re-introduce the bug.
    }
};
