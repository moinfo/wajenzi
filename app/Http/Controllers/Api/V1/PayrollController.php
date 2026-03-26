<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function payslips(Request $request): JsonResponse
    {
        $user = $request->user();

        $payslips = DB::table('payroll_net_salaries')
            ->join('payrolls', 'payroll_net_salaries.payroll_id', '=', 'payrolls.id')
            ->where('payroll_net_salaries.staff_id', $user->id)
            ->where('payrolls.status', 'APPROVED')
            ->orderBy('payrolls.submitted_date', 'desc')
            ->select([
                'payrolls.id as id',
                'payrolls.id as payroll_id',
                'payrolls.payroll_number',
                'payrolls.month',
                'payrolls.year',
                'payrolls.submitted_date',
                'payroll_net_salaries.amount as net_salary',
            ])
            ->paginate($request->per_page ?? 12);

        $items = collect($payslips->items())->map(function ($item) use ($user) {
            return [
                'id' => (int) $item->id,
                'payroll_id' => (int) $item->payroll_id,
                'payroll_name' => $this->formatPayrollName($item->payroll_number, $item->month, $item->year),
                'submitted_date' => $item->submitted_date,
                'basic_salary' => (float) DB::table('payroll_salaries')
                    ->where('payroll_id', $item->payroll_id)
                    ->where('staff_id', $user->id)
                    ->sum('amount'),
                'allowance' => (float) DB::table('payroll_allowances')
                    ->where('payroll_id', $item->payroll_id)
                    ->where('staff_id', $user->id)
                    ->sum('amount'),
                'deduction' => (float) DB::table('payroll_deductions')
                    ->where('payroll_id', $item->payroll_id)
                    ->where('staff_id', $user->id)
                    ->sum('employee_deduction_amount'),
                'taxable' => (float) DB::table('payroll_taxables')
                    ->where('payroll_id', $item->payroll_id)
                    ->where('staff_id', $user->id)
                    ->sum('amount'),
                'paye' => (float) DB::table('payroll_deductions')
                    ->where('payroll_id', $item->payroll_id)
                    ->where('staff_id', $user->id)
                    ->where('deduction_id', 1)
                    ->sum('employee_deduction_amount'),
                'net_salary' => (float) $item->net_salary,
                'loan_deduction' => (float) DB::table('payroll_loan_deductions')
                    ->where('payroll_id', $item->payroll_id)
                    ->where('staff_id', $user->id)
                    ->sum('amount'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $payslips->currentPage(),
                'last_page' => $payslips->lastPage(),
                'per_page' => $payslips->perPage(),
                'total' => $payslips->total(),
            ],
        ]);
    }

    public function payslipDetail(int $id): JsonResponse
    {
        $user = request()->user();

        $payroll = Payroll::where('id', $id)
            ->where('status', 'APPROVED')
            ->first();

        $hasAccess = DB::table('payroll_net_salaries')
            ->where('payroll_id', $id)
            ->where('staff_id', $user->id)
            ->exists();

        if (!$payroll || !$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Payslip not found.',
            ], 404);
        }

        $allowances = DB::table('payroll_allowances')
            ->join('allowances', 'payroll_allowances.allowance_id', '=', 'allowances.id')
            ->where('payroll_allowances.payroll_id', $id)
            ->where('payroll_allowances.staff_id', $user->id)
            ->select(['allowances.name', 'payroll_allowances.amount'])
            ->get();

        $deductions = DB::table('payroll_deductions')
            ->join('deductions', 'payroll_deductions.deduction_id', '=', 'deductions.id')
            ->where('payroll_deductions.payroll_id', $id)
            ->where('payroll_deductions.staff_id', $user->id)
            ->select([
                'deductions.name',
                'payroll_deductions.employee_deduction_amount as employee_contribution',
                'payroll_deductions.employer_deduction_amount as employer_contribution',
            ])
            ->get();

        $periodStart = sprintf('%04d-%02d-01', (int) $payroll->year, (int) $payroll->month);

        return response()->json([
            'success' => true,
            'data' => [
                'payslip' => [
                    'id' => $payroll->id,
                    'payroll_id' => $payroll->id,
                    'payroll_name' => $this->formatPayrollName($payroll->payroll_number, $payroll->month, $payroll->year),
                    'submitted_date' => $payroll->submitted_date,
                    'start_date' => $periodStart,
                    'end_date' => date('Y-m-t', strtotime($periodStart)),
                    'basic_salary' => (float) DB::table('payroll_salaries')
                        ->where('payroll_id', $id)
                        ->where('staff_id', $user->id)
                        ->sum('amount'),
                    'allowance' => (float) DB::table('payroll_allowances')
                        ->where('payroll_id', $id)
                        ->where('staff_id', $user->id)
                        ->sum('amount'),
                    'deduction' => (float) DB::table('payroll_deductions')
                        ->where('payroll_id', $id)
                        ->where('staff_id', $user->id)
                        ->sum('employee_deduction_amount'),
                    'taxable' => (float) DB::table('payroll_taxables')
                        ->where('payroll_id', $id)
                        ->where('staff_id', $user->id)
                        ->sum('amount'),
                    'paye' => (float) DB::table('payroll_deductions')
                        ->where('payroll_id', $id)
                        ->where('staff_id', $user->id)
                        ->where('deduction_id', 1)
                        ->sum('employee_deduction_amount'),
                    'net_salary' => (float) DB::table('payroll_net_salaries')
                        ->where('payroll_id', $id)
                        ->where('staff_id', $user->id)
                        ->sum('amount'),
                    'loan_deduction' => (float) DB::table('payroll_loan_deductions')
                        ->where('payroll_id', $id)
                        ->where('staff_id', $user->id)
                        ->sum('amount'),
                ],
                'allowances' => $allowances,
                'deductions' => $deductions,
            ],
        ]);
    }

    public function loanBalance(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalLoan = User::getStaffLoanAllTheTime($user->id);
        $totalPaid = User::getStaffLoanDeductionAllTheTime($user->id);
        $balance = User::getStaffLoan($user->id);
        $monthlyDeduction = User::getStaffLoanDeductionForCurrentLoan($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'total_loan' => $totalLoan,
                'total_paid' => $totalPaid,
                'balance' => $balance,
                'monthly_deduction' => $monthlyDeduction,
                'has_active_loan' => User::isStaffHasLoan($user->id),
            ],
        ]);
    }

    private function formatPayrollName($payrollNumber, $month, $year): string
    {
        $monthNumber = (int) ($month ?: date('n'));
        $monthName = date('F', mktime(0, 0, 0, max(1, min(12, $monthNumber)), 1));
        $number = $payrollNumber ?: 'Payroll';
        $periodYear = $year ?: date('Y');

        return trim(sprintf('%s - %s %s', $number, $monthName, $periodYear));
    }
}
