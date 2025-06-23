<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\AssetProperty;
use App\Models\FinancialCharge;
use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\PayrollAdvanceSalary;
use App\Models\PayrollAllowance;
use App\Models\PayrollDeduction;
use App\Models\PayrollGrossPay;
use App\Models\PayrollLoan;
use App\Models\PayrollLoanBalance;
use App\Models\PayrollLoanDeduction;
use App\Models\PayrollNetSalary;
use App\Models\PayrollSalary;
use App\Models\PayrollTaxable;
use App\Models\PayrollType;
use App\Models\Staff;
use App\Models\StaffBankDetail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Payroll')) {
            return back();
        }
        $staffs = Staff::onlyStaffs();
        $payroll = Payroll::all();

        $data = [
            'payroll' => $payroll,
             'staffs' => $staffs
        ];
        return view('pages.payroll.payroll_index')->with($data);
    }
    public function crdb_bank_file(Request $request)
    {
        $start_date = $request->start_date ?? date('Y-m-01');
        $end_date = $request->end_date ?? date('Y-m-t');

        $staffs = StaffBankDetail::where('bank_id', 1)->get();

        // Get payrolls within date range if form is submitted
        $payrolls = [];
//        if ($request->has('submit')) {
            $payrolls = PayrollNetSalary::getPayrollList($start_date, $end_date)
                ->where('status', 'APPROVED')
                ->pluck('id')
                ->toArray();
//        }

        $data = [
            'staffs' => $staffs,
            'payrolls' => $payrolls,
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        return view('pages.payroll.crdb_bank_file')->with($data);
    }

    public function salary_slips(Request $request)
    {

        $staffs = Staff::onlyStaffs();
        $payroll = Payroll::all();

        $data = [
            'payroll' => $payroll,
             'staffs' => $staffs
        ];
        return view('pages.payroll.salary_slips')->with($data);
    }

    public function employee_salary_slip(Request $request,$staff_id,$month,$year)
    {

        $this_year = $year;
        $this_month = $month;
        $this_employee = $staff_id;
        $payroll = \App\Models\Payroll::getThisPayrollApproved($this_month,$this_year);
        $payroll_id = $payroll['id'];
        $employee = \App\Models\User::find($this_employee);
        $employee_bank_details = \App\Models\StaffBankDetail::where('staff_id',$this_employee)->get()->first();
        $basic_salary = \App\Models\Staff::getStaffSalaryPaid($this_employee,$payroll_id);
        $total_deduction = 0;
        $gross_salary = \App\Models\Staff::getStaffGrossPayPaid($this_employee,$payroll_id) ?? 0;
        $net_salary = \App\Models\Staff::getStaffNetPaid($staff_id,$payroll_id) ?? 0;
        $employee_deducted_amount_payee = \App\Models\Staff::getStaffDeductionPaid($staff_id, $payroll_id,1,'employee_deduction_amount') ?? 0;


        $advance_salary = \App\Models\Staff::getStaffAdvanceSalaryPaid($staff_id,$payroll_id) ?? 0;
        $loan_balance = \App\Models\Staff::getStaffLoanBalancePaid($staff_id,$payroll_id) ?? 0;
        $current_loan = \App\Models\Staff::getStaffLoanPaid($staff_id,$payroll_id) ?? 0;
        $loan_deduction = \App\Models\Staff::getStaffLoanDeductionPaid($staff_id,$payroll_id) ?? 0;
        $taxable = \App\Models\Staff::getStaffTaxablePaid($staff_id,$payroll_id) ?? 0;
        $data = [
            'payroll' => $payroll,
            'employee' => $employee,
            'employee_bank_details' => $employee_bank_details,
            'basic_salary' => $basic_salary,
            'total_deduction' => $total_deduction,
            'gross_salary' => $gross_salary,
            'employee_deducted_amount_payee' => $employee_deducted_amount_payee,
            'net_salary' => $net_salary,
            'advance_salary' => $advance_salary,
            'loan_balance' => $loan_balance,
            'current_loan' => $current_loan,
            'loan_deduction' => $loan_deduction,
            'taxable' => $taxable,
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll_id' => $payroll_id,
             'staff_id' => $staff_id
        ];
        return view('pages.payroll.employee_salary_slip')->with($data);
    }

    public function create_payroll(Request $request){
        $year = $request->input('year');
        $month = $request->input('month');

        // Create and save the payroll record using save() instead of insert()
        $payroll = new Payroll();
        $payroll->document_number = $request->input('document_number');
        $payroll->payroll_number = $request->input('payroll_number');
        $payroll->year = $request->input('year');
        $payroll->month = $request->input('month');
        $payroll->created_by_id = $request->input('created_by_id');
        $payroll->submitted_date = date("$year-$month-t");
        $payroll->save();

        // Get the created payroll ID
        $last_payroll_id = $payroll->id;

        for ($i = 0; $i < count($request->staff_id); $i++) {
            $taxable = $request->taxable[$i] ?? 0;
            $employee_deducted_amount_payee = $request->employee_deducted_amount_payee[$i] ?? 0;
            $gross_pay = $request->gross_pay[$i] ?? 0;
            $employer_deducted_amount_pension = $request->employer_deducted_amount_pension[$i] ?? 0;
            $employee_deducted_amount_pension = $request->employee_deducted_amount_pension[$i] ?? 0;
            $employee_deducted_amount_wcf = $request->employee_deducted_amount_wcf[$i] ?? 0;
            $employer_deducted_amount_wcf = $request->employer_deducted_amount_wcf[$i] ?? 0;
            $employee_deducted_amount_heslb = $request->employee_deducted_amount_heslb[$i] ?? 0;
            $employer_deducted_amount_heslb = $request->employer_deducted_amount_heslb[$i] ?? 0;
            $employee_deducted_amount_sdl = $request->employee_deducted_amount_sdl[$i] ?? 0;
            $employer_deducted_amount_sdl = $request->employer_deducted_amount_sdl[$i] ?? 0;
            $employee_deducted_amount_health = $request->employee_deducted_amount_health[$i] ?? 0;
            $employer_deducted_amount_health = $request->employer_deducted_amount_health[$i] ?? 0;
            $loan = $request->current_loan[$i] ?? 0;
            $loan_deduction = $request->current_loan_deduction[$i] ?? 0;
            $loan_balance = $request->loan_balance[$i] ?? 0;
            $adjustment = $request->adjustment[$i] ?? 0;
            $net = $request->net[$i] ?? 0;
            $advance_salary = $request->advance_salary[$i] ?? 0;

            // Save salary
            $salaryObj = new PayrollSalary();
            $salaryObj->payroll_id = $last_payroll_id;
            $salaryObj->staff_id = $request->staff_id[$i];
            $salaryObj->amount = $request->basic_salary[$i] ?? 0;
            $salaryObj->staff_salary_id = $request->staff_salary_id[$i] ?? 0;
            $salaryObj->save();

            // Save allowance
            $allowanceObj = new PayrollAllowance();
            $allowanceObj->payroll_id = $last_payroll_id;
            $allowanceObj->staff_id = $request->staff_id[$i];
            $allowanceObj->allowance_id = 1; // todo must pass allowance id
            $allowanceObj->amount = $request->allowance[$i] ?? 0;
            $allowanceObj->save();

            // Save PAYEE deduction
            $payeeObj = new PayrollDeduction();
            $payeeObj->payroll_id = $last_payroll_id;
            $payeeObj->staff_id = $request->staff_id[$i];
            $payeeObj->deduction_id = 1;
            $payeeObj->deduction_source = $taxable;
            $payeeObj->employee_deduction_amount = $employee_deducted_amount_payee;
            $payeeObj->employer_deduction_amount = 0;
            $payeeObj->save();

            // Save NSSF deduction
            $nssfObj = new PayrollDeduction();
            $nssfObj->payroll_id = $last_payroll_id;
            $nssfObj->staff_id = $request->staff_id[$i];
            $nssfObj->deduction_id = 2;
            $nssfObj->deduction_source = $gross_pay;
            $nssfObj->employee_deduction_amount = $employee_deducted_amount_pension;
            $nssfObj->employer_deduction_amount = $employer_deducted_amount_pension;
            $nssfObj->save();

            // Save WCF deduction
            $wcfObj = new PayrollDeduction();
            $wcfObj->payroll_id = $last_payroll_id;
            $wcfObj->staff_id = $request->staff_id[$i];
            $wcfObj->deduction_id = 3;
            $wcfObj->deduction_source = $taxable;
            $wcfObj->employee_deduction_amount = $employee_deducted_amount_wcf;
            $wcfObj->employer_deduction_amount = $employer_deducted_amount_wcf;
            $wcfObj->save();

            // Save HESLB deduction
            $heslbObj = new PayrollDeduction();
            $heslbObj->payroll_id = $last_payroll_id;
            $heslbObj->staff_id = $request->staff_id[$i];
            $heslbObj->deduction_id = 4;
            $heslbObj->deduction_source = $taxable;
            $heslbObj->employee_deduction_amount = $employee_deducted_amount_heslb;
            $heslbObj->employer_deduction_amount = $employer_deducted_amount_heslb;
            $heslbObj->save();

            // Save SDL deduction
            $sdlObj = new PayrollDeduction();
            $sdlObj->payroll_id = $last_payroll_id;
            $sdlObj->staff_id = $request->staff_id[$i];
            $sdlObj->deduction_id = 5;
            $sdlObj->deduction_source = $taxable;
            $sdlObj->employee_deduction_amount = $employee_deducted_amount_sdl;
            $sdlObj->employer_deduction_amount = $employer_deducted_amount_sdl;
            $sdlObj->save();

            // Save NHIF deduction
            $nhifObj = new PayrollDeduction();
            $nhifObj->payroll_id = $last_payroll_id;
            $nhifObj->staff_id = $request->staff_id[$i];
            $nhifObj->deduction_id = 6;
            $nhifObj->deduction_source = $taxable;
            $nhifObj->employee_deduction_amount = $employee_deducted_amount_health;
            $nhifObj->employer_deduction_amount = $employer_deducted_amount_health;
            $nhifObj->save();

            // Save taxable pay
            $taxableObj = new PayrollTaxable();
            $taxableObj->payroll_id = $last_payroll_id;
            $taxableObj->staff_id = $request->staff_id[$i];
            $taxableObj->amount = $taxable;
            $taxableObj->save();

            // Save gross pay
            $grossObj = new PayrollGrossPay();
            $grossObj->payroll_id = $last_payroll_id;
            $grossObj->staff_id = $request->staff_id[$i];
            $grossObj->amount = $gross_pay;
            $grossObj->save();

            // Save advance salary
            $advanceObj = new PayrollAdvanceSalary();
            $advanceObj->payroll_id = $last_payroll_id;
            $advanceObj->staff_id = $request->staff_id[$i];
            $advanceObj->amount = $advance_salary;
            $advanceObj->save();

            // Save loan
            $loanObj = new PayrollLoan();
            $loanObj->payroll_id = $last_payroll_id;
            $loanObj->staff_id = $request->staff_id[$i];
            $loanObj->amount = $loan;
            $loanObj->save();

            // Save loan deduction
            $loanDeductionObj = new PayrollLoanDeduction();
            $loanDeductionObj->payroll_id = $last_payroll_id;
            $loanDeductionObj->staff_id = $request->staff_id[$i];
            $loanDeductionObj->amount = $loan_deduction;
            $loanDeductionObj->save();

            // Save loan balance
            $loanBalanceObj = new PayrollLoanBalance();
            $loanBalanceObj->payroll_id = $last_payroll_id;
            $loanBalanceObj->staff_id = $request->staff_id[$i];
            $loanBalanceObj->amount = $loan_balance;
            $loanBalanceObj->save();

            // Save net salary
            $netObj = new PayrollNetSalary();
            $netObj->payroll_id = $last_payroll_id;
            $netObj->staff_id = $request->staff_id[$i];
            $netObj->amount = $net;
            $netObj->save();

            // Save adjustment
            $adjustmentObj = new PayrollAdjustment();
            $adjustmentObj->payroll_id = $last_payroll_id;
            $adjustmentObj->staff_id = $request->staff_id[$i];
            $adjustmentObj->amount = $adjustment;
            $adjustmentObj->save();
        }

        return Redirect::back();
    }
    public function payroll_administration(Request $request)
    {
        if($this->handleCrud($request, 'Payroll')) {
            return back();
        }
        $staffs = Staff::onlyStaffs();
        $payrolls = Payroll::all();

        $data = [
            'payrolls' => $payrolls,
             'staffs' => $staffs
        ];
        return view('pages.payroll.payroll_administration')->with($data);
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

    public function payroll_view($id,$document_type_id){
        $payroll = \App\Models\Payroll::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
//        $staffs = Staff::getAllStaffSalaryPaid($payroll->id);
        $payroll_types = PayrollType::all();
        $data = [
            'payroll_types' => $payroll_types,
            'payroll' => $payroll,
            'payroll_id' => $payroll->id,
//            'staffs' => $staffs,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.payroll.payroll_view')->with($data);
    }
}
