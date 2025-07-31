<?php

use App\Http\Controllers\LeaveRequestController;
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

    Route::match(['get', 'post'], '/expense_adjustable', [App\Http\Controllers\AdjustmentExpenseController::class, 'index'])->name('expense_adjustable');
    Route::match(['get', 'post'], '/bank_reconciliation', [App\Http\Controllers\BankReconciliationController::class, 'index'])->name('bank_reconciliation');
    Route::match(['get', 'post'], '/bank_reconciliation/bank_deposits', [App\Http\Controllers\BankReconciliationController::class, 'bank_reconciliation_deposits'])->name('bank_reconciliation_deposits');
    Route::match(['get', 'post'], '/bank_reconciliation/unrepresented_slip', [App\Http\Controllers\BankReconciliationController::class, 'unrepresented_slip'])->name('unrepresented_slip');
    Route::match(['get', 'post'], '/bank_reconciliation/bank_withdraws', [App\Http\Controllers\BankReconciliationController::class, 'bank_reconciliation_withdraws'])->name('bank_reconciliation_withdraws');
    Route::match(['get', 'post'], '/bank_reconciliation/bank_transfers', [App\Http\Controllers\BankReconciliationController::class, 'bank_reconciliation_transfers'])->name('bank_reconciliation_transfers');
    Route::match(['get', 'post'], '/bank_reconciliation/sales_bank_deposited', [App\Http\Controllers\BankReconciliationController::class, 'bank_reconciliation_sales_bank_deposited'])->name('bank_reconciliation_sales_bank_deposited');
    Route::match(['get', 'post'], '/bank_reconciliation/bank_reconciliation_suppliers_statement', [App\Http\Controllers\BankReconciliationController::class, 'bank_reconciliation_suppliers_statement'])->name('bank_reconciliation_suppliers_statement');
    Route::match(['get', 'post'], '/bank_reconciliation/bank_reconciliation_bank_reconciliation_statement', [App\Http\Controllers\BankReconciliationController::class, 'bank_reconciliation_bank_reconciliation_statement'])->name('bank_reconciliation_bank_reconciliation_statement');
    Route::match(['get', 'post'], '/supplier_targets', [App\Http\Controllers\BankReconciliationController::class, 'supplier_targets'])->name('supplier_targets');
    Route::match(['get', 'post'], '/supplier_target_preparation', [App\Http\Controllers\BankReconciliationController::class, 'supplier_target_preparation'])->name('supplier_target_preparation');
    Route::match(['get', 'post'], '/bank_deposit_report', [App\Http\Controllers\BankReconciliationController::class, 'bank_deposit_report'])->name('bank_deposit_report');
    Route::match(['get', 'post'], '/slip_review_report', [App\Http\Controllers\BankReconciliationController::class, 'slip_review_report'])->name('slip_review_report');
    Route::match(['get', 'post'], '/supplier_commissions', [App\Http\Controllers\BankReconciliationController::class, 'supplier_commissions'])->name('supplier_commissions');
    Route::match(['get', 'post'], '/supplier_targets_report', [App\Http\Controllers\BankReconciliationController::class, 'supplier_targets_report'])->name('supplier_targets_report');
    Route::match(['get', 'post'], '/bank_withdraw_reports', [App\Http\Controllers\BankReconciliationController::class, 'bank_withdraw_reports'])->name('bank_withdraw_reports');
    Route::match(['get', 'post'], '/bank_deposit_reports', [App\Http\Controllers\BankReconciliationController::class, 'bank_deposit_reports'])->name('bank_deposit_reports');
    Route::match(['get', 'post'], '/bank_reconciliations/{id}/{document_type_id}', [App\Http\Controllers\BankReconciliationController::class, 'bank_reconciliation'])->name('bank_reconciliations');
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
    Route::match(['get', 'post'], '/supplier_beneficiary', [App\Http\Controllers\BeneficiaryController::class, 'getSupplierBeneficiary'])->name('supplier_beneficiary');
    Route::match(['get', 'post'], '/get-bonge-sales', [App\Http\Controllers\ReportsController::class, 'getBongeSales'])->name('get.bonge.sales');
    Route::match(['get', 'post'], '/get-target-details', [App\Http\Controllers\SupplierTargetController::class, 'getTargetDetails'])->name('get.target.details');
    Route::match(['get', 'post'], '/supplier_beneficiary_account', [App\Http\Controllers\BeneficiaryController::class, 'getSupplierBeneficiaryAccount'])->name('supplier_beneficiary_account');
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
    Route::match(['get', 'post'], '/update_password', [App\Http\Controllers\UserController::class, 'update_password'])->name('update_password');
    Route::match(['get', 'post'], '/user_permissions', [App\Http\Controllers\UserController::class, 'user_permissions'])->name('user_permissions');
    Route::match(['get', 'post'], '/system', [App\Http\Controllers\SystemController::class, 'index'])->name('system');
    Route::match(['get', 'post'], '/sales', [App\Http\Controllers\SaleController::class, 'index'])->name('sales');
    Route::match(['get', 'post'], '/sale/{id}/{document_type_id}', [App\Http\Controllers\SaleController::class, 'sale'])->name('sale');
    Route::match(['get', 'post'], '/purchases', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchases');
    Route::match(['get', 'post'], '/auto_purchases', [App\Http\Controllers\AutoPurchaseController::class, 'index'])->name('auto_purchases');
    Route::match(['get', 'post'], '/purchase/{id}/{document_type_id}', [App\Http\Controllers\PurchaseController::class, 'purchase'])->name('purchase');

    // Purchase Approval Routes
    Route::post('/purchases/{purchase}/submit', [App\Http\Controllers\PurchaseController::class, 'submit'])->name('purchase.submit');
    Route::post('/purchases/{purchase}/approve', [App\Http\Controllers\PurchaseController::class, 'approve'])->name('purchase.approve');
    Route::post('/purchases/{purchase}/reject', [App\Http\Controllers\PurchaseController::class, 'reject'])->name('purchase.reject');
    Route::match(['get', 'post'], '/payroll', [App\Http\Controllers\PayrollController::class, 'index'])->name('payroll');
    Route::match(['get', 'post'], '/payroll/{id}/{document_type_id}', [App\Http\Controllers\PayrollController::class, 'payroll_view'])->name('payroll_view');
    Route::match(['get', 'post'], '/payroll/create_payroll', [App\Http\Controllers\PayrollController::class, 'create_payroll'])->name('create_payroll');
    Route::match(['get', 'post'], '/payroll/payroll_administration', [App\Http\Controllers\PayrollController::class, 'payroll_administration'])->name('payroll_administration');
    Route::match(['get', 'post'], '/payroll/crdb_bank_file', [App\Http\Controllers\PayrollController::class, 'crdb_bank_file'])->name('crdb_bank_file');
    Route::post('/payroll/bank-file-data', 'App\Http\Controllers\PayrollController@getBankFileData')->name('payroll.bank-file-data');
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
    Route::match(['get', 'post'], '/employee_profile', [App\Http\Controllers\EmployeeProfileController::class, 'index'])->name('employee_profile');

    Route::match(['get', 'post'], '/adjusted_assessment_taxes', [App\Http\Controllers\AdjustedAssessmentTaxController::class, 'index'])->name('adjusted_assessment_taxes');

    Route::match(['get', 'post'], '/withholding_taxes', [App\Http\Controllers\WithholdingTaxController::class, 'index'])->name('withholding_taxes');


    Route::match(['get', 'post'], '/expenses', [App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses');
    Route::match(['get', 'post'], '/expense/{id}/{document_type_id}', [App\Http\Controllers\ExpenseController::class, 'expense'])->name('expense');
//    Route::match(['get', 'post'], '/payroll/payroll_view/{month}/{year}', [App\Http\Controllers\PayrollController::class, 'payroll_view'])->name('payroll_view');
    Route::match(['get', 'post'], '/expenses_search', [App\Http\Controllers\ExpenseController::class, 'search'])->name('expenses_search');

    Route::match(['get', 'post'], '/financial_charges', [App\Http\Controllers\FinancialChargeController::class, 'index'])->name('financial_charges');
    Route::match(['get', 'post'], '/provision_tax', [App\Http\Controllers\ProvisionTaxController::class, 'index'])->name('provision_tax');

    Route::match(['get', 'post'], '/eSMS', [App\Http\Controllers\MessageController::class, 'index'])->name('eSMS');
    Route::match(['get', 'post'], '/bulk_sms', [App\Http\Controllers\MessageController::class, 'bulk_sms'])->name('bulk_sms');



    Route::match(['get', 'post'], '/user/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('user_profile');
    Route::match(['get', 'post'], '/user/settings', [App\Http\Controllers\UserController::class, 'settings'])->name('user_settings');
    Route::match(['get', 'post'], '/user/inbox', [App\Http\Controllers\UserController::class, 'inbox'])->name('user_inbox');
    Route::match(['get', 'post'], '/user/notifications', [App\Http\Controllers\UserController::class, 'notifications'])->name('user_notifications');
    Route::match(['get', 'post'], '/user/notifications/read_all', [App\Http\Controllers\UserController::class, 'readAllNotification'])->name('read_all_notifications');

    Route::match(['get', 'post'], '/reports/bank_report', [App\Http\Controllers\ReportsController::class, 'bank_report'])->name('reports_bank_report');
    Route::match(['get', 'post'], '/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('hr_settings');
    Route::match(['get', 'post'], '/settings/supervisor', [App\Http\Controllers\SettingsController::class, 'supervisors'])->name('hr_settings_supervisors');
    Route::match(['get', 'post'], '/settings/beneficiary_account', [App\Http\Controllers\SettingsController::class, 'beneficiary_account'])->name('beneficiary_account');
    Route::match(['get', 'post'], '/settings/beneficiaries', [App\Http\Controllers\SettingsController::class, 'beneficiaries'])->name('beneficiaries');
    Route::match(['get', 'post'], '/settings/wakalas', [App\Http\Controllers\SettingsController::class, 'wakalas'])->name('wakalas');
    Route::match(['get', 'post'], '/settings/departments', [App\Http\Controllers\SettingsController::class, 'departments'])->name('hr_settings_departments');
    Route::match(['get', 'post'], '/settings/allowances', [App\Http\Controllers\SettingsController::class, 'allowances'])->name('hr_settings_allowances');
    Route::match(['get', 'post'], '/settings/staff_salaries', [App\Http\Controllers\SettingsController::class, 'staff_salaries'])->name('hr_settings_staff_salary');
    Route::match(['get', 'post'], '/settings/staff_loans', [App\Http\Controllers\SettingsController::class, 'staff_loans'])->name('hr_settings_staff_loan');
    Route::match(['get', 'post'], '/settings/staff_loans/{id}/{document_type_id}', [App\Http\Controllers\SettingsController::class, 'staff_loan'])->name('staff_loan');
    Route::match(['get', 'post'], '/settings/allowance_subscriptions', [App\Http\Controllers\SettingsController::class, 'allowance_subscriptions'])->name('allowance_subscriptions');
    Route::match(['get', 'post'], '/settings/advance_salaries', [App\Http\Controllers\SettingsController::class, 'advance_salaries'])->name('hr_settings_advance_salary');
    Route::match(['get', 'post'], '/settings/system_settings', [App\Http\Controllers\SettingsController::class, 'system_settings'])->name('system_settings');
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
    Route::match(['get', 'post'], '/settings/process_approval_flows', [App\Http\Controllers\SettingsController::class, 'process_approval_flows'])->name('hr_settings_process_approval_flows');
    Route::match(['get', 'post'], '/settings/process_approval_flow_steps', [App\Http\Controllers\SettingsController::class, 'process_approval_flow_steps'])->name('hr_settings_process_approval_flow_steps');
    Route::match(['get', 'post'], '/statutory_payments', [App\Http\Controllers\StatutoryPaymentController::class, 'statutory_payments'])->name('hr_settings_statutory_payments');
    Route::match(['get', 'post'], '/statutory_payments/{id}/{document_type_id}', [App\Http\Controllers\StatutoryPaymentController::class, 'statutory_payment'])->name('hr_settings_statutory_payment');
    Route::match(['get', 'post'], '/settings/user_group', [App\Http\Controllers\SettingsController::class, 'user_group'])->name('hr_settings_user_group');
    Route::match(['get', 'post'], '/settings/stock', [App\Http\Controllers\SettingsController::class, 'stock'])->name('hr_settings_stock');
    Route::match(['get', 'post'], '/settings/leave_types', [App\Http\Controllers\SettingsController::class, 'leave_types'])->name('hr_settings_leave_types');
    Route::match(['get', 'post'], '/settings/client_sources', [App\Http\Controllers\SettingsController::class, 'client_sources'])->name('client_sources');
    Route::match(['get', 'post'], '/settings/roles_permissions', [App\Http\Controllers\SettingsController::class, 'updateRolePermissions'])->name('hr_settings_role_permissions');
    Route::match(['get', 'post'], '/settings/roles_users', [App\Http\Controllers\SettingsController::class, 'assignUsersToRole'])->name('hr_settings_role_users');

    // BOQ Template System Routes
    Route::match(['get', 'post'], '/settings/building_types', [App\Http\Controllers\SettingsController::class, 'building_types'])->name('hr_settings_building_types');
    Route::match(['get', 'post'], '/settings/boq_item_categories', [App\Http\Controllers\SettingsController::class, 'boq_item_categories'])->name('hr_settings_boq_item_categories');
    Route::match(['get', 'post'], '/settings/construction_stages', [App\Http\Controllers\SettingsController::class, 'construction_stages'])->name('hr_settings_construction_stages');
    Route::match(['get', 'post'], '/settings/activities', [App\Http\Controllers\SettingsController::class, 'activities'])->name('hr_settings_activities');
    Route::match(['get', 'post'], '/settings/sub_activities', [App\Http\Controllers\SettingsController::class, 'sub_activities'])->name('hr_settings_sub_activities');
    Route::match(['get', 'post'], '/settings/boq_items', [App\Http\Controllers\SettingsController::class, 'boq_items'])->name('hr_settings_boq_items');
    Route::match(['get', 'post'], '/settings/boq_templates', [App\Http\Controllers\SettingsController::class, 'boq_templates'])->name('hr_settings_boq_templates');
    Route::match(['get', 'post'], '/settings/boq_template_builder', [App\Http\Controllers\SettingsController::class, 'boq_template_builder'])->name('hr_settings_boq_template_builder');
    Route::get('/settings/boq_template_report/{templateId}', [App\Http\Controllers\SettingsController::class, 'boq_template_report'])->name('hr_settings_boq_template_report');
    
    // Debug route for BOQ template builder issues
    Route::get('/debug/boq_template/{templateId}', function($templateId) {
        $template = \App\Models\BoqTemplate::with(['templateStages.constructionStage', 'templateStages.templateActivities.activity', 'templateStages.templateActivities.templateSubActivities.subActivity.materials.boqItem', 'buildingType'])->find($templateId);
        
        return response()->json([
            'template_id_provided' => $templateId,
            'template_found' => $template ? true : false,
            'template_data' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'building_type' => $template->buildingType ? $template->buildingType->name : null,
                'is_active' => $template->is_active,
                'stages_count' => $template->templateStages->count(),
                'template_exists_check' => $template ? 'YES' : 'NO',
                'template_is_null' => $template === null ? 'YES' : 'NO',
                'template_is_empty' => empty($template) ? 'YES' : 'NO'
            ] : null,
            'construction_stages_count' => \App\Models\ConstructionStage::count(),
            'boq_items_count' => \App\Models\BoqTemplateItem::count(),
            'auth_user' => auth()->check() ? auth()->user()->name : 'Not authenticated'
        ]);
    })->middleware('auth');

    Route::match(['get', 'post'], '/finance', [App\Http\Controllers\SettingsController::class, 'finance'])->name('finance');
    Route::match(['get', 'post'], '/finance/financial_settings/account_types', [App\Http\Controllers\SettingsController::class, 'account_types'])->name('account_types');
    Route::match(['get', 'post'], '/finance/financial_settings/charts_of_accounts', [App\Http\Controllers\SettingsController::class, 'charts_of_accounts'])->name('charts_of_accounts');
    Route::match(['get', 'post'], '/finance/financial_settings/exchange_rates', [App\Http\Controllers\SettingsController::class, 'exchange_rates'])->name('exchange_rates');
    Route::match(['get', 'post'], '/finance/financial_settings/charts_of_account_usages', [App\Http\Controllers\SettingsController::class, 'charts_of_account_usages'])->name('charts_of_account_usages');
    Route::match(['get', 'post'], '/finance/financial_settings/chart_of_account_variables', [App\Http\Controllers\SettingsController::class, 'chart_of_account_variables'])->name('chart_of_account_variables');
    Route::match(['get', 'post'], '/finance/petty_cash_management/petty_cash_refill_requests', [App\Http\Controllers\SettingsController::class, 'petty_cash_refill_requests'])->name('petty_cash_refill_requests');
    Route::match(['get', 'post'], '/finance/petty_cash_management/petty_cash_refill_requests/{id}/{document_type_id}', [App\Http\Controllers\SettingsController::class, 'petty_cash_refill_request'])->name('petty_cash_refill_request');
    Route::match(['get', 'post'], '/finance/imprest_management/imprest_requests', [App\Http\Controllers\SettingsController::class, 'imprest_requests'])->name('imprest_requests');
    Route::match(['get', 'post'], '/finance/imprest_management/imprest_requests/{id}/{document_type_id}', [App\Http\Controllers\SettingsController::class, 'imprest_request'])->name('imprest_request');




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
    Route::match(['get', 'post'], '/reports/attendances_report', [App\Http\Controllers\ReportsController::class, 'attendances_report'])->name('reports_attendances_report');
    Route::match(['get', 'post'], '/reports/daily_attendances_report', [App\Http\Controllers\ReportsController::class, 'daily_attendances_report'])->name('reports_daily_attendances_report');
    Route::match(['get', 'post'], '/reports/purchases_by_supplier_report', [App\Http\Controllers\ReportsController::class, 'purchases_by_supplier_report'])->name('reports_purchases_by_supplier_report');
    Route::match(['get', 'post'], '/reports/efd_report', [App\Http\Controllers\ReportsController::class, 'efd_report'])->name('reports_efd_report');
    Route::match(['get', 'post'], '/reports/commission_vs_deposit_report', [App\Http\Controllers\ReportsController::class, 'commission_vs_deposit_report'])->name('reports_commission_vs_deposit_report');
    Route::match(['get', 'post'], '/reports/detailed_efd_report', [App\Http\Controllers\ReportsController::class, 'detailed_efd_report'])->name('reports_detailed_efd_report');
    Route::match(['get', 'post'], '/reports/bank_reconciliation_report', [App\Http\Controllers\ReportsController::class, 'bank_reconciliation_report'])->name('reports_bank_reconciliation_report');
    Route::match(['get', 'post'], '/reports/statement_report', [App\Http\Controllers\ReportsController::class, 'statement_report'])->name('reports_statement_report');
    Route::match(['get', 'post'], '/reports/supplier_2_report', [App\Http\Controllers\ReportsController::class, 'supplier_2_report'])->name('reports_supplier_2_report');
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
    Route::match(['get', 'post'], '/reports/annually_deduction_report', [App\Http\Controllers\ReportsController::class, 'annually_deduction_report'])->name('reports_annually_deduction_report');
    Route::match(['get', 'post'], '/reports/annually_nhif_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_nhif_summary_report'])->name('reports_annually_nhif_summary_report');
    Route::match(['get', 'post'], '/reports/annually_net_salary_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_net_salary_summary_report'])->name('reports_annually_net_salary_summary_report');
    Route::match(['get', 'post'], '/reports/annually_heslb_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_heslb_summary_report'])->name('reports_annually_heslb_summary_report');
    Route::match(['get', 'post'], '/reports/annually_allowance_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_allowance_summary_report'])->name('reports_annually_allowance_summary_report');
    Route::match(['get', 'post'], '/reports/annually_advance_salary_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_advance_salary_summary_report'])->name('reports_annually_advance_salary_summary_report');
    Route::match(['get', 'post'], '/reports/annually_expense_sub_categories_summary_report', [App\Http\Controllers\ReportsController::class, 'annually_expense_sub_categories_summary_report'])->name('reports_annually_expense_sub_categories_summary_report');
    Route::match(['get', 'post'], '/reports/net_report', [App\Http\Controllers\ReportsController::class, 'net_report'])->name('reports_net_report');
    Route::match(['get', 'post'], '/reports/paye_report', [App\Http\Controllers\ReportsController::class, 'paye_report'])->name('reports_paye_report');
    Route::match(['get', 'post'], '/reports/nhif_report', [App\Http\Controllers\ReportsController::class, 'nhif_report'])->name('reports_nhif_report');
    Route::match(['get', 'post'], '/reports/nssf_report', [App\Http\Controllers\ReportsController::class, 'nssf_report'])->name('reports_nssf_report');
    Route::match(['get', 'post'], '/reports/wcf_report', [App\Http\Controllers\ReportsController::class, 'wcf_report'])->name('reports_wcf_report');
    Route::match(['get', 'post'], '/reports/sdl_report', [App\Http\Controllers\ReportsController::class, 'sdl_report'])->name('reports_sdl_report');
    Route::match(['get', 'post'], '/reports/heslb_report', [App\Http\Controllers\ReportsController::class, 'heslb_report'])->name('reports_heslb_report');
    Route::match(['get', 'post'], '/reports/loan_report', [App\Http\Controllers\ReportsController::class, 'loan_report'])->name('reports_loan_report');
    Route::match(['get', 'post'], '/reports/advance_salary_report', [App\Http\Controllers\ReportsController::class, 'advance_salary_report'])->name('reports_advance_salary_report');
    Route::match(['get', 'post'], '/reports/allowance_report', [App\Http\Controllers\ReportsController::class, 'allowance_report'])->name('reports_allowance_report');
    Route::match(['get', 'post'], '/payroll/staff_bank_details', [App\Http\Controllers\StaffController::class, 'staff_bank_details'])->name('staff_bank_details');
    Route::match(['get', 'post'], '/payroll/salary_slips', [App\Http\Controllers\PayrollController::class, 'salary_slips'])->name('salary_slips');
    Route::match(['get', 'post'], '/payroll/employee_salary_slip/{staff_id}/{month}/{year}', [App\Http\Controllers\PayrollController::class, 'employee_salary_slip'])->name('employee_salary_slip');
    Route::match(['get', 'post'], '/payroll/monthly_workdays', [App\Http\Controllers\PayrollController::class, 'monthly_workdays'])->name('monthly_workdays');
    Route::match(['get', 'post'], '/payroll/update_monthly_allowance', [App\Http\Controllers\PayrollController::class, 'update_monthly_allowance'])->name('update_monthly_allowance');
    Route::match(['get', 'post'], '/reports/statutory_payment_report', [App\Http\Controllers\ReportsController::class, 'statutory_payment_report'])->name('reports_statutory_payment_report');
    Route::match(['get', 'post'], '/reports/statutory_category_report', [App\Http\Controllers\ReportsController::class, 'statutory_category_report'])->name('reports_statutory_category_report');
    Route::match(['get', 'post'], '/reports/statutory_schedules_report', [App\Http\Controllers\ReportsController::class, 'statutory_schedules_report'])->name('reports_statutory_schedules_report');

    Route::match(['get', 'post'], '/leaves/leave_request', [App\Http\Controllers\LeaveRequestController::class, 'index'])->name('leave_request');
    Route::match(['get', 'post'], '/leaves/leave_dashboard', [App\Http\Controllers\LeaveRequestController::class, 'dashboard'])->name('leave_dashboard');
    Route::match(['get', 'post'], '/leaves/add_leave_request', [App\Http\Controllers\LeaveRequestController::class, 'store'])->name('leaves.store');
    Route::match(['get', 'post', 'put'], '/leaves/leave_managements', [App\Http\Controllers\LeaveRequestController::class, 'leave_managements'])->name('leave_managements');
    Route::match(['get', 'post', 'put'], '/leaves/{leaveRequest}', [App\Http\Controllers\LeaveRequestController::class, 'update'])->name('admin.leaves.update');
//    Route::resource('leaves', LeaveRequestController::class);

    // Project Routes
    Route::match(['get', 'post'], '/projects', [App\Http\Controllers\ProjectController::class, 'index'])->name('projects');
    Route::match(['get', 'post'], '/project/create', [App\Http\Controllers\ProjectController::class, 'create'])->name('project.create');
    Route::match(['get', 'post'], '/project/edit/{id}', [App\Http\Controllers\ProjectController::class, 'edit'])->name('project.edit');
    Route::match(['get', 'post'], '/project/show/{id}', [App\Http\Controllers\ProjectController::class, 'show'])->name('project.show');
    Route::post('/project/store', [App\Http\Controllers\ProjectController::class, 'store'])->name('project.store');
    Route::post('/project/update/{id}', [App\Http\Controllers\ProjectController::class, 'update'])->name('project.update');
    Route::post('/project/delete/{id}', [App\Http\Controllers\ProjectController::class, 'destroy'])->name('project.delete');
    Route::match(['get', 'post'], '/projects/{id}/{document_type_id}', [App\Http\Controllers\ProjectController::class, 'projects'])->name('individual_projects');

    // Project Client Routes
    Route::match(['get', 'post'], '/project_clients', [App\Http\Controllers\ProjectClientController::class, 'index'])->name('project_clients');
    Route::match(['get', 'post'], '/project_client/create', [App\Http\Controllers\ProjectClientController::class, 'create'])->name('project_client.create');
    Route::match(['get', 'post'], '/project_client/edit/{id}', [App\Http\Controllers\ProjectClientController::class, 'edit'])->name('project_client.edit');
    Route::match(['get', 'post'], '/project_client/show/{id}', [App\Http\Controllers\ProjectClientController::class, 'show'])->name('project_client.show');
    Route::post('/project_client/store', [App\Http\Controllers\ProjectClientController::class, 'store'])->name('project_client.store');
    Route::post('/project_client/update/{id}', [App\Http\Controllers\ProjectClientController::class, 'update'])->name('project_client.update');
    Route::post('/project_client/delete/{id}', [App\Http\Controllers\ProjectClientController::class, 'destroy'])->name('project_client.delete');
    Route::match(['get', 'post'], '/project_clients/{id}/{document_type_id}', [App\Http\Controllers\ProjectClientController::class, 'project_clients'])->name('individual_project_clients');


    // Project Type Routes
    Route::match(['get', 'post'], '/project_types', [App\Http\Controllers\ProjectTypeController::class, 'index'])->name('project_types');
    Route::match(['get', 'post'], '/project_type/create', [App\Http\Controllers\ProjectTypeController::class, 'create'])->name('project_type.create');
    Route::match(['get', 'post'], '/project_type/edit/{id}', [App\Http\Controllers\ProjectTypeController::class, 'edit'])->name('project_type.edit');
    Route::match(['get', 'post'], '/project_type/show/{id}', [App\Http\Controllers\ProjectTypeController::class, 'show'])->name('project_type.show');
    Route::post('/project_type/store', [App\Http\Controllers\ProjectTypeController::class, 'store'])->name('project_type.store');
    Route::post('/project_type/update/{id}', [App\Http\Controllers\ProjectTypeController::class, 'update'])->name('project_type.update');
    Route::post('/project_type/delete/{id}', [App\Http\Controllers\ProjectTypeController::class, 'destroy'])->name('project_type.delete');

    // Project BOQ Routes
    Route::match(['get', 'post'], '/project_boqs', [App\Http\Controllers\ProjectBoqController::class, 'index'])->name('project_boqs');
    Route::match(['get', 'post'], '/project_boq/create', [App\Http\Controllers\ProjectBoqController::class, 'create'])->name('project_boq.create');
    Route::match(['get', 'post'], '/project_boq/edit/{id}', [App\Http\Controllers\ProjectBoqController::class, 'edit'])->name('project_boq.edit');
    Route::match(['get', 'post'], '/project_boq/show/{id}', [App\Http\Controllers\ProjectBoqController::class, 'show'])->name('project_boq.show');
    Route::post('/project_boq/store', [App\Http\Controllers\ProjectBoqController::class, 'store'])->name('project_boq.store');
    Route::post('/project_boq/update/{id}', [App\Http\Controllers\ProjectBoqController::class, 'update'])->name('project_boq.update');
    Route::post('/project_boq/delete/{id}', [App\Http\Controllers\ProjectBoqController::class, 'destroy'])->name('project_boq.delete');

    // Project BOQ Items Routes
    Route::match(['get', 'post'], '/project_boq_items', [App\Http\Controllers\ProjectBoqItemController::class, 'index'])->name('project_boq_items');
    Route::match(['get', 'post'], '/project_boq_item/create', [App\Http\Controllers\ProjectBoqItemController::class, 'create'])->name('project_boq_item.create');
    Route::match(['get', 'post'], '/project_boq_item/edit/{id}', [App\Http\Controllers\ProjectBoqItemController::class, 'edit'])->name('project_boq_item.edit');
    Route::match(['get', 'post'], '/project_boq_item/show/{id}', [App\Http\Controllers\ProjectBoqItemController::class, 'show'])->name('project_boq_item.show');
    Route::post('/project_boq_item/store', [App\Http\Controllers\ProjectBoqItemController::class, 'store'])->name('project_boq_item.store');
    Route::post('/project_boq_item/update/{id}', [App\Http\Controllers\ProjectBoqItemController::class, 'update'])->name('project_boq_item.update');
    Route::post('/project_boq_item/delete/{id}', [App\Http\Controllers\ProjectBoqItemController::class, 'destroy'])->name('project_boq_item.delete');

    // Project Expense Routes
    Route::match(['get', 'post'], '/project_expenses', [App\Http\Controllers\ProjectExpenseController::class, 'index'])->name('project_expenses');
    Route::match(['get', 'post'], '/project_expense/create', [App\Http\Controllers\ProjectExpenseController::class, 'create'])->name('project_expense.create');
    Route::match(['get', 'post'], '/project_expense/edit/{id}', [App\Http\Controllers\ProjectExpenseController::class, 'edit'])->name('project_expense.edit');
    Route::match(['get', 'post'], '/project_expense/show/{id}', [App\Http\Controllers\ProjectExpenseController::class, 'show'])->name('project_expense.show');
    Route::post('/project_expense/store', [App\Http\Controllers\ProjectExpenseController::class, 'store'])->name('project_expense.store');
    Route::post('/project_expense/update/{id}', [App\Http\Controllers\ProjectExpenseController::class, 'update'])->name('project_expense.update');
    Route::post('/project_expense/delete/{id}', [App\Http\Controllers\ProjectExpenseController::class, 'destroy'])->name('project_expense.delete');

    // Project Material Routes
    Route::match(['get', 'post'], '/project_materials', [App\Http\Controllers\ProjectMaterialController::class, 'index'])->name('project_materials');
    Route::match(['get', 'post'], '/project_material/create', [App\Http\Controllers\ProjectMaterialController::class, 'create'])->name('project_material.create');
    Route::match(['get', 'post'], '/project_material/edit/{id}', [App\Http\Controllers\ProjectMaterialController::class, 'edit'])->name('project_material.edit');
    Route::match(['get', 'post'], '/project_material/show/{id}', [App\Http\Controllers\ProjectMaterialController::class, 'show'])->name('project_material.show');
    Route::post('/project_material/store', [App\Http\Controllers\ProjectMaterialController::class, 'store'])->name('project_material.store');
    Route::post('/project_material/update/{id}', [App\Http\Controllers\ProjectMaterialController::class, 'update'])->name('project_material.update');
    Route::post('/project_material/delete/{id}', [App\Http\Controllers\ProjectMaterialController::class, 'destroy'])->name('project_material.delete');

    // Project Material Inventory Routes
    Route::match(['get', 'post'], '/project_material_inventory', [App\Http\Controllers\ProjectMaterialInventoryController::class, 'index'])->name('project_material_inventory');
    Route::match(['get', 'post'], '/project_material_inventory/create', [App\Http\Controllers\ProjectMaterialInventoryController::class, 'create'])->name('project_material_inventory.create');
    Route::match(['get', 'post'], '/project_material_inventory/edit/{id}', [App\Http\Controllers\ProjectMaterialInventoryController::class, 'edit'])->name('project_material_inventory.edit');
    Route::match(['get', 'post'], '/project_material_inventory/show/{id}', [App\Http\Controllers\ProjectMaterialInventoryController::class, 'show'])->name('project_material_inventory.show');
    Route::post('/project_material_inventory/store', [App\Http\Controllers\ProjectMaterialInventoryController::class, 'store'])->name('project_material_inventory.store');
    Route::post('/project_material_inventory/update/{id}', [App\Http\Controllers\ProjectMaterialInventoryController::class, 'update'])->name('project_material_inventory.update');
    Route::post('/project_material_inventory/delete/{id}', [App\Http\Controllers\ProjectMaterialInventoryController::class, 'destroy'])->name('project_material_inventory.delete');


// Project Site Visits Routes
    Route::match(['get', 'post'], '/project_site_visits', [App\Http\Controllers\ProjectSiteVisitController::class, 'index'])->name('project_site_visits');
    Route::match(['get', 'post'], '/project_site_visit/create', [App\Http\Controllers\ProjectSiteVisitController::class, 'create'])->name('project_site_visit.create');
    Route::match(['get', 'post'], '/project_site_visit/edit/{id}', [App\Http\Controllers\ProjectSiteVisitController::class, 'edit'])->name('project_site_visit.edit');
    Route::match(['get', 'post'], '/project_site_visit/show/{id}', [App\Http\Controllers\ProjectSiteVisitController::class, 'show'])->name('project_site_visit.show');
    Route::post('/project_site_visit/store', [App\Http\Controllers\ProjectSiteVisitController::class, 'store'])->name('project_site_visit.store');
    Route::post('/project_site_visit/update/{id}', [App\Http\Controllers\ProjectSiteVisitController::class, 'update'])->name('project_site_visit.update');
    Route::post('/project_site_visit/delete/{id}', [App\Http\Controllers\ProjectSiteVisitController::class, 'destroy'])->name('project_site_visit.delete');
    Route::match(['get', 'post'], '/project_site_visits/{id}/{document_type_id}', [App\Http\Controllers\ProjectSiteVisitController::class, 'project_site_visits'])->name('individual_project_site_visits');

// Project Design Routes
    Route::match(['get', 'post'], '/project_designs', [App\Http\Controllers\ProjectDesignController::class, 'index'])->name('project_designs');
    Route::match(['get', 'post'], '/project_design/create', [App\Http\Controllers\ProjectDesignController::class, 'create'])->name('project_design.create');
    Route::match(['get', 'post'], '/project_design/edit/{id}', [App\Http\Controllers\ProjectDesignController::class, 'edit'])->name('project_design.edit');
    Route::match(['get', 'post'], '/project_design/show/{id}', [App\Http\Controllers\ProjectDesignController::class, 'show'])->name('project_design.show');
    Route::post('/project_design/store', [App\Http\Controllers\ProjectDesignController::class, 'store'])->name('project_design.store');
    Route::post('/project_design/update/{id}', [App\Http\Controllers\ProjectDesignController::class, 'update'])->name('project_design.update');
    Route::post('/project_design/delete/{id}', [App\Http\Controllers\ProjectDesignController::class, 'destroy'])->name('project_design.delete');

// Project Construction Phase Routes
    Route::match(['get', 'post'], '/project_construction_phases', [App\Http\Controllers\ProjectConstructionPhaseController::class, 'index'])->name('project_construction_phases');
    Route::match(['get', 'post'], '/project_construction_phase/create', [App\Http\Controllers\ProjectConstructionPhaseController::class, 'create'])->name('project_construction_phase.create');
    Route::match(['get', 'post'], '/project_construction_phase/edit/{id}', [App\Http\Controllers\ProjectConstructionPhaseController::class, 'edit'])->name('project_construction_phase.edit');
    Route::match(['get', 'post'], '/project_construction_phase/show/{id}', [App\Http\Controllers\ProjectConstructionPhaseController::class, 'show'])->name('project_construction_phase.show');
    Route::post('/project_construction_phase/store', [App\Http\Controllers\ProjectConstructionPhaseController::class, 'store'])->name('project_construction_phase.store');
    Route::post('/project_construction_phase/update/{id}', [App\Http\Controllers\ProjectConstructionPhaseController::class, 'update'])->name('project_construction_phase.update');
    Route::post('/project_construction_phase/delete/{id}', [App\Http\Controllers\ProjectConstructionPhaseController::class, 'destroy'])->name('project_construction_phase.delete');

// Project Daily Report Routes
    Route::match(['get', 'post'], '/project_daily_reports', [App\Http\Controllers\ProjectDailyReportController::class, 'index'])->name('project_daily_reports');
    Route::match(['get', 'post'], '/project_daily_report/create', [App\Http\Controllers\ProjectDailyReportController::class, 'create'])->name('project_daily_report.create');
    Route::match(['get', 'post'], '/project_daily_report/edit/{id}', [App\Http\Controllers\ProjectDailyReportController::class, 'edit'])->name('project_daily_report.edit');
    Route::match(['get', 'post'], '/project_daily_report/show/{id}', [App\Http\Controllers\ProjectDailyReportController::class, 'show'])->name('project_daily_report.show');
    Route::post('/project_daily_report/store', [App\Http\Controllers\ProjectDailyReportController::class, 'store'])->name('project_daily_report.store');
    Route::post('/project_daily_report/update/{id}', [App\Http\Controllers\ProjectDailyReportController::class, 'update'])->name('project_daily_report.update');
    Route::post('/project_daily_report/delete/{id}', [App\Http\Controllers\ProjectDailyReportController::class, 'destroy'])->name('project_daily_report.delete');

// Sales Daily Report Routes
    Route::match(['get', 'post'], '/sales_daily_reports', [App\Http\Controllers\SalesDailyReportController::class, 'index'])->name('sales_daily_reports');
    Route::match(['get', 'post'], '/sales_daily_report/create', [App\Http\Controllers\SalesDailyReportController::class, 'create'])->name('sales_daily_report.create');
    Route::match(['get', 'post'], '/sales_daily_report/edit/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'edit'])->name('sales_daily_report.edit');
    Route::match(['get', 'post'], '/sales_daily_report/show/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'show'])->name('sales_daily_report.show');
    Route::match(['get', 'post'], '/sales_daily_report/{id}/{document_type_id}', [App\Http\Controllers\SalesDailyReportController::class, 'show'])->name('sales_daily_report');
    Route::post('/sales_daily_report/store', [App\Http\Controllers\SalesDailyReportController::class, 'store'])->name('sales_daily_report.store');
    Route::post('/sales_daily_report/update/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'update'])->name('sales_daily_report.update');
    Route::post('/sales_daily_report/delete/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'destroy'])->name('sales_daily_report.delete');
    Route::post('/sales_daily_report/submit/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'submit'])->name('sales_daily_report.submit');
    Route::post('/sales_daily_report/approve/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'approve'])->name('sales_daily_report.approve');
    Route::post('/sales_daily_report/reject/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'reject'])->name('sales_daily_report.reject');
    Route::get('/sales_daily_report/export/{id}', [App\Http\Controllers\SalesDailyReportController::class, 'exportPDF'])->name('sales_daily_report.export');

    // Lead Management Routes
    Route::resource('leads', App\Http\Controllers\LeadController::class);

// Project Invoice Routes
    Route::match(['get', 'post'], '/project_invoices', [App\Http\Controllers\ProjectInvoiceController::class, 'index'])->name('project_invoices');
    Route::match(['get', 'post'], '/project_invoice/create', [App\Http\Controllers\ProjectInvoiceController::class, 'create'])->name('project_invoice.create');
    Route::match(['get', 'post'], '/project_invoice/edit/{id}', [App\Http\Controllers\ProjectInvoiceController::class, 'edit'])->name('project_invoice.edit');
    Route::match(['get', 'post'], '/project_invoice/show/{id}', [App\Http\Controllers\ProjectInvoiceController::class, 'show'])->name('project_invoice.show');
    Route::post('/project_invoice/store', [App\Http\Controllers\ProjectInvoiceController::class, 'store'])->name('project_invoice.store');
    Route::post('/project_invoice/update/{id}', [App\Http\Controllers\ProjectInvoiceController::class, 'update'])->name('project_invoice.update');
    Route::post('/project_invoice/delete/{id}', [App\Http\Controllers\ProjectInvoiceController::class, 'destroy'])->name('project_invoice.delete');

// Project Payment Routes
    Route::match(['get', 'post'], '/project_payments', [App\Http\Controllers\ProjectPaymentController::class, 'index'])->name('project_payments');
    Route::match(['get', 'post'], '/project_payment/create', [App\Http\Controllers\ProjectPaymentController::class, 'create'])->name('project_payment.create');
    Route::match(['get', 'post'], '/project_payment/edit/{id}', [App\Http\Controllers\ProjectPaymentController::class, 'edit'])->name('project_payment.edit');
    Route::match(['get', 'post'], '/project_payment/show/{id}', [App\Http\Controllers\ProjectPaymentController::class, 'show'])->name('project_payment.show');
    Route::post('/project_payment/store', [App\Http\Controllers\ProjectPaymentController::class, 'store'])->name('project_payment.store');
    Route::post('/project_payment/update/{id}', [App\Http\Controllers\ProjectPaymentController::class, 'update'])->name('project_payment.update');
    Route::post('/project_payment/delete/{id}', [App\Http\Controllers\ProjectPaymentController::class, 'destroy'])->name('project_payment.delete');

// Project Activity Log Routes
    Route::match(['get', 'post'], '/project_activity_logs', [App\Http\Controllers\ProjectActivityLogController::class, 'index'])->name('project_activity_logs');
    Route::match(['get', 'post'], '/project_activity_log/show/{id}', [App\Http\Controllers\ProjectActivityLogController::class, 'show'])->name('project_activity_log.show');

// Project System Backup Routes
    Route::match(['get', 'post'], '/project_system_backups', [App\Http\Controllers\ProjectSystemBackupController::class, 'index'])->name('project_system_backups');
    Route::post('/project_system_backup/create', [App\Http\Controllers\ProjectSystemBackupController::class, 'create'])->name('project_system_backup.create');
    Route::post('/project_system_backup/restore/{id}', [App\Http\Controllers\ProjectSystemBackupController::class, 'restore'])->name('project_system_backup.restore');
    Route::post('/project_system_backup/delete/{id}', [App\Http\Controllers\ProjectSystemBackupController::class, 'destroy'])->name('project_system_backup.delete');


// routes/web.php

// Project Material Request Routes
    Route::match(['get', 'post'], '/project_material_requests', [App\Http\Controllers\ProjectMaterialRequestController::class, 'index'])->name('project_material_requests');
    Route::match(['get', 'post'], '/project_material_request/create', [App\Http\Controllers\ProjectMaterialRequestController::class, 'create'])->name('project_material_request.create');
    Route::match(['get', 'post'], '/project_material_request/edit/{id}', [App\Http\Controllers\ProjectMaterialRequestController::class, 'edit'])->name('project_material_request.edit');
    Route::match(['get', 'post'], '/project_material_request/show/{id}', [App\Http\Controllers\ProjectMaterialRequestController::class, 'show'])->name('project_material_request.show');
    Route::post('/project_material_request/store', [App\Http\Controllers\ProjectMaterialRequestController::class, 'store'])->name('project_material_request.store');
    Route::post('/project_material_request/update/{id}', [App\Http\Controllers\ProjectMaterialRequestController::class, 'update'])->name('project_material_request.update');
    Route::post('/project_material_request/delete/{id}', [App\Http\Controllers\ProjectMaterialRequestController::class, 'destroy'])->name('project_material_request.delete');

// Project Client Document Routes
    Route::match(['get', 'post'], '/project_client_documents', [App\Http\Controllers\ProjectClientDocumentController::class, 'index'])->name('project_client_documents');
    Route::match(['get', 'post'], '/project_client_document/create', [App\Http\Controllers\ProjectClientDocumentController::class, 'create'])->name('project_client_document.create');
    Route::match(['get', 'post'], '/project_client_document/edit/{id}', [App\Http\Controllers\ProjectClientDocumentController::class, 'edit'])->name('project_client_document.edit');
    Route::match(['get', 'post'], '/project_client_document/show/{id}', [App\Http\Controllers\ProjectClientDocumentController::class, 'show'])->name('project_client_document.show');
    Route::post('/project_client_document/store', [App\Http\Controllers\ProjectClientDocumentController::class, 'store'])->name('project_client_document.store');
    Route::post('/project_client_document/update/{id}', [App\Http\Controllers\ProjectClientDocumentController::class, 'update'])->name('project_client_document.update');
    Route::post('/project_client_document/delete/{id}', [App\Http\Controllers\ProjectClientDocumentController::class, 'destroy'])->name('project_client_document.delete');

// Project Team Member Routes
    Route::match(['get', 'post'], '/project_team_members', [App\Http\Controllers\ProjectTeamMemberController::class, 'index'])->name('project_team_members');
    Route::match(['get', 'post'], '/project_team_member/create', [App\Http\Controllers\ProjectTeamMemberController::class, 'create'])->name('project_team_member.create');
    Route::match(['get', 'post'], '/project_team_member/edit/{id}', [App\Http\Controllers\ProjectTeamMemberController::class, 'edit'])->name('project_team_member.edit');
    Route::match(['get', 'post'], '/project_team_member/show/{id}', [App\Http\Controllers\ProjectTeamMemberController::class, 'show'])->name('project_team_member.show');
    Route::post('/project_team_member/store', [App\Http\Controllers\ProjectTeamMemberController::class, 'store'])->name('project_team_member.store');
    Route::post('/project_team_member/update/{id}', [App\Http\Controllers\ProjectTeamMemberController::class, 'update'])->name('project_team_member.update');
    Route::post('/project_team_member/delete/{id}', [App\Http\Controllers\ProjectTeamMemberController::class, 'destroy'])->name('project_team_member.delete');

// Project Report Routes
    Route::match(['get', 'post'], '/project_reports', [App\Http\Controllers\ProjectReportController::class, 'index'])->name('project_reports');
    Route::get('/project_report/generate/{type}', [App\Http\Controllers\ProjectReportController::class, 'generate'])->name('project_report.generate');
    Route::get('/project_report/download/{id}', [App\Http\Controllers\ProjectReportController::class, 'download'])->name('project_report.download');

// Project Dashboard Routes
    Route::get('/project_dashboard', [App\Http\Controllers\ProjectDashboardController::class, 'index'])->name('project_dashboard');
    Route::get('/project_dashboard/stats', [App\Http\Controllers\ProjectDashboardController::class, 'getStats'])->name('project_dashboard.stats');
    Route::get('/project_dashboard/charts', [App\Http\Controllers\ProjectDashboardController::class, 'getCharts'])->name('project_dashboard.charts');

// Project Document Routes (General Documents)
    Route::match(['get', 'post'], '/project_documents', [App\Http\Controllers\ProjectDocumentController::class, 'index'])->name('project_documents');
    Route::match(['get', 'post'], '/project_document/create', [App\Http\Controllers\ProjectDocumentController::class, 'create'])->name('project_document.create');
    Route::match(['get', 'post'], '/project_document/edit/{id}', [App\Http\Controllers\ProjectDocumentController::class, 'edit'])->name('project_document.edit');
    Route::match(['get', 'post'], '/project_document/show/{id}', [App\Http\Controllers\ProjectDocumentController::class, 'show'])->name('project_document.show');
    Route::post('/project_document/store', [App\Http\Controllers\ProjectDocumentController::class, 'store'])->name('project_document.store');
    Route::post('/project_document/update/{id}', [App\Http\Controllers\ProjectDocumentController::class, 'update'])->name('project_document.update');
    Route::post('/project_document/delete/{id}', [App\Http\Controllers\ProjectDocumentController::class, 'destroy'])->name('project_document.delete');
    Route::get('/project_document/download/{id}', [App\Http\Controllers\ProjectDocumentController::class, 'download'])->name('project_document.download');

// Project Comment Routes
    Route::match(['get', 'post'], '/project_comments', [App\Http\Controllers\ProjectCommentController::class, 'index'])->name('project_comments');
    Route::post('/project_comment/store', [App\Http\Controllers\ProjectCommentController::class, 'store'])->name('project_comment.store');
    Route::post('/project_comment/update/{id}', [App\Http\Controllers\ProjectCommentController::class, 'update'])->name('project_comment.update');
    Route::post('/project_comment/delete/{id}', [App\Http\Controllers\ProjectCommentController::class, 'destroy'])->name('project_comment.delete');

// Project Search Routes
    Route::get('/project_search', [App\Http\Controllers\ProjectSearchController::class, 'index'])->name('project_search');
    Route::post('/project_search/results', [App\Http\Controllers\ProjectSearchController::class, 'search'])->name('project_search.results');

// Project Export Routes
    Route::get('/project_export/{type}', [App\Http\Controllers\ProjectExportController::class, 'export'])->name('project_export');
    Route::post('/project_export/custom', [App\Http\Controllers\ProjectExportController::class, 'customExport'])->name('project_export.custom');

// Project Import Routes
    Route::get('/project_import', [App\Http\Controllers\ProjectImportController::class, 'showForm'])->name('project_import');
    Route::post('/project_import/process', [App\Http\Controllers\ProjectImportController::class, 'process'])->name('project_import.process');

//    Route::put('/profile', 'SettingsController@users')->name('profile.update');
    Route::match(['get', 'post'], '/profile', [App\Http\Controllers\UserController::class, 'update_profile'])->name('profile.update');
    Route::match(['get', 'post'], '/profile/password', [App\Http\Controllers\UserController::class, 'update_password'])->name('profile.password.update');

});

Auth::routes(['register' => false]);

