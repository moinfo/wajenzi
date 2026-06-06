<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Deletes a record's process-approval rows when the record itself is deleted.
 *
 * Without this, deleting an approvable record leaves its
 * process_approval_statuses / process_approvals rows behind. When a later
 * insert reuses the same auto-increment id (InnoDB resets AUTO_INCREMENT to
 * MAX(id)+1 after a MySQL restart), the new record "adopts" the stale rows and
 * shows a ghost approval — e.g. a freshly-created PettyCashRefillRequest that
 * displays "Approval completed!" from a long-deleted record that shared its id.
 *
 * Laravel calls boot{TraitName}() from Model::boot()/bootTraits(), so this runs
 * for every model that uses the trait, even those defining their own boot().
 */
trait CascadesApprovalRecords
{
    public static function bootCascadesApprovalRecords(): void
    {
        static::deleting(static function (Model $model): void {
            // On a soft delete the record still exists — keep its approval trail.
            // Only clean up on a real (hard / force) delete.
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            $model->approvals()->delete();
            $model->approvalStatus()->delete();
        });
    }
}
