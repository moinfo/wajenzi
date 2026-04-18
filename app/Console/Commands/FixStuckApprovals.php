<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalStatus;

class FixStuckApprovals extends Command
{
    protected $signature = 'approvals:fix-stuck
                            {model? : Fully-qualified model class, e.g. "App\\Models\\ProjectClient"}
                            {--dry-run : Preview what would be fixed without writing anything}';

    protected $description = 'Fix approval records stuck because a flow step was deleted mid-approval';

    public function handle(): int
    {
        $modelClass = $this->argument('model');
        $dryRun     = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN — no changes will be saved.');
        }

        // Collect all approvable types to process
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

            // Active step IDs for this approvable type
            $activeStepIds = ProcessApprovalFlowStep::query()
                ->join('process_approval_flows', 'process_approval_flows.id', 'process_approval_flow_steps.process_approval_flow_id')
                ->where('process_approval_flows.approvable_type', $type)
                ->pluck('process_approval_flow_steps.id')
                ->toArray();

            if (empty($activeStepIds)) {
                $this->line("  No active flow steps — skipping.");
                continue;
            }

            // Find non-completed approval statuses for this type
            $statuses = ProcessApprovalStatus::query()
                ->where('approvable_type', $type)
                ->whereNotIn('status', ['APPROVED', 'REJECTED', 'DISCARDED'])
                ->get();

            foreach ($statuses as $status) {
                $steps = collect($status->steps ?? []);

                // Check if any step references a deleted flow step with a null action
                $hasGhostStep = $steps->contains(function ($s) use ($activeStepIds) {
                    return !in_array($s['id'] ?? null, $activeStepIds)
                        && ($s['process_approval_action'] === null || $s['process_approval_id'] === null);
                });

                if (!$hasGhostStep) {
                    $skipped++;
                    continue;
                }

                // Strip ghost steps
                $filtered = $steps
                    ->filter(fn($s) => in_array($s['id'] ?? null, $activeStepIds))
                    ->values()
                    ->toArray();

                // Check if all remaining active steps are approved
                $allApproved = collect($filtered)->every(function ($s) {
                    return $s['process_approval_action'] !== null
                        && $s['process_approval_id'] !== null
                        && $s['process_approval_action'] !== 'RETURNED'
                        && $s['process_approval_action'] !== 'REJECTED';
                });

                $model = $type::find($status->approvable_id);
                if (!$model) {
                    $this->warn("  [{$type} #{$status->approvable_id}] Model not found — skipping.");
                    $skipped++;
                    continue;
                }

                $this->line("  [{$type} #{$status->approvable_id}] Ghost step removed."
                    . ($allApproved ? ' → Will mark APPROVED.' : ' → Still pending other steps.'));

                if (!$dryRun) {
                    $status->update(['steps' => $filtered]);
                    $model->unsetRelation('approvalStatus');

                    if ($allApproved) {
                        $lastApproval = $model->approvals()->latest()->first();
                        if ($lastApproval) {
                            $model->onApprovalCompleted($lastApproval);
                        }
                        $status->update(['status' => 'APPROVED']);
                    }
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
