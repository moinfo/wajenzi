# Implementing Approval Functionality in Models

This document provides a blueprint for implementing approval functionality in any model that requires an approval workflow using the RingleSoft Laravel Process Approval package.

## Step 1: Modify the Model

To add approval functionality to a model (e.g., ExpenseRequest, Purchase, Sale, etc.), follow these steps:

```php
// File: app/Models/YourModel.php

use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\ProcessApproval;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class YourModel extends Model implements ApprovableModel
{
    // Apply the Approvable trait
    use HasFactory, Approvable;
    
    // ... your existing model code ...
    
    /**
     * Logic executed when the approval process is completed.
     *
     * @param ProcessApproval $approval The approval object
     * @return bool Whether the approval completion logic succeeded
     */
    public function onApprovalCompleted(ProcessApproval|\RingleSoft\LaravelProcessApproval\Models\ProcessApproval $approval): bool
    {
        // Update the model's status or perform any other actions needed when the approval process completes
        $this->status = 'COMPLETED';  // Use your own status values
        $this->updated_at = now();
        $this->save();
        return true;
    }
    
    // Optional: Enable auto-submission upon creation
    // public function enableAutoSubmit(): bool
    // {
    //     return true;
    // }
    
    // Optional: Bypass approval process in certain conditions
    // public function bypassApprovalProcess(): bool
    // {
    //     return $this->amount < 1000;  // Example: bypassing for small amounts
    // }
}
```

## Step 2: Create/Update Controller Methods

Update your controller to handle approval operations:

```php
// File: app/Http/Controllers/YourModelController.php

class YourModelController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    
    // Show index page with list of models
    public function index(Request $request)
    {
        // Your existing logic
        $items = YourModel::all();
        $documentTypeId = 10; // Use appropriate document type ID
        
        return view('path.to.index.view')->with([
            'items' => $items,
            'approval_document_type_id' => $documentTypeId
        ]);
    }
    
    // Show details of a single model with approval info
    public function show($id, $document_type_id)
    {
        $item = YourModel::findOrFail($id);
        
        // Mark notification as read if this came from a notification
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'your_route_name');
        
        return view('path.to.show.view')->with([
            'item' => $item,
        ]);
    }
    
    // Submit a model for approval
    public function submit(YourModel $model)
    {
        $model->submit();
        return redirect()->back()->with('success', 'Request submitted for approval');
    }
    
    // Approve a model
    public function approve(YourModel $model)
    {
        $model->approve();
        return redirect()->back()->with('success', 'Request approved');
    }
    
    // Reject a model
    public function reject(YourModel $model, Request $request)
    {
        $model->reject($request->reason);
        return redirect()->back()->with('success', 'Request rejected');
    }
}
```

## Step 3: Update the Index View

Add approval status indicators to the list view:

```blade
{{-- File: resources/views/path/to/index.view.blade.php --}}

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Name/Title</th>
            {{-- Other model fields --}}
            <th class="text-center">Approvals</th>
            <th class="text-center">Status</th>
            <th class="text-center">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->name }}</td>
                {{-- Other model fields --}}
                <td class="text-center">
                    <x-ringlesoft-approval-status-summary :model="$item" />
                </td>
                <td class="text-center">
                    @php
                        $approvalStatus = $item->approvalStatus?->status ?? 'PENDING';
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
                        <a class="btn btn-sm btn-success" href="{{ route('your_route.show', [$item->id, $approval_document_type_id]) }}">
                            <i class="fas fa-eye"></i>
                        </a>
                        {{-- Edit and delete buttons --}}
                    </div>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
```

## Step 4: Create/Update the Detail View

Add the approval component to the detail view:

```blade
{{-- File: resources/views/path/to/show.view.blade.php --}}

@extends('layouts.backend')

@section('content')
    <div class="main-container">
        <div class="content">
            <div class="content-heading">
                {{ $modelName }} Details: {{ $item->name }}
                <div class="float-right">
                    <a href="{{ route('your_route.index') }}" class="btn btn-rounded btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>

            <!-- Item Details Card -->
            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Information</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        {{-- Display your model fields in a nice format --}}
                    </div>
                </div>
            </div>

            <!-- Approval Component -->
            <x-ringlesoft-approval-actions :model="$item" />
            
        </div>
    </div>
@endsection
```

## Step 5: Add Routes

Add the necessary routes for handling approvals:

```php
// File: routes/web.php

// Main resource routes
Route::match(['get', 'post'], '/your_models', [App\Http\Controllers\YourModelController::class, 'index'])->name('your_route.index');
Route::match(['get', 'post'], '/your_models/{id}/{document_type_id}', [App\Http\Controllers\YourModelController::class, 'show'])->name('your_route.show');

// Approval action routes
Route::post('/your_models/{your_model}/submit', [App\Http\Controllers\YourModelController::class, 'submit'])->name('your_route.submit');
Route::post('/your_models/{your_model}/approve', [App\Http\Controllers\YourModelController::class, 'approve'])->name('your_route.approve');
Route::post('/your_models/{your_model}/reject', [App\Http\Controllers\YourModelController::class, 'reject'])->name('your_route.reject');
```

## Step 6: Set Up Approval Flow

Use the artisan commands to set up approval flow for your model:

```bash
# Create a new approval flow
php artisan process-approval:flow add YourModelName

# Add steps to the approval flow
php artisan process-approval:step add
```

## Step 7: Set Up Notifications

To notify approvers when a document is ready for their approval:

1. Make sure you have the approval notification listeners set up in `EventServiceProvider.php`:

```php
protected $listen = [
    \RingleSoft\LaravelProcessApproval\Events\ProcessSubmittedEvent::class => [
        \App\Listeners\ProcessSubmittedListener::class,
    ],
    \RingleSoft\LaravelProcessApproval\Events\ApprovalNotificationEvent::class => [
        \App\Listeners\ApprovalNotificationListener::class,
    ],
    // Other events...
];
```

2. Create/update the listener to send notifications:

```php
// File: app/Listeners/ProcessSubmittedListener.php

public function handle(ProcessSubmittedEvent $event): void
{
    $nextApprovers = $event->approvable->getNextApprovers();
    foreach ($nextApprovers as $nextApprover) {
        $nextApprover->notify(new AwaitingApprovalNotification($event->approvable));
    }
}
```

## Recommended Models to Add Approval To

Consider implementing approval workflow for these common models:

1. Expense requests
2. Purchase orders
3. Invoices
4. Leave requests
5. Budget allocations
6. Payment requests
7. Contract approvals
8. Procurement requests
9. Reimbursement claims
10. HR documents (hiring, promotions, etc.)

## Notes on Testing

When implementing approval functionality:

1. Test the submission process
2. Test the approval process
3. Test the rejection process
4. Test bypassing logic (if implemented)
5. Test notifications to approvers
6. Test the completion callback logic