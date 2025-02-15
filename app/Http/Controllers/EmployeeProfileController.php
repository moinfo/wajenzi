<?php

namespace App\Http\Controllers;

use App\Models\AdvanceSalary;
use App\Models\AssetProperty;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\StaffSalary;
use App\Models\User;
use App\Models\Warning;
use App\Models\WorkPermit;
use Illuminate\Http\Request;

class EmployeeProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $start_date = $request->input('start_date') ?? date('Y-01-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $staff_id = $request->input('staff_id') ?? 1;
        $start_date_year = date('Y').'-'.'01'.'-'.'01';
//        $start_date_year = '2022-01-01';
        $loan_balance = Loan::getTotalStaffLoanBalance($end_date,$staff_id);
        $month = date('m');
        $allowance = Staff::getStaffAllowance($staff_id,$month);
        $gross_pay = \App\Models\Staff::getStaffGrossPay($staff_id,$month);
        $pension = \App\Models\Staff::getStaffDeduction($staff_id,'NSSF');
        $health = \App\Models\Staff::getStaffDeduction($staff_id,'NHIF');
        $paye = \App\Models\Staff::getStaffDeduction($staff_id,'PAYE');
        $loan_deduction = \App\Models\Staff::getStaffLoanDeductionForCurrentLoan($staff_id);
        $account_number = \App\Models\Staff::getStaffBankDetails($staff_id)->account_number ?? 0;
        $basic_salary = \App\Models\Staff::getStaffSalary($staff_id);
        $advance_salary = \App\Models\Staff::getStaffAdvanceSalary($staff_id,$start_date,$end_date);
        $check_if_staff_has_loan = \App\Models\Staff::isStaffHasLoan($staff_id);
        $loan = \App\Models\Staff::getStaffLoan($staff_id);
        if($check_if_staff_has_loan){
            $current_loan = $loan;
            $current_loan_deduction = $loan_deduction;
        }else{
            $current_loan = 0;
            $current_loan_deduction = 0;
        }
        if($pension['nature'] == 'GROSS'){
            $employee_pension_amount = $gross_pay * ($pension['employee_percentage']/100);
        }else{
            $employee_pension_amount = $basic_salary * ($pension['employee_percentage']/100);
        }

        if($health['nature'] == 'GROSS'){
            $employee_health_amount = $gross_pay * ($health['employee_percentage']/100);
        }else{
            $employee_health_amount = $basic_salary * ($health['employee_percentage']/100);
        }
        $taxable = $gross_pay - ($employee_pension_amount+$employee_health_amount);
        $total_deduction = $employee_pension_amount+$employee_health_amount+$loan_deduction+$advance_salary;
        $net = $taxable - ($advance_salary+$current_loan_deduction);
        $loan_histories = Loan::where('staff_id',$staff_id)->where('status','APPROVED')->whereBetween('date',[$start_date,$end_date])->groupBy('date')->get();
        $advance_salaries = AdvanceSalary::where('staff_id',$staff_id)->where('status','APPROVED')->whereBetween('date',[$start_date,$end_date])->groupBy('date')->get();
//        $attendances = WorkPermit::where('user_id',$staff_id)->whereBetween('start_date',[$start_date,$end_date])->groupBy('start_date')->get();
//        $leave_requests = LeaveRequest::where('staff_id',$staff_id)->where('status','APPROVED')->whereBetween('start_date',[$start_date,$end_date])->groupBy('start_date')->get();
        $payrolls = Payroll::where('status','APPROVED')->whereBetween('submitted_date',[$start_date,$end_date])->groupBy('submitted_date')->get();
        $assets = AssetProperty::select('asset_properties.*','asset_properties.name as asset_proper','assets.name as asset_name')->join('assets','assets.id','=','asset_properties.asset_id')->where('user_id',$staff_id)->get();

        $data = [
            'staffs' => User::all(),
            'employee' => User::find($staff_id),
//            'warnings' => Warning::where('employee_id',$staff_id)->get(),
            'basic_salary' => StaffSalary::staffSalary($staff_id),
            'loan_balance' => $loan_balance,
            'allowance' => $allowance,
            'payrolls' => $payrolls,
            'gross_pay' => $gross_pay,
            'assets' => $assets,
            'account_number' => $account_number,
            'total_deduction' => $total_deduction,
            'loan_histories' => $loan_histories,
            'advance_salaries' => $advance_salaries,
//            'attendances' => $attendances,
//            'leave_requests' => $leave_requests,
            'net' => $net,
            'staff_id' => $staff_id,
            ];
        return view('pages.employee_profile.employee_profile')->with($data);
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
     * @param  \App\Models\EmployeeProfile  $employeeProfile
     * @return \Illuminate\Http\Response
     */
    public function show(EmployeeProfile $employeeProfile)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\EmployeeProfile  $employeeProfile
     * @return \Illuminate\Http\Response
     */
    public function edit(EmployeeProfile $employeeProfile)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmployeeProfile  $employeeProfile
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmployeeProfile $employeeProfile)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmployeeProfile  $employeeProfile
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmployeeProfile $employeeProfile)
    {
        //
    }
}
