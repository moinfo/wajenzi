<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WebTableExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The data table of a web list or report page, as JSON, for the mobile app's
 * native table screen. The page is rendered for the signed-in user through the
 * web kernel (so its own middleware and permission checks apply) and the same
 * table the web's Excel / PDF buttons export is read.
 *
 *   GET web-tables/{route}[?index=0]
 */
class WebTableApiController extends Controller
{
    /** Pages offered in the app (web route name => title). */
    public const PAGES = [
        // Projects
        'project_dashboard' => 'Project Dashboard',
        'project_payments' => 'Project Payments',
        'project_team_members' => 'Project Team Members',
        'project_boq_templates' => 'Project BOQ Templates',
        'project_boq_items' => 'Project BOQ Items',
        'project_stock.index' => 'Project Stock',
        'project_stock_receipts.index' => 'Project Stock Receipts',
        // Bank reconciliation and statements
        'bank_reconciliation' => 'Bank Reconciliation',
        'bank_reconciliation_deposits' => 'Bank Deposits',
        'bank_reconciliation_withdraws' => 'Bank Withdraws',
        'bank_reconciliation_transfers' => 'Bank Transfers',
        'unrepresented_slip' => 'Unrepresented Slips',
        'bank_reconciliation_sales_bank_deposited' => 'Sales Bank Deposited',
        'bank_reconciliation_suppliers_statement' => 'Suppliers Statement',
        'bank_reconciliation_bank_reconciliation_statement' => 'Bank Reconciliation Statement',
        'supplier_targets' => 'Supplier Targets',
        'supplier_target_preparation' => 'Supplier Target Preparation',
        'supplier_targets_report' => 'Supplier Targets Report',
        'supplier_commissions' => 'Supplier Commissions',
        'bank_deposit_report' => 'Bank Deposit Report',
        'bank_deposit_reports' => 'Bank Deposit Reports',
        'bank_withdraw_reports' => 'Bank Withdraw Reports',
        'slip_review_report' => 'Slip Review Report',
        'transfer_by_only_supplier_reports' => 'Transfer By Only Supplier Reports',
        // Reports
        'reports_vat_analysis' => 'Vat Analysis',
        'reports_vat_payment' => 'Vat Payment',
        'reports_exempt_analysis' => 'Exempt Analysis',
        'reports_sales_report' => 'Sales Report',
        'reports_purchases_report' => 'Purchases Report',
        'reports_attendances_report' => 'Attendances Report',
        'reports_daily_attendances_report' => 'Daily Attendances Report',
        'reports_purchases_by_supplier_report' => 'Purchases By Supplier Report',
        'reports_gross_summary_report' => 'Gross Summary Report',
        'reports_deduction_report' => 'Deduction Report',
        'reports_allowance_subscriptions_report' => 'Allowance Subscriptions Report',
        'reports_statement_of_comprehensive_income_report' => 'Statement Of Comprehensive Income Report',
        'reports_statement_of_financial_position_report' => 'Statement Of Financial Position Report',
        'reports_detailed_expenditure_statement_report' => 'Detailed Expenditure Statement Report',
        'reports_efd_report' => 'Efd Report',
        'reports_detailed_efd_report' => 'Detailed Efd Report',
        'reports_annually_sales_summary_report' => 'Annually Sales Summary Report',
        'reports_annually_purchases_summary_report' => 'Annually Purchases Summary Report',
        'reports_annually_expenses_summary_report' => 'Annually Expenses Summary Report',
        'reports_annually_expense_sub_categories_summary_report' => 'Annually Expense Sub Categories Summary Report',
        'reports_annually_financial_charges_summary_report' => 'Annually Financial Charges Summary Report',
        'reports_annually_salaries_summary_report' => 'Annually Salaries Summary Report',
        'reports_annually_sdl_summary_report' => 'Annually Sdl Summary Report',
        'reports_annually_advance_salary_summary_report' => 'Annually Advance Salary Summary Report',
        'reports_annually_allowance_summary_report' => 'Annually Allowance Summary Report',
        'reports_annually_heslb_summary_report' => 'Annually Heslb Summary Report',
        'reports_annually_net_salary_summary_report' => 'Annually Net Salary Summary Report',
        'reports_annually_nhif_summary_report' => 'Annually Nhif Summary Report',
        'reports_annually_nssf_summary_report' => 'Annually Nssf Summary Report',
        'reports_annually_deduction_report' => 'Annually Deduction Report',
        'reports_annually_paye_summary_report' => 'Annually Paye Summary Report',
        'reports_annually_wcf_summary_report' => 'Annually Wcf Summary Report',
        // Bookkeeping
        'collection' => 'Collection',
        'gross' => 'Gross',
        'transaction_movement' => 'Transaction Movement',
        'receiving' => 'Receiving',
        'supplier_receiving' => 'Supplier Receiving',
        'system_inventory' => 'System Inventory',
        'system_credit' => 'System Credit',
        'system_capital' => 'System Capital',
        'system_cash' => 'System Cash',
        'withholding_taxes' => 'Withholding Taxes',
        'adjusted_assessment_taxes' => 'Adjusted Assessment Taxes',
        'expense_adjustable' => 'Expense Adjustable',
        'financial_charges' => 'Financial Charges',
    ];

    public function show(Request $request, WebTableExporter $web, string $route): JsonResponse
    {
        if (!isset(self::PAGES[$route])) {
            return response()->json(['success' => false, 'message' => 'Page not found.'], 404);
        }

        try {
            // The same filter the web page reads from its query string.
            $params = array_filter([
                'project_id' => $request->query('project_id'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
                'year' => $request->query('year') ?: substr((string) $request->query('end_date'), 0, 4),
            ]);
            $table = $web->read($route, $params, $request->user(), (int) $request->query('index', 0), true);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'route' => $route,
                'title' => self::PAGES[$route],
                'headers' => $table['headers'],
                'rows' => $table['rows'],
                'has_date_filter' => $table['has_date_filter'] ?? false,
            ],
        ]);
    }
}
