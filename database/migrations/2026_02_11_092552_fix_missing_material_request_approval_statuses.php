<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ProjectMaterialRequest;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

return new class extends Migration
{
    public function up(): void
    {
        $requests = ProjectMaterialRequest::whereDoesntHave('approvalStatus')->get();

        foreach ($requests as $request) {
            $steps = $request->approvalFlowSteps()->map(fn($item) => $item->toApprovalStatusArray());

            // Already-approved requests get "Approved" status; pending get "Submitted"
            $isApproved = strtoupper($request->status) === 'APPROVED';

            $request->approvalStatus()->create([
                'steps' => $steps,
                'status' => $isApproved ? ApprovalStatusEnum::APPROVED->value : ApprovalStatusEnum::SUBMITTED->value,
                'creator_id' => $request->requester_id,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('process_approval_statuses')
            ->where('approvable_type', ProjectMaterialRequest::class)
            ->delete();
    }
};
