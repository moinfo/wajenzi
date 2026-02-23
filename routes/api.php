<?php

use App\Http\Controllers\InvoiceAdjustmentController;
use App\Http\Controllers\InvoicePaymentController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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

// Mobile App API v1
Route::prefix('v1')->group(function () {
    require __DIR__ . '/api/v1.php';
});

// Client Portal API
Route::prefix('client')->group(function () {
    require __DIR__ . '/api/client.php';
});

// Legacy API routes
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ── Scanner App Auth ─────────────────────────────────────────────
Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    $token = Str::random(64);
    $expiresAt = now()->addHours(24);

    User::where('id', $user->id)->update([
        'api_token' => $token,
        'token_expires_at' => $expiresAt,
    ]);

    return response()->json([
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'designation' => $user->designation ?? '',
            'employee_number' => $user->employee_number ?? '',
        ],
    ]);
});

Route::middleware('apiToken')->get('/validate-token', function (Request $request) {
    $user = auth()->user();
    return response()->json([
        'valid' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'designation' => $user->designation ?? '',
            'employee_number' => $user->employee_number ?? '',
        ],
    ]);
});

Route::middleware('apiToken')->post('/logout', function (Request $request) {
    $user = auth()->user();
    User::where('id', $user->id)->update(['api_token' => null, 'token_expires_at' => null]);
    return response()->json(['message' => 'Logged out successfully']);
});

// ── Protected Scanner/Receipt Routes ─────────────────────────────
Route::middleware('apiToken')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\ApiController::class, 'dashboard'])->name('dashboard');
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
});

Route::post('/post', [App\Http\Controllers\PostController::class, 'store']);
Route::middleware('apiAuth')->group(function() {
    Route::match(['get', 'post'], '/attendance', [App\Http\Controllers\ApiController::class, 'store'])->name('attendance');
});
