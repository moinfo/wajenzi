<?php

namespace App\Http\Controllers;

use App\Models\Allowance;
use App\Models\BankReconciliation;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Deduction;
use App\Models\Efd;
use App\Models\Expense;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use App\Models\FinancialChargeCategory;
use App\Models\Gross;
use App\Models\Product;
use App\Models\ProvisionTax;
use App\Models\Report;
use App\Models\Staff;
use App\Models\SubCategory;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\SupplierTarget;
use App\Models\System;
use App\Models\TransactionMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $reports = [
            ['name'=>'Bank Statement', 'route'=>'reports_bank_statement_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name'=>'Total Current Credit Suppliers Report', 'route'=>'reports_total_current_credit_suppliers_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name'=>'Total Credit Suppliers Report', 'route'=>'reports_total_credit_suppliers_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name'=>'VAT Analysis', 'route'=>'reports_vat_analysis', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name'=>'VAT Payments', 'route'=>'reports_vat_payment', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name'=>'Exempt Analysis', 'route'=>'reports_exempt_analysis', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name'=>'Sales Report', 'route'=>'reports_sales_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name'=>'Purchases Report', 'route'=>'reports_purchases_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name'=>'Purchases By Supplier Report', 'route'=>'reports_purchases_by_supplier_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name'=>'Departments', 'route'=>'hr_settings_departments', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'General Report', 'route' => 'reports_general_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Auto Transaction Report', 'route' => 'reports_auto_transaction_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Business Position Details Report', 'route' => 'reports_business_position_details_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Supplier Credit Report', 'route' => 'reports_supplier_credit_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Transaction Movement Report', 'route' => 'reports_transaction_movement_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Expenses Report', 'route' => 'reports_expenses_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Collection Report', 'route' => 'reports_collection_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Supplier Receiving Report', 'route' => 'reports_supplier_receiving_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Supplier Transaction Report', 'route' => 'reports_supplier_transaction_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Supplier Report', 'route' => 'reports_supplier_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Gross Summary Report', 'route' => 'reports_gross_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Supervisor Report', 'route' => 'reports_supervisor_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Deduction Report', 'route' => 'reports_deduction_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Collection Per System Report', 'route' => 'reports_collection_per_system_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Expenses Per System Report', 'route' => 'reports_expenses_per_system_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Expenses Categories Report', 'route' => 'reports_expenses_categories_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Expenses Sub Categories Report', 'route' => 'reports_expenses_sub_categories_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Business Position Report', 'route' => 'reports_business_position_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Allowance Subscriptions Report', 'route' => 'reports_allowance_subscriptions_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Statement of Comprehensive Income Report', 'route' => 'reports_statement_of_comprehensive_income_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Statement of Financial Position Report', 'route' => 'reports_statement_of_financial_position_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Detailed Expenditure Statement Report', 'route' => 'reports_detailed_expenditure_statement_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Efd Report', 'route' => 'reports_efd_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Detailed Efd Report', 'route' => 'reports_detailed_efd_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Bank Reconciliation Report', 'route' => 'reports_bank_reconciliation_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Bank Report', 'route' => 'reports_bank_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Supplier Bank Deposit Report', 'route' => 'reports_supplier_bank_deposit_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Statement Report', 'route' => 'reports_statement_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Supplier Report', 'route' => 'reports_supplier_2_report', 'icon' => 'si si-book-open', 'badge' => 0],
//            ['name' => 'Provision Report', 'route' => 'reports_provision_report', 'icon' => 'si si-book-open', 'badge' => 0],
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
//            ['name' => 'Commission Vs Deposit Report', 'route' => 'reports_commission_vs_deposit_report', 'icon' => 'si si-book-open', 'badge' => 0],

            ];
        $data = [
            'reports' => $reports
        ];
        return view('pages.reports.reports_index')->with($data);
    }

    public function statutory_category_report(Request $request){
//        $suppliers = Supplier::all();
        $categories = Category::all();
        $data = [
            'categories' => $categories
        ];
        return view('pages.reports.reports_statutory_category_report')->with($data);
    }

    public function statutory_schedules_report(Request $request){
//        $suppliers = Supplier::all();
        $products = Product::all();
        $data = [
            'products' => $products
        ];
        return view('pages.reports.reports_statutory_schedules_report')->with($data);
    }

    public function net_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_net_report')->with($data);
    }
    public function sdl_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_sdl_report')->with($data);
    }
    public function paye_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_paye_report')->with($data);
    }
    public function nssf_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_nssf_report')->with($data);
    }
    public function nhif_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_nhif_report')->with($data);
    }
    public function heslb_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_heslb_report')->with($data);
    }
    public function advance_salary_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_advance_salary_report')->with($data);
    }
    public function loan_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_loan_report')->with($data);
    }
    public function wcf_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_wcf_report')->with($data);
    }
    public function allowance_report(Request $request){
        $this_month = $request->input('month')  ?? date('m');
        $this_year = $request->input('year')  ?? date('Y');
        $payroll = \App\Models\Payroll::getThisPayroll($this_month,$this_year);
        $payroll_id = $payroll->id ?? 0;
        $staffs = \App\Models\Staff::onlyStaffs();
        $data = [
            'this_month' => $this_month,
            'this_year' => $this_year,
            'payroll' => $payroll,
            'payroll_id' => $payroll_id,
            'staffs' => $staffs,
        ];
        return view('pages.reports.reports_allowance_report')->with($data);
    }
    public function getBongeSales(Request $request)
    {
        $efd = \App\Models\Efd::find($request->efd_id);
        $date = $request->date;

        // Get Bonge sales
        $bonge_sales = \App\Models\Report::getTotalDaysSalesBonge($date, $date, $efd->bonge_customer_id);

        // Get total amount used in supplier target preparations for this EFD
        $used_amount = DB::table('supplier_target_preparations')
            ->where('efd_id', $efd->id)
            ->whereDate('date', $date)
            ->sum('amount');

        return response()->json([
            'efd_name' => $efd->name,
            'bonge_sales' => $bonge_sales,
            'used_amount' => $used_amount,
            'balance' => $bonge_sales - $used_amount
        ]);
    }
    public function commission_vs_deposit_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supplier_id = $request->input('supplier_id') ?? 0;
//        $suppliers = SupplierTarget::getTotalSupplierCommissionWithDeposit($supplier_id,$start_date,$end_date);
        $suppliers = Supplier::all();
        $data = [
            'suppliers' => $suppliers,
        ];
        return view('pages.reports.reports_commission_vs_deposit_report')->with($data);
    }

    public function bank_statement_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $suppliers = Supplier::all();
        $data = [
            'suppliers' => $suppliers,
        ];
        return view('pages.reports.reports_bank_statement_report')->with($data);
    }

    public function annually_advance_salary_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_advance_salary_summary_report')->with($data);
    }

    public function annually_allowance_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_allowance_summary_report')->with($data);
    }

    public function annually_heslb_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_heslb_summary_report')->with($data);
    }

    public function annually_net_salary_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_net_salary_summary_report')->with($data);
    }

    public function annually_nhif_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_nhif_summary_report')->with($data);
    }

    public function annually_nssf_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_nssf_summary_report')->with($data);
    }

    public function annually_deduction_report(Request $request){
        $deductions = Deduction::where('is_financial','YES')->get();
        $sub_expenses = ExpensesSubCategory::where('is_financial','YES')->get();

        $data = [
            'deductions' => $deductions,
            'sub_expenses' => $sub_expenses
        ];
        return view('pages.reports.reports_annually_deduction_report')->with($data);
    }

    public function annually_paye_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_paye_summary_report')->with($data);
    }

    public function annually_wcf_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_wcf_summary_report')->with($data);
    }

    public function annually_expense_sub_categories_summary_report(Request $request){
        $sub_categories = ExpensesSubCategory::all();

        $data = [
            'sub_categories' => $sub_categories
        ];
        return view('pages.reports.reports_annually_expense_sub_categories_summary_report')->with($data);
    }

    public function annually_sales_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_sales_summary_report')->with($data);
    }

    public function annually_purchases_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_purchases_summary_report')->with($data);
    }

    public function annually_expenses_summary_report(Request $request){
        $expenses_categories = ExpensesCategory::all();

        $data = [
            'expenses_categories' => $expenses_categories
        ];
        return view('pages.reports.reports_annually_expenses_summary_report')->with($data);
    }

    public function annually_financial_charges_summary_report(Request $request){
        $financial_charge_categories = FinancialChargeCategory::all();

        $data = [
            'financial_charge_categories' => $financial_charge_categories
        ];
        return view('pages.reports.reports_annually_financial_charges_summary_report')->with($data);
    }

    public function annually_salaries_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_salaries_summary_report')->with($data);
    }

    public function annually_sdl_summary_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_annually_sdl_summary_report')->with($data);
    }


    public function auto_transaction_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $transaction_muhidini = Report::getTotalTransactionMuhidini($start_date,$end_date);
        $transaction_kassim = Report::getTotalTransactionKassim($start_date,$end_date);
        $transaction_leruma = Report::getTotalTransactionLeruma($start_date,$end_date);
        $transaction_whitestar = Report::getTotalTransactionWhitestar($start_date,$end_date);
        $bonge_payments = Report::getSupplierDailyDebit($start_date,$end_date);
        $whitestar_payments = Report::getSupplierDailyDebitWhitestar($start_date,$end_date);

        $first_start_date = '2022-04-01';
        $transaction_muhidini_all_time = Report::getTotalTransactionMuhidini($first_start_date,$end_date);
        $transaction_kassim_all_time = Report::getTotalTransactionKassim($first_start_date,$end_date);
        $transaction_leruma_all_time = Report::getTotalTransactionLeruma($first_start_date,$end_date);
        $transaction_whitestar_all_time = Report::getTotalTransactionWhitestar($first_start_date,$end_date);
        $bonge_payment_all_time = Report::getSupplierDailyDebitAllTime($first_start_date,$end_date);
        $white_payment_all_time = Report::getSupplierDailyDebitAllTimeWhiteStar($first_start_date,$end_date);


        $withdraws_all_time = 0;
        $deposits_all_time = 0;
        $loans_all_time = Report::getTotalLoan($first_start_date,$end_date);
        $advance_salaries_all_time = Report::getTotalAdvanceSalary($first_start_date,$end_date);
        $payrolls_all_time = Report::getTotalNetSalary($first_start_date,$end_date);
        $allowances_all_time = Report::getTotalAllowance($first_start_date,$end_date);

//            DB::connection('mysql2')->select("Select * FROM ospos_items");
        $withdraws = 0;
        $deposits = 0;
        $loans = DB::select("SELECT c.name, SUM(s.amount) AS amount FROM loans s JOIN users c ON (s.staff_id = c.id) WHERE s.status = 'APPROVED' AND s.date BETWEEN '$start_date' AND '$end_date' GROUP BY c.id,s.staff_id");
        $advance_salaries = DB::select("SELECT c.name, SUM(s.amount) AS amount FROM advance_salaries s JOIN users c ON (s.staff_id = c.id) WHERE s.status = 'APPROVED' AND s.date BETWEEN '$start_date' AND '$end_date' GROUP BY c.id,s.staff_id");
        $payrolls = DB::select("SELECT c.name, SUM(s.net) AS amount FROM payroll_records s JOIN users c ON (s.staff_id = c.id) WHERE s.status = 'APPROVED' AND DATE(s.created_at) BETWEEN '$start_date' AND '$end_date' GROUP BY c.id,s.staff_id");
        $allowances = DB::select("SELECT c.name, SUM(s.allowance) AS amount FROM payroll_records s JOIN users c ON (s.staff_id = c.id) WHERE s.status = 'APPROVED' AND DATE(s.created_at) BETWEEN '$start_date' AND '$end_date' GROUP BY c.id,s.staff_id");
        $transactions = DB::select("SELECT s.name, SUM(c.amount) AS amount FROM supervisors s JOIN collections c ON (c.supervisor_id = s.id) WHERE c.status = 'APPROVED' AND c.date BETWEEN '$start_date' AND '$end_date' GROUP BY s.id,c.supervisor_id");
        $payments = DB::select("SELECT s.name, SUM(c.amount) AS amount FROM suppliers s JOIN transaction_movements c ON (c.supplier_id = s.id) WHERE c.status = 'APPROVED' AND c.date BETWEEN '$start_date' AND '$end_date' GROUP BY s.id,c.supplier_id");

        $data = [
            'payrolls' => $payrolls,
            'whitestar_payments' => $whitestar_payments,
            'payrolls_all_time' => $payrolls_all_time,
            'white_payment_all_time' => $white_payment_all_time,
            'transaction_muhidini' => $transaction_muhidini,
            'transaction_whitestar' => $transaction_whitestar,
            'transaction_kassim' => $transaction_kassim,
            'transaction_leruma' => $transaction_leruma,
            'transaction_muhidini_all_time' => $transaction_muhidini_all_time,
            'transaction_whitestar_all_time' => $transaction_whitestar_all_time,
            'transaction_kassim_all_time' => $transaction_kassim_all_time,
            'transaction_leruma_all_time' => $transaction_leruma_all_time,
            'allowances' => $allowances,
            'advance_salaries' => $advance_salaries,
            'loans' => $loans,
            'withdraws' => $withdraws,
            'deposits' => $deposits,
            'allowances_all_time' => $allowances_all_time,
            'advance_salaries_all_time' => $advance_salaries_all_time,
            'loans_all_time' => $loans_all_time,
            'withdraws_all_time' => $withdraws_all_time,
            'deposits_all_time' => $deposits_all_time,
            'payments' => $payments,
            'transactions' => $transactions,
            'bonge_payments' => $bonge_payments,
            'bonge_payment_all_time' => $bonge_payment_all_time
        ];
        return view('pages.reports.reports_auto_transaction_report')->with($data);
    }



    public function general_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::all();
        $supervisor_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expenses, s.name as supervisor_name,c.date as expense_date FROM expenses c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_expenses' => $supervisor_with_amount_of_expenses,
            'supervisors' => $supervisors,
            'expenses' => $expenses
        ];
        return view('pages.reports.reports_general_report')->with($data);
    }

    public function gross_summary_report(Request $request){
        $grosses = Gross::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::where('employee_id',1)->get();
        $supervisor_with_amount_of_grosses = DB::select('SELECT SUM(c.amount) as total_gross, s.name as supervisor_name,c.date as gross_date FROM grosses c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_grosses' => $supervisor_with_amount_of_grosses,
            'supervisors' => $supervisors,
            'grosses' => $grosses
        ];
        return view('pages.reports.reports_gross_summary_report')->with($data);
    }

    public function supervisor_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_supervisor_report')->with($data);
    }

    public function statutory_payment_report(Request $request){
//        $suppliers = Supplier::all();
        $sub_categories = SubCategory::all();
        $data = [
            'sub_categories' => $sub_categories
        ];
        return view('pages.reports.reports_statutory_payment_report')->with($data);
    }
    public function total_credit_suppliers_report(Request $request){
        $suppliers_with_bonge = Supplier::where('supplier_depend_on_system','=','BONGE')->where('supplier_type','INDIRECT')->where('id','!=','88')->where('id','!=','156')->where('id','!=','192')->where('is_transferred','!=','CAN BE BOTH')->orderBy('supplier_depend_on_system', 'DESC')->get();
        $suppliers_with_whitestar = Supplier::where('supplier_depend_on_system','=','WHITESTAR')->where('supplier_type','INDIRECT')->where('id','!=','52')->where('id','!=','192')->where('id','!=','156')->where('is_transferred','!=','CAN BE BOTH')->orderBy('supplier_depend_on_system', 'DESC')->get();

        $data = [
            'suppliers_with_bonge' => $suppliers_with_bonge,
            'suppliers_with_whitestar' => $suppliers_with_whitestar
        ];
        return view('pages.reports.reports_total_credit_suppliers_report')->with($data);
    }
    public function total_current_credit_suppliers_report(Request $request){
        $suppliers_with_bonge = Supplier::where('supplier_depend_on_system','=','BONGE')->where('id','!=','88')->where('id','!=','192')->where('id','!=','156')->where('is_transferred','!=','CAN BE BOTH')->orderBy('supplier_depend_on_system', 'DESC')->get();
        $suppliers_with_whitestar = Supplier::where('supplier_depend_on_system','=','WHITESTAR')->where('id','!=','52')->where('id','!=','192')->where('id','!=','156')->where('is_transferred','!=','CAN BE BOTH')->orderBy('supplier_depend_on_system', 'DESC')->get();

        $data = [
            'suppliers_with_bonge' => $suppliers_with_bonge,
            'suppliers_with_whitestar' => $suppliers_with_whitestar
        ];
        return view('pages.reports.reports_total_current_credit_suppliers_report')->with($data);
    }

    public function supplier_bank_deposit_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efds = Efd::all();
        $reports = Efd::allWithTransactionsWithOfficePaymentType($start_date, $end_date, 'SALES');;
        $data = [
            'efds' => $efds,
            'reports' => $reports,
        ];
        return view('pages.reports.reports_supplier_bank_deposit_report')->with($data);
    }

    public function statement_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_statement_report')->with($data);
    }

    public function supplier_2_report(Request $request){
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_supplier_2_report')->with($data);
    }

    public function bank_report(Request $request){
        $suppliers = Supplier::all();
        $data = [
            'suppliers' => $suppliers

        ];
        return view('pages.reports.reports_bank_report')->with($data);
    }

    public function bank_reconciliation_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $suppliers = Supplier::all();
        $supplier_with_deposits = BankReconciliation::where('date','>=',$start_date)->where('date','<=',$end_date)->where('payment_type','=','SALES')->select('suppliers.name','bank_reconciliations.supplier_id')
                ->join('suppliers','suppliers.id','=','bank_reconciliations.supplier_id')->groupBy('supplier_id')->get();
        $efds = Efd::all();
        $reports = Efd::allWithTransactions($start_date, $end_date);
        $maxTransactions = 0;
        foreach ($reports as $index => $item) {
            if($item->transactions()->count() > $maxTransactions){
                $maxTransactions = $item->transactions()->count();
            }
        }
        $data = [
            'supplier_with_deposits' => $supplier_with_deposits,
            'suppliers' => $suppliers,
            'efds' => $efds,
            'efdTransactions' => $reports,
            'maxTransactions' => $maxTransactions

        ];
        return view('pages.reports.reports_bank_reconciliation_report')->with($data);
    }


    public function statement_of_comprehensive_income_report(Request $request){
        $data = [];
        return view('pages.reports.reports_statement_of_comprehensive_income_report')->with($data);
    }

    public function statement_of_financial_position_report(Request $request){
        $data = [];
        return view('pages.reports.reports_statement_of_financial_position_report')->with($data);
    }
    public function detailed_expenditure_statement_report(Request $request){
        $data = [];
        return view('pages.reports.reports_detailed_expenditure_statement_report')->with($data);
    }

    public function vat_payments_report(Request $request){
        $suppliers = Supplier::all();
        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_vat_payments_report')->with($data);
    }

    public function vat_analysis_report(Request $request){
        $suppliers = Supplier::all();
        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_vat_analysis_report')->with($data);
    }

    public function exempt_analysis_report(Request $request){
        $suppliers = Supplier::all();
        $data = [
            'suppliers' => $suppliers
        ];
        return view('pages.reports.reports_exempt_analysis_report')->with($data);
    }

    public function sales_report(Request $request){
        $efds = Efd::all();
        $data = [
            'efds' => $efds
        ];
        return view('pages.reports.reports_sales_report')->with($data);
    }

    public function purchases_report(Request $request){
        $suppliers = Supplier::all();
        $purchases_types = [
            ['id'=>'1','name'=>'VAT'],
            ['id'=>'2','name'=>'EXEMPT']
        ];
        $data = [
            'suppliers' => $suppliers,
            'purchases_types' => $purchases_types
        ];
        return view('pages.reports.reports_purchases_report')->with($data);
    }
    public function purchases_by_supplier_report(Request $request){
        $suppliers = Supplier::all();
        $purchases_types = [
            ['id'=>'1','name'=>'VAT'],
            ['id'=>'2','name'=>'EXEMPT']
        ];
        $data = [
            'suppliers' => $suppliers,
            'purchases_types' => $purchases_types
        ];
        return view('pages.reports.reports_purchases_by_supplier_report')->with($data);
    }

    public function business_position_report(Request $request){
        $data = [];
        return view('pages.reports.reports_business_position_report')->with($data);
    }
    public function business_position_details_report(Request $request){
        $data = [];
        return view('pages.reports.reports_business_position_details_report')->with($data);
    }
    public function supplier_credit_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-t');
        $supplier_id = $request->input('supplier_id') ?? 1;
        $statements = DB::select("select id, date, description, debit, credit, sum( coalesce(debit, 0) - coalesce(credit, 0) ) over (order by date) as balance from ((select id, date,s.description as description, s.amount as debit, null as credit from supplier_receivings s WHERE s.supplier_id = '$supplier_id' ) union all (select id, date, t.description as description,  null as debit, t.amount from transaction_movements t WHERE t.supplier_id = '$supplier_id')) b WHERE b.date BETWEEN '$start_date' AND '$end_date' order by b.date ;");
        $systems = System::all();
        return view('pages.reports.reports_supplier_credit_report',compact('statements','systems'));
    }
    public function provision_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-01-01');
        $end_date = $request->input('end_date') ?? date('Y-12-t');
        $provisions = ProvisionTax::whereBetween('date',[$start_date,$end_date])->get();
        return view('pages.reports.reports_provision_report',compact('provisions'));
    }
    public function deduction_report(Request $request){
        $staffs = Staff::getList();
        $data = [
            'staffs' => $staffs
        ];
        return view('pages.reports.reports_deduction_report')->with($data);
    }

    public function transaction_movement_report(Request $request){
        $efds = Efd::all();
        $systems = System::where('id','!=',7)->where('id','!=',6)->where('id','!=',5)->get();
        $few_systems = System::where('id','!=',1)->where('id','!=',2)->where('id','!=',3)->get();
        $all_systems = system::all();
        $data = [
            'efds' => $efds,
            'systems' => $systems,
            'all_systems' => $all_systems,
            'few_systems' => $few_systems,
        ];
        return view('pages.reports.reports_transaction_movement_report')->with($data);
    }

    public function supplier_report(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-t');
        $supplier_id = $request->input('supplier_id') ?? 1;
        $statements = DB::select("select id, date, description, debit, credit, sum( coalesce(debit, 0) - coalesce(credit, 0) ) over (order by date) as balance from ((select id, date,s.description as description, s.amount as debit, null as credit from supplier_receivings s WHERE s.supplier_id = '$supplier_id' ) union all (select id, date, t.description as description,  null as debit, t.amount from transaction_movements t WHERE t.supplier_id = '$supplier_id')) b WHERE b.date BETWEEN '$start_date' AND '$end_date' order by b.date ;");
        $suppliers = Supplier::all();
        return view('pages.reports.reports_supplier_report',compact('statements','suppliers'));
    }


    public function supplier_report_search(Request $request){
        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-t');
        $supplier_id = $request->input('supplier_id') ?? 1;
        $statements = DB::select("select id, date, description, debit, credit, sum( coalesce(debit, 0) - coalesce(credit, 0) ) over (order by date) as balance from ((select id, date,s.description as description, s.amount as debit, null as credit from supplier_receivings s WHERE s.supplier_id = '$supplier_id' ) union all (select id, date, t.description as description,  null as debit, t.amount from transaction_movements t WHERE t.supplier_id = '$supplier_id')) b WHERE b.date BETWEEN '$start_date' AND '$end_date' order by b.date ;");
        $suppliers = Supplier::all();
        return view('pages.reports.reports_supplier_report',compact('statements','suppliers'));
    }

    public function transaction_movement_report_search(Request $request){
        $start_date = $request->input('start_date') ?? '2000-01-01';
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $submit = $request->input('submit');

        $transactions = DB::select("SELECT s.name, SUM(c.amount) AS amount FROM supervisors s JOIN collections c ON (c.supervisor_id = s.id) WHERE c.date BETWEEN '$start_date' AND '$end_date' GROUP BY s.id,c.supervisor_id");
        $payments = DB::select("SELECT s.name, SUM(c.amount) AS amount FROM suppliers s JOIN transaction_movements c ON (c.supplier_id = s.id) WHERE c.date BETWEEN '$start_date' AND '$end_date' GROUP BY s.id,c.supplier_id");


        return view('pages.reports.reports_transaction_movement_report',compact('transactions','payments'));
    }

    public function collection_report(Request $request){
        $collections = Collection::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::where('employee_id',1)->get();
        $supervisor_with_amount_of_collections = DB::select('SELECT SUM(c.amount) as total_collection, s.name as supervisor_name,c.date as collection_date FROM collections c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_collections' => $supervisor_with_amount_of_collections,
            'supervisors' => $supervisors,
            'collections' => $collections
        ];
        return view('pages.reports.reports_collection_report')->with($data);
    }

    public function collection_per_system_report(Request $request){
        $collections = Collection::whereDate('date', DB::raw('CURDATE()'))->get();
        $systems = System::all();
        // $system_with_amount_of_collections = DB::select('SELECT SUM(c.amount) as total_collection, s.name as system_name,c.date as collection_date FROM collections c JOIN systems s ON (s.id = c.system_id) JOIN systems sy ON (sy.id = s.system_id) GROUP BY c.system_id,c.date');
        $data = [
            //   'system_with_amount_of_collections' => $system_with_amount_of_collections,
            'systems' => $systems,
            'collections' => $collections
        ];
        return view('pages.reports.reports_collection_per_system_report')->with($data);
    }

    public function supplier_transaction_report(Request $request){
        $transaction_movements = TransactionMovement::whereDate('date', DB::raw('CURDATE()'))->get();
        $suppliers = Supplier::all();
        $supplier_with_amount_of_transaction_movements = DB::select('SELECT SUM(c.amount) as total_transaction_movement, s.name as supplier_name,c.date as transaction_movement_date FROM transaction_movements c JOIN suppliers s ON (s.id = c.supplier_id) GROUP BY c.supplier_id,c.date');
        $data = [
            'supplier_with_amount_of_transaction_movements' => $supplier_with_amount_of_transaction_movements,
            'suppliers' => $suppliers,
            'transaction_movements' => $transaction_movements
        ];
        return view('pages.reports.reports_supplier_transaction_report')->with($data);
    }

    public function supplier_receiving_report(Request $request){
        $supplier_receivings = TransactionMovement::whereDate('date', DB::raw('CURDATE()'))->get();
        $suppliers = Supplier::all();
        $supplier_with_amount_of_supplier_receivings = DB::select('SELECT SUM(c.amount) as total_supplier_receiving, s.name as supplier_name,c.date as supplier_receiving_date FROM supplier_receivings c JOIN suppliers s ON (s.id = c.supplier_id) GROUP BY c.supplier_id,c.date');
        $data = [
            'supplier_with_amount_of_supplier_receivings' => $supplier_with_amount_of_supplier_receivings,
            'suppliers' => $suppliers,
            'supplier_receivings' => $supplier_receivings
        ];
        return view('pages.reports.reports_supplier_receiving_report')->with($data);
    }

    public function expenses_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::all();
        $supervisor_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expenses, s.name as supervisor_name,c.date as expense_date FROM expenses c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_expenses' => $supervisor_with_amount_of_expenses,
            'supervisors' => $supervisors,
            'expenses' => $expenses
        ];
        return view('pages.reports.reports_expenses_report')->with($data);
    }

    public function efd_report(Request $request){
        $efds = Efd::all();
        $data = [
            'efds' => $efds,
        ];
        return view('pages.reports.reports_efd_report')->with($data);
    }

    public function detailed_efd_report(Request $request){
        $efds = Efd::all();
        $data = [
            'efds' => $efds,
        ];
        return view('pages.reports.reports_detailed_efd_report')->with($data);
    }

    public function expenses_per_system_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $systems = System::all();
        // $system_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expense, s.name as system_name,c.date as expense_date FROM expenses c JOIN systems s ON (s.id = c.system_id) JOIN systems sy ON (sy.id = s.system_id) GROUP BY c.system_id,c.date');
        $data = [
            //   'system_with_amount_of_expenses' => $system_with_amount_of_expenses,
            'systems' => $systems,
            'expenses' => $expenses
        ];
        return view('pages.reports.reports_expenses_per_system_report')->with($data);
    }
    public function expenses_categories_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $categories = ExpensesCategory::all();
        $categories_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expenses,
       s.name as category_name,c.date as expense_date FROM expenses c
           JOIN expenses_categories s ON (s.id = c.expenses_sub_category_id)
           JOIN expenses_sub_categories sc ON (sc.expenses_category_id = c.id)
            GROUP BY c.expenses_sub_category_id,c.date');
        $data = [
            'categories_with_amount_of_expenses' => $categories_with_amount_of_expenses,
            'expenses_categories' => $categories,
            'expenses' => $expenses
        ];
        return view('pages.reports.reports_expenses_categories_report')->with($data);
    }
    public function expenses_sub_categories_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $categories = ExpensesSubCategory::all();
        $categories_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expenses,
       s.name as category_name,c.date as expense_date FROM expenses c
           JOIN expenses_categories s ON (s.id = c.expenses_sub_category_id)
           JOIN expenses_sub_categories sc ON (sc.expenses_category_id = c.id)
            GROUP BY c.expenses_sub_category_id,c.date');
        $data = [
            'categories_with_amount_of_expenses' => $categories_with_amount_of_expenses,
            'expenses_categories' => $categories,
            'expenses' => $expenses
        ];
        return view('pages.reports.reports_expenses_sub_categories_report')->with($data);
    }
    public function allowance_subscriptions_report(Request $request){
        $allowances = Allowance::all();
        $staffs = Staff::getList();
        $data = [
            'staffs' => $staffs,
            'allowances' => $allowances,
        ];
        return view('pages.reports.reports_allowance_subscriptions_report')->with($data);
    }

}
