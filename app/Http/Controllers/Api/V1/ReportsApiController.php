<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsApiController extends Controller
{
    private function resolveReportUrl(?string $route): ?string
    {
        if (blank($route)) {
            return null;
        }

        try {
            return route($route);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function reportsCatalog(): array
    {
        return [
            ['name' => 'Bank Statement', 'route' => 'reports_bank_statement_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'VAT Analysis', 'route' => 'reports_vat_analysis', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'VAT Payments', 'route' => 'reports_vat_payment', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Exempt Analysis', 'route' => 'reports_exempt_analysis', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Sales Report', 'route' => 'reports_sales_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Purchases Report', 'route' => 'reports_purchases_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Attendances Report', 'route' => 'reports_attendances_report', 'icon' => 'si si-clock', 'badge' => 0],
            ['name' => 'Daily Attendances Report', 'route' => 'reports_daily_attendances_report', 'icon' => 'si si-clock', 'badge' => 0],
            ['name' => 'Purchases By Supplier Report', 'route' => 'reports_purchases_by_supplier_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Departments', 'route' => 'hr_settings_departments', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Gross Summary Report', 'route' => 'reports_gross_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Deduction Report', 'route' => 'reports_deduction_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Allowance Subscriptions Report', 'route' => 'reports_allowance_subscriptions_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Statement of Comprehensive Income Report', 'route' => 'reports_statement_of_comprehensive_income_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Statement of Financial Position Report', 'route' => 'reports_statement_of_financial_position_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Detailed Expenditure Statement Report', 'route' => 'reports_detailed_expenditure_statement_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Efd Report', 'route' => 'reports_efd_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Detailed Efd Report', 'route' => 'reports_detailed_efd_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Statutory Payment Report', 'route' => 'reports_statutory_payment_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Sales Summary Report', 'route' => 'reports_annually_sales_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Purchases Summary Report', 'route' => 'reports_annually_purchases_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Expenses Summary Report', 'route' => 'reports_annually_expenses_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Expense Sub Categories Summary Report', 'route' => 'reports_annually_expense_sub_categories_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Financial Charges Summary Report', 'route' => 'reports_annually_financial_charges_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Salaries and Wages Summary Report', 'route' => 'reports_annually_salaries_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually SDL Summary Report', 'route' => 'reports_annually_sdl_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Advance Salary Summary Report', 'route' => 'reports_annually_advance_salary_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Allowance Summary Report', 'route' => 'reports_annually_allowance_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually HESLB Summary Report', 'route' => 'reports_annually_heslb_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Net Salary Summary Report', 'route' => 'reports_annually_net_salary_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually NHIF Summary Report', 'route' => 'reports_annually_nhif_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually NSSF Summary Report', 'route' => 'reports_annually_nssf_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually Deduction Report', 'route' => 'reports_annually_deduction_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually PAYE Summary Report', 'route' => 'reports_annually_paye_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Annually WCF Summary Report', 'route' => 'reports_annually_wcf_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $reports = collect($this->reportsCatalog())
            ->filter(fn (array $report) => $user->can($report['name']))
            ->map(fn (array $report) => [
                'name' => $report['name'],
                'route' => $report['route'],
                'icon' => $report['icon'],
                'badge' => $report['badge'],
                'url' => $this->resolveReportUrl($report['route']),
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'reports' => $reports,
                'total' => $reports->count(),
            ],
        ]);
    }
}
