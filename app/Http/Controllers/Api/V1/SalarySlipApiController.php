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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\WebTableExporter;

class SalarySlipApiController extends Controller
{
    /**
     * Payslip as a PDF (same figures and layout as the web payslip), returned
     * as base64 JSON like the other mobile downloads.
     */
    public function pdf(Request $request, WebTableExporter $web): JsonResponse
    {
        try {
            $slip = $this->slipData($request, $web);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        if (!($slip['has_payslip'] ?? false)) {
            return response()->json(['success' => false, 'message' => $slip['empty_message'] ?? 'Salary slip not available'], 404);
        }

        // The logo needs the GD extension in DomPDF; leave it out where GD is missing.
        $logoPath = extension_loaded('gd') ? public_path('media/logo/wajenzilogo.png') : null;
        if ($logoPath && !is_file($logoPath)) {
            $logoPath = null;
        }

        $pdf = Pdf::loadView('pages.payroll.payslip_pdf', ['slip' => $slip, 'logoPath' => $logoPath])
            ->setPaper('a4', 'portrait');
        $base64 = base64_encode($pdf->output());

        $employee = collect($slip['employee'])->firstWhere('label', 'Employee Name')['value'] ?? 'Employee';
        $filename = 'Payslip_' . preg_replace('/\s+/', '_', $employee) . '_'
            . preg_replace('/\s+/', '_', (string) $slip['period_label']) . '.pdf';

        return response()->json([
            'success' => true,
            'data' => [
                'filename' => $filename,
                'mime_type' => 'application/pdf',
                'file_base64' => $base64,
                'pdf_base64' => $base64,
            ],
        ]);
    }

    /**
     * GET salary-slips/slip?month=&year=&staff_id=
     *
     * The web's Salary Slips page, part by part: header, filter lists, the three
     * summary cards, the payslip document (company, employee grid, income and
     * deductions panels, net salary banner) or the "not available" message.
     * The page is rendered for the user by the web itself, so every figure is the
     * web's. Defaults (latest approved payroll, first employee) are the web's too.
     */
    public function slip(Request $request, WebTableExporter $web): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->slipData($request, $web)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** The slip as structured data (shared by the screen and the PDF). */
    private function slipData(Request $request, WebTableExporter $web): array
    {
        $params = array_filter([
            'month' => $request->query('month'),
            'year' => $request->query('year'),
            'staff_id' => $request->query('staff_id'),
        ], fn ($v) => $v !== null && $v !== '');

        $html = $web->render('/payroll/salary_slips', $params, $request->user());

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html);
        libxml_clear_errors();
        $xp = new \DOMXPath($dom);

        $cls = fn (string $c) => "contains(concat(' ', normalize-space(@class), ' '), ' {$c} ')";
        $text = fn (?\DOMNode $n) => $n ? trim(preg_replace('/\s+/u', ' ', $n->textContent)) : null;
        $first = fn (string $q, ?\DOMNode $ctx = null) => $xp->query($q, $ctx)->item(0);
        $options = function (string $selectId) use ($xp, $text) {
            $out = [];
            foreach ($xp->query("//select[@id='{$selectId}']/option") as $o) {
                $out[] = ['id' => $o->getAttribute('value'), 'name' => $text($o), 'selected' => $o->hasAttribute('selected')];
            }

            return $out;
        };

        $orgLine = $text($first("//div[{$cls('salary-slip-title')}]/p"));
        $summary = [];
        foreach ($xp->query("//div[{$cls('salary-summary-item')}]") as $item) {
            $summary[] = ['label' => $text($first('.//span', $item)), 'value' => $text($first('.//strong', $item))];
        }

        $panel = function (string $which) use ($xp, $cls, $text, $first): array {
            $p = $first("//div[{$cls('salary-panel')} and {$cls($which)}]");
            if (!$p) {
                return ['title' => null, 'amount' => null, 'rows' => [], 'total_label' => null, 'total' => null];
            }
            $rows = [];
            foreach ($xp->query('.//tbody/tr', $p) as $tr) {
                $tds = $xp->query('./td', $tr);
                $rows[] = ['name' => $text($tds->item(0)), 'value' => $text($tds->item(1))];
            }

            return [
                'title' => $text($first(".//div[{$cls('salary-panel-header')}]/span", $p)),
                'amount' => $text($first(".//div[{$cls('salary-panel-header')}]/strong", $p)),
                'rows' => $rows,
                'total_label' => $text($first('.//tfoot//th', $p)),
                'total' => $text($first('.//tfoot//td', $p)),
            ];
        };

        $employee = [];
        foreach ($xp->query("//div[{$cls('employee-field')}]") as $field) {
            $employee[] = ['label' => $text($first('.//span', $field)), 'value' => $text($first('.//strong', $field))];
        }

        $has = $xp->query("//div[@id='payslip-container']")->length > 0;
        $meta = $first("//p[{$cls('payslip-company-meta')}]");
        $companyLines = [];
        if ($meta) {
            foreach ($meta->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE && trim($child->textContent) !== '') {
                    $companyLines[] = trim(preg_replace('/\s+/u', ' ', $child->textContent));
                }
            }
        }
        $logo = $first("//div[{$cls('payslip-brand-logo')}]//img");

        return [
                'title' => $text($first("//div[{$cls('salary-slip-title')}]/h1")) ?? 'Salary Slip',
                'organization' => $text($first("//p[{$cls('payslip-company-name')}]")) ?? trim(str_replace('payroll document', '', (string) $orgLine)),
                'subtitle' => $orgLine,
                'period_label' => $text($first("//div[{$cls('salary-slip-period')}]/strong")),
                'months' => $options('month'),
                'years' => $options('year'),
                'staff' => $options('input-staff-id'),
                'has_payslip' => $has,
                'empty_message' => $text($first("//div[{$cls('empty-payslip')}]//p")),
                'summary' => $summary,
                'company' => [
                    'name' => $text($first("//p[{$cls('payslip-company-name')}]")),
                    'lines' => $companyLines,
                    'logo_url' => $logo ? $request->root() . '/' . ltrim(parse_url($logo->getAttribute('src'), PHP_URL_PATH) ?: '', '/') : null,
                ],
                'badge' => $text($first("//div[{$cls('payslip-label')}]/span")),
                'document_title' => $text($first("//div[{$cls('payslip-label')}]/strong")),
                'document_period' => $text($first("//div[{$cls('payslip-label')}]/small")),
                'employee' => $employee,
                'income' => $panel('earnings-panel'),
                'deductions' => $panel('deductions-panel'),
                'net' => [
                    'label' => $text($first("//div[{$cls('salary-net-banner')}]/div[1]/span")),
                    'amount' => $text($first("//div[{$cls('salary-net-banner')}]/div[1]/strong")),
                    'payment_label' => $text($first("//div[{$cls('salary-net-bank')}]/small")),
                    'payment_account' => $text($first("//div[{$cls('salary-net-bank')}]/b")),
                ],
                'footer' => [
                    'note' => $text($first("//div[{$cls('payslip-footer')}]/span")),
                    'organization' => $text($first("//div[{$cls('payslip-footer')}]/strong")),
                ],
        ];
    }
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
        $advanceSalaryBalance = Staff::getStaffAdvanceSalaryBalance($staffId, $payrollId) ?? 0;
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
                'advance_salary_balance' => $advanceSalaryBalance,
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
