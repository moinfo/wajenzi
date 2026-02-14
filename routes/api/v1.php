<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\SiteDailyReportController;
use App\Http\Controllers\Api\V1\SalesDailyReportController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectSiteVisitController;
use App\Http\Controllers\Api\V1\MaterialRequestController;
use App\Http\Controllers\Api\V1\BillingDocumentController;
use App\Http\Controllers\Api\V1\BillingPaymentController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MenuController;

/*
|--------------------------------------------------------------------------
| API V1 Routes - Mobile App
|--------------------------------------------------------------------------
|
| These routes are for the Wajenzi mobile application with offline-first
| architecture. All routes use Sanctum authentication except login.
|
*/

// Public routes (no authentication required)
Route::post('auth/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Menus (permission-filtered sidebar)
    Route::get('menus', [MenuController::class, 'index']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/activities', [DashboardController::class, 'activities']);
        Route::get('/invoices', [DashboardController::class, 'invoices']);
        Route::get('/followups', [DashboardController::class, 'followups']);
        Route::get('/calendar', [DashboardController::class, 'calendar']);
        Route::get('/project-status', [DashboardController::class, 'projectStatus']);
        Route::get('/recent-activities', [DashboardController::class, 'recentActivities']);
    });

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('device-token', [AuthController::class, 'registerDeviceToken']);
    });

    // Attendance (Offline-Critical)
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::post('check-in', [AttendanceController::class, 'checkIn']);
        Route::post('check-out', [AttendanceController::class, 'checkOut']);
        Route::get('status', [AttendanceController::class, 'status']);
        Route::get('daily-report', [AttendanceController::class, 'dailyReport']);
        Route::get('summary', [AttendanceController::class, 'summary']);
    });

    // Site Daily Reports (Offline-Critical)
    Route::prefix('site-daily-reports')->group(function () {
        Route::get('/', [SiteDailyReportController::class, 'index']);
        Route::post('/', [SiteDailyReportController::class, 'store']);
        Route::get('{id}', [SiteDailyReportController::class, 'show']);
        Route::put('{id}', [SiteDailyReportController::class, 'update']);
        Route::delete('{id}', [SiteDailyReportController::class, 'destroy']);
        Route::post('{id}/submit', [SiteDailyReportController::class, 'submit']);
        Route::post('{id}/approve', [SiteDailyReportController::class, 'approve']);
        Route::post('{id}/reject', [SiteDailyReportController::class, 'reject']);
    });

    // Sales Daily Reports (Offline-Critical)
    Route::prefix('sales-daily-reports')->group(function () {
        Route::get('/', [SalesDailyReportController::class, 'index']);
        Route::post('/', [SalesDailyReportController::class, 'store']);
        Route::get('{id}', [SalesDailyReportController::class, 'show']);
        Route::put('{id}', [SalesDailyReportController::class, 'update']);
        Route::delete('{id}', [SalesDailyReportController::class, 'destroy']);
        Route::post('{id}/submit', [SalesDailyReportController::class, 'submit']);
        Route::post('{id}/approve', [SalesDailyReportController::class, 'approve']);
        Route::post('{id}/reject', [SalesDailyReportController::class, 'reject']);
    });

    // Expenses (Offline-Critical)
    Route::prefix('expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index']);
        Route::post('/', [ExpenseController::class, 'store']);
        Route::get('categories', [ExpenseController::class, 'categories']);
        Route::get('{id}', [ExpenseController::class, 'show']);
        Route::put('{id}', [ExpenseController::class, 'update']);
        Route::delete('{id}', [ExpenseController::class, 'destroy']);
        Route::post('{id}/submit', [ExpenseController::class, 'submit']);
        Route::post('{id}/approve', [ExpenseController::class, 'approve']);
        Route::post('{id}/reject', [ExpenseController::class, 'reject']);
    });

    // Unified Approvals
    Route::prefix('approvals')->group(function () {
        Route::get('/', [ApprovalController::class, 'index']);
        Route::get('pending', [ApprovalController::class, 'pending']);
        Route::post('{type}/{id}/approve', [ApprovalController::class, 'approve']);
        Route::post('{type}/{id}/reject', [ApprovalController::class, 'reject']);
    });

    // Projects
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('{id}', [ProjectController::class, 'show']);
        Route::get('{id}/boq', [ProjectController::class, 'boq']);
        Route::get('{id}/materials', [ProjectController::class, 'materials']);
        Route::get('{id}/sites', [ProjectController::class, 'sites']);
        Route::get('{id}/team', [ProjectController::class, 'team']);
    });

    // Site Visits
    Route::prefix('site-visits')->group(function () {
        Route::get('/', [ProjectSiteVisitController::class, 'index']);
        Route::post('/', [ProjectSiteVisitController::class, 'store']);
        Route::get('{id}', [ProjectSiteVisitController::class, 'show']);
        Route::put('{id}', [ProjectSiteVisitController::class, 'update']);
        Route::delete('{id}', [ProjectSiteVisitController::class, 'destroy']);
        Route::post('{id}/submit', [ProjectSiteVisitController::class, 'submit']);
    });

    // Material Requests
    Route::prefix('material-requests')->group(function () {
        Route::get('/', [MaterialRequestController::class, 'index']);
        Route::post('/', [MaterialRequestController::class, 'store']);
        Route::get('{id}', [MaterialRequestController::class, 'show']);
        Route::put('{id}', [MaterialRequestController::class, 'update']);
        Route::delete('{id}', [MaterialRequestController::class, 'destroy']);
        Route::post('{id}/submit', [MaterialRequestController::class, 'submit']);
        Route::post('{id}/approve', [MaterialRequestController::class, 'approve']);
        Route::post('{id}/reject', [MaterialRequestController::class, 'reject']);
    });

    // Billing Documents
    Route::prefix('billing')->group(function () {
        // Documents (Invoices, Quotes, Proformas)
        Route::prefix('documents')->group(function () {
            Route::get('/', [BillingDocumentController::class, 'index']);
            Route::post('/', [BillingDocumentController::class, 'store']);
            Route::get('{id}', [BillingDocumentController::class, 'show']);
            Route::put('{id}', [BillingDocumentController::class, 'update']);
            Route::delete('{id}', [BillingDocumentController::class, 'destroy']);
            Route::post('{id}/send', [BillingDocumentController::class, 'send']);
            Route::get('{id}/pdf', [BillingDocumentController::class, 'pdf']);
        });

        // Payments
        Route::prefix('payments')->group(function () {
            Route::get('/', [BillingPaymentController::class, 'index']);
            Route::post('/', [BillingPaymentController::class, 'store']);
            Route::get('{id}', [BillingPaymentController::class, 'show']);
            Route::put('{id}', [BillingPaymentController::class, 'update']);
            Route::delete('{id}', [BillingPaymentController::class, 'destroy']);
        });

        // Clients (read-only for mobile)
        Route::get('clients', [BillingDocumentController::class, 'clients']);
    });

    // Leave Requests
    Route::prefix('leave-requests')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index']);
        Route::post('/', [LeaveRequestController::class, 'store']);
        Route::get('balance', [LeaveRequestController::class, 'balance']);
        Route::get('types', [LeaveRequestController::class, 'types']);
        Route::get('{id}', [LeaveRequestController::class, 'show']);
        Route::put('{id}', [LeaveRequestController::class, 'update']);
        Route::delete('{id}', [LeaveRequestController::class, 'destroy']);
    });

    // Payroll (read-only)
    Route::prefix('payroll')->group(function () {
        Route::get('payslips', [PayrollController::class, 'payslips']);
        Route::get('payslips/{id}', [PayrollController::class, 'payslipDetail']);
        Route::get('loan-balance', [PayrollController::class, 'loanBalance']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    });

    // Sync (Offline support)
    Route::prefix('sync')->group(function () {
        Route::post('push', [SyncController::class, 'push']);
        Route::get('pull', [SyncController::class, 'pull']);
        Route::get('reference-data', [SyncController::class, 'referenceData']);
    });
});
