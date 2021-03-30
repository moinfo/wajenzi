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


    Route::match(['get', 'post'], '/timesheet', [App\Http\Controllers\TimesheetController::class, 'index'])->name('timesheet');
    Route::match(['get', 'post'], '/leave', [App\Http\Controllers\LeaveController::class, 'index'])->name('leave');
    Route::match(['get', 'post'], '/loan', [App\Http\Controllers\LoanController::class, 'index'])->name('loan');
    Route::match(['get', 'post'], '/recruitment', [App\Http\Controllers\RecruitmentController::class, 'index'])->name('recruitment');
    Route::match(['get', 'post'], '/staff', [App\Http\Controllers\StaffController::class, 'index'])->name('staff');
    Route::match(['get', 'post'], '/accounting', [App\Http\Controllers\AccoutingController::class, 'index'])->name('accounting');
    Route::match(['get', 'post'], '/reports', [App\Http\Controllers\ReportsController::class, 'index'])->name('reports');
    Route::match(['get', 'post'], '/user', [App\Http\Controllers\UserController::class, 'index'])->name('user');
    Route::match(['get', 'post'], '/system', [App\Http\Controllers\SystemController::class, 'index'])->name('system');
    Route::match(['get', 'post'], '/sales', [App\Http\Controllers\SaleController::class, 'index'])->name('sales');
    Route::match(['get', 'post'], '/purchases', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchases');


    Route::match(['get', 'post'], '/user/profile', [App\Http\Controllers\UserController::class, 'profile'])->name('user_profile');
    Route::match(['get', 'post'], '/user/settings', [App\Http\Controllers\UserController::class, 'settings'])->name('user_settings');
    Route::match(['get', 'post'], '/user/inbox', [App\Http\Controllers\UserController::class, 'inbox'])->name('user_inbox');
    Route::match(['get', 'post'], '/user/notifications', [App\Http\Controllers\UserController::class, 'notifications'])->name('user_notifications');

    Route::match(['get', 'post'], '/settings', [App\Http\Controllers\SettingsController::class, 'index'])->name('hr_settings');
    Route::match(['get', 'post'], '/settings/allowances', [App\Http\Controllers\SettingsController::class, 'allowances'])->name('hr_settings_allowances');
    Route::match(['get', 'post'], '/settings/departments', [App\Http\Controllers\SettingsController::class, 'departments'])->name('hr_settings_departments');
    Route::match(['get', 'post'], '/settings/deductions', [App\Http\Controllers\SettingsController::class, 'deductions'])->name('hr_settings_deductions');
    Route::match(['get', 'post'], '/settings/banks', [App\Http\Controllers\SettingsController::class, 'banks'])->name('hr_settings_banks');
    Route::match(['get', 'post'], '/settings/positions', [App\Http\Controllers\SettingsController::class, 'positions'])->name('hr_settings_positions');
    Route::match(['get', 'post'], '/settings/roles', [App\Http\Controllers\SettingsController::class, 'roles'])->name('hr_settings_roles');
    Route::match(['get', 'post'], '/settings/permissions', [App\Http\Controllers\SettingsController::class, 'permissions'])->name('hr_settings_permissions');
    Route::match(['get', 'post'], '/settings/efd', [App\Http\Controllers\SettingsController::class, 'efd'])->name('hr_settings_efd');


});

Auth::routes(['register' => false]);

