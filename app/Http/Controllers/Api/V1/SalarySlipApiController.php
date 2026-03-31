<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Allowance;
use App\Models\Deduction;
use App\Models\Payroll;
use App\Models\PayrollNetSalary;
use App\Models\Staff;
use App\Models\StaffBankDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalarySlipApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $staffs = collect(User::onlyStaffs())
            ->sortBy('name')
            ->values()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'employee_number' => $user->employee_number,
                'designation' => $user->designation,
            ]);

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');
        
        $months = collect(range(1, $currentMonth))->map(fn (int $month) => [
            'id' => $month,
            'name' => date('F', strtotime(sprintf('2000-%02d-01', $month))),
        ])->values();

        $years = collect(range($currentYear - 2, $currentYear))->map(fn (int $year) => [
            'id' => $year,
            'name' => (string) $year,
        ])->values();

        $approvedPayrolls = Payroll::where('status', 'APPROVED')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get(['id', 'payroll_number', 'year', 'month'])
            ->map(fn (Payroll $p) => [
                'id' => $p->id,
                'payroll_number' => $p->payroll_number,
                'year' => $p->year,
                'month' => $p->month,
                'month_name' => date('F', strtotime(sprintf('2000-%02d-01', $p->month))),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'staffs' => $staffs,
                'months' => $months,
                'years' => $years,
                'approved_payrolls' => $approvedPayrolls,
            ],
        ]);
    }

    public function getPayslip(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        $staffId = $request->input('staff_id');
        $month = $request->input('month');
        $year = $request->input('year');

        $payroll = Payroll::getThisPayrollApproved($month, $year);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => "No approved payroll found for " . date('F', strtotime(sprintf('2000-%02d-01', $month))) . " $year",
            ], 404);
        }

        $payrollId = $payroll->id;
        $employee = User::find($staffId);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found',
            ], 404);
        }

        $bankDetail = StaffBankDetail::with('bank:id,name')
            ->where('staff_id', $staffId)
            ->first();

        $basicSalary = Staff::getStaffSalaryPaid($staffId, $payrollId) ?? 0;
        $grossSalary = Staff::getStaffGrossPayPaid($staffId, $payrollId) ?? 0;
        $netSalary = Staff::getStaffNetPaid($staffId, $payrollId) ?? 0;
        $advanceSalary = Staff::getStaffAdvanceSalaryPaid($staffId, $payrollId) ?? 0;
        $loanBalance = Staff::getStaffLoanBalancePaid($staffId, $payrollId) ?? 0;
        $loanDeduction = Staff::getStaffLoanDeductionPaid($staffId, $payrollId) ?? 0;
        $currentLoan = Staff::getStaffLoanPaid($staffId, $payrollId) ?? 0;

        $allowances = Allowance::select('allowance_subscriptions.amount as amount', 'allowances.name as allowance_name', 'allowances.allowance_type as allowance_type')
            ->join('allowance_subscriptions', 'allowance_subscriptions.allowance_id', '=', 'allowances.id')
            ->where('allowance_subscriptions.staff_id', $staffId)
            ->get()
            ->map(function ($allowance) use ($month) {
                $allowanceAmount = Allowance::getAllowanceAmountPerType($allowance->allowance_type, $allowance->amount, $month) ?? 0;
                return [
                    'name' => strtoupper($allowance->allowance_name),
                    'amount' => $allowanceAmount,
                ];
            })
            ->filter(fn ($item) => $item['amount'] > 0)
            ->values();

        $deductions = Deduction::select(
                'deduction_settings.employee_percentage as employee_deducted_percentage',
                'deductions.id as deduction_id',
                'deductions.name as keyword'
            )
            ->join('deduction_settings', 'deduction_settings.deduction_id', '=', 'deductions.id')
            ->join('deduction_subscriptions', 'deduction_subscriptions.deduction_id', 'deductions.id')
            ->where('deductions.id', '!=', 1)
            ->where('deduction_subscriptions.staff_id', $staffId)
            ->get()
            ->map(function ($deduction) use ($staffId, $payrollId) {
                $deductedAmount = Staff::getStaffDeductionPaid($staffId, $payrollId, $deduction->deduction_id, 'employee_deduction_amount') ?? 0;
                $percentage = $deduction->employee_deducted_percentage > 0 ? "({$deduction->employee_deducted_percentage}%)" : '';
                return [
                    'name' => strtoupper($deduction->keyword) . $percentage,
                    'amount' => $deductedAmount,
                ];
            })
            ->filter(fn ($item) => $item['amount'] > 0)
            ->values();

        $payeeAmount = Staff::getStaffDeductionPaid($staffId, $payrollId, 1, 'employee_deduction_amount') ?? 0;
        $totalDeductions = $payeeAmount + $deductions->sum('amount') + $advanceSalary + $loanDeduction;

        return response()->json([
            'success' => true,
            'data' => [
                'payroll' => [
                    'id' => $payroll->id,
                    'payroll_number' => $payroll->payroll_number,
                    'month' => $payroll->month,
                    'month_name' => date('F', strtotime(sprintf('2000-%02d-01', $payroll->month))),
                    'year' => $payroll->year,
                ],
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'employee_number' => $employee->employee_number,
                    'designation' => $employee->designation,
                ],
                'bank' => [
                    'name' => $bankDetail?->bank?->name,
                    'account_number' => $bankDetail?->account_number,
                ],
                'basic_salary' => $basicSalary,
                'allowances' => $allowances,
                'gross_salary' => $grossSalary,
                'deductions' => $deductions,
                'payee' => [
                    'name' => 'PAYEE',
                    'amount' => $payeeAmount,
                ],
                'advance_salary' => $advanceSalary,
                'loan_deduction' => $loanDeduction,
                'current_loan' => $currentLoan,
                'loan_balance' => $loanBalance,
                'total_deductions' => $totalDeductions,
                'net_salary' => $netSalary,
            ],
        ]);
    }

    public function listStaffPayslips(Request $request): JsonResponse
    {
        $request->validate([
            'staff_id' => 'required|exists:users,id',
        ]);

        $staffId = $request->input('staff_id');
        
        $staff = User::find($staffId);
        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
            ], 404);
        }

        $payrollIds = PayrollNetSalary::where('staff_id', $staffId)
            ->where('status', 'APPROVED')
            ->pluck('payroll_id')
            ->toArray();

        $payrolls = Payroll::whereIn('id', $payrollIds)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function ($payroll) use ($staffId) {
                $netSalary = Staff::getStaffNetPaid($staffId, $payroll->id) ?? 0;
                return [
                    'id' => $payroll->id,
                    'payroll_number' => $payroll->payroll_number,
                    'month' => $payroll->month,
                    'month_name' => date('F', strtotime(sprintf('2000-%02d-01', $payroll->month))),
                    'year' => $payroll->year,
                    'net_salary' => $netSalary,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'staff' => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'employee_number' => $staff->employee_number,
                ],
                'payslips' => $payrolls,
            ],
        ]);
    }
}
