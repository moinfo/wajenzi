<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RingleSoft\LaravelProcessApproval\Models\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalStatus;

/**
 * Removes "ghost" approval rows adopted by a record that reused a deleted
 * record's auto-increment id.
 *
 * When an approvable record is hard-deleted its process_approval_statuses /
 * process_approvals rows used to be left behind (now prevented by the
 * CascadesApprovalRecords trait). If a later insert reused the same id
 * (InnoDB resets AUTO_INCREMENT to MAX(id)+1 after a MySQL restart), the new
 * record adopted those stale rows and showed a ghost approval.
 *
 * Detection rule: for a LIVE record, any approval row whose created_at predates
 * the record's own created_at cannot belong to it — it is an orphan from the
 * previous id holder. Those rows are removed; the record's real rows (created
 * at or after the record) are kept.
 *
 * Dry-run by default. Pass --apply to delete.
 */
class FixGhostApprovalAdoptions extends Command
{
    protected $signature = 'approvals:fix-ghost-adoptions
                            {model? : Fully-qualified model class, e.g. "App\\Models\\PettyCashRefillRequest"}
                            {--apply : Actually delete (default is a dry run)}';

    protected $description = 'Remove approval rows adopted by a record that reused a deleted record\'s id';

    public function handle(): int
    {
        $apply = $this->option('apply');
        $this->{$apply ? 'warn' : 'info'}($apply ? 'APPLY MODE — rows will be deleted.' : 'DRY RUN — no changes will be saved (pass --apply to delete).');

        $types = $this->argument('model')
            ? [$this->argument('model')]
            : ProcessApprovalStatus::query()->distinct()->pluck('approvable_type')->toArray();

        $totalStatuses = 0;
        $totalApprovals = 0;

        foreach ($types as $type) {
            if (!class_exists($type)) {
                $this->warn("Skipping {$type}: class not found.");
                continue;
            }
            $table = (new $type)->getTable();
            if (!Schema::hasTable($table)) {
                $this->warn("Skipping {$type}: table {$table} not found.");
                continue;
            }

            // Live records keyed by id -> created_at
            $live = DB::table($table)->pluck('created_at', 'id');
            if ($live->isEmpty()) {
                continue;
            }

            $ghostStatusIds  = [];
            $ghostApprovalIds = [];

            foreach (['process_approval_statuses' => ProcessApprovalStatus::class,
                      'process_approvals'         => ProcessApproval::class] as $relTable => $_) {
                $rows = DB::table($relTable)
                    ->where('approvable_type', $type)
                    ->whereIn('approvable_id', $live->keys())
                    ->get(['id', 'approvable_id', 'created_at']);

                foreach ($rows as $row) {
                    $recordCreatedAt = $live[$row->approvable_id] ?? null;
                    // Only flag rows strictly older than the record that now owns the id.
                    if ($recordCreatedAt !== null && $row->created_at < $recordCreatedAt) {
                        if ($relTable === 'process_approval_statuses') {
                            $ghostStatusIds[] = $row->id;
                        } else {
                            $ghostApprovalIds[] = $row->id;
                        }
                        $this->line(sprintf(
                            "  [%s #%s] ghost %s id=%s (row %s < record %s)",
                            class_basename($type), $row->approvable_id,
                            $relTable === 'process_approval_statuses' ? 'status' : 'approval',
                            $row->id, $row->created_at, $recordCreatedAt
                        ));
                    }
                }
            }

            if (empty($ghostStatusIds) && empty($ghostApprovalIds)) {
                continue;
            }

            $totalStatuses  += count($ghostStatusIds);
            $totalApprovals += count($ghostApprovalIds);

            if ($apply) {
                DB::transaction(function () use ($ghostStatusIds, $ghostApprovalIds) {
                    if ($ghostStatusIds)  ProcessApprovalStatus::whereIn('id', $ghostStatusIds)->delete();
                    if ($ghostApprovalIds) ProcessApproval::whereIn('id', $ghostApprovalIds)->delete();
                });
            }
        }

        $this->newLine();
        $verb = $apply ? 'Deleted' : 'Would delete';
        $this->info("{$verb}: {$totalStatuses} status row(s), {$totalApprovals} approval row(s).");
        if (!$apply && ($totalStatuses || $totalApprovals)) {
            $this->comment('Re-run with --apply to perform the deletion.');
        }

        return self::SUCCESS;
    }
}
