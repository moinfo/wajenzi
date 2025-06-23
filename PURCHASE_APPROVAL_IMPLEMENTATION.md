# Purchase Model Approval Implementation

This document provides step-by-step instructions for implementing the RingleSoft approval system for the Purchase model.

## 1. Update the Purchase Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class Purchase extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    // Existing fillable array
    public $fillable = ['id', 'supplier_id','is_expense', 'item_id', 'tax_invoice', 'invoice_date', 'create_by_id', 'total_amount', 'amount_vat_exc', 'vat_amount', 'purchase_type', 'file', 'date', 'status'];

    // Add the onApprovalCompleted method required by the ApprovableModel interface
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        $this->status = 'COMPLETED';
        $this->updated_at = now();
        $this->save();
        return true;
    }

    // Existing relationships and methods...
    
    // Optional: Add a method to determine if a purchase should bypass approval
    // public function bypassApprovalProcess(): bool
    // {
    //     return $this->total_amount < 1000; // Example: small purchases bypass approval
    // }
    
    // Existing methods...
}
```

## 2. Update the PurchaseController

```php
<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Item;
use App\Services\ApprovalService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request) 
    {
        // Handle existing CRUD operations
        if($this->handleCrud($request, 'Purchase')) {
            return back();
        }

        // Fetch purchases
        $purchases = Purchase::with(['supplier', 'item', 'user'])->get();
        
        // Fetch suppliers and items for form
        $suppliers = Supplier::all();
        $items = Item::all();
        
        // Set the document type ID for approvals
        $approval_document_type_id = 11; // Use appropriate ID for purchases
        
        return view('pages.purchases.purchases')->with([
            'purchases' => $purchases,
            'suppliers' => $suppliers,
            'items' => $items,
            'approval_document_type_id' => $approval_document_type_id
        ]);
    }

    public function purchase($id, $document_type_id)
    {
        // Find the purchase
        $purchase = Purchase::with(['supplier', 'item', 'user'])->findOrFail($id);
        
        // Mark notification as read if this is from a notification
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'purchase');
                
        return view('pages.purchases.purchase')->with([
            'purchase' => $purchase,
            'approval_document_type_id' => $document_type_id
        ]);
    }

    // Add approval-specific methods
    public function submit(Purchase $purchase)
    {
        $purchase->submit();
        return redirect()->back()->with('success', 'Purchase submitted for approval');
    }

    public function approve(Purchase $purchase)
    {
        $purchase->approve();
        return redirect()->back()->with('success', 'Purchase approved');
    }

    public function reject(Purchase $purchase, Request $request)
    {
        $purchase->reject($request->reason);
        return redirect()->back()->with('success', 'Purchase rejected');
    }

    // Existing methods...
}
```

## 3. Update the Purchases List View

Open `resources/views/pages/purchases/purchases.blade.php` and modify it to include the approval status:

```blade
@extends('layouts.backend')

@section('css')
<style>
    .summary-stats {
        display: inline-flex !important;
        border: 1px solid #e9ecef;
        border-radius: 5px;
        overflow: hidden;
    }
    .summary-stats .badge {
        border-radius: 0;
        margin: 0;
        font-size: 0.8rem;
        padding: 0.3rem 0.5rem;
    }
    .summary-stats .badge:hover {
        filter: brightness(0.9);
    }
</style>
@endsection

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">Purchases
                <div class="float-right">
                    @can('Create Purchase')
                        <button type="button" onclick="loadFormModal('purchase_form', {className: 'Purchase'}, 'Create New Purchase', 'modal-md');" class="btn btn-rounded btn-outline-primary min-width-125 mb-10"><i class="fas fa-plus">&nbsp;</i>New Purchase</button>
                    @endcan
                </div>
            </div>
            <div>
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">All Purchases</h3>
                    </div>
                    <div class="block-content">
                        <!-- Filter/search form if any -->
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                                <thead>
                                <tr>
                                    <th class="text-center" style="width: 50px;">#</th>
                                    <th>Supplier</th>
                                    <th>Item</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th class="text-center">Approvals</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center" style="width: 100px;">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($purchases as $purchase)
                                    <tr id="purchase-tr-{{$purchase->id}}">
                                        <td class="text-center">{{$loop->index + 1}}</td>
                                        <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                                        <td>{{ $purchase->item->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($purchase->total_amount, 2) }}</td>
                                        <td>{{ $purchase->date }}</td>
                                        <td class="text-center">
                                            <!-- Approval status summary component -->
                                            <x-ringlesoft-approval-status-summary :model="$purchase" />
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $approvalStatus = $purchase->approvalStatus?->status ?? 'PENDING';
                                                $statusClass = [
                                                    'PENDING' => 'warning',
                                                    'SUBMITTED' => 'info',
                                                    'APPROVED' => 'success',
                                                    'REJECTED' => 'danger',
                                                    'PAID' => 'primary',
                                                    'COMPLETED' => 'success',
                                                    'DISCARDED' => 'danger',
                                                ][$approvalStatus] ?? 'secondary';
                                                
                                                $statusIcon = [
                                                    'PENDING' => '<i class="fas fa-clock"></i>',
                                                    'SUBMITTED' => '<i class="fas fa-paper-plane"></i>',
                                                    'APPROVED' => '<i class="fas fa-check"></i>',
                                                    'REJECTED' => '<i class="fas fa-times"></i>',
                                                    'PAID' => '<i class="fas fa-money-bill"></i>',
                                                    'COMPLETED' => '<i class="fas fa-check-circle"></i>',
                                                    'DISCARDED' => '<i class="fas fa-trash"></i>',
                                                ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
                                            @endphp
                                            <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
                                                {!! $statusIcon !!} {{ $approvalStatus }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <a class="btn btn-sm btn-success js-tooltip-enabled"
                                                   href="{{ route('purchase', [$purchase->id, $approval_document_type_id]) }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @can('Edit Purchase')
                                                    <button type="button"
                                                            onclick="loadFormModal('purchase_form', {className: 'Purchase', id: {{$purchase->id}}}, 'Edit Purchase')"
                                                            class="btn btn-sm btn-primary">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                @endcan
                                                @can('Delete Purchase')
                                                    <button type="button"
                                                            onclick="deleteModelItem('Purchase', {{$purchase->id}}, 'purchase-tr-{{$purchase->id}}');"
                                                            class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 4. Update the Purchase Detail View

Create or modify `resources/views/pages/purchases/purchase.blade.php`:

```blade
@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">
                Purchase Details: {{ $purchase->tax_invoice }}
                <div class="float-right">
                    <a href="{{ route('purchases') }}" class="btn btn-rounded btn-outline-secondary min-width-125 mb-10">
                        <i class="fas fa-arrow-left"></i> Back to Purchases
                    </a>
                </div>
            </div>

            <!-- Purchase Details Card -->
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Purchase Information</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Supplier</th>
                                        <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Item</th>
                                        <td>{{ $purchase->item->name ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Tax Invoice</th>
                                        <td>{{ $purchase->tax_invoice }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Amount (Excl. VAT)</th>
                                        <td>{{ number_format($purchase->amount_vat_exc, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>VAT Amount</th>
                                        <td>{{ number_format($purchase->vat_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Total Amount</th>
                                        <td>{{ number_format($purchase->total_amount, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Date</th>
                                        <td>{{ $purchase->date }}</td>
                                    </tr>
                                    <tr>
                                        <th>Invoice Date</th>
                                        <td>{{ $purchase->invoice_date }}</td>
                                    </tr>
                                    <tr>
                                        <th>Purchase Type</th>
                                        <td>{{ $purchase->purchase_type == 1 ? 'VAT' : 'Exempt' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th style="width: 30%;">Is Expense</th>
                                        <td>{{ $purchase->is_expense ? 'Yes' : 'No' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $purchase->user->name ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($purchase->file)
                    <div class="row mt-4">
                        <div class="col-12">
                            <a href="{{ url($purchase->file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-file-pdf"></i> View Document
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Approval Component -->
            <x-ringlesoft-approval-actions :model="$purchase" />
            
        </div>
    </div>
@endsection
```

## 5. Add Routes for Purchase Approval

In `routes/web.php` find the existing purchase routes and add/update them:

```php
// Purchase Routes
Route::match(['get', 'post'], '/purchases', [App\Http\Controllers\PurchaseController::class, 'index'])->name('purchases');
Route::match(['get', 'post'], '/purchases/{id}/{document_type_id}', [App\Http\Controllers\PurchaseController::class, 'purchase'])->name('purchase');

// Purchase Approval Routes
Route::post('/purchases/{purchase}/submit', [App\Http\Controllers\PurchaseController::class, 'submit'])->name('purchase.submit');
Route::post('/purchases/{purchase}/approve', [App\Http\Controllers\PurchaseController::class, 'approve'])->name('purchase.approve');
Route::post('/purchases/{purchase}/reject', [App\Http\Controllers\PurchaseController::class, 'reject'])->name('purchase.reject');
```

## 6. Set Up Approval Flow for Purchases

Run the following artisan commands to set up an approval flow for purchases:

```bash
# Create approval flow for Purchase model
php artisan process-approval:flow add Purchase

# Add steps to the approval flow (this will prompt for roles)
php artisan process-approval:step add
```

## 7. Update Event Listeners

Ensure you have the necessary event listeners in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    // Existing listeners...
    
    \RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent::class => [
        \App\Listeners\ProcessSubmittedListener::class,
    ],
    \RingleSoft\LaravelProcessApproval\Events\ApprovalNotificationEvent::class => [
        \App\Listeners\ApprovalNotificationListener::class,
    ],
];
```

## 8. Testing the Implementation

1. Create a new purchase record
2. Submit it for approval 
3. Check that it appears in the approvals list
4. Approve/reject the purchase to test the workflow