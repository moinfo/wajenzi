<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AttendanceTypeApiController;
use App\Http\Controllers\Api\V1\SiteDailyReportController;
use App\Http\Controllers\Api\V1\SalesDailyReportController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ApprovalController;
use App\Http\Controllers\Api\V1\SyncController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\ProjectClientController;
use App\Http\Controllers\Api\V1\ProjectSiteVisitController;
use App\Http\Controllers\Api\V1\BoqController;
use App\Http\Controllers\Api\V1\ProjectMaterialApiController;
use App\Http\Controllers\Api\V1\SaleApiController;
use App\Http\Controllers\Api\V1\MaterialRequestController;
use App\Http\Controllers\Api\V1\BillingDocumentController;
use App\Http\Controllers\Api\V1\BillingDashboardApiController;
use App\Http\Controllers\Api\V1\BillingPaymentController;
use App\Http\Controllers\Api\V1\BillingEmailApiController;
use App\Http\Controllers\Api\V1\BillingProductApiController;
use App\Http\Controllers\Api\V1\StatutoryPaymentApiController;
use App\Http\Controllers\Api\V1\StatutoryCategoryReportApiController;
use App\Http\Controllers\Api\V1\StatutoryPaymentReportApiController;
use App\Http\Controllers\Api\V1\StatutorySchedulesReportApiController;
use App\Http\Controllers\Api\V1\LeaveRequestController;
use App\Http\Controllers\Api\V1\LeaveTypeApiController;
use App\Http\Controllers\Api\V1\CrdbBankFileApiController;
use App\Http\Controllers\Api\V1\PayrollController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\EmployeeProfileController;
use App\Http\Controllers\Api\V1\VatController;
use App\Http\Controllers\Api\V1\StaffBankDetailController;
use App\Http\Controllers\Api\V1\PayrollAdministrationApiController;
use App\Http\Controllers\Api\V1\AdjustmentController;
use App\Http\Controllers\Api\V1\ProcurementController;
use App\Http\Controllers\Api\V1\SupplierQuotationController;
use App\Http\Controllers\Api\V1\PurchaseApiController;
use App\Http\Controllers\Api\V1\MaterialInspectionController;
use App\Http\Controllers\Api\V1\MaterialInventoryApiController;
use App\Http\Controllers\Api\V1\AccountingController;
use App\Http\Controllers\Api\V1\ProjectDailyReportApiController;
use App\Http\Controllers\Api\V1\ProjectTypeApiController;
use App\Http\Controllers\Api\V1\ProjectDocumentApiController;
use App\Http\Controllers\Api\V1\ProjectReportApiController;
use App\Http\Controllers\Api\V1\ProjectScheduleApiController;
use App\Http\Controllers\Api\V1\ChartOfAccountApiController;
use App\Http\Controllers\Api\V1\AccountTypeApiController;
use App\Http\Controllers\Api\V1\ChartOfAccountUsageApiController;
use App\Http\Controllers\Api\V1\LeadApiController;
use App\Http\Controllers\Api\V1\PettyCashRefillRequestApiController;
use App\Http\Controllers\Api\V1\ImprestRequestApiController;
use App\Http\Controllers\Api\V1\ExchangeRateApiController;
use App\Http\Controllers\Api\V1\ChartAccountVariableApiController;
use App\Http\Controllers\Api\V1\BuildingTypeApiController;
use App\Http\Controllers\Api\V1\BoqItemCategoryApiController;
use App\Http\Controllers\Api\V1\BoqItemApiController;
use App\Http\Controllers\Api\V1\BoqTemplateApiController;
use App\Http\Controllers\Api\V1\ConstructionStageApiController;
use App\Http\Controllers\Api\V1\ActivitySettingsApiController;
use App\Http\Controllers\Api\V1\SubActivitySettingsApiController;
use App\Http\Controllers\Api\V1\DeductionApiController;
use App\Http\Controllers\Api\V1\DeductionSubscriptionApiController;
use App\Http\Controllers\Api\V1\AllowanceApiController;
use App\Http\Controllers\Api\V1\AdvanceSalaryApiController;
use App\Http\Controllers\Api\V1\StaffSalaryApiController;
use App\Http\Controllers\Api\V1\LoanApiController;
use App\Http\Controllers\Api\V1\SiteApiController;
use App\Http\Controllers\Api\V1\SiteSupervisorAssignmentApiController;

/*
|--------------------------------------------------------------------------
| API V1 Routes - Mobile App
|--------------------------------------------------------------------------
|
| These routes are for the Wajenzi mobile application with offline-first
| architecture. All routes use Sanctum authentication except login.
|
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

    Route::prefix('attendance-types')->group(function () {
        Route::get('/', [AttendanceTypeApiController::class, 'index']);
        Route::post('/', [AttendanceTypeApiController::class, 'store']);
        Route::get('{id}', [AttendanceTypeApiController::class, 'show']);
        Route::put('{id}', [AttendanceTypeApiController::class, 'update']);
        Route::delete('{id}', [AttendanceTypeApiController::class, 'destroy']);
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

    // Sites Management
    Route::prefix('sites')->group(function () {
        Route::get('/', [SiteApiController::class, 'index']);
        Route::post('/', [SiteApiController::class, 'store']);
        Route::get('{id}', [SiteApiController::class, 'show']);
        Route::put('{id}', [SiteApiController::class, 'update']);
        Route::delete('{id}', [SiteApiController::class, 'destroy']);
        Route::get('supervisors', [SiteApiController::class, 'supervisors']);
    });

    // Site Supervisor Assignments
    Route::prefix('site-supervisor-assignments')->group(function () {
        Route::get('/', [SiteSupervisorAssignmentApiController::class, 'index']);
        Route::post('/', [SiteSupervisorAssignmentApiController::class, 'store']);
        Route::get('available-sites', [SiteSupervisorAssignmentApiController::class, 'availableSites']);
        Route::get('supervisors', [SiteSupervisorAssignmentApiController::class, 'getSupervisors']);
        Route::get('history/{site}', [SiteSupervisorAssignmentApiController::class, 'history']);
        Route::get('{id}', [SiteSupervisorAssignmentApiController::class, 'show']);
        Route::put('{id}', [SiteSupervisorAssignmentApiController::class, 'update']);
        Route::delete('{id}', [SiteSupervisorAssignmentApiController::class, 'destroy']);
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

    // Project Daily Reports
    Route::prefix('project-daily-reports')->group(function () {
        Route::get('projects', [ProjectDailyReportApiController::class, 'projects']);
        Route::get('/', [ProjectDailyReportApiController::class, 'index']);
        Route::post('/', [ProjectDailyReportApiController::class, 'store']);
        Route::get('{id}', [ProjectDailyReportApiController::class, 'show']);
        Route::put('{id}', [ProjectDailyReportApiController::class, 'update']);
        Route::delete('{id}', [ProjectDailyReportApiController::class, 'destroy']);
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

    Route::prefix('petty-cash-refill-requests')->group(function () {
        Route::get('reference-data', [PettyCashRefillRequestApiController::class, 'referenceData']);
        Route::get('/', [PettyCashRefillRequestApiController::class, 'index']);
        Route::post('/', [PettyCashRefillRequestApiController::class, 'store']);
        Route::get('{id}', [PettyCashRefillRequestApiController::class, 'show']);
        Route::post('{id}', [PettyCashRefillRequestApiController::class, 'update']);
        Route::put('{id}', [PettyCashRefillRequestApiController::class, 'update']);
        Route::delete('{id}', [PettyCashRefillRequestApiController::class, 'destroy']);
    });

    Route::prefix('imprest-requests')->group(function () {
        Route::get('reference-data', [ImprestRequestApiController::class, 'referenceData']);
        Route::get('/', [ImprestRequestApiController::class, 'index']);
        Route::post('/', [ImprestRequestApiController::class, 'store']);
        Route::get('{id}', [ImprestRequestApiController::class, 'show']);
        Route::post('{id}', [ImprestRequestApiController::class, 'update']);
        Route::put('{id}', [ImprestRequestApiController::class, 'update']);
        Route::delete('{id}', [ImprestRequestApiController::class, 'destroy']);
    });

    Route::prefix('exchange-rates')->group(function () {
        Route::get('reference-data', [ExchangeRateApiController::class, 'referenceData']);
        Route::get('/', [ExchangeRateApiController::class, 'index']);
        Route::post('/', [ExchangeRateApiController::class, 'store']);
        Route::get('{id}', [ExchangeRateApiController::class, 'show']);
        Route::put('{id}', [ExchangeRateApiController::class, 'update']);
        Route::delete('{id}', [ExchangeRateApiController::class, 'destroy']);
    });

    Route::prefix('statutory-payments')->group(function () {
        Route::get('reference-data', [StatutoryPaymentApiController::class, 'referenceData']);
        Route::get('/', [StatutoryPaymentApiController::class, 'index']);
        Route::post('/', [StatutoryPaymentApiController::class, 'store']);
        Route::get('{id}', [StatutoryPaymentApiController::class, 'show']);
        Route::put('{id}', [StatutoryPaymentApiController::class, 'update']);
        Route::delete('{id}', [StatutoryPaymentApiController::class, 'destroy']);
    });

    Route::get('reports/statutory-category-report', [StatutoryCategoryReportApiController::class, 'index']);
    Route::get('reports/statutory-payment-report', [StatutoryPaymentReportApiController::class, 'index']);
    Route::get('reports/statutory-schedules-report', [StatutorySchedulesReportApiController::class, 'index']);

    Route::prefix('chart-account-variables')->group(function () {
        Route::get('/', [ChartAccountVariableApiController::class, 'index']);
        Route::post('/', [ChartAccountVariableApiController::class, 'store']);
        Route::get('{id}', [ChartAccountVariableApiController::class, 'show']);
        Route::put('{id}', [ChartAccountVariableApiController::class, 'update']);
        Route::delete('{id}', [ChartAccountVariableApiController::class, 'destroy']);
    });

    Route::prefix('building-types')->group(function () {
        Route::get('reference-data', [BuildingTypeApiController::class, 'referenceData']);
        Route::get('/', [BuildingTypeApiController::class, 'index']);
        Route::post('/', [BuildingTypeApiController::class, 'store']);
        Route::get('{id}', [BuildingTypeApiController::class, 'show']);
        Route::put('{id}', [BuildingTypeApiController::class, 'update']);
        Route::delete('{id}', [BuildingTypeApiController::class, 'destroy']);
    });

    Route::prefix('boq-item-categories')->group(function () {
        Route::get('reference-data', [BoqItemCategoryApiController::class, 'referenceData']);
        Route::get('/', [BoqItemCategoryApiController::class, 'index']);
        Route::post('/', [BoqItemCategoryApiController::class, 'store']);
        Route::get('{id}', [BoqItemCategoryApiController::class, 'show']);
        Route::put('{id}', [BoqItemCategoryApiController::class, 'update']);
        Route::delete('{id}', [BoqItemCategoryApiController::class, 'destroy']);
    });

    Route::prefix('boq-items')->group(function () {
        Route::get('reference-data', [BoqItemApiController::class, 'referenceData']);
        Route::get('/', [BoqItemApiController::class, 'index']);
        Route::post('/', [BoqItemApiController::class, 'store']);
        Route::get('{id}', [BoqItemApiController::class, 'show']);
        Route::put('{id}', [BoqItemApiController::class, 'update']);
        Route::delete('{id}', [BoqItemApiController::class, 'destroy']);
    });

    Route::prefix('boq-templates')->group(function () {
        Route::get('reference-data', [BoqTemplateApiController::class, 'referenceData']);
        Route::get('/', [BoqTemplateApiController::class, 'index']);
        Route::post('/', [BoqTemplateApiController::class, 'store']);
        Route::get('{id}', [BoqTemplateApiController::class, 'show']);
        Route::put('{id}', [BoqTemplateApiController::class, 'update']);
        Route::delete('{id}', [BoqTemplateApiController::class, 'destroy']);
    });

    Route::prefix('construction-stages')->group(function () {
        Route::get('reference-data', [ConstructionStageApiController::class, 'referenceData']);
        Route::get('/', [ConstructionStageApiController::class, 'index']);
        Route::post('/', [ConstructionStageApiController::class, 'store']);
        Route::get('{id}', [ConstructionStageApiController::class, 'show']);
        Route::put('{id}', [ConstructionStageApiController::class, 'update']);
        Route::delete('{id}', [ConstructionStageApiController::class, 'destroy']);
    });

    Route::prefix('settings-activities')->group(function () {
        Route::get('reference-data', [ActivitySettingsApiController::class, 'referenceData']);
        Route::get('/', [ActivitySettingsApiController::class, 'index']);
        Route::post('/', [ActivitySettingsApiController::class, 'store']);
        Route::get('{id}', [ActivitySettingsApiController::class, 'show']);
        Route::put('{id}', [ActivitySettingsApiController::class, 'update']);
        Route::delete('{id}', [ActivitySettingsApiController::class, 'destroy']);
    });

    Route::prefix('settings-sub-activities')->group(function () {
        Route::get('reference-data', [SubActivitySettingsApiController::class, 'referenceData']);
        Route::get('/', [SubActivitySettingsApiController::class, 'index']);
        Route::post('/', [SubActivitySettingsApiController::class, 'store']);
        Route::get('{id}', [SubActivitySettingsApiController::class, 'show']);
        Route::put('{id}', [SubActivitySettingsApiController::class, 'update']);
        Route::delete('{id}', [SubActivitySettingsApiController::class, 'destroy']);
    });

    Route::prefix('deductions')->group(function () {
        Route::get('reference-data', [DeductionApiController::class, 'referenceData']);
        Route::get('/', [DeductionApiController::class, 'index']);
        Route::post('/', [DeductionApiController::class, 'store']);
        Route::get('{id}', [DeductionApiController::class, 'show']);
        Route::put('{id}', [DeductionApiController::class, 'update']);
        Route::delete('{id}', [DeductionApiController::class, 'destroy']);
    });

    Route::prefix('allowances')->group(function () {
        Route::get('reference-data', [AllowanceApiController::class, 'referenceData']);
        Route::get('/', [AllowanceApiController::class, 'index']);
        Route::post('/', [AllowanceApiController::class, 'store']);
        Route::get('{id}', [AllowanceApiController::class, 'show']);
        Route::put('{id}', [AllowanceApiController::class, 'update']);
        Route::delete('{id}', [AllowanceApiController::class, 'destroy']);
    });

    Route::prefix('advance-salaries')->group(function () {
        Route::get('reference-data', [AdvanceSalaryApiController::class, 'referenceData']);
        Route::get('/', [AdvanceSalaryApiController::class, 'index']);
        Route::post('/', [AdvanceSalaryApiController::class, 'store']);
        Route::get('{id}', [AdvanceSalaryApiController::class, 'show']);
        Route::put('{id}', [AdvanceSalaryApiController::class, 'update']);
        Route::delete('{id}', [AdvanceSalaryApiController::class, 'destroy']);
    });

    Route::prefix('staff-salaries')->group(function () {
        Route::get('reference-data', [StaffSalaryApiController::class, 'referenceData']);
        Route::get('/', [StaffSalaryApiController::class, 'index']);
        Route::post('/', [StaffSalaryApiController::class, 'store']);
        Route::get('{id}', [StaffSalaryApiController::class, 'show']);
        Route::put('{id}', [StaffSalaryApiController::class, 'update']);
        Route::delete('{id}', [StaffSalaryApiController::class, 'destroy']);
    });

    Route::prefix('staff-loans')->group(function () {
        Route::get('reference-data', [LoanApiController::class, 'referenceData']);
        Route::get('/', [LoanApiController::class, 'index']);
        Route::post('/', [LoanApiController::class, 'store']);
        Route::get('{id}', [LoanApiController::class, 'show']);
        Route::put('{id}', [LoanApiController::class, 'update']);
        Route::delete('{id}', [LoanApiController::class, 'destroy']);
    });

    Route::prefix('deduction-subscriptions')->group(function () {
        Route::get('reference-data', [DeductionSubscriptionApiController::class, 'referenceData']);
        Route::get('/', [DeductionSubscriptionApiController::class, 'index']);
        Route::post('/', [DeductionSubscriptionApiController::class, 'store']);
        Route::get('{id}', [DeductionSubscriptionApiController::class, 'show']);
        Route::put('{id}', [DeductionSubscriptionApiController::class, 'update']);
        Route::delete('{id}', [DeductionSubscriptionApiController::class, 'destroy']);
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
        Route::get('stats', [ProjectController::class, 'stats']);
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('{id}', [ProjectController::class, 'show']);
        Route::put('{id}', [ProjectController::class, 'update']);
        Route::delete('{id}', [ProjectController::class, 'destroy']);
        Route::get('{id}/boq', [ProjectController::class, 'boq']);
        Route::get('{id}/materials', [ProjectController::class, 'materials']);
        Route::get('{id}/sites', [ProjectController::class, 'sites']);
        Route::get('{id}/team', [ProjectController::class, 'team']);
    });

    // Leads
    Route::prefix('leads')->group(function () {
        Route::get('reference-data', [LeadApiController::class, 'referenceData']);
        Route::get('/', [LeadApiController::class, 'index']);
        Route::post('/', [LeadApiController::class, 'store']);
        Route::get('{id}', [LeadApiController::class, 'show']);
        Route::put('{id}', [LeadApiController::class, 'update']);
        Route::delete('{id}', [LeadApiController::class, 'destroy']);
    });

    // Project Types
    Route::prefix('project-types')->group(function () {
        Route::get('/', [ProjectTypeApiController::class, 'index']);
        Route::post('/', [ProjectTypeApiController::class, 'store']);
        Route::get('{id}', [ProjectTypeApiController::class, 'show']);
        Route::put('{id}', [ProjectTypeApiController::class, 'update']);
        Route::delete('{id}', [ProjectTypeApiController::class, 'destroy']);
    });

    // Project Clients
    Route::prefix('project-clients')->group(function () {
        Route::get('/', [ProjectClientController::class, 'index']);
        Route::post('/', [ProjectClientController::class, 'store']);
        Route::get('{id}', [ProjectClientController::class, 'show']);
        Route::put('{id}', [ProjectClientController::class, 'update']);
        Route::delete('{id}', [ProjectClientController::class, 'destroy']);
    });

    // BOQ Management
    Route::prefix('boqs')->group(function () {
        Route::get('projects', [BoqController::class, 'projects']);
        Route::get('/', [BoqController::class, 'index']);
        Route::post('/', [BoqController::class, 'store']);
        Route::get('{id}', [BoqController::class, 'show']);
        Route::put('{id}', [BoqController::class, 'update']);
        Route::delete('{id}', [BoqController::class, 'destroy']);
        Route::get('next-version', [BoqController::class, 'nextVersion']);
    });

    // Project Materials
    Route::prefix('project-materials')->group(function () {
        Route::get('/', [ProjectMaterialApiController::class, 'index']);
        Route::post('/', [ProjectMaterialApiController::class, 'store']);
        Route::get('{id}', [ProjectMaterialApiController::class, 'show']);
        Route::put('{id}', [ProjectMaterialApiController::class, 'update']);
        Route::delete('{id}', [ProjectMaterialApiController::class, 'destroy']);
    });

    // Project Documents
    Route::prefix('project-documents')->group(function () {
        Route::get('/', [ProjectDocumentApiController::class, 'index']);
        Route::get('{id}', [ProjectDocumentApiController::class, 'show']);
    });

    // Project Reports
    Route::prefix('project-reports')->group(function () {
        Route::get('projects', [ProjectReportApiController::class, 'projects']);
        Route::get('/', [ProjectReportApiController::class, 'index']);
    });

    // Project Schedules
    Route::prefix('project-schedules')->group(function () {
        Route::get('/', [ProjectScheduleApiController::class, 'index']);
        Route::get('{id}', [ProjectScheduleApiController::class, 'show']);
    });

    // Charts of Accounts
    Route::prefix('charts-of-accounts')->group(function () {
        Route::get('/', [ChartOfAccountApiController::class, 'index']);
        Route::post('/', [ChartOfAccountApiController::class, 'store']);
        Route::get('{id}', [ChartOfAccountApiController::class, 'show']);
        Route::put('{id}', [ChartOfAccountApiController::class, 'update']);
        Route::delete('{id}', [ChartOfAccountApiController::class, 'destroy']);
    });

    // Account Types
    Route::prefix('account-types')->group(function () {
        Route::get('/', [AccountTypeApiController::class, 'index']);
        Route::post('/', [AccountTypeApiController::class, 'store']);
        Route::get('{id}', [AccountTypeApiController::class, 'show']);
        Route::put('{id}', [AccountTypeApiController::class, 'update']);
        Route::delete('{id}', [AccountTypeApiController::class, 'destroy']);
    });

    // Chart Account Usages
    Route::prefix('chart-account-usages')->group(function () {
        Route::get('reference-data', [ChartOfAccountUsageApiController::class, 'referenceData']);
        Route::get('/', [ChartOfAccountUsageApiController::class, 'index']);
        Route::post('/', [ChartOfAccountUsageApiController::class, 'store']);
        Route::get('{id}', [ChartOfAccountUsageApiController::class, 'show']);
        Route::put('{id}', [ChartOfAccountUsageApiController::class, 'update']);
        Route::delete('{id}', [ChartOfAccountUsageApiController::class, 'destroy']);
    });

    // Material Inventory
    Route::prefix('material-inventory')->group(function () {
        Route::get('projects', [MaterialInventoryApiController::class, 'projects']);
        Route::get('materials', [MaterialInventoryApiController::class, 'materials']);
        Route::get('/', [MaterialInventoryApiController::class, 'index']);
        Route::post('/', [MaterialInventoryApiController::class, 'store']);
        Route::get('{id}', [MaterialInventoryApiController::class, 'show']);
        Route::put('{id}', [MaterialInventoryApiController::class, 'update']);
        Route::delete('{id}', [MaterialInventoryApiController::class, 'destroy']);
    });

    // Sales
    Route::prefix('sales')->group(function () {
        Route::get('efds', [SaleApiController::class, 'efds']);
        Route::get('/', [SaleApiController::class, 'index']);
        Route::post('/', [SaleApiController::class, 'store']);
        Route::get('{id}', [SaleApiController::class, 'show']);
        Route::put('{id}', [SaleApiController::class, 'update']);
        Route::delete('{id}', [SaleApiController::class, 'destroy']);
    });

    // Purchases (Procurement)
    Route::prefix('purchases')->group(function () {
        Route::get('suppliers', [PurchaseApiController::class, 'suppliers']);
        Route::get('/', [PurchaseApiController::class, 'index']);
        Route::post('/', [PurchaseApiController::class, 'store']);
        Route::get('{id}', [PurchaseApiController::class, 'show']);
        Route::put('{id}', [PurchaseApiController::class, 'update']);
        Route::delete('{id}', [PurchaseApiController::class, 'destroy']);
    });

    // Site Visits
    Route::prefix('site-visits')->group(function () {
        Route::get('/projects', [ProjectSiteVisitController::class, 'projects']);
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
        Route::get('dashboard', [BillingDashboardApiController::class, 'index']);
        Route::get('reference-data', [BillingDocumentController::class, 'referenceData']);
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
            Route::get('reference-data', [BillingPaymentController::class, 'referenceData']);
            Route::get('/', [BillingPaymentController::class, 'index']);
            Route::post('/', [BillingPaymentController::class, 'store']);
            Route::get('{id}', [BillingPaymentController::class, 'show']);
            Route::put('{id}', [BillingPaymentController::class, 'update']);
            Route::delete('{id}', [BillingPaymentController::class, 'destroy']);
        });

        Route::prefix('emails')->group(function () {
            Route::get('/', [BillingEmailApiController::class, 'index']);
            Route::get('{id}', [BillingEmailApiController::class, 'show']);
            Route::post('{id}/resend', [BillingEmailApiController::class, 'resend']);
        });

        Route::prefix('products')->group(function () {
            Route::get('reference-data', [BillingProductApiController::class, 'referenceData']);
            Route::get('/', [BillingProductApiController::class, 'index']);
            Route::post('/', [BillingProductApiController::class, 'store']);
            Route::get('{id}', [BillingProductApiController::class, 'show']);
            Route::put('{id}', [BillingProductApiController::class, 'update']);
            Route::delete('{id}', [BillingProductApiController::class, 'destroy']);
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

    Route::prefix('leave-managements')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'managementIndex']);
        Route::get('{id}', [LeaveRequestController::class, 'managementShow']);
        Route::put('{id}', [LeaveRequestController::class, 'managementUpdate']);
    });

    Route::prefix('leave-types')->group(function () {
        Route::get('/', [LeaveTypeApiController::class, 'index']);
        Route::post('/', [LeaveTypeApiController::class, 'store']);
        Route::get('{id}', [LeaveTypeApiController::class, 'show']);
        Route::put('{id}', [LeaveTypeApiController::class, 'update']);
        Route::delete('{id}', [LeaveTypeApiController::class, 'destroy']);
    });

    // Payroll (read-only)
    Route::prefix('payroll')->group(function () {
        Route::get('payslips', [PayrollController::class, 'payslips']);
        Route::get('payslips/{id}', [PayrollController::class, 'payslipDetail']);
        Route::get('loan-balance', [PayrollController::class, 'loanBalance']);
        Route::get('crdb-bank-file', [CrdbBankFileApiController::class, 'index']);
    });

    Route::prefix('payroll-administration')->group(function () {
        Route::get('reference-data', [PayrollAdministrationApiController::class, 'referenceData']);
        Route::get('/', [PayrollAdministrationApiController::class, 'index']);
        Route::post('/', [PayrollAdministrationApiController::class, 'store']);
        Route::get('{id}', [PayrollAdministrationApiController::class, 'show']);
        Route::put('{id}', [PayrollAdministrationApiController::class, 'update']);
        Route::delete('{id}', [PayrollAdministrationApiController::class, 'destroy']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::put('{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('read-all', [NotificationController::class, 'markAllAsRead']);
        Route::get('unread-count', [NotificationController::class, 'unreadCount']);
    });

    // VAT Module
    Route::prefix('vat')->group(function () {
        Route::get('reference-data', [VatController::class, 'referenceData']);

        Route::get('sales', [VatController::class, 'sales']);
        Route::post('sales', [VatController::class, 'storeSale']);
        Route::get('sales/{id}', [VatController::class, 'showSale']);
        Route::put('sales/{id}', [VatController::class, 'updateSale']);
        Route::delete('sales/{id}', [VatController::class, 'destroySale']);

        Route::get('purchases', [VatController::class, 'purchases']);
        Route::post('purchases', [VatController::class, 'storePurchase']);
        Route::get('purchases/{id}', [VatController::class, 'showPurchase']);
        Route::put('purchases/{id}', [VatController::class, 'updatePurchase']);
        Route::delete('purchases/{id}', [VatController::class, 'destroyPurchase']);

        Route::get('auto-purchases', [VatController::class, 'autoPurchases']);

        Route::get('payments', [VatController::class, 'payments']);
        Route::post('payments', [VatController::class, 'storePayment']);
        Route::get('payments/{id}', [VatController::class, 'showPayment']);
        Route::put('payments/{id}', [VatController::class, 'updatePayment']);
        Route::delete('payments/{id}', [VatController::class, 'destroyPayment']);
    });

    // Employee Profile
    Route::get('employee-profile', [EmployeeProfileController::class, 'index']);
    Route::get('employee-profile/staff-list', [EmployeeProfileController::class, 'staffList']);

    // Sync (Offline support)
    Route::prefix('sync')->group(function () {
        Route::post('push', [SyncController::class, 'push']);
        Route::get('pull', [SyncController::class, 'pull']);
        Route::get('reference-data', [SyncController::class, 'referenceData']);
    });

    // Staff Bank Details
    Route::prefix('staff-bank-details')->group(function () {
        Route::get('reference-data', [StaffBankDetailController::class, 'referenceData']);
        Route::get('/', [StaffBankDetailController::class, 'index']);
        Route::post('/', [StaffBankDetailController::class, 'store']);
        Route::get('{id}', [StaffBankDetailController::class, 'show']);
        Route::put('{id}', [StaffBankDetailController::class, 'update']);
        Route::delete('{id}', [StaffBankDetailController::class, 'destroy']);
    });

    // Adjustments
    Route::get('adjustments', [AdjustmentController::class, 'index']);
    Route::get('adjustments/{id}', [AdjustmentController::class, 'show']);

    // Accounting
    Route::get('accounting', [AccountingController::class, 'index']);

    // Procurement
    Route::prefix('procurement')->group(function () {
        Route::get('dashboard', [ProcurementController::class, 'dashboard']);
        Route::get('supplier-quotations', [SupplierQuotationController::class, 'index']);
        Route::get('supplier-quotations/{id}', [SupplierQuotationController::class, 'show']);
        Route::get('purchases', [PurchaseApiController::class, 'index']);
        Route::get('purchases/{id}', [PurchaseApiController::class, 'show']);
        Route::get('inspections', [MaterialInspectionController::class, 'index']);
        Route::get('inspections/{id}', [MaterialInspectionController::class, 'show']);
    });
});
