<?php


namespace App\Services;

use App\Models\Approval;
use App\Models\ApprovalLevel;
use App\Models\AssignUserGroup;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ApprovalService
{
    /**
     * Mark notifications as read based on the current URL
     *
     * @param mixed $routeId The route/document ID
     * @param mixed $documentTypeId The document type ID
     * @param string $redirect_route The base redirect route
     * @return int Number of notifications marked as read
     */
    public function markNotificationAsRead($routeId, $documentTypeId, $redirect_route)
    {
        $user = Auth::user();

        if (!$user) {
            return 0;
        }

        // Exact link format as it might appear in the database
        $exactLink = "{$redirect_route}/{$routeId}/{$documentTypeId}";

        // Get first few unread notifications to check their format
        $sampleNotifications = $user->unreadNotifications()
            ->limit(3)
            ->get();

        foreach ($sampleNotifications as $notification) {
            \Log::info("Sample notification link format: " . json_encode($notification->data));
        }

        // Try multiple approaches to mark notifications as read

        // Approach 1: Direct JSON path match
        $count1 = $user->notifications()
            ->whereNull('read_at')
            ->where('data->link', $exactLink)
            ->update(['read_at' => now()]);

        \Log::info("Approach 1 (exact match) marked {$count1} notifications as read");

        // Approach 2: Try with LIKE operator
        $count2 = $user->notifications()
            ->whereNull('read_at')
            ->whereRaw("JSON_EXTRACT(data, '$.link') LIKE ?", ["%{$redirect_route}/{$routeId}/{$documentTypeId}%"])
            ->update(['read_at' => now()]);

        \Log::info("Approach 2 (LIKE operator) marked {$count2} notifications as read");

        // Approach 3: Manual check for each notification
        $count3 = 0;
        foreach ($user->unreadNotifications as $notification) {
            if (isset($notification->data['link'])) {
                $link = $notification->data['link'];
                \Log::info("Checking link: {$link} against {$exactLink}");

                if ($link == $exactLink ||
                    strpos($link, $exactLink) !== false ||
                    (strpos($link, $routeId) !== false &&
                        strpos($link, $documentTypeId) !== false &&
                        strpos($link, $redirect_route) !== false)) {

                    $notification->markAsRead();
                    $count3++;
                    \Log::info("Marked notification {$notification->id} as read");
                }
            }
        }

        \Log::info("Approach 3 (manual check) marked {$count3} notifications as read");

        return $count1 + $count2 + $count3;
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

            // Check if $approved is an array before accessing array elements
            if (is_array($approved)) {
                $approver = isset($approved['user_id']) ? User::getUserName($approved['user_id']) : null;
                $signature = isset($approved['user_id']) ? User::getUserSignature($approved['user_id']) : null;

                $timeline[] = [
                    'group_name' => $groupName,
                    'approver' => $approver,
                    'signature' => $signature,
                    'approved_at' => $approved['created_at'] ?? null,
                    'comments' => $approved['comments'] ?? null
                ];
            } else {
                // Handle the case where $approved is not an array
                $timeline[] = [
                    'group_name' => $groupName,
                    'approver' => null,
                    'signature' => null,
                    'approved_at' => null,
                    'comments' => null
                ];
            }
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
            'link' => "{$redirect_route}/{$documentId}/{$approval_document_type_id}",
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
