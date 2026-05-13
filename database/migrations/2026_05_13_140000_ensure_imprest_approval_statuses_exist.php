<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;

return new class extends Migration
{
    public function up(): void
    {
        $type = 'App\\Models\\ImprestRequest';

        $imprestsWithoutStatus = DB::table('imprest_requests as i')
            ->leftJoin('process_approval_statuses as s', function ($join) use ($type) {
                $join->on('s.approvable_id', '=', 'i.id')
                     ->where('s.approvable_type', $type);
            })
            ->whereNull('s.id')
            ->select('i.id', 'i.status', 'i.create_by_id', 'i.created_at')
            ->get();

        if ($imprestsWithoutStatus->isEmpty()) {
            return;
        }

        $steps = ProcessApprovalFlowStep::query()
            ->join('process_approval_flows', 'process_approval_flows.id', 'process_approval_flow_steps.process_approval_flow_id')
            ->where('process_approval_flows.approvable_type', $type)
            ->select('process_approval_flow_steps.*')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->map(fn($s) => $s->toApprovalStatusArray())
            ->toArray();

        foreach ($imprestsWithoutStatus as $imprest) {
            $approvalStatus = strtoupper($imprest->status ?? '') === 'COMPLETED'
                ? ApprovalStatusEnum::APPROVED->value
                : ApprovalStatusEnum::SUBMITTED->value;

            DB::table('process_approval_statuses')->insert([
                'approvable_type' => $type,
                'approvable_id'   => $imprest->id,
                'steps'           => json_encode($steps),
                'status'          => $approvalStatus,
                'creator_id'      => $imprest->create_by_id,
                'created_at'      => $imprest->created_at,
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Backfill is not reversible.
    }
};
