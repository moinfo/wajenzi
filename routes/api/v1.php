<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\AttendanceTypeApiController;
use App\Http\Controllers\Api\V1\SiteDailyReportController;
use App\Http\Controllers\Api\V1\SalesDailyReportController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ProjectExpenseController;
use App\Http\Controllers\Api\V1\LaborDashboardApiController;
use App\Http\Controllers\Api\V1\LaborContractApiController;
use App\Http\Controllers\Api\V1\LaborRequestApiController;
use App\Http\Controllers\Api\V1\LaborWorkLogApiController;
use App\Http\Controllers\Api\V1\LaborInspectionApiController;
use App\Http\Controllers\Api\V1\LaborPaymentApiController;
use App\Http\Controllers\Api\V1\ArchitectBonusApiController;
use App\Http\Controllers\Api\V1\ProvisionTaxApiController;
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
use App\Http\Controllers\Api\V1\MessageApiController;
use App\Http\Controllers\Api\V1\ReportsApiController;
use App\Http\Controllers\Api\V1\SettingsCatalogApiController;
use App\Http\Controllers\Api\V1\ProcessApprovalFlowApiController;
use App\Http\Controllers\Api\V1\ProcessApprovalFlowStepApiController;
use App\Http\Controllers\Api\V1\AllowanceSubscriptionApiController;
use App\Http\Controllers\Api\V1\DeductionSettingApiController;
use App\Http\Controllers\Api\V1\DepartmentApiController;
use App\Http\Controllers\Api\V1\PositionApiController;
use App\Http\Controllers\Api\V1\RoleApiController;
use App\Http\Controllers\Api\V1\PermissionApiController;
use App\Http\Controllers\Api\V1\SupplierSettingsApiController;
use App\Http\Controllers\Api\V1\ItemSettingsApiController;
use App\Http\Controllers\Api\V1\ExpensesCategoryApiController;
use App\Http\Controllers\Api\V1\ExpensesSubCategoryApiController;
use App\Http\Controllers\Api\V1\FinancialChargeCategoryApiController;
use App\Http\Controllers\Api\V1\EfdApiController;
use App\Http\Controllers\Api\V1\ApprovalDocumentTypeApiController;
use App\Http\Controllers\Api\V1\ApprovalLevelApiController;
use App\Http\Controllers\Api\V1\ServiceInterestedApiController;
use App\Http\Controllers\Api\V1\LeadStatusApiController;
use App\Http\Controllers\Api\V1\LeadSourceApiController;
use App\Http\Controllers\Api\V1\ServiceTypeApiController;
use App\Http\Controllers\Api\V1\ProjectStatusApiController;
use App\Http\Controllers\Api\V1\CostCategoryApiController;
use App\Http\Controllers\Api\V1\SystemApiController;
use App\Http\Controllers\Api\V1\SettingsUserApiController;
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
use App\Http\Controllers\Api\V1\SalarySlipApiController;
use App\Http\Controllers\Api\V1\SiteApiController;
use App\Http\Controllers\Api\V1\SiteSupervisorAssignmentApiController;
use App\Http\Controllers\Api\V1\KpiApiController;
use App\Http\Controllers\Api\V1\CurrencyApiController;
use App\Http\Controllers\Api\V1\DesignServicePackageApiController;
use App\Http\Controllers\Api\V1\DesignServiceAddonApiController;
use App\Http\Controllers\Api\V1\DesignSpecialStructureApiController;
use App\Http\Controllers\Api\V1\SiteVisitLocationApiController;
use App\Http\Controllers\Api\V1\DesignPricingCalculatorApiController;
use App\Http\Controllers\Api\V1\SiteVisitCalculatorApiController;
use App\Http\Controllers\Api\V1\FieldMarketingApiController;
use App\Http\Controllers\Api\V1\WhatsAppMarketingApiController;
use App\Http\Controllers\Api\V1\ContentCreatorApiController;
use App\Http\Controllers\Api\V1\StructuralDesignApiController;
use App\Http\Controllers\Api\V1\ServiceDesignApiController;

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

// Public landing/website content (CMS) — shown on the pre-login landing screen.
Route::prefix('public')->group(function () {
    Route::get('portfolio', [\App\Http\Controllers\Api\V1\Public\LandingPortfolioController::class, 'index']);
    Route::get('portfolio/{id}', [\App\Http\Controllers\Api\V1\Public\LandingPortfolioController::class, 'show']);
    Route::post('portfolio/{id}/like', [\App\Http\Controllers\Api\V1\Public\LandingPortfolioController::class, 'toggleLike']);
    Route::get('awards', [\App\Http\Controllers\Api\V1\Public\LandingAwardController::class, 'index']);
    Route::get('services', [\App\Http\Controllers\Api\V1\Public\LandingServiceController::class, 'index']);
    Route::get('posters', [\App\Http\Controllers\Api\V1\Public\LandingPosterController::class, 'index']);
    Route::get('stats', [\App\Http\Controllers\Api\V1\Public\LandingStatController::class, 'index']);
    Route::get('about', [\App\Http\Controllers\Api\V1\Public\LandingAboutController::class, 'index']);
});

// Protected routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/user', [AuthController::class, 'user']);
    Route::post('auth/device-token', [AuthController::class, 'registerDeviceToken']);
    Route::post('auth/profile', [AuthController::class, 'updateProfile']);
    Route::put('auth/password', [AuthController::class, 'changePassword']);

    // Menus (permission-filtered sidebar)
    Route::get('menus', [MenuController::class, 'index']);
    Route::get('reports', [ReportsApiController::class, 'index']);
    Route::get('settings/catalog', [SettingsCatalogApiController::class, 'index']);
    Route::prefix('process-approval-flows')->group(function () {
        Route::get('/', [ProcessApprovalFlowApiController::class, 'index']);
        Route::post('/', [ProcessApprovalFlowApiController::class, 'store']);
        Route::get('{id}', [ProcessApprovalFlowApiController::class, 'show']);
        Route::put('{id}', [ProcessApprovalFlowApiController::class, 'update']);
        Route::delete('{id}', [ProcessApprovalFlowApiController::class, 'destroy']);
    });
    Route::prefix('process-approval-flow-steps')->group(function () {
        Route::get('reference-data', [ProcessApprovalFlowStepApiController::class, 'referenceData']);
        Route::get('/', [ProcessApprovalFlowStepApiController::class, 'index']);
        Route::post('/', [ProcessApprovalFlowStepApiController::class, 'store']);
        Route::get('{id}', [ProcessApprovalFlowStepApiController::class, 'show']);
        Route::put('{id}', [ProcessApprovalFlowStepApiController::class, 'update']);
        Route::delete('{id}', [ProcessApprovalFlowStepApiController::class, 'destroy']);
    });
    Route::prefix('allowance-subscriptions')->group(function () {
        Route::get('reference-data', [AllowanceSubscriptionApiController::class, 'referenceData']);
        Route::get('/', [AllowanceSubscriptionApiController::class, 'index']);
        Route::post('/', [AllowanceSubscriptionApiController::class, 'store']);
        Route::get('{id}', [AllowanceSubscriptionApiController::class, 'show']);
        Route::put('{id}', [AllowanceSubscriptionApiController::class, 'update']);
        Route::delete('{id}', [AllowanceSubscriptionApiController::class, 'destroy']);
    });
    Route::prefix('deduction-settings')->group(function () {
        Route::get('reference-data', [DeductionSettingApiController::class, 'referenceData']);
        Route::get('/', [DeductionSettingApiController::class, 'index']);
        Route::post('/', [DeductionSettingApiController::class, 'store']);
        Route::get('{id}', [DeductionSettingApiController::class, 'show']);
        Route::put('{id}', [DeductionSettingApiController::class, 'update']);
        Route::delete('{id}', [DeductionSettingApiController::class, 'destroy']);
    });
    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentApiController::class, 'index']);
        Route::post('/', [DepartmentApiController::class, 'store']);
        Route::get('{id}', [DepartmentApiController::class, 'show']);
        Route::put('{id}', [DepartmentApiController::class, 'update']);
        Route::delete('{id}', [DepartmentApiController::class, 'destroy']);
    });
    Route::prefix('positions')->group(function () {
        Route::get('reference-data', [PositionApiController::class, 'referenceData']);
        Route::get('/', [PositionApiController::class, 'index']);
        Route::post('/', [PositionApiController::class, 'store']);
        Route::get('{id}', [PositionApiController::class, 'show']);
        Route::put('{id}', [PositionApiController::class, 'update']);
        Route::delete('{id}', [PositionApiController::class, 'destroy']);
    });
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleApiController::class, 'index']);
        Route::post('/', [RoleApiController::class, 'store']);
        Route::get('{id}', [RoleApiController::class, 'show']);
        Route::put('{id}', [RoleApiController::class, 'update']);
        Route::delete('{id}', [RoleApiController::class, 'destroy']);
    });
    Route::prefix('permissions')->group(function () {
        Route::get('reference-data', [PermissionApiController::class, 'referenceData']);
        Route::get('/', [PermissionApiController::class, 'index']);
        Route::post('/', [PermissionApiController::class, 'store']);
        Route::get('{id}', [PermissionApiController::class, 'show']);
        Route::put('{id}', [PermissionApiController::class, 'update']);
        Route::delete('{id}', [PermissionApiController::class, 'destroy']);
    });
    Route::prefix('settings-suppliers')->group(function () {
        Route::get('reference-data', [SupplierSettingsApiController::class, 'referenceData']);
        Route::get('/', [SupplierSettingsApiController::class, 'index']);
        Route::post('/', [SupplierSettingsApiController::class, 'store']);
        Route::get('{id}', [SupplierSettingsApiController::class, 'show']);
        Route::put('{id}', [SupplierSettingsApiController::class, 'update']);
        Route::delete('{id}', [SupplierSettingsApiController::class, 'destroy']);
        Route::post('{supplierId}/contacts', [SupplierSettingsApiController::class, 'storeContact']);
        Route::put('contacts/{contactId}', [SupplierSettingsApiController::class, 'updateContact']);
        Route::delete('contacts/{contactId}', [SupplierSettingsApiController::class, 'destroyContact']);
    });
    Route::prefix('settings-items')->group(function () {
        Route::get('/', [ItemSettingsApiController::class, 'index']);
        Route::post('/', [ItemSettingsApiController::class, 'store']);
        Route::get('{id}', [ItemSettingsApiController::class, 'show']);
        Route::put('{id}', [ItemSettingsApiController::class, 'update']);
        Route::delete('{id}', [ItemSettingsApiController::class, 'destroy']);
    });
    Route::prefix('expenses-categories')->group(function () {
        Route::get('/', [ExpensesCategoryApiController::class, 'index']);
        Route::post('/', [ExpensesCategoryApiController::class, 'store']);
        Route::get('{id}', [ExpensesCategoryApiController::class, 'show']);
        Route::put('{id}', [ExpensesCategoryApiController::class, 'update']);
        Route::delete('{id}', [ExpensesCategoryApiController::class, 'destroy']);
    });
    Route::prefix('expenses-sub-categories')->group(function () {
        Route::get('reference-data', [ExpensesSubCategoryApiController::class, 'referenceData']);
        Route::get('/', [ExpensesSubCategoryApiController::class, 'index']);
        Route::post('/', [ExpensesSubCategoryApiController::class, 'store']);
        Route::get('{id}', [ExpensesSubCategoryApiController::class, 'show']);
        Route::put('{id}', [ExpensesSubCategoryApiController::class, 'update']);
        Route::delete('{id}', [ExpensesSubCategoryApiController::class, 'destroy']);
    });
    Route::prefix('financial-charge-categories')->group(function () {
        Route::get('/', [FinancialChargeCategoryApiController::class, 'index']);
        Route::post('/', [FinancialChargeCategoryApiController::class, 'store']);
        Route::get('{id}', [FinancialChargeCategoryApiController::class, 'show']);
        Route::put('{id}', [FinancialChargeCategoryApiController::class, 'update']);
        Route::delete('{id}', [FinancialChargeCategoryApiController::class, 'destroy']);
    });
    Route::prefix('efds')->group(function () {
        Route::get('reference-data', [EfdApiController::class, 'referenceData']);
        Route::get('/', [EfdApiController::class, 'index']);
        Route::post('/', [EfdApiController::class, 'store']);
        Route::get('{id}', [EfdApiController::class, 'show']);
        Route::put('{id}', [EfdApiController::class, 'update']);
        Route::delete('{id}', [EfdApiController::class, 'destroy']);
    });
    Route::prefix('approval-document-types')->group(function () {
        Route::get('/', [ApprovalDocumentTypeApiController::class, 'index']);
        Route::post('/', [ApprovalDocumentTypeApiController::class, 'store']);
        Route::get('{id}', [ApprovalDocumentTypeApiController::class, 'show']);
        Route::put('{id}', [ApprovalDocumentTypeApiController::class, 'update']);
        Route::delete('{id}', [ApprovalDocumentTypeApiController::class, 'destroy']);
    });
    Route::prefix('approval-levels')->group(function () {
        Route::get('reference-data', [ApprovalLevelApiController::class, 'referenceData']);
        Route::get('/', [ApprovalLevelApiController::class, 'index']);
        Route::post('/', [ApprovalLevelApiController::class, 'store']);
        Route::get('{id}', [ApprovalLevelApiController::class, 'show']);
        Route::put('{id}', [ApprovalLevelApiController::class, 'update']);
        Route::delete('{id}', [ApprovalLevelApiController::class, 'destroy']);
    });
    Route::prefix('service-interesteds')->group(function () {
        Route::get('/', [ServiceInterestedApiController::class, 'index']);
        Route::post('/', [ServiceInterestedApiController::class, 'store']);
        Route::get('{id}', [ServiceInterestedApiController::class, 'show']);
        Route::put('{id}', [ServiceInterestedApiController::class, 'update']);
        Route::delete('{id}', [ServiceInterestedApiController::class, 'destroy']);
    });
    Route::prefix('lead-statuses')->group(function () {
        Route::get('/', [LeadStatusApiController::class, 'index']);
        Route::post('/', [LeadStatusApiController::class, 'store']);
        Route::get('{id}', [LeadStatusApiController::class, 'show']);
        Route::put('{id}', [LeadStatusApiController::class, 'update']);
        Route::delete('{id}', [LeadStatusApiController::class, 'destroy']);
    });
    Route::prefix('lead-sources')->group(function () {
        Route::get('/', [LeadSourceApiController::class, 'index']);
        Route::post('/', [LeadSourceApiController::class, 'store']);
        Route::get('{id}', [LeadSourceApiController::class, 'show']);
        Route::put('{id}', [LeadSourceApiController::class, 'update']);
        Route::delete('{id}', [LeadSourceApiController::class, 'destroy']);
    });
    Route::prefix('service-types')->group(function () {
        Route::get('/', [ServiceTypeApiController::class, 'index']);
        Route::post('/', [ServiceTypeApiController::class, 'store']);
        Route::get('{id}', [ServiceTypeApiController::class, 'show']);
        Route::put('{id}', [ServiceTypeApiController::class, 'update']);
        Route::delete('{id}', [ServiceTypeApiController::class, 'destroy']);
    });
    Route::prefix('project-statuses')->group(function () {
        Route::get('/', [ProjectStatusApiController::class, 'index']);
        Route::post('/', [ProjectStatusApiController::class, 'store']);
        Route::get('{id}', [ProjectStatusApiController::class, 'show']);
        Route::put('{id}', [ProjectStatusApiController::class, 'update']);
        Route::delete('{id}', [ProjectStatusApiController::class, 'destroy']);
    });
    Route::prefix('cost-categories')->group(function () {
        Route::get('/', [CostCategoryApiController::class, 'index']);
        Route::post('/', [CostCategoryApiController::class, 'store']);
        Route::get('{id}', [CostCategoryApiController::class, 'show']);
        Route::put('{id}', [CostCategoryApiController::class, 'update']);
        Route::delete('{id}', [CostCategoryApiController::class, 'destroy']);
    });
    Route::prefix('systems')->group(function () {
        Route::get('/', [SystemApiController::class, 'index']);
        Route::post('/', [SystemApiController::class, 'store']);
        Route::get('{id}', [SystemApiController::class, 'show']);
        Route::put('{id}', [SystemApiController::class, 'update']);
        Route::delete('{id}', [SystemApiController::class, 'destroy']);
    });
    Route::prefix('settings-users')->group(function () {
        Route::get('reference-data', [SettingsUserApiController::class, 'referenceData']);
        Route::get('/', [SettingsUserApiController::class, 'index']);
        Route::post('/', [SettingsUserApiController::class, 'store']);
        Route::get('{id}', [SettingsUserApiController::class, 'show']);
        Route::put('{id}', [SettingsUserApiController::class, 'update']);
        Route::delete('{id}', [SettingsUserApiController::class, 'destroy']);
        Route::post('{id}/toggle-status', [SettingsUserApiController::class, 'toggleStatus']);
    });

    Route::prefix('messages')->group(function () {
        Route::get('/', [MessageApiController::class, 'index']);
        Route::get('reference-data', [MessageApiController::class, 'referenceData']);
        Route::get('birthdays', [MessageApiController::class, 'birthdays']);
        Route::post('/', [MessageApiController::class, 'store']);
        Route::post('bulk', [MessageApiController::class, 'bulkStore']);
        Route::get('{id}', [MessageApiController::class, 'show']);
        Route::put('{id}', [MessageApiController::class, 'update']);
        Route::delete('{id}', [MessageApiController::class, 'destroy']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        
        Route::get('/activities', [DashboardController::class, 'activities']);
        Route::get('/followups', [DashboardController::class, 'followups']);
        Route::get('/invoices', [DashboardController::class, 'invoices']);
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

    Route::prefix('project-expenses')->group(function () {
        Route::get('/', [ProjectExpenseController::class, 'index']);
        Route::post('/', [ProjectExpenseController::class, 'store']);
        Route::get('categories', [ProjectExpenseController::class, 'categories']);
        Route::get('{id}', [ProjectExpenseController::class, 'show']);
        Route::put('{id}', [ProjectExpenseController::class, 'update']);
        Route::delete('{id}', [ProjectExpenseController::class, 'destroy']);
    });

    Route::prefix('labor')->group(function () {
        Route::get('dashboard', [LaborDashboardApiController::class, 'index']);
        
        // Labor Requests CRUD
        Route::get('requests/reference-data', [LaborRequestApiController::class, 'referenceData']);
        Route::get('requests/construction-phases/{projectId}', [LaborRequestApiController::class, 'getConstructionPhases']);
        Route::get('requests/dashboard', [LaborRequestApiController::class, 'dashboard']);
        Route::get('requests', [LaborRequestApiController::class, 'index']);
        Route::post('requests', [LaborRequestApiController::class, 'store']);
        Route::get('requests/{id}', [LaborRequestApiController::class, 'show']);
        Route::put('requests/{id}', [LaborRequestApiController::class, 'update']);
        Route::delete('requests/{id}', [LaborRequestApiController::class, 'destroy']);
        Route::post('requests/{id}/submit', [LaborRequestApiController::class, 'submit']);
        Route::post('requests/{id}/approve', [LaborRequestApiController::class, 'approve']);
        Route::post('requests/{id}/reject', [LaborRequestApiController::class, 'reject']);
        Route::post('requests/{id}/negotiation', [LaborRequestApiController::class, 'updateNegotiation']);
        Route::post('requests/{id}/assessment', [LaborRequestApiController::class, 'recordAssessment']);

        // Labor Contracts CRUD
        Route::get('contracts/reference-data', [LaborContractApiController::class, 'referenceData']);
        Route::get('contracts/dashboard', [LaborContractApiController::class, 'dashboard']);
        Route::get('contracts', [LaborContractApiController::class, 'index']);
        Route::post('contracts', [LaborContractApiController::class, 'store']);
        Route::get('contracts/{id}', [LaborContractApiController::class, 'show']);
        Route::put('contracts/{id}', [LaborContractApiController::class, 'update']);
        Route::post('contracts/{id}/hold', [LaborContractApiController::class, 'putOnHold']);
        Route::post('contracts/{id}/resume', [LaborContractApiController::class, 'resume']);
        Route::post('contracts/{id}/terminate', [LaborContractApiController::class, 'terminate']);
        Route::post('contracts/{id}/sign', [LaborContractApiController::class, 'sign']);

        // Labor Work Logs CRUD
        Route::get('logs/reference-data', [LaborWorkLogApiController::class, 'referenceData']);
        Route::get('logs/dashboard', [LaborWorkLogApiController::class, 'dashboard']);
        Route::get('logs', [LaborWorkLogApiController::class, 'index']);
        Route::post('logs', [LaborWorkLogApiController::class, 'store']);
        Route::get('logs/{id}', [LaborWorkLogApiController::class, 'show']);
        Route::put('logs/{id}', [LaborWorkLogApiController::class, 'update']);
        Route::delete('logs/{id}', [LaborWorkLogApiController::class, 'destroy']);
        Route::get('logs/contract/{contractId}', [LaborWorkLogApiController::class, 'contractLogs']);

        // Labor Inspections CRUD
        Route::get('inspections/reference-data', [LaborInspectionApiController::class, 'referenceData']);
        Route::get('inspections/dashboard', [LaborInspectionApiController::class, 'dashboard']);
        Route::get('inspections', [LaborInspectionApiController::class, 'index']);
        Route::post('inspections', [LaborInspectionApiController::class, 'store']);
        Route::get('inspections/{id}', [LaborInspectionApiController::class, 'show']);
        Route::put('inspections/{id}', [LaborInspectionApiController::class, 'update']);
        Route::delete('inspections/{id}', [LaborInspectionApiController::class, 'destroy']);
        Route::post('inspections/{id}/submit', [LaborInspectionApiController::class, 'submit']);
        Route::get('inspections/contract/{contractId}', [LaborInspectionApiController::class, 'contractInspections']);

        // Labor Payments CRUD
        Route::get('payments/reference-data', [LaborPaymentApiController::class, 'referenceData']);
        Route::get('payments/dashboard', [LaborPaymentApiController::class, 'dashboard']);
        Route::get('payments', [LaborPaymentApiController::class, 'index']);
        Route::post('payments', [LaborPaymentApiController::class, 'store']);
        Route::get('payments/{id}', [LaborPaymentApiController::class, 'show']);
        Route::put('payments/{id}', [LaborPaymentApiController::class, 'update']);
        Route::delete('payments/{id}', [LaborPaymentApiController::class, 'destroy']);
        Route::post('payments/{id}/approve', [LaborPaymentApiController::class, 'approve']);
        Route::post('payments/{id}/process', [LaborPaymentApiController::class, 'processPayment']);
    });

    // Architect Bonus CRUD
    Route::get('architect-bonus/reference-data', [ArchitectBonusApiController::class, 'referenceData']);
    Route::get('architect-bonus', [ArchitectBonusApiController::class, 'index']);
    Route::get('architect-bonus/report', [ArchitectBonusApiController::class, 'report']);
    Route::get('architect-bonus/weights', [ArchitectBonusApiController::class, 'weights']);
    Route::post('architect-bonus', [ArchitectBonusApiController::class, 'store']);
    Route::put('architect-bonus/weights', [ArchitectBonusApiController::class, 'updateWeights']);
    Route::put('architect-bonus/tier/{id}', [ArchitectBonusApiController::class, 'updateTier']);
    Route::get('architect-bonus/{id}', [ArchitectBonusApiController::class, 'show']);
    Route::put('architect-bonus/{id}', [ArchitectBonusApiController::class, 'update']);
    Route::delete('architect-bonus/{id}', [ArchitectBonusApiController::class, 'destroy']);
    Route::post('architect-bonus/{id}/start', [ArchitectBonusApiController::class, 'start']);
    Route::post('architect-bonus/{id}/score', [ArchitectBonusApiController::class, 'score']);
    Route::post('architect-bonus/{id}/paid', [ArchitectBonusApiController::class, 'markPaid']);

    // Provision Tax CRUD
    Route::get('provision-tax/reference-data', [ProvisionTaxApiController::class, 'referenceData']);
    Route::get('provision-tax', [ProvisionTaxApiController::class, 'index']);
    Route::post('provision-tax', [ProvisionTaxApiController::class, 'store']);
    Route::get('provision-tax/{id}', [ProvisionTaxApiController::class, 'show']);
    Route::post('provision-tax/{id}', [ProvisionTaxApiController::class, 'update']);
    Route::put('provision-tax/{id}', [ProvisionTaxApiController::class, 'update']);
    Route::delete('provision-tax/{id}', [ProvisionTaxApiController::class, 'destroy']);

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
        Route::get('reference-data', [ProjectController::class, 'referenceData']);
        Route::get('stats', [ProjectController::class, 'stats']);
        Route::get('/', [ProjectController::class, 'index']);
        Route::post('/', [ProjectController::class, 'store']);
        Route::get('{id}', [ProjectController::class, 'show']);
        Route::post('{id}/submit', [ProjectController::class, 'submit']);
        Route::post('{id}/approve', [ProjectController::class, 'approve']);
        Route::post('{id}/reject', [ProjectController::class, 'reject']);
        Route::post('{id}/return', [ProjectController::class, 'returnForCorrection']);
        Route::post('{id}/discard', [ProjectController::class, 'discard']);
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
        Route::get('reference-data', [ProjectClientController::class, 'referenceData']);
        Route::get('/', [ProjectClientController::class, 'index']);
        Route::post('/', [ProjectClientController::class, 'store']);
        Route::get('{id}', [ProjectClientController::class, 'show']);
        Route::post('{id}/submit', [ProjectClientController::class, 'submit']);
        Route::post('{id}/approve', [ProjectClientController::class, 'approve']);
        Route::post('{id}/reject', [ProjectClientController::class, 'reject']);
        Route::post('{id}/return', [ProjectClientController::class, 'returnForCorrection']);
        Route::post('{id}/discard', [ProjectClientController::class, 'discard']);
        Route::put('{id}', [ProjectClientController::class, 'update']);
        Route::delete('{id}', [ProjectClientController::class, 'destroy']);
    });

    // BOQ Management
    Route::prefix('boqs')->group(function () {
        Route::get('projects', [BoqController::class, 'projects']);
        Route::get('next-version', [BoqController::class, 'nextVersion']);
        Route::get('/', [BoqController::class, 'index']);
        Route::post('/', [BoqController::class, 'store']);
        Route::get('{id}', [BoqController::class, 'show']);
        Route::put('{id}', [BoqController::class, 'update']);
        Route::delete('{id}', [BoqController::class, 'destroy']);
        Route::post('{id}/submit', [BoqController::class, 'submit']);
        Route::post('{id}/approve', [BoqController::class, 'approve']);
        Route::post('{id}/reject', [BoqController::class, 'reject']);
        Route::post('{id}/return', [BoqController::class, 'returnBoq']);
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
        Route::get('movements', [MaterialInventoryApiController::class, 'movements']);
        Route::post('issue', [MaterialInventoryApiController::class, 'issue']);
        Route::post('adjust', [MaterialInventoryApiController::class, 'adjust']);
        Route::post('movements/{id}/verify', [MaterialInventoryApiController::class, 'verifyMovement']);
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
        Route::post('auto-purchases', [VatController::class, 'storeAutoPurchase']);
        Route::get('auto-purchases/{id}', [VatController::class, 'showAutoPurchase']);
        Route::put('auto-purchases/{id}', [VatController::class, 'updateAutoPurchase']);
        Route::delete('auto-purchases/{id}', [VatController::class, 'destroyAutoPurchase']);

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

    // Salary Slips
    Route::prefix('salary-slips')->group(function () {
        Route::get('/', [SalarySlipApiController::class, 'index']);
        Route::get('/payslip', [SalarySlipApiController::class, 'getPayslip']);
        Route::get('/staff/{staffId}/payslips', [SalarySlipApiController::class, 'listStaffPayslips']);
    });

    // Accounting
    Route::get('accounting', [AccountingController::class, 'index']);

    // Procurement
    Route::prefix('procurement')->group(function () {
        Route::get('dashboard', [ProcurementController::class, 'dashboard']);
        Route::get('quotation-comparisons', [ProcurementController::class, 'quotationComparisons']);
        Route::get('quotation-comparisons/{id}', [ProcurementController::class, 'showQuotationComparison']);
        Route::get('pending-deliveries', [PurchaseApiController::class, 'pendingDeliveries']);
        Route::get('receivings', [PurchaseApiController::class, 'receivings']);
        Route::get('receivings/{id}', [PurchaseApiController::class, 'showReceiving']);
        Route::get('supplier-quotations/reference-data', [SupplierQuotationController::class, 'referenceData']);
        Route::get('supplier-quotations', [SupplierQuotationController::class, 'index']);
        Route::post('supplier-quotations', [SupplierQuotationController::class, 'store']);
        Route::get('supplier-quotations/{id}', [SupplierQuotationController::class, 'show']);
        Route::put('supplier-quotations/{id}', [SupplierQuotationController::class, 'update']);
        Route::delete('supplier-quotations/{id}', [SupplierQuotationController::class, 'destroy']);
        Route::get('purchases', [PurchaseApiController::class, 'index']);
        Route::get('purchases/{id}', [PurchaseApiController::class, 'show']);
        Route::post('purchases/{id}/deliveries', [PurchaseApiController::class, 'storeDelivery']);
        Route::get('inspections', [MaterialInspectionController::class, 'index']);
        Route::get('inspections/{id}', [MaterialInspectionController::class, 'show']);
    });

    // ───────────────────────────────────────────────────────────────────
    // Cluster A: Calculators + Design / Site-visit catalog settings
    // ───────────────────────────────────────────────────────────────────
    Route::prefix('currencies')->group(function () {
        Route::get('/', [CurrencyApiController::class, 'index']);
        Route::post('/', [CurrencyApiController::class, 'store']);
        Route::get('{id}', [CurrencyApiController::class, 'show']);
        Route::put('{id}', [CurrencyApiController::class, 'update']);
        Route::delete('{id}', [CurrencyApiController::class, 'destroy']);
    });
    Route::prefix('design-service-packages')->group(function () {
        Route::get('/', [DesignServicePackageApiController::class, 'index']);
        Route::post('/', [DesignServicePackageApiController::class, 'store']);
        Route::get('{id}', [DesignServicePackageApiController::class, 'show']);
        Route::put('{id}', [DesignServicePackageApiController::class, 'update']);
        Route::delete('{id}', [DesignServicePackageApiController::class, 'destroy']);
    });
    Route::prefix('design-service-addons')->group(function () {
        Route::get('/', [DesignServiceAddonApiController::class, 'index']);
        Route::post('/', [DesignServiceAddonApiController::class, 'store']);
        Route::get('{id}', [DesignServiceAddonApiController::class, 'show']);
        Route::put('{id}', [DesignServiceAddonApiController::class, 'update']);
        Route::delete('{id}', [DesignServiceAddonApiController::class, 'destroy']);
    });
    Route::prefix('design-special-structures')->group(function () {
        Route::get('/', [DesignSpecialStructureApiController::class, 'index']);
        Route::post('/', [DesignSpecialStructureApiController::class, 'store']);
        Route::get('{id}', [DesignSpecialStructureApiController::class, 'show']);
        Route::put('{id}', [DesignSpecialStructureApiController::class, 'update']);
        Route::delete('{id}', [DesignSpecialStructureApiController::class, 'destroy']);
    });
    Route::prefix('site-visit-locations')->group(function () {
        Route::get('/', [SiteVisitLocationApiController::class, 'index']);
        Route::post('/', [SiteVisitLocationApiController::class, 'store']);
        Route::get('{id}', [SiteVisitLocationApiController::class, 'show']);
        Route::put('{id}', [SiteVisitLocationApiController::class, 'update']);
        Route::delete('{id}', [SiteVisitLocationApiController::class, 'destroy']);
    });
    Route::prefix('calculators')->group(function () {
        Route::get('design-pricing',           [DesignPricingCalculatorApiController::class, 'index']);
        Route::post('design-pricing/compute',  [DesignPricingCalculatorApiController::class, 'compute']);
        Route::get('site-visit',               [SiteVisitCalculatorApiController::class, 'index']);
        Route::post('site-visit/compute',      [SiteVisitCalculatorApiController::class, 'compute']);
    });

    // Performance / KPI reviews (mirrors web KpiController)
    Route::prefix('performance')->group(function () {
        Route::get('/', [KpiApiController::class, 'index']);
        Route::get('create-info', [KpiApiController::class, 'createInfo']);
        Route::post('/', [KpiApiController::class, 'store']);
        Route::get('{id}', [KpiApiController::class, 'show']);
        Route::patch('{id}/self', [KpiApiController::class, 'updateSelf']);
        Route::post('{id}/submit', [KpiApiController::class, 'submit']);
        Route::post('{id}/recall', [KpiApiController::class, 'recall']);
        Route::patch('{id}/review', [KpiApiController::class, 'updateReviewer']);
    });

    // Field Marketing (mirrors web FieldMarketingController)
    Route::prefix('field-marketing')->group(function () {
        Route::get('reference-data', [FieldMarketingApiController::class, 'referenceData']);
        Route::get('stats', [FieldMarketingApiController::class, 'stats']);
        Route::get('/', [FieldMarketingApiController::class, 'index']);
        Route::post('sessions', [FieldMarketingApiController::class, 'storeSession']);
        Route::get('sessions/{id}', [FieldMarketingApiController::class, 'showSession']);
        Route::put('sessions/{id}', [FieldMarketingApiController::class, 'updateSession']);
        Route::delete('sessions/{id}', [FieldMarketingApiController::class, 'destroySession']);
        Route::post('sessions/{id}/visits', [FieldMarketingApiController::class, 'storeVisit']);
        Route::put('visits/{id}', [FieldMarketingApiController::class, 'updateVisit']);
        Route::delete('visits/{id}', [FieldMarketingApiController::class, 'destroyVisit']);
        Route::post('targets', [FieldMarketingApiController::class, 'storeTarget']);
    });

    // WhatsApp Marketing (mirrors web WhatsAppMarketingController)
    Route::prefix('whatsapp-marketing')->group(function () {
        Route::get('reference-data', [WhatsAppMarketingApiController::class, 'referenceData']);
        Route::get('/', [WhatsAppMarketingApiController::class, 'index']);
        Route::get('campaigns', [WhatsAppMarketingApiController::class, 'indexCampaigns']);
        Route::post('campaigns', [WhatsAppMarketingApiController::class, 'storeCampaign']);
        Route::put('campaigns/{id}', [WhatsAppMarketingApiController::class, 'updateCampaign']);
        Route::patch('campaigns/{id}/close', [WhatsAppMarketingApiController::class, 'closeCampaign']);
        Route::delete('campaigns/{id}', [WhatsAppMarketingApiController::class, 'destroyCampaign']);
        Route::post('contacts', [WhatsAppMarketingApiController::class, 'storeContact']);
        Route::get('contacts/{id}', [WhatsAppMarketingApiController::class, 'showContact']);
        Route::put('contacts/{id}', [WhatsAppMarketingApiController::class, 'updateContact']);
        Route::patch('contacts/{id}/stage', [WhatsAppMarketingApiController::class, 'updateContactStage']);
        Route::delete('contacts/{id}', [WhatsAppMarketingApiController::class, 'destroyContact']);
        Route::get('contacts/{id}/calls', [WhatsAppMarketingApiController::class, 'indexCalls']);
        Route::post('contacts/{id}/calls', [WhatsAppMarketingApiController::class, 'storeCall']);
    });

    // Content Creator (mirrors web ContentCreatorController)
    Route::prefix('content-creator')->group(function () {
        Route::get('reference-data', [ContentCreatorApiController::class, 'referenceData']);
        Route::get('board', [ContentCreatorApiController::class, 'board']);
        Route::get('/', [ContentCreatorApiController::class, 'index']);
        Route::post('tasks', [ContentCreatorApiController::class, 'storeTask']);
        Route::get('tasks/{id}', [ContentCreatorApiController::class, 'showTask']);
        Route::put('tasks/{id}', [ContentCreatorApiController::class, 'updateTask']);
        Route::delete('tasks/{id}', [ContentCreatorApiController::class, 'destroyTask']);
        Route::post('tasks/{id}/progress', [ContentCreatorApiController::class, 'updateProgress']);
        Route::post('tasks/{id}/approve', [ContentCreatorApiController::class, 'approveTask']);
        Route::post('tasks/{id}/comments', [ContentCreatorApiController::class, 'addComment']);
        Route::post('targets', [ContentCreatorApiController::class, 'setTarget']);
    // Engineering Design — Structural Design (mirrors ProjectStructuralDesignController)
    Route::prefix('structural-designs')->group(function () {
        Route::get('reference-data', [StructuralDesignApiController::class, 'referenceData']);
        Route::get('/', [StructuralDesignApiController::class, 'index']);
        Route::post('/', [StructuralDesignApiController::class, 'store']);
        Route::get('{id}', [StructuralDesignApiController::class, 'show']);
        Route::put('{id}', [StructuralDesignApiController::class, 'update']);
        Route::delete('{id}', [StructuralDesignApiController::class, 'destroy']);
        Route::post('{id}/submit', [StructuralDesignApiController::class, 'submit']);
        Route::post('{id}/schedule', [StructuralDesignApiController::class, 'submitSchedule']);
        Route::post('{id}/stages/{stageId}', [StructuralDesignApiController::class, 'updateStage']);
        Route::post('{id}/stages/{stageId}/submit', [StructuralDesignApiController::class, 'submitStage']);
    });

    // Engineering Design — Service Design (mirrors ProjectServiceDesignController)
    Route::prefix('service-designs')->group(function () {
        Route::get('reference-data', [ServiceDesignApiController::class, 'referenceData']);
        Route::get('/', [ServiceDesignApiController::class, 'index']);
        Route::post('/', [ServiceDesignApiController::class, 'store']);
        Route::get('{id}', [ServiceDesignApiController::class, 'show']);
        Route::put('{id}', [ServiceDesignApiController::class, 'update']);
        Route::delete('{id}', [ServiceDesignApiController::class, 'destroy']);
        Route::post('{id}/submit', [ServiceDesignApiController::class, 'submit']);
        Route::post('{id}/schedule', [ServiceDesignApiController::class, 'submitSchedule']);
        Route::post('{id}/stages/{stageId}', [ServiceDesignApiController::class, 'updateStage']);
        Route::post('{id}/stages/{stageId}/submit', [ServiceDesignApiController::class, 'submitStage']);
    });
});
