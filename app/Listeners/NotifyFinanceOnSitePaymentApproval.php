<?php

namespace App\Listeners;

use App\Listeners\Concerns\BuildsApprovalLinks;
use App\Models\SitePaymentRequest;
use App\Models\User;
use App\Notifications\ApprovalNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Events\ProcessApprovalCompletedEvent;

/**
 * When a Site Payment Request clears the RingleSoft chain (Procurement → MD),
 * Finance is NOT a RingleSoft step, so no built-in event notifies them. This
 * listener pings everyone authorised to process the payment ("Process Site
 * Payment") that the request is approved and ready to pay.
 */
class NotifyFinanceOnSitePaymentApproval implements ShouldQueue
{
    use InteractsWithQueue, BuildsApprovalLinks;

    public function handle(ProcessApprovalCompletedEvent $event): void
    {
        $approvable = $event->approvable ?? null;
        if (!$approvable instanceof SitePaymentRequest) {
            return;
        }

        // Don't re-notify the person who just gave final approval.
        $finalApproverId = $approvable->approvals()
            ->where('approval_action', 'Approved')
            ->latest('id')->value('user_id');

        $link = $this->getLinkForDocumentType('SitePaymentRequest', $approvable->id);

        foreach ($this->financeRecipients($finalApproverId) as $user) {
            $user->notify(new ApprovalNotification([
                'staff_id'         => $user->id,
                'link'             => $link,
                'title'            => 'Payment Request Ready for Payment',
                'body'             => "Payment request {$approvable->request_number} ("
                    . number_format((float) $approvable->total_amount) . " TZS) has been fully approved "
                    . "and is ready for Finance to process.",
                'document_id'      => (string) $approvable->id,
                'document_type_id' => '0',
            ]));
        }
    }

    /**
     * Route the "ready for payment" ping to the Finance department itself
     * (the Accountant role) rather than to everyone who merely *can* process a
     * payment — the permission is also held by MD/System Administrator as an
     * authorisation fallback, and pinging every admin would be pure noise.
     *
     * Resolved through the pivot tables to stay correct despite the mixed role
     * guard_name values in this database. Falls back to all permission holders
     * if no Accountant role/users exist in the environment.
     */
    private function financeRecipients($excludeUserId): \Illuminate\Support\Collection
    {
        $accountantRoleId = DB::table('roles')->where('name', 'Accountant')->value('id');

        $userIds = collect();
        if ($accountantRoleId) {
            $userIds = DB::table('model_has_roles')
                ->where('role_id', $accountantRoleId)
                ->where('model_type', User::class)
                ->pluck('model_id');
        }

        if ($userIds->isEmpty()) {
            // Fallback: anyone authorised to process the payment.
            $permId  = DB::table('permissions')->where('name', 'Process Site Payment')->value('id');
            $roleIds = $permId
                ? DB::table('role_has_permissions')->where('permission_id', $permId)->pluck('role_id')
                : collect();
            $userIds = DB::table('model_has_roles')
                ->whereIn('role_id', $roleIds)
                ->where('model_type', User::class)
                ->pluck('model_id');
        }

        $userIds = $userIds
            ->reject(fn ($id) => (string) $id === (string) $excludeUserId)
            ->unique();

        return User::whereIn('id', $userIds)->get();
    }
}
