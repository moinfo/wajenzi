<?php

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
    Route::view('/lock', 'auth.lock');

    Route::match(['get', 'post'], '/ajax/{fx}', [App\Http\Controllers\AjaxController::class, 'index'])->name('ajax_request');


    Route::match(['get', 'post'], '/collection', [App\Http\Controllers\CollectionController::class, 'index'])->name('collection');
    Route::match(['get', 'post'], '/transaction_movement', [App\Http\Controllers\TransactionMovementController::class, 'index'])->name('transaction_movement');
    Route::match(['get', 'post'], '/gross', [App\Http\Controllers\GrossController::class, 'index'])->name('gross');
    Route::match(['get', 'post'], '/supplier_receiving', [App\Http\Controllers\SupplierReceivingController::class, 'index'])->name('supplier_receiving');
    Route::match(['get', 'post'], '/staff', [App\Http\Controllers\StaffController::class, 'index'])->name('staff');
    Route::match(['get', 'post'], '/accounting', [App\Http\Controllers\AccoutingController::class, 'index'])->name('accounting');
    Route::match(['get', 'post'], '/user', [App\Http\Controllers\UserController::class, 'index'])->name('user');
    Route::match(['get', 'post'], '/system', [App\Http\Controllers\SystemController::class, 'index'])->name('system');
    Route::match(['get', 'post'], '/sales', [App\Http\Controllers\SaleController::class, 'index'])->name('sales');
    Route::match(['get', 'post'], '/purchases', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchases');
    Route::match(['get', 'post'], '/expenses', [App\Http\Controllers\ExpenseController::class, 'index'])->name('expenses');
    Route::match(['get', 'post'], '/financial_charges', [App\Http\Controllers\FinancialChargeController::class, 'index'])->name('financial_charges');


    Route::match(['get', 'post'], '/user/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('user_profile');
    Route::match(['get', 'post'], '/user/settings', [App\Http\Controllers\UserController::class, 'settings'])->name('user_settings');
    Route::match(['get', 'post'], '/user/inbox', [App\Http\Controllers\UserController::class, 'inbox'])->name('user_inbox');
    Route::match(['get', 'post'], '/user/notifications', [App\Http\Controllers\UserController::class, 'notifications'])->name('user_notifications');

    Route::match(['get', 'post'], '/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('hr_settings');
    Route::match(['get', 'post'], '/settings/supervisor', [App\Http\Controllers\SettingsController::class, 'supervisors'])->name('hr_settings_supervisors');
    Route::match(['get', 'post'], '/settings/departments', [App\Http\Controllers\SettingsController::class, 'departments'])->name('hr_settings_departments');
    Route::match(['get', 'post'], '/settings/deductions', [App\Http\Controllers\SettingsController::class, 'deductions'])->name('hr_settings_deductions');
    Route::match(['get', 'post'], '/settings/banks', [App\Http\Controllers\SettingsController::class, 'banks'])->name('hr_settings_banks');
    Route::match(['get', 'post'], '/settings/positions', [App\Http\Controllers\SettingsController::class, 'positions'])->name('hr_settings_positions');
    Route::match(['get', 'post'], '/settings/roles', [App\Http\Controllers\SettingsController::class, 'roles'])->name('hr_settings_roles');
    Route::match(['get', 'post'], '/settings/permissions', [App\Http\Controllers\SettingsController::class, 'permissions'])->name('hr_settings_permissions');
    Route::match(['get', 'post'], '/settings/suppliers', [App\Http\Controllers\SettingsController::class, 'suppliers'])->name('hr_settings_suppliers');
    Route::match(['get', 'post'], '/settings/items', [App\Http\Controllers\SettingsController::class, 'items'])->name('hr_settings_items');
    Route::match(['get', 'post'], '/settings/efd', [App\Http\Controllers\SettingsController::class, 'efd'])->name('hr_settings_efd');
    Route::match(['get', 'post'], '/settings/expenses_categories', [App\Http\Controllers\SettingsController::class, 'expenses_categories'])->name('hr_settings_expenses_categories');
    Route::match(['get', 'post'], '/settings/financial_charge_categories', [App\Http\Controllers\SettingsController::class, 'financial_charge_categories'])->name('hr_settings_financial_charge_categories');

    Route::match(['get', 'post'], '/reports', [App\Http\Controllers\ReportsController::class, 'index'])->name('reports');
    Route::match(['get', 'post'], '/reports/general_report', [App\Http\Controllers\ReportsController::class, 'general_report'])->name('reports_general_report');
    Route::match(['get', 'post'], '/reports/supervisor_report', [App\Http\Controllers\ReportsController::class, 'supervisor_report'])->name('reports_supervisor_report');
    Route::match(['get', 'post'], '/reports/supplier_report', [App\Http\Controllers\ReportsController::class, 'supplier_report'])->name('reports_supplier_report');
    Route::match(['get', 'post'], '/reports/collection_report', [App\Http\Controllers\ReportsController::class, 'collection_report'])->name('reports_collection_report');
    Route::match(['get', 'post'], '/reports/gross_summary_report', [App\Http\Controllers\ReportsController::class, 'gross_summary_report'])->name('reports_gross_summary_report');

});

Auth::routes(['register' => false]);

