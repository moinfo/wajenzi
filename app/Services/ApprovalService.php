<?php


namespace App\Services;

use App\Models\Approval;
use App\Models\ApprovalLevel;
use App\Models\AssignUserGroup;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class ApprovalService
{
    /**
     * Mark notification as read for the current route
     */
    public function markNotificationAsRead($routeId, $documentTypeId, $redirect_route)
    {
        $notifiableId = Auth::user()->id;
        $baseRoute = "{$redirect_route}/{$routeId}/{$documentTypeId}";

        foreach (Auth::user()->unreadNotifications as $notification) {
            if ($notification->data['link'] == $baseRoute) {
                $notificationId = Notification::where('notifiable_id', $notifiableId)
                    ->where('data->link', $baseRoute)
                    ->first()->id;

                if ($notificationId) {
                    auth()->user()->notifications()->find($notificationId)->markAsRead();
                }
            }
        }
    }

    /**
     * Get formatted status badge HTML
     */
    public function getStatusBadge($status)
    {
        $statusClasses = [
            'PENDING' => 'pending',
            'APPROVED' => 'approved',
            'REJECTED' => 'rejected',
            'PAID' => 'paid',
            'COMPLETED' => 'completed'
        ];

        $class = $statusClasses[$status] ?? 'default';
        return "<span class='status-badge {$class}'>{$status}</span>";
    }

    /**
     * Get approval timeline data
     */
    public function getApprovalTimeline($documentTypeId, $saleId)
    {
        $approvals = ApprovalLevel::getUsersApprovals($documentTypeId);
        $timeline = [];

        foreach ($approvals as $approval) {
            $groupName = ApprovalLevel::getUserGroupName($approval->id);
            $approved = Approval::getApprovedDocument($approval->id, $documentTypeId, $saleId);
            $approver = User::getUserName($approved['user_id']);
            $signature = User::getUserSignature($approved['user_id']);

            $timeline[] = [
                'group_name' => $groupName,
                'approver' => $approver,
                'signature' => $signature,
                'approved_at' => $approved['created_at'],
                'comments' => $approved['comments']
            ];
        }

        return $timeline;
    }

    /**
     * Check if user has permission to approve
     */
    public function userCanApprove($nextApproval)
    {
        if (!$nextApproval) {
            return false;
        }

        $userGroupIds = AssignUserGroup::getAssignUserGroup(Auth::user()->id)
            ->pluck('user_group_id')
            ->toArray();

        return in_array($nextApproval->user_group_id, $userGroupIds);
    }

    /**
     * Get approval form data
     */
    public function getApprovalFormData($nextApproval, $documentId, $approval_document_type_id, $redirect_route)
    {
        return [
            'status' => 'APPROVED',
            'approval_document_types_id' => $nextApproval->document_id,
            'link' => "sales/{$documentId}/{$approval_document_type_id}",
            'user_id' => Auth::user()->id,
            'approval_level_id' => $nextApproval->order_id,
            'user_group_id' => $nextApproval->user_group_id,
            'document_id' => $documentId,
            'document_type_id' => $approval_document_type_id,
            'approval_date' => date('Y-m-d H:i:s'),
            'route' => "$redirect_route"
        ];
    }
}
