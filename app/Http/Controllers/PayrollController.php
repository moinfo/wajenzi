<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\FinancialCharge;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Payroll')) {
            return back();
        }
        $staffs = User::where('type','STAFF')->get();
        $payroll = Payroll::all();

        $data = [
            'payroll' => $payroll,
             'staffs' => $staffs
        ];
        return view('pages.payroll.generate_payroll')->with($data);
    }

    public function payroll_view(Request $request,$month,$year)
    {
        if($this->handleCrud($request, 'Payroll')) {
            return back();
        }
        $staffs = Staff::getList();
        $payroll = Payroll::all();

        $data = [
            'payroll' => $payroll,
             'staffs' => $staffs
        ];
        return view('pages.payroll.payroll_index')->with($data);
    }

    public function payroll_record($id,$document_type_id){
        $payroll_record = \App\Models\PayrollRecord::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'payroll_record' => $payroll_record,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.payroll_records.payroll_record')->with($data);
    }
}
