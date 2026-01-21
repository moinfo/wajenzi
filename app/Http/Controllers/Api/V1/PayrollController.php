<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function payslips(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get payroll records for user
        $payslips = \DB::table('payroll_records')
            ->join('payrolls', 'payroll_records.payroll_id', '=', 'payrolls.id')
            ->where('payroll_records.staff_id', $user->id)
            ->where('payrolls.status', 'APPROVED')
            ->orderBy('payrolls.submitted_date', 'desc')
            ->select([
                'payroll_records.id',
                'payrolls.id as payroll_id',
                'payrolls.name as payroll_name',
                'payrolls.submitted_date',
                'payroll_records.basicSalary as basic_salary',
                'payroll_records.allowance',
                'payroll_records.deduction',
                'payroll_records.taxable',
                'payroll_records.paye',
                'payroll_records.netSalary as net_salary',
                'payroll_records.loanDeduction as loan_deduction',
            ])
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'success' => true,
            'data' => $payslips->items(),
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

        $payslip = \DB::table('payroll_records')
            ->join('payrolls', 'payroll_records.payroll_id', '=', 'payrolls.id')
            ->where('payroll_records.id', $id)
            ->where('payroll_records.staff_id', $user->id)
            ->select([
                'payroll_records.*',
                'payrolls.name as payroll_name',
                'payrolls.submitted_date',
                'payrolls.start_date',
                'payrolls.end_date',
            ])
            ->first();

        if (!$payslip) {
            return response()->json([
                'success' => false,
                'message' => 'Payslip not found.',
            ], 404);
        }

        // Get allowances
        $allowances = \DB::table('payroll_allowances')
            ->join('allowances', 'payroll_allowances.allowance_id', '=', 'allowances.id')
            ->where('payroll_allowances.payroll_id', $payslip->payroll_id)
            ->where('payroll_allowances.staff_id', $user->id)
            ->select(['allowances.name', 'payroll_allowances.amount'])
            ->get();

        // Get deductions
        $deductions = \DB::table('payroll_deductions')
            ->join('deductions', 'payroll_deductions.deduction_id', '=', 'deductions.id')
            ->where('payroll_deductions.payroll_id', $payslip->payroll_id)
            ->where('payroll_deductions.staff_id', $user->id)
            ->select(['deductions.name', 'payroll_deductions.employee_contribution', 'payroll_deductions.employer_contribution'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'payslip' => $payslip,
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
}
