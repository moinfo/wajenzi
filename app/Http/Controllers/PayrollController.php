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
        $payroll_data = [
          'document_number' => $request->input('document_number'),
          'payroll_number' => $request->input('payroll_number'),
          'year' => $request->input('year'),
          'month' => $request->input('month'),
          'created_by_id' => $request->input('created_by_id'),
          'submitted_date' => date("$year-$month-t"),
        ];


         Payroll::insert($payroll_data);

        $payrolls_data = Payroll::latest('id')->first();
        $last_payroll_id = $payrolls_data->id;



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




            $salary[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $request->basic_salary[$i] ?? 0,
                'staff_salary_id' => $request->staff_salary_id[$i] ?? 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $allowance[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'allowance_id' => 1, //todo must pass allowance id
                'amount' => $request->allowance[$i] ?? 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $payee[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'deduction_id' => 1,
                'deduction_source' => $request->taxable[$i] ?? 0,
                'employee_deduction_amount' => $employee_deducted_amount_payee,
                'employer_deduction_amount' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $nssf[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'deduction_id' => 2,
                'deduction_source' => $gross_pay,
                'employee_deduction_amount' => $employee_deducted_amount_pension,
                'employer_deduction_amount' => $employer_deducted_amount_pension,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $wcf[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'deduction_id' => 3,
                'deduction_source' => $taxable,
                'employee_deduction_amount' => $employee_deducted_amount_wcf,
                'employer_deduction_amount' => $employer_deducted_amount_wcf,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];$heslb[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'deduction_id' => 4,
                'deduction_source' => $taxable,
                'employee_deduction_amount' => $employee_deducted_amount_heslb,
                'employer_deduction_amount' => $employer_deducted_amount_heslb,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];$sdl[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'deduction_id' => 5,
                'deduction_source' => $taxable,
                'employee_deduction_amount' => $employee_deducted_amount_sdl,
                'employer_deduction_amount' => $employer_deducted_amount_sdl,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];$nhif[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'deduction_id' => 6,
                'deduction_source' => $taxable,
                'employee_deduction_amount' => $employee_deducted_amount_health,
                'employer_deduction_amount' => $employer_deducted_amount_health,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $taxable_pay[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $taxable,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $gross[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $gross_pay,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $advance[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $advance_salary,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $total_loan[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $loan,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $total_loan_deduction[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $loan_deduction,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $total_loan_balance[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $loan_balance,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $total_net[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $net,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $total_adjustment[] = [
                'payroll_id' => $last_payroll_id,
                'staff_id' => $request->staff_id[$i],
                'amount' => $adjustment,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

        }
//
        PayrollSalary::insert($salary);
        PayrollAllowance::insert($allowance);
        PayrollDeduction::insert($payee);
        PayrollDeduction::insert($nssf);
        PayrollDeduction::insert($wcf);
        PayrollDeduction::insert($heslb);
        PayrollDeduction::insert($sdl);
        PayrollDeduction::insert($nhif);
        PayrollTaxable::insert($taxable_pay);
        PayrollGrossPay::insert($gross);
        PayrollAdvanceSalary::insert($advance);
        PayrollLoan::insert($total_loan);
        PayrollLoanDeduction::insert($total_loan_deduction);
        PayrollLoanBalance::insert($total_loan_balance);
        PayrollNetSalary::insert($total_net);
        PayrollAdjustment::insert($total_adjustment);
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
