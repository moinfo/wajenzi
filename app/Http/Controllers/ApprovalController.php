<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AssignUserGroup;
use App\Models\Expense;
//use App\Models\StatutoryPayment;
use App\Models\Notification;
use App\Models\StatutoryPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;


class ApprovalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

    }

    public function approvals(Request $request) {
        if($request->approveItem){
            $class_name =  $request->approveItem;
            $class_object = 'App\Models\\' . $class_name;
           $approve =  Approval::create([
                'approval_document_types_id' => $request->approval_document_types_id,
                'user_id' => $request->user_id,
                'document_id' => $request->document_id,
                'approval_level_id' => $request->approval_level_id,
                'user_group_id' => $request->user_group_id,
                'approval_date' => $request->approval_date,
                'comments' => $request->comments,
                'status' => 'APPROVED']);

           if($approve){
               $this->notify($class_name .'Approved Successfully', 'Approved!', 'success');
               if(Approval::getNextApproval($request->document_id,$request->document_type_id)) {
                   $next_user_group_id = Approval::getNextApproval($request->document_id,$request->document_type_id)->user_group_id;
                   $next_user_id = AssignUserGroup::getUserId($next_user_group_id)->user_id;
                   $user = User::find($next_user_id);

                   $details = [
                       'staff_id' => $next_user_id,
                       'title' => $class_name. ' '. 'Waiting for Approval',
                       'body' => 'A new '.$class_name.' '.$request->document_number.' has been created and submitted. You are required to review and approve the created '. $class_name,
                       'link' => $request->link,
                       'document_id' => $request->document_id,
                       'document_type_id' => $request->document_type_id
                   ];

                   $user->notify(new \App\Notifications\ApprovalNotification($details));
               }
               if (Approval::isApprovalCompleted($request->document_id,$request->document_type_id)){
                   $class_object::where('id', $request->document_id)->update(['status' => 'APPROVED']);
               }else{
                   $class_object::where('id', $request->document_id)->update(['status' => 'PENDING']);
               }
           }else{
               $this->notify('Failed to Approve '.$class_name, 'Failed', 'error');
              // redirect('settings/statutory_payments');
           }

        }else{
            $class_name =  $request->rejectItem;
            $class_object = 'App\Models\\' . $class_name;
            $reject = Approval::create([
                'approval_document_types_id' => $request->approval_document_types_id,
                'user_id' => $request->user_id,
                'document_id' => $request->document_id,
                'approval_level_id' => $request->approval_level_id,
                'user_group_id' => $request->user_group_id,
                'approval_date' => $request->approval_date,
                'comments' => $request->comments,
                'status' => 'REJECTED']);
            if ($reject == true){
                $this->notify($class_name .'Rejected Successfully', 'Rejected!', 'success');
                $class_object::where('id', $request->document_id)->update(['status' => 'REJECTED']);

            }else{
                $this->notify('Failed to Reject '.$class_name, 'Failed', 'error');
            }


        }

        return Redirect::back();
//        $statutory_payments = StatutoryPayment::all();
//        $data = [
//            'statutory_payments' => $statutory_payments
//        ];
//        return view('pages.settings.settings_statutory_payments')->with($data);

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
