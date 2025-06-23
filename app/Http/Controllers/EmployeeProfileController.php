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
        // Get input parameters with defaults
        $start_date = $request->input('start_date', date('Y-01-01'));
        $end_date = $request->input('end_date', date('Y-m-d'));
        $staff_id = $request->input('staff_id', 1);
        $month = date('m');

        // Get staff object once to avoid repeated database queries
        $staff = User::findOrFail($staff_id);

        // Get staff financial information
        $basic_salary = \App\Models\Staff::getStaffSalary($staff_id);
        $gross_pay = \App\Models\Staff::getStaffGrossPay($staff_id, $month);
        $allowance = Staff::getStaffAllowance($staff_id, $month);
        $account_number = \App\Models\Staff::getStaffBankDetails($staff_id)->account_number ?? 0;
        $advance_salary = \App\Models\Staff::getStaffAdvanceSalary($staff_id, $start_date, $end_date);

        // Get loan information
        $check_if_staff_has_loan = \App\Models\Staff::isStaffHasLoan($staff_id);
        $loan_balance = Loan::getTotalStaffLoanBalance($end_date, $staff_id);
        $loan = \App\Models\Staff::getStaffLoan($staff_id);
        $loan_deduction = \App\Models\Staff::getStaffLoanDeductionForCurrentLoan($staff_id);

        // Set current loan values based on whether staff has a loan
        $current_loan = $check_if_staff_has_loan ? $loan : 0;
        $current_loan_deduction = $check_if_staff_has_loan ? $loan_deduction : 0;

        // Get deduction information
        $pension = \App\Models\Staff::getStaffDeduction($staff_id, 'NSSF') ?? ['nature' => '', 'employee_percentage' => 0];
        $health = \App\Models\Staff::getStaffDeduction($staff_id, 'NHIF') ?? ['nature' => '', 'employee_percentage' => 0];

        // Calculate pension amount based on nature
        $employee_pension_amount = (!empty($pension) && $pension['nature'] == 'GROSS')
            ? $gross_pay * ($pension['employee_percentage'] / 100)
            : $basic_salary * ($pension['employee_percentage'] / 100);

        // Calculate health amount based on nature
        $employee_health_amount = (!empty($health) && $health['nature'] == 'GROSS')
            ? $gross_pay * ($health['employee_percentage'] / 100)
            : $basic_salary * ($health['employee_percentage'] / 100);

        // Calculate final amounts
        $taxable = $gross_pay - ($employee_pension_amount + $employee_health_amount);
        $total_deduction = $employee_pension_amount + $employee_health_amount + $loan_deduction + $advance_salary;
        $net = $taxable - ($advance_salary + $current_loan_deduction);

        // Get historical data
        $loan_histories = Loan::where('staff_id', $staff_id)
            ->where('status', 'APPROVED')
            ->whereBetween('date', [$start_date, $end_date])
            ->groupBy('date')
            ->get();

        $advance_salaries = AdvanceSalary::where('staff_id', $staff_id)
            ->where('status', 'APPROVED')
            ->whereBetween('date', [$start_date, $end_date])
            ->groupBy('date')
            ->get();

        $payrolls = Payroll::where('status', 'APPROVED')
            ->whereBetween('submitted_date', [$start_date, $end_date])
            ->groupBy('submitted_date')
            ->get();

        $assets = AssetProperty::select('asset_properties.*', 'asset_properties.name as asset_proper', 'assets.name as asset_name')
            ->join('assets', 'assets.id', '=', 'asset_properties.asset_id')
            ->where('user_id', $staff_id)
            ->get();

        // Prepare view data
        $data = [
            'staffs' => User::all(),
            'employee' => $staff,
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
