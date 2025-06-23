<?php

use App\Http\Controllers\InvoiceAdjustmentController;
use App\Http\Controllers\InvoicePaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/post', 'PostController@store');
Route::middleware('apiAuth')->group(function() {
    Route::match(['get', 'post'], '/attendance', [App\Http\Controllers\ApiController::class, 'store'])->name('attendance');
});

Route::match(['get', 'post'], '/add_receipt', [App\Http\Controllers\ReceiptController::class, 'store'])->name('add_receipt');
Route::match(['get', 'post'], '/add_receipt_item', [App\Http\Controllers\ReceiptItemController::class, 'store'])->name('add_receipt_item');
Route::match(['get', 'post'], '/receipts/{id?}', [App\Http\Controllers\ApiController::class, 'receipts'])->name('receipts');
Route::match(['get', 'post'], '/receipt_items/{id?}', [App\Http\Controllers\ApiController::class, 'receipt_items'])->name('receipt_items');
Route::match(['get', 'post'], '/employees/{id?}', [App\Http\Controllers\ApiController::class, 'employees'])->name('employees');
// Invoice Adjustments
Route::post('/adjustments', [InvoiceAdjustmentController::class, 'store']);
Route::delete('/adjustments/{adjustment}', [InvoiceAdjustmentController::class, 'destroy']);

// Invoice Payments
Route::post('/payments', [InvoicePaymentController::class, 'store']);
Route::delete('/payments/{payment}', [InvoicePaymentController::class, 'destroy']);
