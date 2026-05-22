<?php

namespace App\Http\Controllers;

use App\Listeners\Concerns\BuildsApprovalLinks;
use App\Models\Approval;
use App\Models\AssignUserGroup;
use App\Models\Expense;
//use App\Models\StatutoryPayment;
use App\Models\Notification;
use App\Models\StatutoryPayment;
use App\Models\User;
use App\Support\ResolvesApprovableCreator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;


class ApprovalController extends Controller
{
    use BuildsApprovalLinks, ResolvesApprovableCreator;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

    }

    public function approvals(Request $request)
    {
        if ($request->approveItem) {
            return $this->handleApprove($request);
        }
        return $this->handleReject($request);
    }

    protected function handleApprove(Request $request)
    {
        $class_name   = $request->approveItem;
        $class_object = 'App\Models\\' . $class_name;
        $documentId   = $request->document_id;
        $docTypeId    = $request->document_type_id;

        $approve = Approval::create([
            'approval_document_types_id' => $request->approval_document_types_id,
            'user_id'            => $request->user_id,
            'document_id'        => $documentId,
            'approval_level_id'  => $request->approval_level_id,
            'user_group_id'      => $request->user_group_id,
            'approval_date'      => $request->approval_date,
            'comments'           => $request->comments,
            'status'             => 'APPROVED',
        ]);

        if (!$approve) {
            $this->notify('Failed to Approve ' . $class_name, 'Failed', 'error');
            return Redirect::back();
        }

        $this->notify($class_name . 'Approved Successfully', 'Approved!', 'success');

        $isCompleted = Approval::isApprovalCompleted($documentId, $docTypeId);
        $class_object::where('id', $documentId)->update(['status' => $isCompleted ? 'APPROVED' : 'PENDING']);

        if ($isCompleted) {
            // Final approval — notify the creator
            $this->notifyCreatorOutcome($class_name, $documentId, $docTypeId, 'approved', $request->comments);
        } else {
            // More steps remain — notify every user in the next approval group
            $this->notifyNextApprovers($class_name, $request, $documentId, $docTypeId);
        }

        return Redirect::back();
    }

    protected function handleReject(Request $request)
    {
        $class_name   = $request->rejectItem;
        $class_object = 'App\Models\\' . $class_name;
        $documentId   = $request->document_id;
        $docTypeId    = $request->document_type_id;

        $reject = Approval::create([
            'approval_document_types_id' => $request->approval_document_types_id,
            'user_id'            => $request->user_id,
            'document_id'        => $documentId,
            'approval_level_id'  => $request->approval_level_id,
            'user_group_id'      => $request->user_group_id,
            'approval_date'      => $request->approval_date,
            'comments'           => $request->comments,
            'status'             => 'REJECTED',
        ]);

        if (!$reject) {
            $this->notify('Failed to Reject ' . $class_name, 'Failed', 'error');
            return Redirect::back();
        }

        $this->notify($class_name . 'Rejected Successfully', 'Rejected!', 'success');
        $class_object::where('id', $documentId)->update(['status' => 'REJECTED']);

        // Notify the creator that their submission was rejected
        $this->notifyCreatorOutcome($class_name, $documentId, $docTypeId, 'rejected', $request->comments);

        return Redirect::back();
    }

    /**
     * Notify every user authorised for the next approval stage.
     * Prefers Spatie roles (al.role_id), falls back to user_groups for legacy rows.
     */
    protected function notifyNextApprovers(string $class_name, Request $request, $documentId, $docTypeId): void
    {
        $next = Approval::getNextApproval($documentId, $docTypeId);
        if (!$next) {
            return;
        }
        $users = Approval::getApproversFor($next);
        if ($users->isEmpty()) {
            $route = $next->role_id
                ? "role_id={$next->role_id}"
                : "group_id=" . ($next->user_group_id ?? 'null');
            \Log::warning("No approver assigned for {$class_name} document_type_id={$docTypeId}, {$route}");
            return;
        }
        foreach ($users as $user) {
            $details = [
                'staff_id'         => $user->id,
                'title'            => $class_name . ' Waiting for Approval',
                'body'             => 'A new ' . $class_name . ' ' . $request->document_number . ' has been created and submitted. You are required to review and approve the created ' . $class_name,
                'link'             => $request->link,
                'document_id'      => $documentId,
                'document_type_id' => $docTypeId,
            ];
            $user->notify(new \App\Notifications\ApprovalNotification($details));
            // Realtime broadcast (channel keyed by staff_id)
            event(new \App\Events\Approved($details));
        }
    }

    /**
     * Notify the document creator when their submission reaches a terminal outcome.
     * Uses the same probe order as the RingleSoft ApprovalOutcomeListener so behaviour
     * is consistent across the two approval systems.
     */
    protected function notifyCreatorOutcome(string $class_name, $documentId, $docTypeId, string $outcome, ?string $comment): void
    {
        $class_object = 'App\Models\\' . $class_name;
        if (!class_exists($class_object)) {
            return;
        }
        $approvable = $class_object::find($documentId);
        if (!$approvable) {
            return;
        }
        $creatorId = $this->resolveCreatorId($approvable);
        if (!$creatorId) {
            return;
        }
        $creator = User::find($creatorId);
        if (!$creator) {
            return;
        }

        $link = $this->getLinkForDocumentType($class_name, $documentId, (int) $docTypeId);
        $human = trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $class_name));

        if ($outcome === 'approved') {
            $title = "{$human} Approved";
            $body  = "Your {$human} submission has been fully approved.";
        } else {
            $title = "{$human} Rejected";
            $body  = "Your {$human} submission has been rejected.";
            if ($comment) {
                $body .= " Reason: {$comment}";
            }
        }

        $creator->notify(new \App\Notifications\ApprovalNotification([
            'staff_id'         => $creator->id,
            'link'             => $link,
            'title'            => $title,
            'body'             => $body,
            'outcome'          => $outcome,
            'document_id'      => (string) $documentId,
            'document_type_id' => (string) $docTypeId,
        ]));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Approval  $approval
     * @return \Illuminate\Http\Response
     */
    public function show(Approval $approval)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Approval  $approval
     * @return \Illuminate\Http\Response
     */
    public function edit(Approval $approval)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Approval  $approval
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Approval $approval)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Approval  $approval
     * @return \Illuminate\Http\Response
     */
    public function destroy(Approval $approval)
    {
        //
    }
}
