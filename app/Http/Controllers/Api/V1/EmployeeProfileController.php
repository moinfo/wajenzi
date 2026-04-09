<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdvanceSalary;
use App\Models\AssetProperty;
use App\Models\Loan;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\StaffSalary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EmployeeProfileController extends Controller
{
    /**
     * List active staff for the employee selector dropdown.
     */
    public function staffList(Request $request): JsonResponse
    {
        $hasSystemId = Schema::hasColumn('users', 'system_id');

        $staffQuery = User::where('status', 'ACTIVE')
            ->where('type', 'STAFF')
            ->select('id', 'name', 'employee_number', 'designation', 'department_id');

        if ($hasSystemId) {
            $staffQuery->addSelect('system_id')
                ->with(['system:id,name']);
        }

        $staff = $staffQuery
            ->with(['department:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'employee_number' => $u->employee_number,
                'designation' => $u->designation,
                'department' => $u->department->name ?? null,
                'system' => $hasSystemId ? ($u->system->name ?? null) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $authUser = $request->user();
        // Allow selecting another staff member via query param
        $staffId = $request->input('staff_id', $authUser->id);
        $user = $staffId == $authUser->id ? $authUser : User::findOrFail($staffId);

        $month = date('m');
        $startDate = $request->input('start_date', date('Y-01-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        // Load relationships
        $user->load(['department', 'system']);

        // Financial summary
        $basicSalary = StaffSalary::staffSalary($staffId);
        $grossPay = Staff::getStaffGrossPay($staffId, $month);
        $allowance = Staff::getStaffAllowance($staffId, $month);
        $bankDetails = Staff::getStaffBankDetails($staffId);
        $loanBalance = Loan::getTotalStaffLoanBalance($endDate, $staffId);

        // Deductions
        $pension = Staff::getStaffDeduction($staffId, 'NSSF') ?? ['nature' => '', 'employee_percentage' => 0];
        $health = Staff::getStaffDeduction($staffId, 'NHIF') ?? ['nature' => '', 'employee_percentage' => 0];

        $pensionAmount = (!empty($pension) && ($pension['nature'] ?? '') == 'GROSS')
            ? $grossPay * (($pension['employee_percentage'] ?? 0) / 100)
            : $basicSalary * (($pension['employee_percentage'] ?? 0) / 100);

        $healthAmount = (!empty($health) && ($health['nature'] ?? '') == 'GROSS')
            ? $grossPay * (($health['employee_percentage'] ?? 0) / 100)
            : $basicSalary * (($health['employee_percentage'] ?? 0) / 100);

        $hasLoan = Staff::isStaffHasLoan($staffId);
        $loanDeduction = $hasLoan ? Staff::getStaffLoanDeductionForCurrentLoan($staffId) : 0;
        $advanceSalaryAmount = Staff::getStaffAdvanceSalary($staffId, $startDate, $endDate);
        $totalDeduction = $pensionAmount + $healthAmount + $loanDeduction + $advanceSalaryAmount;
        $taxable = $grossPay - ($pensionAmount + $healthAmount);
        $net = $taxable - ($advanceSalaryAmount + $loanDeduction);

        // Loan history
        $loanHistories = Loan::where('staff_id', $staffId)
            ->where('status', 'APPROVED')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($loan) => [
                'id' => $loan->id,
                'date' => $loan->date,
                'deduction' => (float) $loan->deduction,
                'amount' => (float) $loan->amount,
            ]);
        $totalLoan = (float) $loanHistories->sum('amount');

        // Advance salary history
        $advanceSalaries = AdvanceSalary::where('staff_id', $staffId)
            ->where('status', 'APPROVED')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($adv) => [
                'id' => $adv->id,
                'date' => $adv->date,
                'description' => $adv->description,
                'amount' => (float) $adv->amount,
            ]);
        $totalAdvanceSalary = (float) $advanceSalaries->sum('amount');

        // Payroll history (APPROVED payrolls in date range)
        $payrolls = Payroll::where('status', 'APPROVED')
            ->whereBetween('submitted_date', [$startDate, $endDate])
            ->orderBy('submitted_date', 'desc')
            ->get()
            ->map(function ($payroll) use ($staffId) {
                $pid = $payroll->id;
                $salary = Staff::getStaffSalaryPaid($staffId, $pid);
                $allowance = Staff::getStaffAllowancePaid($staffId, $pid);
                $gross = Staff::getStaffGrossPayPaid($staffId, $pid);
                $nssf = Staff::getStaffDeductionPaid($staffId, $pid, 2, 'employee_deduction_amount');
                $paye = Staff::getStaffDeductionPaid($staffId, $pid, 1, 'employee_deduction_amount');
                $advance = Staff::getStaffAdvanceSalaryPaid($staffId, $pid);
                $loan = Staff::getStaffLoanPaid($staffId, $pid);
                $loanDeduction = Staff::getStaffLoanDeductionPaid($staffId, $pid);
                $loanBalance = Staff::getStaffLoanBalancePaid($staffId, $pid);
                $net = Staff::getStaffNetPaid($staffId, $pid);

                return [
                    'id' => $payroll->id,
                    'month' => (int) $payroll->month,
                    'year' => (int) $payroll->year,
                    'period' => date('F', strtotime("01-{$payroll->month}-{$payroll->year}")) . ' ' . $payroll->year,
                    'salary' => (float) $salary,
                    'allowance' => (float) $allowance,
                    'gross' => (float) $gross,
                    'nssf' => (float) $nssf,
                    'paye' => (float) $paye,
                    'advance' => (float) $advance,
                    'loan' => (float) $loan,
                    'loan_deduction' => (float) $loanDeduction,
                    'loan_balance' => (float) $loanBalance,
                    'net' => (float) $net,
                    'slip_url' => route('employee_salary_slip', [
                        'staff_id' => $staffId,
                        'month' => $payroll->month,
                        'year' => $payroll->year,
                    ]),
                ];
            });
        $payrollTotals = [
            'salary' => (float) $payrolls->sum('salary'),
            'allowance' => (float) $payrolls->sum('allowance'),
            'gross' => (float) $payrolls->sum('gross'),
            'nssf' => (float) $payrolls->sum('nssf'),
            'paye' => (float) $payrolls->sum('paye'),
            'advance' => (float) $payrolls->sum('advance'),
            'loan' => (float) $payrolls->sum('loan'),
            'loan_deduction' => (float) $payrolls->sum('loan_deduction'),
            'loan_balance' => (float) $payrolls->sum('loan_balance'),
            'net' => (float) $payrolls->sum('net'),
        ];

        // Assets
        $assets = AssetProperty::select('asset_properties.*', 'asset_properties.name as asset_proper', 'assets.name as asset_name')
            ->join('assets', 'assets.id', '=', 'asset_properties.asset_id')
            ->where('user_id', $staffId)
            ->get()
            ->map(fn($asset) => [
                'id' => $asset->id,
                'name' => $asset->asset_proper,
                'description' => $asset->description,
                'asset' => $asset->asset_name,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'personal_info' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_number' => $user->employee_number,
                    'email' => $user->email,
                    'designation' => $user->designation,
                    'gender' => $user->gender,
                    'dob' => $user->dob,
                    'phone' => $user->phone_number,
                    'address' => $user->address,
                    'department' => $user->department->name ?? null,
                    'system' => $user->system->name ?? null,
                    'national_id' => $user->national_id,
                    'tin' => $user->tin,
                    'status' => $user->status,
                    'employment_date' => $user->employment_date ?? optional($user->created_at)->toDateString(),
                    'status_updated_at' => optional($user->updated_at)->format('Y-m-d H:i:s'),
                    'profile_photo' => $user->profile,
                    'account_number' => $bankDetails->account_number ?? null,
                ],
                'financial_summary' => [
                    'basic_salary' => (float) $basicSalary,
                    'gross_pay' => (float) $grossPay,
                    'allowances' => (float) $allowance,
                    'total_deductions' => (float) $totalDeduction,
                    'taxable' => (float) $taxable,
                    'net_pay' => (float) $net,
                    'loan_balance' => (float) $loanBalance,
                ],
                'loan_history' => $loanHistories,
                'loan_history_summary' => [
                    'total_loan' => $totalLoan,
                ],
                'advance_salaries' => $advanceSalaries,
                'advance_salary_summary' => [
                    'total_advance_salary' => $totalAdvanceSalary,
                ],
                'payroll_history' => $payrolls,
                'payroll_summary' => $payrollTotals,
                'assets' => $assets,
            ],
        ]);
    }
}
