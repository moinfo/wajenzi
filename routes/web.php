<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::middleware(['auth'])->group(function () {
    Route::get('/', [App\Http\Controllers\DashboardController::class, 'index'])->name('home');
    Route::match(['get', 'post'], '/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::match(['get', 'post'], '/404', [App\Http\Controllers\ErrorController::class, 'index'])->name('404');
    Route::view('/lock', 'auth.lock');
//    Route::get('notification', 'HomeController@notification');

    Route::match(['get', 'post'], '/ajax/{fx}', [App\Http\Controllers\AjaxController::class, 'index'])->name('ajax_request');
    Route::match(['get', 'post'], '/AjaxController', [App\Http\Controllers\AjaxController::class, 'ajaxRequestPost'])->name('ajax_request.post');

    Route::match(['get', 'post'], '/bank_reconciliation', [App\Http\Controllers\BankReconciliationController::class, 'index'])->name('bank_reconciliation');
    Route::match(['get', 'post'], '/transfer', [App\Http\Controllers\BankReconciliationController::class, 'transfer'])->name('transfer');
    Route::match(['get', 'post'], '/receiving', [App\Http\Controllers\ReceivingController::class, 'index'])->name('receiving');
    Route::match(['get', 'post'], '/transfer_reports', [App\Http\Controllers\BankReconciliationController::class, 'transferReports'])->name('transfer_reports');
    Route::match(['get', 'post'], '/transfer_by_only_supplier_reports', [App\Http\Controllers\BankReconciliationController::class, 'transfer_by_only_supplier_reports'])->name('transfer_by_only_supplier_reports');

    Route::match(['get', 'post'], '/vat_payment', [App\Http\Controllers\VatPaymentController::class, 'index'])->name('vat_payment');
    Route::match(['get', 'post'], '/vat_payment/{id}/{document_type_id}', [App\Http\Controllers\VatPaymentController::class, 'vat_payment'])->name('individual_vat_payment');

    Route::match(['get', 'post'], '/collection', [App\Http\Controllers\CollectionController::class, 'index'])->name('collection');
    Route::match(['get', 'post'], '/collection/{id}/{document_type_id}', [App\Http\Controllers\CollectionController::class, 'collection'])->name('collections');
    Route::match(['get', 'post'], '/collection_search', [App\Http\Controllers\CollectionController::class, 'search'])->name('collection_search');

    Route::match(['get', 'post'], '/transaction_movement', [App\Http\Controllers\TransactionMovementController::class, 'index'])->name('transaction_movement');
    Route::match(['get', 'post'], '/transaction_movement/{id}/{document_type_id}', [App\Http\Controllers\TransactionMovementController::class, 'transaction_movement'])->name('transaction_movements');
    Route::match(['get', 'post'], '/transaction_movement_search', [App\Http\Controllers\TransactionMovementController::class, 'search'])->name('transaction_movement_search');

    Route::match(['get', 'post'], '/gross', [App\Http\Controllers\GrossController::class, 'index'])->name('gross');
    Route::match(['get', 'post'], '/gross/{id}/{document_type_id}', [App\Http\Controllers\GrossController::class, 'gross'])->name('grosses');
    Route::match(['get', 'post'], '/gross_search', [App\Http\Controllers\GrossController::class, 'search'])->name('gross_search');

    Route::match(['get', 'post'], '/supplier_receiving', [App\Http\Controllers\SupplierReceivingController::class, 'index'])->name('supplier_receiving');
    Route::match(['get', 'post'], '/supplier_receiving/{id}/{document_type_id}', [App\Http\Controllers\SupplierReceivingController::class, 'supplier_receiving'])->name('supplier_receivings');
    Route::match(['get', 'post'], '/supplier_receiving_search', [App\Http\Controllers\SupplierReceivingController::class, 'search'])->name('supplier_receiving_search');

    Route::match(['get', 'post'], '/staff', [App\Http\Controllers\StaffController::class, 'index'])->name('staff');
    Route::match(['get', 'post'], '/accounting', [App\Http\Controllers\AccoutingController::class, 'index'])->name('accounting');
    Route::match(['get', 'post'], '/list_asset_properties', [App\Http\Controllers\AssetPropertyController::class, 'getAssetProperties'])->name('list_asset_properties');
    Route::match(['get', 'post'], '/transfer_balance', [App\Http\Controllers\BankReconciliationController::class, 'getTransferredBalance'])->name('transfer_balance');
    Route::match(['get', 'post'], '/sub_category_list', [App\Http\Controllers\SubCategoryController::class, 'getSubCategories'])->name('sub_category_list');
    Route::match(['get', 'post'], '/charge', [App\Http\Controllers\FinancialChargeController::class, 'getCharges'])->name('charge');
    Route::match(['get', 'post'], '/getLastEfdNumber', [App\Http\Controllers\SaleController::class, 'getLastEfdNumber'])->name('getLastEfdNumber');

    Route::match(['get', 'post'], '/makeReadNotification', [App\Http\Controllers\SettingsController::class, 'makeReadNotification'])->name('makeReadNotification');

    Route::match(['get', 'post'], '/reports', [App\Http\Controllers\ReportsController::class, 'index'])->name('reports');
    Route::match(['get', 'post'], '/reports/vat_analysis_report', [App\Http\Controllers\ReportsController::class, 'vat_analysis_report'])->name('reports_vat_analysis');
    Route::match(['get', 'post'], '/reports/exempt_analysis_report', [App\Http\Controllers\ReportsController::class, 'exempt_analysis_report'])->name('reports_exempt_analysis');
    Route::match(['get', 'post'], '/reports/vat_payments_report', [App\Http\Controllers\ReportsController::class, 'vat_payments_report'])->name('reports_vat_payment');
    Route::match(['get', 'post'], '/reports/statement_of_comprehensive_income_report', [App\Http\Controllers\ReportsController::class, 'statement_of_comprehensive_income_report'])->name('reports_statement_of_comprehensive_income_report');
    Route::match(['get', 'post'], '/reports/detailed_expenditure_statement_report', [App\Http\Controllers\ReportsController::class, 'detailed_expenditure_statement_report'])->name('reports_detailed_expenditure_statement_report');
    Route::match(['get', 'post'], '/reports/statement_of_financial_position_report', [App\Http\Controllers\ReportsController::class, 'statement_of_financial_position_report'])->name('reports_statement_of_financial_position_report');

    Route::match(['get', 'post'], '/user', [App\Http\Controllers\UserController::class, 'index'])->name('user');
    Route::match(['get', 'post'], '/user_permissions', [App\Http\Controllers\UserController::class, 'user_permissions'])->name('user_permissions');
    Route::match(['get', 'post'], '/system', [App\Http\Controllers\SystemController::class, 'index'])->name('system');
    Route::match(['get', 'post'], '/sales', [App\Http\Controllers\SaleController::class, 'index'])->name('sales');
    Route::match(['get', 'post'], '/sales/{id}/{document_type_id}', [App\Http\Controllers\SaleController::class, 'sale'])->name('sale');
    Route::match(['get', 'post'], '/purchases', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchases');
    Route::match(['get', 'post'], '/auto_purchases', [App\Http\Controllers\AutoPurchaseController::class, 'index'])->name('auto_purchases');
    Route::match(['get', 'post'], '/purchases/{id}/{document_type_id}', [App\Http\Controllers\PurchaseController::class, 'purchase'])->name('purchase');
    Route::match(['get', 'post'], '/payroll', [App\Http\Controllers\PayrollController::class, 'index'])->name('payroll');
    Route::match(['get', 'post'], '/system_inventory', [App\Http\Controllers\SystemInventoryController::class, 'index'])->name('system_inventory');
    Route::match(['get', 'post'], '/system_inventory/{id}/{document_type_id}', [App\Http\Controllers\SystemInventoryController::class, 'system_inventory'])->name('system_inventories');
    Route::match(['get', 'post'], '/system_credit', [App\Http\Controllers\SystemCreditController::class, 'index'])->name('system_credit');
    Route::match(['get', 'post'], '/system_credit/{id}/{document_type_id}', [App\Http\Controllers\SystemCreditController::class, 'system_credit'])->name('system_credits');
    Route::match(['get', 'post'], '/system_capital', [App\Http\Controllers\SystemCapitalController::class, 'index'])->name('system_capital');
    Route::match(['get', 'post'], '/system_capital/{id}/{document_type_id}', [App\Http\Controllers\SystemCapitalController::class, 'system_capital'])->name('system_capitals');
    Route::match(['get', 'post'], '/system_cash', [App\Http\Controllers\SystemCashController::class, 'index'])->name('system_cash');
    Route::match(['get', 'post'], '/system_cash/{id}/{document_type_id}', [App\Http\Controllers\SystemCashController::class, 'system_cash'])->name('system_cashes');
    Route::match(['get', 'post'], '/bank_withdraw', [App\Http\Controllers\BankWithdrawController::class, 'index'])->name('bank_withdraw');
    Route::match(['get', 'post'], '/bank_withdraw/{id}/{document_type_id}', [App\Http\Controllers\BankWithdrawController::class, 'bank_withdraw'])->name('bank_withdraws');
    Route::match(['get', 'post'], '/bank_deposit', [App\Http\Controllers\BankDepositController::class, 'index'])->name('bank_deposit');
    Route::match(['get', 'post'], '/bank_deposit/{id}/{document_type_id}', [App\Http\Controllers\BankDepositController::class, 'bank_deposit'])->name('bank_deposits');

    Route::match(['get', 'post'], '/expenses', [App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses');
    Route::match(['get', 'post'], '/expenses/{id}/{document_type_id}', [App\Http\Controllers\ExpenseController::class, 'expense'])->name('expense');
    Route::match(['get', 'post'], '/payroll/payroll_view/{month}/{year}', [App\Http\Controllers\PayrollController::class, 'payroll_view'])->name('payroll_view');
    Route::match(['get', 'post'], '/expenses_search', [App\Http\Controllers\ExpenseController::class, 'search'])->name('expenses_search');

    Route::match(['get', 'post'], '/financial_charges', [App\Http\Controllers\FinancialChargeController::class, 'index'])->name('financial_charges');
    Route::match(['get', 'post'], '/provision_tax', [App\Http\Controllers\ProvisionTaxController::class, 'index'])->name('provision_tax');


    Route::match(['get', 'post'], '/user/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('user_profile');
    Route::match(['get', 'post'], '/user/settings', [App\Http\Controllers\UserController::class, 'settings'])->name('user_settings');
    Route::match(['get', 'post'], '/user/inbox', [App\Http\Controllers\UserController::class, 'inbox'])->name('user_inbox');
    Route::match(['get', 'post'], '/user/notifications', [App\Http\Controllers\UserController::class, 'notifications'])->name('user_notifications');
    Route::match(['get', 'post'], '/user/notifications/read_all', [App\Http\Controllers\UserController::class, 'readAllNotification'])->name('read_all_notifications');

    Route::match(['get', 'post'], '/reports/bank_report', [App\Http\Controllers\ReportsController::class, 'bank_report'])->name('reports_bank_report');
    Route::match(['get', 'post'], '/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('hr_settings');
    Route::match(['get', 'post'], '/settings/supervisor', [App\Http\Controllers\SettingsController::class, 'supervisors'])->name('hr_settings_supervisors');
    Route::match(['get', 'post'], '/settings/departments', [App\Http\Controllers\SettingsController::class, 'departments'])->name('hr_settings_departments');
    Route::match(['get', 'post'], '/settings/allowances', [App\Http\Controllers\SettingsController::class, 'allowances'])->name('hr_settings_allowances');
    Route::match(['get', 'post'], '/settings/staff_salaries', [App\Http\Controllers\SettingsController::class, 'staff_salaries'])->name('hr_settings_staff_salary');
    Route::match(['get', 'post'], '/settings/staff_loans', [App\Http\Controllers\SettingsController::class, 'staff_loans'])->name('hr_settings_staff_loan');
    Route::match(['get', 'post'], '/settings/staff_loans/{id}/{document_type_id}', [App\Http\Controllers\SettingsController::class, 'staff_loan'])->name('staff_loan');
    Route::match(['get', 'post'], '/settings/allowance_subscriptions', [App\Http\Controllers\SettingsController::class, 'allowance_subscriptions'])->name('allowance_subscriptions');
    Route::match(['get', 'post'], '/settings/advance_salaries', [App\Http\Controllers\SettingsController::class, 'advance_salaries'])->name('hr_settings_advance_salary');
    Route::match(['get', 'post'], '/settings/advance_salaries/{id}/{document_type_id}', [App\Http\Controllers\SettingsController::class, 'advance_salary'])->name('advance_salary');
    Route::match(['get', 'post'], '/settings/deductions', [App\Http\Controllers\SettingsController::class, 'deductions'])->name('hr_settings_deductions');
    Route::match(['get', 'post'], '/settings/deduction_subscriptions', [App\Http\Controllers\SettingsController::class, 'deduction_subscriptions'])->name('hr_settings_deduction_subscriptions');
    Route::match(['get', 'post'], '/settings/deduction_settings', [App\Http\Controllers\SettingsController::class, 'deduction_settings'])->name('hr_settings_deduction_settings');
    Route::match(['get', 'post'], '/settings/banks', [App\Http\Controllers\SettingsController::class, 'banks'])->name('hr_settings_banks');
    Route::match(['get', 'post'], '/settings/assets', [App\Http\Controllers\SettingsController::class, 'assets'])->name('hr_settings_assets');
    Route::match(['get', 'post'], '/settings/asset_properties', [App\Http\Controllers\SettingsController::class, 'asset_properties'])->name('hr_settings_asset_properties');
    Route::match(['get', 'post'], '/settings/systems', [App\Http\Controllers\SettingsController::class, 'systems'])->name('hr_settings_systems');
    Route::match(['get', 'post'], '/settings/users', [App\Http\Controllers\SettingsController::class, 'users'])->name('hr_settings_users');
    Route::match(['get', 'post'], '/settings/approvals', [App\Http\Controllers\ApprovalController::class, 'approvals'])->name('hr_settings_approvals');
    Route::match(['get', 'post'], '/settings/positions', [App\Http\Controllers\SettingsController::class, 'positions'])->name('hr_settings_positions');
    Route::match(['get', 'post'], '/settings/roles', [App\Http\Controllers\SettingsController::class, 'roles'])->name('hr_settings_roles');
    Route::match(['get', 'post'], '/settings/permissions', [App\Http\Controllers\SettingsController::class, 'permissions'])->name('hr_settings_permissions');
    Route::match(['get', 'post'], '/settings/suppliers', [App\Http\Controllers\SettingsController::class, 'suppliers'])->name('hr_settings_suppliers');
    Route::match(['get', 'post'], '/settings/items', [App\Http\Controllers\SettingsController::class, 'items'])->name('hr_settings_items');
    Route::match(['get', 'post'], '/settings/efd', [App\Http\Controllers\SettingsController::class, 'efd'])->name('hr_settings_efd');
    Route::match(['get', 'post'], '/settings/expenses_categories', [App\Http\Controllers\SettingsController::class, 'expenses_categories'])->name('hr_settings_expenses_categories');
    Route::match(['get', 'post'], '/settings/expenses_sub_categories', [App\Http\Controllers\SettingsController::class, 'expenses_sub_categories'])->name('hr_settings_expenses_sub_categories');
    Route::match(['get', 'post'], '/settings/financial_charge_categories', [App\Http\Controllers\SettingsController::class, 'financial_charge_categories'])->name('hr_settings_financial_charge_categories');
    Route::match(['get', 'post'], '/settings/approval_document_types', [App\Http\Controllers\SettingsController::class, 'approval_document_types'])->name('hr_settings_approval_document_types');
    Route::match(['get', 'post'], '/settings/approval_document_types', [App\Http\Controllers\SettingsController::class, 'approval_document_types'])->name('hr_settings_approval_document_types');
    Route::match(['get', 'post'], '/settings/approval_levels', [App\Http\Controllers\SettingsController::class, 'approval_levels'])->name('hr_settings_approval_levels');
    Route::match(['get', 'post'], '/settings/assign_user_groups', [App\Http\Controllers\SettingsController::class, 'assign_user_groups'])->name('hr_settings_assign_user_groups');
    Route::match(['get', 'post'], '/settings/user_groups', [App\Http\Controllers\SettingsController::class, 'user_groups'])->name('hr_settings_user_groups');
    Route::match(['get', 'post'], '/settings/categories', [App\Http\Controllers\SettingsController::class, 'categories'])->name('hr_settings_categories');
    Route::match(['get', 'post'], '/settings/sub_categories', [App\Http\Controllers\SettingsController::class, 'sub_categories'])->name('hr_settings_sub_categories');
    Route::match(['get', 'post'], '/settings/statutory_payments', [App\Http\Controllers\SettingsController::class, 'statutory_payments'])->name('hr_settings_statutory_payments');
    Route::match(['get', 'post'], '/settings/statutory_payments/{id}/{document_type_id}', [App\Http\Controllers\SettingsController::class, 'statutory_payment'])->name('hr_settings_statutory_payment');
    Route::match(['get', 'post'], '/settings/user_group', [App\Http\Controllers\SettingsController::class, 'user_group'])->name('hr_settings_user_group');
    Route::match(['get', 'post'], '/settings/stock', [App\Http\Controllers\SettingsController::class, 'stock'])->name('hr_settings_stock');
    Route::match(['get', 'post'], '/reports', [App\Http\Controllers\ReportsController::class, 'index'])->name('reports');
    Route::match(['get', 'post'], '/reports/allowance_subscriptions_report', [App\Http\Controllers\ReportsController::class, 'allowance_subscriptions_report'])->name('reports_allowance_subscriptions_report');
    Route::match(['get', 'post'], '/reports/general_report', [App\Http\Controllers\ReportsController::class, 'general_report'])->name('reports_general_report');
    Route::match(['get', 'post'], '/reports/supervisor_report', [App\Http\Controllers\ReportsController::class, 'supervisor_report'])->name('reports_supervisor_report');
    Route::match(['get', 'post'], '/reports/deduction_report', [App\Http\Controllers\ReportsController::class, 'deduction_report'])->name('reports_deduction_report');
    Route::match(['get', 'post'], '/reports/supplier_report', [App\Http\Controllers\ReportsController::class, 'supplier_report'])->name('reports_supplier_report');
    Route::match(['get', 'post'], '/reports/supplier_report_search', [App\Http\Controllers\ReportsController::class, 'supplier_report_search'])->name('reports_supplier_report_search');
    Route::match(['get', 'post'], '/reports/supplier_receiving_report', [App\Http\Controllers\ReportsController::class, 'supplier_receiving_report'])->name('reports_supplier_receiving_report');
    Route::match(['get', 'post'], '/reports/supplier_transaction_report', [App\Http\Controllers\ReportsController::class, 'supplier_transaction_report'])->name('reports_supplier_transaction_report');
    Route::match(['get', 'post'], '/reports/transaction_movement_report', [App\Http\Controllers\ReportsController::class, 'transaction_movement_report'])->name('reports_transaction_movement_report');
    Route::match(['get', 'post'], '/reports/transaction_movement_report_search', [App\Http\Controllers\ReportsController::class, 'transaction_movement_report_search'])->name('transaction_movement_report_search');
    Route::match(['get', 'post'], '/reports/collection_report', [App\Http\Controllers\ReportsController::class, 'collection_report'])->name('reports_collection_report');
    Route::match(['get', 'post'], '/reports/collection_per_system_report', [App\Http\Controllers\ReportsController::class, 'collection_per_system_report'])->name('reports_collection_per_system_report');
    Route::match(['get', 'post'], '/reports/gross_summary_report', [App\Http\Controllers\ReportsController::class, 'gross_summary_report'])->name('reports_gross_summary_report');
    Route::match(['get', 'post'], '/reports/expenses_report', [App\Http\Controllers\ReportsController::class, 'expenses_report'])->name('reports_expenses_report');
    Route::match(['get', 'post'], '/reports/total_credit_suppliers_report', [App\Http\Controllers\ReportsController::class, 'total_credit_suppliers_report'])->name('reports_total_credit_suppliers_report');
    Route::match(['get', 'post'], '/reports/total_current_credit_suppliers_report', [App\Http\Controllers\ReportsController::class, 'total_current_credit_suppliers_report'])->name('reports_total_current_credit_suppliers_report');
    Route::match(['get', 'post'], '/reports/expenses_per_system_report', [App\Http\Controllers\ReportsController::class, 'expenses_per_system_report'])->name('reports_expenses_per_system_report');
    Route::match(['get', 'post'], '/reports/expenses_categories_report', [App\Http\Controllers\ReportsController::class, 'expenses_categories_report'])->name('reports_expenses_categories_report');
    Route::match(['get', 'post'], '/reports/expenses_sub_categories_report', [App\Http\Controllers\ReportsController::class, 'expenses_sub_categories_report'])->name('reports_expenses_sub_categories_report');
    Route::match(['get', 'post'], '/reports/bank_statement_report', [App\Http\Controllers\ReportsController::class, 'bank_statement_report'])->name('reports_bank_statement_report');
    Route::match(['get', 'post'], '/reports/business_position_report', [App\Http\Controllers\ReportsController::class, 'business_position_report'])->name('reports_business_position_report');
    Route::match(['get', 'post'], '/reports/business_position_details_report', [App\Http\Controllers\ReportsController::class, 'business_position_details_report'])->name('reports_business_position_details_report');
    Route::match(['get', 'post'], '/reports/sales_report', [App\Http\Controllers\ReportsController::class, 'sales_report'])->name('reports_sales_report');
    Route::match(['get', 'post'], '/reports/purchases_report', [App\Http\Controllers\ReportsController::class, 'purchases_report'])->name('reports_purchases_report');
    Route::match(['get', 'post'], '/reports/purchases_by_supplier_report', [App\Http\Controllers\ReportsController::class, 'purchases_by_supplier_report'])->name('reports_purchases_by_supplier_report');
    Route::match(['get', 'post'], '/reports/efd_report', [App\Http\Controllers\ReportsController::class, 'efd_report'])->name('reports_efd_report');
    Route::match(['get', 'post'], '/reports/detailed_efd_report', [App\Http\Controllers\ReportsController::class, 'detailed_efd_report'])->name('reports_detailed_efd_report');
    Route::match(['get', 'post'], '/reports/bank_reconciliation_report', [App\Http\Controllers\ReportsController::class, 'bank_reconciliation_report'])->name('reports_bank_reconciliation_report');
    Route::match(['get', 'post'], '/reports/statement_report', [App\Http\Controllers\ReportsController::class, 'statement_report'])->name('reports_statement_report');
    Route::match(['get', 'post'], '/reports/supplier_bank_deposit_report', [App\Http\Controllers\ReportsController::class, 'supplier_bank_deposit_report'])->name('reports_supplier_bank_deposit_report');
    Route::match(['get', 'post'], '/reports/auto_transaction_report', [App\Http\Controllers\ReportsController::class, 'auto_transaction_report'])->name('reports_auto_transaction_report');
    Route::match(['get', 'post'], '/reports/provision_report', [App\Http\Controllers\ReportsController::class, 'provision_report'])->name('reports_provision_report');
    Route::match(['get', 'post'], '/reports/statutory_payment_report', [App\Http\Controllers\ReportsController::class, 'statutory_payment_report'])->name('reports_statutory_payment_report');
    Route::match(['get', 'post'], '/reports/annually_sales_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_sales_summary_report'])->name('reports_annually_sales_summary_report');
    Route::match(['get', 'post'], '/reports/annually_purchases_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_purchases_summary_report'])->name('reports_annually_purchases_summary_report');
    Route::match(['get', 'post'], '/reports/annually_financial_charges_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_financial_charges_summary_report'])->name('reports_annually_financial_charges_summary_report');
    Route::match(['get', 'post'], '/reports/annually_salaries_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_salaries_summary_report'])->name('reports_annually_salaries_summary_report');
    Route::match(['get', 'post'], '/reports/annually_sdl_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_sdl_summary_report'])->name('reports_annually_sdl_summary_report');
    Route::match(['get', 'post'], '/reports/annually_expenses_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_expenses_summary_report'])->name('reports_annually_expenses_summary_report');
    Route::match(['get', 'post'], '/reports/annually_wcf_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_wcf_summary_report'])->name('reports_annually_wcf_summary_report');
    Route::match(['get', 'post'], '/reports/annually_paye_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_paye_summary_report'])->name('reports_annually_paye_summary_report');
    Route::match(['get', 'post'], '/reports/annually_nssf_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_nssf_summary_report'])->name('reports_annually_nssf_summary_report');
    Route::match(['get', 'post'], '/reports/annually_nhif_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_nhif_summary_report'])->name('reports_annually_nhif_summary_report');
    Route::match(['get', 'post'], '/reports/annually_net_salary_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_net_salary_summary_report'])->name('reports_annually_net_salary_summary_report');
    Route::match(['get', 'post'], '/reports/annually_heslb_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_heslb_summary_report'])->name('reports_annually_heslb_summary_report');
    Route::match(['get', 'post'], '/reports/annually_allowance_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_allowance_summary_report'])->name('reports_annually_allowance_summary_report');
    Route::match(['get', 'post'], '/reports/annually_advance_salary_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_advance_salary_summary_report'])->name('reports_annually_advance_salary_summary_report');
    Route::match(['get', 'post'], '/reports/annually_expense_sub_categories_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_expense_sub_categories_summary_report'])->name('reports_annually_expense_sub_categories_summary_report');

});

Auth::routes(['register' => false]);

