<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\User;
use App\Services\WebTableExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Column order of the web payroll page's `#payroll` table. The web's
     * "Create Payroll" button reads exactly these cells (hidden raw-value
     * columns) by position, so the API does the same.
     */
    private const STAFF_PAYROLL_COLUMNS = [
        1 => 'name', 2 => 'basicSalary', 3 => 'allowance', 4 => 'grossPay',
        5 => 'employerPension', 6 => 'employeePension', 7 => 'employerHealth',
        8 => 'employeeHealth', 9 => 'taxable', 10 => 'paye', 11 => 'wpf',
        12 => 'sdl', 13 => 'heslb', 14 => 'advanceSalary', 15 => 'totalLoan',
        16 => 'loanDeduction', 17 => 'loanBalance', 18 => 'net', 19 => 'staff_id',
    ];

    /**
     * GET /payroll/staff-payroll
     * The "Staff Payroll" table of the web Payroll page for the current month.
     */
    public function staffPayroll(Request $request, WebTableExporter $web): JsonResponse
    {
        try {
            $rows = $this->staffPayrollRows($web, $request->user(), true);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $paid = $this->currentPayrollPaid();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'meta' => [
                'month' => date('F Y'),
                'already_created' => $paid,
                'can_create' => !$paid && $request->user()->can('Add Payroll'),
            ],
        ]);
    }

    /**
     * POST /payroll/staff-payroll
     * The web "Create Payroll" button: stores the displayed rows in
     * payroll_records (AjaxController::ajaxRequestPost), once per month.
     */
    public function createStaffPayroll(Request $request, WebTableExporter $web): JsonResponse
    {
        if (!$request->user()->can('Add Payroll')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to create payroll.'], 403);
        }
        if ($this->currentPayrollPaid()) {
            return response()->json(['success' => false, 'message' => 'Payroll for this month has already been created.'], 422);
        }

        try {
            $rows = $this->staffPayrollRows($web, $request->user(), false);
            if (!$rows) {
                return response()->json(['success' => false, 'message' => 'There is no payroll data to create.'], 422);
            }
            DB::table('payroll_records')->insert($rows);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            \Log::error('Create payroll failed: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Changes are not saved. There is a problem.'], 500);
        }

        return response()->json(['success' => true, 'message' => 'Payroll created for this month.']);
    }

    private function currentPayrollPaid(): bool
    {
        return (bool) Payroll::isCurrentPayrollPaid(date('Y-m-01'), date('Y-m-t'));
    }

    /**
     * Rows of the `#payroll` table keyed like the web JS (storeTblValues):
     * every row, minus the header (first) and the totals row (last).
     */
    private function staffPayrollRows(WebTableExporter $web, $user, bool $trim): array
    {
        $html = $web->html('payroll', [], $user);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $trs = [];
        foreach ($xp->query("//table[@id='payroll']//tr") as $tr) {
            $trs[] = $tr;
        }
        if (count($trs) < 3) {
            return [];
        }
        array_shift($trs);
        array_pop($trs);

        $rows = [];
        foreach ($trs as $tr) {
            $cells = $xp->query('./td', $tr);
            $row = [];
            foreach (self::STAFF_PAYROLL_COLUMNS as $i => $key) {
                $text = $cells->item($i)?->textContent ?? '';
                $row[$key] = $trim ? trim($text) : $text;
            }
            $rows[] = $row;
        }

        return $rows;
    }

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
