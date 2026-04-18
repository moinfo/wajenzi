<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalActionEnum;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalStatus;

class FixStuckApprovals extends Command
{
    protected $signature = 'approvals:fix-stuck
                            {model? : Fully-qualified model class, e.g. "App\\Models\\ProjectClient"}
                            {--dry-run : Preview what would be fixed without writing anything}
                            {--force-complete : Force-approve any remaining unapproved steps and mark APPROVED}
                            {--user= : User ID to record as approver when using --force-complete (defaults to user 1)}';

    protected $description = 'Fix approval records stuck because a flow step was deleted mid-approval';

    public function handle(): int
    {
        $modelClass    = $this->argument('model');
        $dryRun        = $this->option('dry-run');
        $forceComplete = $this->option('force-complete');
        $userId        = $this->option('user') ?? 1;

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        if ($forceComplete && !$dryRun) {
            $approver = \App\Models\User::find($userId);
            if (!$approver) {
                $this->error("User ID {$userId} not found. Use --user=ID to specify a valid approver.");
                return self::FAILURE;
            }
            $this->warn("Force-complete mode: unapproved steps will be recorded as approved by \"{$approver->name}\".");
        }

        $types = $modelClass
            ? [$modelClass]
            : $this->getAllApprovableTypes();

        if (empty($types)) {
            $this->error('No approvable model types found.');
            return self::FAILURE;
        }

        $fixed = 0;
        $skipped = 0;

        foreach ($types as $type) {
            $this->line("\nProcessing: <info>{$type}</info>");

            $activeStepIds = ProcessApprovalFlowStep::query()
                ->join('process_approval_flows', 'process_approval_flows.id', 'process_approval_flow_steps.process_approval_flow_id')
                ->where('process_approval_flows.approvable_type', $type)
                ->pluck('process_approval_flow_steps.id')
                ->toArray();

            if (empty($activeStepIds)) {
                $this->line("  No active flow steps — skipping.");
                continue;
            }

            $statuses = ProcessApprovalStatus::query()
                ->where('approvable_type', $type)
                ->whereNotIn('status', ['APPROVED', 'REJECTED', 'DISCARDED'])
                ->get();

            foreach ($statuses as $status) {
                $steps = collect($status->steps ?? []);

                $hasGhostStep = $steps->contains(function ($s) use ($activeStepIds) {
                    return !in_array($s['id'] ?? null, $activeStepIds)
                        && ($s['process_approval_action'] === null || $s['process_approval_id'] === null);
                });

                if (!$hasGhostStep) {
                    $skipped++;
                    continue;
                }

                $model = $type::find($status->approvable_id);
                if (!$model) {
                    $this->warn("  [{$type} #{$status->approvable_id}] Model not found — skipping.");
                    $skipped++;
                    continue;
                }

                // Strip ghost steps
                $filtered = $steps
                    ->filter(fn($s) => in_array($s['id'] ?? null, $activeStepIds))
                    ->values();

                $pendingSteps = $filtered->filter(
                    fn($s) => $s['process_approval_action'] === null || $s['process_approval_id'] === null
                );

                $label = "  [{$type} #{$status->approvable_id}] Ghost step removed.";

                if ($pendingSteps->isEmpty()) {
                    $this->line($label . ' → All active steps approved. Will mark APPROVED.');
                } elseif ($forceComplete) {
                    $this->line($label . " → {$pendingSteps->count()} step(s) force-approved. Will mark APPROVED.");
                } else {
                    $this->line($label . " → {$pendingSteps->count()} step(s) still pending (use --force-complete to auto-approve).");
                }

                if ($dryRun) {
                    $fixed++;
                    continue;
                }

                // Force-approve any remaining null steps
                if ($forceComplete && $pendingSteps->isNotEmpty()) {
                    $approver = \App\Models\User::find($userId);
                    $filtered = $filtered->map(function ($s) use ($model, $approver, $status) {
                        if ($s['process_approval_action'] === null || $s['process_approval_id'] === null) {
                            $approval = ProcessApproval::create([
                                'approvable_type'               => $model->getMorphClass(),
                                'approvable_id'                 => $model->id,
                                'process_approval_flow_step_id' => $s['id'],
                                'approval_action'               => ApprovalActionEnum::APPROVED->value,
                                'comment'                       => 'Auto-approved by fix-stuck command',
                                'user_id'                       => $approver->id,
                                'approver_name'                 => $approver->name,
                            ]);
                            $s['process_approval_id']     = $approval->id;
                            $s['process_approval_action'] = ApprovalActionEnum::APPROVED->value;
                        }
                        return $s;
                    });
                }

                $status->update(['steps' => $filtered->toArray()]);
                $model->unsetRelation('approvalStatus');

                if ($forceComplete || $pendingSteps->isEmpty()) {
                    $lastApproval = $model->approvals()->latest()->first();
                    if ($lastApproval) {
                        $model->onApprovalCompleted($lastApproval);
                    }
                    $status->update(['status' => ApprovalStatusEnum::APPROVED->value]);
                }

                $fixed++;
            }
        }

        $this->newLine();
        $this->info("Done. Fixed: {$fixed}  |  Skipped (no ghost steps): {$skipped}");

        return self::SUCCESS;
    }

    private function getAllApprovableTypes(): array
    {
        return ProcessApprovalStatus::query()
            ->distinct()
            ->pluck('approvable_type')
            ->toArray();
    }
}
