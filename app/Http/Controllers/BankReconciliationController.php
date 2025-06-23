<?php

namespace App\Http\Controllers;

use App\Classes\Utility;
use App\Models\Approval;
use App\Models\AssetProperty;
use App\Models\BankReconciliation;
use App\Models\Efd;
use App\Models\Supplier;
use App\Models\SupplierTarget;
use App\Models\SupplierTargetPreparation;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class BankReconciliationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $systems = System::where('id','!=',5)->get();


        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $start_date = $request->input('start_date') ?? '2020-01-20';
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id =  $request->input('efd_id') ?? null;
        $supplier_id =  $request->input('supplier_id') ?? null;
        $payment_type = 'SALES';
        $bank_reconciliations = \App\Models\BankReconciliation::unrepresentedSlipCount($start_date,$end_date,$efd_id,$supplier_id,$payment_type);

        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'systems' => $systems,
            'total_unrepresented_slip' => $bank_reconciliations,
        ];
        return view('pages.bank_reconciliation.bank_reconciliation_index')->with($data);
    }

    public function bank_reconciliation_deposits(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id =  $request->input('efd_id') ?? null;
        $supplier_id =  $request->input('supplier_id') ?? null;
//        $payment_type = null;
        $suppliers = Supplier::all();
        $efds = Efd::all();
        $bank_reconciliations = \App\Models\BankReconciliation::bankDeposits($start_date,$end_date,$efd_id,$supplier_id);
        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'bank_reconciliations' => $bank_reconciliations,
            'suppliers' => $suppliers,
            'efds' => $efds,
        ];
        return view('pages.bank_reconciliation.deposit')->with($data);
    }

    public function unrepresented_slip(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? '2020-01-20';
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id =  $request->input('efd_id') ?? null;
        $supplier_id =  $request->input('supplier_id') ?? null;
        $payment_type = 'SALES';
        $suppliers = Supplier::all();
        $efds = Efd::all();
        $bank_reconciliations = \App\Models\BankReconciliation::unrepresentedSlip($start_date,$end_date,$efd_id,$supplier_id,$payment_type);
        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'bank_reconciliations' => $bank_reconciliations,
            'suppliers' => $suppliers,
            'efds' => $efds,
        ];
        return view('pages.bank_reconciliation.unrepresented_slip')->with($data);
    }

    public function bank_reconciliation_suppliers_statement(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id =  $request->input('efd_id') ?? null;
        $supplier_id =  $request->input('supplier_id') ?? null;
        $payment_type = 'SALES';
        $suppliers = Supplier::all();
        $efds = Efd::all();
        $supplier_with_deposits = BankReconciliation::where('date','>=',$start_date)->where('date','<=',$end_date)->where('payment_type','SALES')->select('suppliers.name','bank_reconciliations.supplier_id')
            ->join('suppliers','suppliers.id','=','bank_reconciliations.supplier_id')->groupBy('supplier_id')->get();        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'supplier_with_deposits' => $supplier_with_deposits,
            'suppliers' => $suppliers,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'efds' => $efds,
        ];
        return view('pages.bank_reconciliation.suppliers_statement')->with($data);
    }

    public function bank_reconciliation_bank_reconciliation_statement(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $payment_type = 'SALES';
        $efds = Efd::all();

        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $reports = Efd::allWithTransactions($start_date, $end_date);
        $reports_2 = Efd::allWithTransactionsWithOfficePaymentType($start_date, $end_date, $payment_type);
        $reports_3 = Efd::allWithTransactionsWithOfficePaymentType($start_date, $end_date, 'SALES');
        $maxTransactions = 0;
        foreach ($reports as $index => $item) {
            if($item->transactions()->count() > $maxTransactions){
                $maxTransactions = $item->transactions()->count();
            }
        }

        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'efdTransactions' => $reports,
            'efdTransactions_2' => $reports_2,
            'efdTransactions_3' => $reports_3,
            'maxTransactions' => $maxTransactions,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'efds' => $efds,
        ];
        return view('pages.bank_reconciliation.bank_reconciliation_statement')->with($data);
    }
    public function bank_reconciliation_sales_bank_deposited(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id =  $request->input('efd_id') ?? null;
        $supplier_id =  $request->input('supplier_id') ?? null;
        $payment_type = 'SALES';
        $suppliers = Supplier::all();
        $efds = Efd::all();
        $bank_reconciliations = \App\Models\BankReconciliation::getAll($start_date,$end_date,$efd_id,$supplier_id,$payment_type);
        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'bank_reconciliations' => $bank_reconciliations,
            'suppliers' => $suppliers,
            'efds' => $efds,
        ];
        return view('pages.bank_reconciliation.sales_bank_deposited')->with($data);
    }

    public function bank_reconciliation_withdraws(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id =  $request->input('efd_id') ?? null;
        $supplier_id =  $request->input('supplier_id') ?? null;
        $payment_type = 'SALES';
        $suppliers = Supplier::all();
        $efds = Efd::all();
        $bank_reconciliations = \App\Models\BankReconciliation::bankWithdraws($start_date,$end_date,$efd_id,$supplier_id,$payment_type);
        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'bank_reconciliations' => $bank_reconciliations,
            'suppliers' => $suppliers,
            'efds' => $efds,
        ];
        return view('pages.bank_reconciliation.withdraw')->with($data);
    }

    public function bank_reconciliation_transfers(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id =  $request->input('efd_id') ?? null;
        $supplier_id =  $request->input('supplier_id') ?? null;
        $payment_type = 'SALES';
        $suppliers = Supplier::all();
        $efds = Efd::all();
        $bank_reconciliations = \App\Models\BankReconciliation::bankTransfers($start_date,$end_date,$efd_id,$supplier_id,$payment_type);
        $bank_reconciliation_payment_types = [
            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,
            'bank_reconciliations' => $bank_reconciliations,
            'suppliers' => $suppliers,
            'efds' => $efds,
        ];
        return view('pages.bank_reconciliation.transfer')->with($data);
    }
    public function supplier_targets(Request $request)
    {
        if($this->handleCrud($request, 'SupplierTarget')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id') ?? 0;

        $suppliers = Supplier::all();
        $supplier_targets = SupplierTarget::getAllTargets($start_date,$end_date,$supplier_id);

        $data = [
            'suppliers' => $suppliers,
            'supplier_targets' => $supplier_targets,
        ];
        return view('pages.bank_reconciliation.supplier_targets')->with($data);
    }
    public function bank_deposit_report(Request $request)
    {
        if($this->handleCrud($request, 'SupplierTarget')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $systems = System::where('id','!=',5)->get();


        $data = [
            'systems' => $systems,
        ];
        return view('pages.bank_reconciliation.bank_deposit_report')->with($data);
    }
    public function slip_review_report(Request $request)
    {
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');

//        dump($start_date);
//        dump($end_date);
//        return;
        $bank_reconciliations = \App\Models\BankReconciliation::getAll($start_date,$end_date,null,null,null);
        $data = [
            'bank_reconciliations' => $bank_reconciliations,
        ];
        return view('pages.bank_reconciliation.slip_review_report')->with($data);
    }
    public function supplier_commissions(Request $request)
    {
        if($this->handleCrud($request, 'SupplierTarget')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id') ?? 0;

        $suppliers = Supplier::all();
        $supplier_targets = SupplierTarget::getAllCommissions($start_date,$end_date,$supplier_id);

        $data = [
            'suppliers' => $suppliers,
            'supplier_targets' => $supplier_targets,
        ];
        return view('pages.bank_reconciliation.supplier_commissions')->with($data);
    }
    public function supplier_targets_report(Request $request)
    {
        if($this->handleCrud($request, 'SupplierTarget')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id') ?? 0;

        $suppliers = Supplier::all();
        $supplier_targets_reports = SupplierTarget::getTargetDifference($start_date,$end_date,$supplier_id);

        $data = [
            'suppliers' => $suppliers,
            'supplier_targets_reports' => $supplier_targets_reports,
        ];
        return view('pages.bank_reconciliation.supplier_targets_reports')->with($data);
    }

    public function supplier_target_preparation(Request $request)
    {
        if($this->handleCrud($request, 'SupplierTargetPreparation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id') ?? 0;

        $suppliers = Supplier::all();
        $efds = Efd::all();
        $supplier_target_preparations = SupplierTarget::getTargetDifference($start_date,$end_date,$supplier_id);
        $supplier_target_preparation_lists = SupplierTargetPreparation::getAll($start_date,$end_date);

        $data = [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'efds' => $efds,
            'suppliers' => $suppliers,
            'supplier_target_preparations' => $supplier_target_preparations,
            'supplier_target_preparation_lists' => $supplier_target_preparation_lists,
        ];
        return view('pages.bank_reconciliation.supplier_targets_preparation')->with($data);
    }

    public function bank_deposit_reports(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-t');
        $efd_id = $request->input('efd_id') ?? null;
        $supplier_id = $request->input('supplier_id') ?? null;
        $payment_type = 'OFFICE';
        $bank_reconciliations = \App\Models\BankReconciliation::getAll($start_date,$end_date,$efd_id,$supplier_id,$payment_type);
        $suppliers = Supplier::all();
        $systems = System::where('id','!=',5)->get();
        $efds = Efd::all();
        $bank_reconciliation_payment_types = [
//            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliations' => $bank_reconciliations,
            'suppliers' => $suppliers,
            'systems' => $systems,
            'efds' => $efds,
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,

        ];
        return view('pages.bank_reconciliation.bank_reconciliation_deposit')->with($data);
    }

    public function bank_withdraw_reports(Request $request)
    {
        if($this->handleCrud($request, 'BankReconciliation')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-t');
        $efd_id = $request->input('efd_id') ?? null;
        $supplier_id = $request->input('supplier_id') ?? null;
        $payment_type = 'OFFICE';
        $bank_reconciliations = \App\Models\BankReconciliation::getAll($start_date,$end_date,$efd_id,$supplier_id,$payment_type);
        $suppliers = Supplier::all();
        $systems = System::where('id','!=',5)->get();
        $efds = Efd::all();
        $bank_reconciliation_payment_types = [
//            ['name'=>'SALES'],
            ['name'=>'OFFICE']
        ];
        $data = [
            'bank_reconciliations' => $bank_reconciliations,
            'suppliers' => $suppliers,
            'systems' => $systems,
            'efds' => $efds,
            'bank_reconciliation_payment_types' => $bank_reconciliation_payment_types,

        ];
        return view('pages.bank_reconciliation.bank_reconciliation_withdraw')->with($data);
    }
    public function bank_reconciliation($id,$document_type_id)
    {
        $bank_reconciliation = \App\Models\BankReconciliation::where('id', $id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id, $document_type_id);
        $nextApproval = Approval::getNextApproval($id, $document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id, $document_type_id);
        $rejected = Approval::isRejected($id, $document_type_id);
        $document_id = $id;
        $data = [
            'bank_reconciliation' => $bank_reconciliation,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.bank_reconciliation.bank_reconciliation')->with($data);
    }
    public function transferReports(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id = $request->input('efd_if') ?? null;
        $supplier_id = $request->input('supplier_id') ?? null;

        $bank_reconciliations = \App\Models\BankReconciliation::getOnlyTransfered($start_date,$end_date,$efd_id,$supplier_id);
        $data = [
           'bank_reconciliations' => $bank_reconciliations
        ];
        return view('pages.bank_reconciliation.transfer_reports')->with($data);
    }

    public function transfer_by_only_supplier_reports(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id = $request->input('efd_if') ?? null;
        $supplier_id = $request->input('supplier_id') ?? null;

        $bank_reconciliations = \App\Models\BankReconciliation::getOnlyTransferedBySupplier($start_date,$end_date,$efd_id,$supplier_id);
        $data = [
           'bank_reconciliations' => $bank_reconciliations
        ];
        return view('pages.bank_reconciliation.transfer_by_only_supplier_reports')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function transfer(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $from_to_id = $from.$to;
        $efd_id = $request->input('efd_id');
        $type = $request->input('type');
        $date = $request->input('date');
        $debit = Utility::strip_commas($request->input('debit'));
        $description = $request->input('description');
        $beneficiary_account_id = $request->input('beneficiary_account_id');
        $reference = $request->input('reference');
        $payment_type = $request->input('payment_type');
        $bank_id = $request->input('bank_id');
        $depend = Supplier::find($to)->supplier_depend_on_system;
        if($depend == 'WHITESTAR'){
            DB::table('bank_reconciliations')->insert([
                ['bank_id' => $bank_id,'supplier_id' => $from, 'beneficiary_account_id' => $beneficiary_account_id, 'efd_id' => $efd_id, 'type' => $type, 'from_to_id' => $from_to_id, 'to_id' => $to, 'date' => $date, 'reference' => $reference, 'description' => $description, 'debit' => $debit*-1, 'payment_type' => $payment_type],
                ['bank_id' => $bank_id,'supplier_id' => $to,  'beneficiary_account_id' => $beneficiary_account_id, 'to_id' => $from, 'type' => $type,  'from_to_id' => $to.$from, 'efd_id' => $efd_id, 'date' => $date, 'reference' => "$reference 1",'description' => $description, 'debit' => $debit, 'payment_type' => $payment_type],
                ['bank_id' => $bank_id,'supplier_id' => 50,  'beneficiary_account_id' => $beneficiary_account_id, 'to_id' => $from, 'type' => $type,  'from_to_id' => $to.$from, 'efd_id' => 16, 'date' => $date, 'reference' => "$reference 2",'description' => $description, 'debit' => $debit, 'payment_type' => $payment_type]
            ]);
        }elseif($to == 201){
            DB::table('bank_reconciliations')->insert([
            ['bank_id' => $bank_id,'supplier_id' => $from,  'beneficiary_account_id' => $beneficiary_account_id, 'efd_id' => $efd_id, 'type' => $type, 'from_to_id' => $from_to_id, 'to_id' => $to, 'date' => $date, 'reference' => $reference, 'description' => $description, 'debit' => $debit*-1, 'payment_type' => $payment_type],
            ['bank_id' => $bank_id,'supplier_id' => $to,  'beneficiary_account_id' => $beneficiary_account_id, 'to_id' => $from, 'type' => $type,  'from_to_id' => $to.$from, 'efd_id' => $efd_id, 'date' => $date, 'reference' => "$reference 1",'description' => $description, 'debit' => $debit, 'payment_type' => $payment_type],
            ['bank_id' => $bank_id,'supplier_id' => 183,  'beneficiary_account_id' => $beneficiary_account_id, 'to_id' => $from, 'type' => $type,  'from_to_id' => $to.$from, 'efd_id' => 23, 'date' => $date, 'reference' => "$reference 2",'description' => $description, 'debit' => $debit, 'payment_type' => $payment_type]
            ]);
        }else{
            DB::table('bank_reconciliations')->insert([
                ['bank_id' => $bank_id,'supplier_id' => $from, 'beneficiary_account_id' => $beneficiary_account_id,  'efd_id' => $efd_id, 'type' => $type, 'from_to_id' => $from_to_id, 'to_id' => $to, 'date' => $date, 'reference' => $reference, 'description' => $description, 'debit' => $debit*-1, 'payment_type' => $payment_type],
                ['bank_id' => $bank_id,'supplier_id' => $to,  'beneficiary_account_id' => $beneficiary_account_id, 'to_id' => $from, 'type' => $type,  'from_to_id' => $to.$from, 'efd_id' => $efd_id, 'date' => $date, 'reference' => "$reference 1",'description' => $description, 'debit' => $debit, 'payment_type' => $payment_type]
            ]);
        }
       return Redirect::back();
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
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function show(BankReconciliation $bankReconciliation)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function edit(BankReconciliation $bankReconciliation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, BankReconciliation $bankReconciliation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\BankReconciliation  $bankReconciliation
     * @return \Illuminate\Http\Response
     */
    public function destroy(BankReconciliation $bankReconciliation)
    {
        //
    }

    public function getTransferredBalance(Request $request){
        $efd_id = $request->input('efd_id');
        $date = $request->input('date');
        $supplier_from = $request->input('supplier_from');
        $balance = BankReconciliation::select(DB::raw("SUM(debit) AS total"))->where('date',$date)->where('efd_id',$efd_id)->where('supplier_id',$supplier_from)->get()->first()['total'] ?? 0;
            $balance_arr[] = array("cash_available" => $balance);
        echo json_encode($balance_arr);

    }
}
