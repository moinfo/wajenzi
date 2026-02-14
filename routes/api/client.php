<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Client\AuthController;
use App\Http\Controllers\Api\Client\DashboardController;
use App\Http\Controllers\Api\Client\BillingController;
use App\Http\Controllers\Api\Client\ProjectController;

/*
|--------------------------------------------------------------------------
| Client Portal API Routes
|--------------------------------------------------------------------------
|
| REST API for the client portal mobile app / SPA.
| All routes are prefixed with /api/client (set in api.php).
|
*/

// Public routes
Route::post('auth/login', [AuthController::class, 'login']);

// Protected routes (require Sanctum authentication via client-api guard)
Route::middleware('auth:client-api')->group(function () {

    // Auth
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Projects list
    Route::get('projects', [DashboardController::class, 'projects']);

    // Billing (cross-project)
    Route::get('billing', [BillingController::class, 'index']);
    Route::get('billing/{id}/pdf', [BillingController::class, 'pdf']);

    // Project-specific endpoints
    Route::prefix('projects/{project}')->group(function () {
        Route::get('/', [ProjectController::class, 'show']);
        Route::get('boq', [ProjectController::class, 'boq']);
        Route::get('schedule', [ProjectController::class, 'schedule']);
        Route::get('financials', [ProjectController::class, 'financials']);
        Route::get('documents', [ProjectController::class, 'documents']);
        Route::get('reports', [ProjectController::class, 'reports']);
        Route::get('gallery', [ProjectController::class, 'gallery']);
        Route::get('billing/{document}/pdf', [ProjectController::class, 'billingPdf']);
        Route::get('site-visits/{visit}/pdf', [ProjectController::class, 'siteVisitPdf']);
    });
});
