# CRUD Implementation Guide - Commission Target Pattern

This document provides a comprehensive guide on how CRUD operations are implemented in this Laravel financial analysis system, using the `commission_target` module as the reference example.

## Table of Contents

- [Overview](#overview)
- [Database Structure](#database-structure)
- [Model Implementation](#model-implementation)
- [Controller Implementation](#controller-implementation)
- [Routes Configuration](#routes-configuration)
- [View Implementation](#view-implementation)
- [Form Implementation](#form-implementation)
- [AJAX & Modal Implementation](#ajax--modal-implementation)
- [CRUD Implementation Checklist](#crud-implementation-checklist)
- [Example Implementation](#example-implementation)

## Overview

The application uses a standardized CRUD pattern that combines:
- **Single Controller Method** handling both GET and POST requests
- **Universal handleCrud()** method in base Controller
- **Modal-based Forms** with AJAX operations
- **Permission-based Access Control**
- **DataTable Integration** for listing

## Database Structure

### Migration Example
```php
// File: database/migrations/2022_08_11_113936_create_commission_targets_table.php

Schema::create('commission_targets', function (Blueprint $table) {
    $table->id();
    $table->integer('person_id');              // Foreign key to customer
    $table->integer('commission');             // Commission amount
    $table->integer('target');                 // Sales target
    $table->date('date');                     // Effective date
    $table->integer('manager_commission');     // Manager commission
    $table->integer('manager_target');        // Manager target
    $table->enum('level',['I','II','III'])->default('I'); // Commission level
    $table->timestamps();
});
```

### Level Enhancement Migration
```php
// File: database/migrations/2025_04_26_143125_add_level_to_commission_targets.php

Schema::table('commission_targets', function (Blueprint $table) {
    $table->enum('level',['I','II'])->default('I');
});
```

## Model Implementation

### Basic Model Structure
```php
// File: app/Models/CommissionTarget.php

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommissionTarget extends Model
{
    use HasFactory;

    // Define fillable fields for mass assignment
    public $fillable = [
        'person_id',
        'commission', 
        'target',
        'date',
        'manager_commission',
        'manager_target',
        'level'
    ];

    /**
     * Get customer commission with target by date range and level
     * @param string $start_date
     * @param string $end_date  
     * @param int $person_id
     * @param string $level
     * @return CommissionTarget|null
     */
    public static function getCustomerCommissionWithTarget($start_date, $end_date, $person_id, $level)
    {
        return CommissionTarget::whereBetween('date', [$start_date, $end_date])
            ->where('person_id', $person_id)
            ->where('level', $level)
            ->orderBy('date', 'desc')
            ->first();
    }

    /**
     * Relationship to customer
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function people()
    {
        return $this->belongsTo(BongePeople::class, 'person_id', 'person_id');
    }
}
```

## Controller Implementation

### Settings Controller Method
```php
// File: app/Http/Controllers/SettingsController.php

/**
 * Handle Commission Target CRUD operations
 * @param Request $request
 * @return \Illuminate\Http\Response
 */
public function commission_target(Request $request)
{
    // Handle CRUD operations using base controller method
    if($this->handleCrud($request, 'CommissionTarget')) {
        return back();
    }
    
    // Prepare data for view
    $data = [
        'commissions' => CommissionTarget::all()
    ];
    
    return view('pages.settings.settings_commission_target')->with($data);
}
```

### Base Controller CRUD Handler
```php
// File: app/Http/Controllers/Controller.php

/**
 * Universal CRUD handler for all models
 * @param Request $request
 * @param string $class_name - Model class name
 * @param int|null $id - Optional ID for specific operations
 * @return bool
 */
public function handleCrud(Request $request, $class_name, $id = null) 
{
    if($request->isMethod('POST') || $request->isMethod('PUT')) {
        if($request->has('addItem')) {
            if($this->crudAdd($request, $class_name)) {
                $this->notify($class_name .' Added Successfully', 'Added!', 'success');
            } else {
                $this->notify('Failed to Add '.$class_name, 'Failed', 'error');
            }
            return true;
        } else if($request->has('updateItem')) {
            if($this->crudUpdate($request, $class_name, $id)) {
                $this->notify($class_name .' Updated Successfully', 'Updated!', 'success');
            } else {
                $this->notify('Failed to Update '.$class_name , 'Failed', 'error');
            }
            return true;
        }
    } else if($request->isMethod('DELETE')) {
        if($request->has('deleteItem')) {
            if($this->crudDelete($request, $class_name)) {
                $this->notify($class_name .' Deleted Successfully', 'Deleted!', 'success');
            } else {
                $this->notify('Failed to Delete '.$class_name, 'Failed', 'error');
            }
            return true;
        }
    }
    return false;
}

/**
 * Create new model instance
 */
private function crudAdd(Request $request, $class_name) 
{
    $full_class_name = '\App\Models\\'. $class_name;
    $newObj = new $full_class_name();
    
    // Process monetary values
    $request->request->add([
        'amount' => Utility::strip_commas($request->input('amount')),
        'commission' => Utility::strip_commas($request->input('commission')),
        'target' => Utility::strip_commas($request->input('target')),
    ]);
    
    // Filter empty values and fill model
    $filteredData = array_filter($request->all(), function($value) {
        return $value !== '';
    });
    
    $newObj->fill($filteredData);
    return $newObj->save() ? $newObj : false;
}

/**
 * Update existing model instance
 */
private function crudUpdate(Request $request, $class_name, $id = null)
{
    $full_class_name = '\App\Models\\'. $class_name;
    $obj_id = $request->input('id') ?? $id;
    $obj = $full_class_name::find($obj_id);
    
    // Process monetary values
    $request->request->add([
        'amount' => Utility::strip_commas($request->input('amount')),
        'commission' => Utility::strip_commas($request->input('commission')),
        'target' => Utility::strip_commas($request->input('target')),
    ]);
    
    $obj->fill($request->all());
    return $obj->save();
}
```

## Routes Configuration

### Route Definition
```php
// File: routes/web.php

Route::middleware(['auth'])->group(function () {
    // Commission Target CRUD route
    Route::match(['get', 'post'], '/settings/commission_target', 
        [App\Http\Controllers\SettingsController::class, 'commission_target'])
        ->name('hr_settings_commission_target');
});
```

## View Implementation

### Main View Template
```php
// File: resources/views/pages/settings/settings_commission_target.blade.php

@extends('layouts.backend')

@section('content')
    @php
        // Permission-based filtering for managers
        $logged_in_manager = \App\Models\Manager::where('user_id', auth()->id())->first();
        
        if ($logged_in_manager) {
            $manager_customer_ids = \App\Models\ManagerCustomer::where('manager_id', $logged_in_manager->id)
                ->pluck('person_id')->toArray();
            $commissions = $commissions->whereIn('person_id', $manager_customer_ids);
        }
    @endphp

    <div class="main-container">
        <div class="content">
            <div class="content-heading">
                Settings
                <div class="block-header text-center">
                    <h3 class="block-title">Commission Target</h3>
                </div>
                <div class="float-right">
                    @can('Add Commission Target')
                        <button type="button" 
                                onclick="loadFormModal('settings_commission_target_form', {
                                    className: 'CommissionTarget',
                                    managerId: '{{ $logged_in_manager ? $logged_in_manager->id : '' }}'
                                }, 'Create New Commission Target', 'modal-md');"
                                class="btn btn-rounded btn-outline-primary min-width-125 mb-10">
                            <i class="si si-plus">&nbsp;</i>New Target
                        </button>
                    @endcan
                </div>
            </div>
            
            <div class="block">
                @include('components.headed_paper_settings')
                <div class="block-content">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Target</th>
                                <th>Commission</th>
                                <th>Manager Target</th>
                                <th>Manager Commission</th>
                                <th>Level</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($commissions as $commission_target)
                                <tr id="commission_target-tr-{{$commission_target->id}}">
                                    <td class="text-center">{{$loop->iteration}}</td>
                                    <td class="text-center">{{$commission_target->date}}</td>
                                    <td class="font-w600">
                                        {{ ($commission_target->people->first_name ?? '') .' '. ($commission_target->people->last_name ?? '') }}
                                    </td>
                                    <td class="text-right">{{ number_format($commission_target->target) }}</td>
                                    <td class="text-right">{{ number_format($commission_target->commission) }}</td>
                                    <td class="text-right">{{ number_format($commission_target->manager_target) }}</td>
                                    <td class="text-right">{{ number_format($commission_target->manager_commission) }}</td>
                                    <td class="text-center">{{ $commission_target->level }}</td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            @can('Edit Commission Target')
                                                <button type="button" 
                                                        onclick="loadFormModal('settings_commission_target_form', {
                                                            className: 'CommissionTarget',
                                                            id: {{$commission_target->id}},
                                                            managerId: '{{ $logged_in_manager ? $logged_in_manager->id : '' }}'
                                                        }, 'Edit Commission Target', 'modal-md');"
                                                        class="btn btn-sm btn-primary js-tooltip-enabled" 
                                                        data-toggle="tooltip" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </button>
                                            @endcan

                                            @can('Delete Commission Target')
                                                <button type="button" 
                                                        onclick="deleteModelItem('CommissionTarget', {{$commission_target->id}}, 'commission_target-tr-{{$commission_target->id}}');"
                                                        class="btn btn-sm btn-danger js-tooltip-enabled" 
                                                        data-toggle="tooltip" title="Delete">
                                                    <i class="fa fa-times"></i>
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
@endsection
```

## Form Implementation

### Modal Form Template
```php
// File: resources/views/forms/settings_commission_target_form.blade.php

@php
    // Permission-based customer filtering
    $logged_in_manager = \App\Models\Manager::where('user_id', auth()->id())->first();
    $is_admin = !$logged_in_manager;
    
    if ($logged_in_manager) {
        $manager_customer_ids = \App\Models\ManagerCustomer::where('manager_id', $logged_in_manager->id)
            ->pluck('person_id')->toArray();
        $bonge_customers = $bonge_customers->whereIn('person_id', $manager_customer_ids);
    }
@endphp

<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        
        <!-- Customer Selection -->
        <div class="form-group">
            <label for="person_id" class="control-label">Customer</label>
            <select name="person_id" class="form-control" {{ !$is_admin ? 'disabled' : 'required' }}>
                <option value="">Select Customer</option>
                @foreach ($bonge_customers as $bonge_customer)
                    @php
                        $customer_name = ($bonge_customer->people->first_name ?? '') . ' ' . ($bonge_customer->people->last_name ?? '');
                        $is_selected = ($bonge_customer->person_id == ($object->person_id ?? ''));
                    @endphp
                    <option value="{{ $bonge_customer->person_id }}" {{ $is_selected ? 'selected' : '' }}>
                        {{ trim($customer_name) }}
                    </option>
                @endforeach
            </select>
            @if(!$is_admin)
                <input type="hidden" name="person_id" value="{{ $object->person_id ?? '' }}">
            @endif
        </div>

        <!-- Target Amount -->
        <div class="form-group">
            <label for="target">Target</label>
            <input type="number" class="form-control" name="target"
                   value="{{ $object->target ?? '' }}" {{ !$is_admin ? 'readonly' : 'required' }}>
            @if(!$is_admin)
                <input type="hidden" name="target" value="{{ $object->target ?? '' }}">
            @endif
        </div>

        <!-- Customer Commission -->
        <div class="form-group">
            <label for="commission">Customer Commission</label>
            <input type="number" class="form-control" name="commission"
                   value="{{ $object->commission ?? '' }}" {{ !$is_admin ? 'readonly' : 'required' }}>
            @if(!$is_admin)
                <input type="hidden" name="commission" value="{{ $object->commission ?? '' }}">
            @endif
        </div>

        <!-- Manager Commission -->
        <div class="form-group">
            <label for="manager_commission">Manager Commission</label>
            <input type="number" class="form-control" name="manager_commission"
                   value="{{ $object->manager_commission ?? '' }}" {{ !$is_admin ? 'readonly' : 'required' }}>
            @if(!$is_admin)
                <input type="hidden" name="manager_commission" value="{{ $object->manager_commission ?? '' }}">
            @endif
        </div>

        <!-- Manager Target -->
        <div class="form-group">
            <label for="manager_target">Manager Target</label>
            <input type="number" class="form-control" name="manager_target"
                   value="{{ $object->manager_target ?? '' }}" required>
        </div>

        <!-- Level Selection -->
        <div class="form-group">
            <label for="level" class="control-label required">Level</label>
            <select name="level" class="form-control" required>
                <option value="">Select level</option>
                @foreach ($levels as $level)
                    <option value="{{ $level['name'] }}" {{ ($level['name'] == ($object->level ?? '')) ? 'selected' : '' }}>
                        {{ $level['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Effective Date -->
        <div class="form-group">
            <label for="date">Date</label>
            <input type="text" class="form-control datepicker" name="date"
                   value="{{ $object->date ?? '' }}" {{ !$is_admin ? 'readonly' : 'required' }}>
            @if(!$is_admin)
                <input type="hidden" name="date" value="{{ $object->date ?? '' }}">
            @endif
        </div>

        <!-- Submit Buttons -->
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem">
                    <i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="CommissionTarget">
                    Submit
                </button>
            @endif
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        // Initialize datepicker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd'
        });

        // Format number inputs with thousand separators
        $("input[type='number']").each((i, ele) => {
            let clone = $(ele).clone(false);
            clone.attr("type", "text");
            let ele1 = $(ele);
            clone.val(Number(ele1.val()).toLocaleString("en"));
            $(ele).after(clone);
            $(ele).hide();

            clone.mouseenter(() => {
                ele1.show();
                clone.hide();
            });

            $(ele).mouseleave(() => {
                clone.show();
                ele1.hide();
            });
        });
    });
</script>
```

## AJAX & Modal Implementation

### Modal Functions
```javascript
// File: resources/views/layouts/backend.blade.php

/**
 * Load form in modal
 * @param {string} form_name - Form template name
 * @param {object} params - Parameters to pass to form
 * @param {string} title - Modal title
 * @param {string} modal_size - Modal size class (modal-sm, modal-md, modal-lg)
 */
function loadFormModal(form_name, params = null, title, modal_size = 'modal-md') {
    Utility.ajaxLoadForm(form_name, params, '#ajax-loader-modal-content', function(res){
        if(res !== true) {
            console.log('FormModal Error', res);
        } else {
            $("#ajax-loader-modal #ajax-loader-modal-title").html(title);
            $("#ajax-loader-modal .modal-dialog").removeClass('modal-lg modal-md');
            $("#ajax-loader-modal .modal-dialog").addClass(modal_size);
            $("#ajax-loader-modal input[name='_token']").val(csrf_token);
            $("#ajax-loader-modal").modal('show');
        }
    });
}

/**
 * Delete model item with confirmation
 * @param {string} className - Model class name
 * @param {int} id - Record ID
 * @param {string} row_id - Table row ID for removal
 */
function deleteModelItem(className, id, row_id) {
    Utility.swalConfirm(
        'Are you sure you want to delete this ' + className + '?', 
        'Delete ' + className, 
        {type: 'question'}, 
        function(res) {
            if(res) {
                Utility.deleteModelObject(className, id, function(result) {
                    if(result) {
                        Swal.fire('Deleted!', className + ' deleted successfully', 'success');
                        $("#" + row_id).hide();
                    } else {
                        Swal.fire('Error!', 'Failed to delete ' + className, 'error');
                    }
                }, function(err) {
                    Utility.swal('Error', 'Something went wrong!', 'error');
                }, false);
            }
        }
    );
}
```

### Usage Examples
```javascript
// Create new record
loadFormModal('settings_commission_target_form', {
    className: 'CommissionTarget',
    managerId: '123'
}, 'Create New Commission Target', 'modal-md');

// Edit existing record
loadFormModal('settings_commission_target_form', {
    className: 'CommissionTarget',
    id: 456,
    managerId: '123'
}, 'Edit Commission Target', 'modal-md');

// Delete record
deleteModelItem('CommissionTarget', 456, 'commission_target-tr-456');
```

## CRUD Implementation Checklist

When implementing a new CRUD module, follow this checklist:

### ✅ Database & Model
- [ ] Create migration file with proper table structure
- [ ] Define `$fillable` array in model
- [ ] Add relationships (belongsTo, hasMany, etc.)
- [ ] Create custom query methods if needed
- [ ] Add validation rules

### ✅ Controller
- [ ] Add model import to SettingsController
- [ ] Create controller method following naming pattern
- [ ] Use `handleCrud()` for CRUD operations
- [ ] Pass data to view with proper variable names
- [ ] Add method to settings menu array

### ✅ Routes
- [ ] Add route with `match(['get', 'post'])` pattern
- [ ] Use descriptive route name with `hr_settings_` prefix
- [ ] Group under authentication middleware

### ✅ Views
- [ ] Create main view in `pages/settings/` directory
- [ ] Implement permission-based filtering
- [ ] Add DataTable integration
- [ ] Include action buttons with permissions
- [ ] Add "New" button with proper modal call

### ✅ Forms
- [ ] Create form in `forms/` directory
- [ ] Implement permission-based field restrictions
- [ ] Add proper validation and error handling
- [ ] Include JavaScript for datepickers, formatting
- [ ] Handle create vs edit mode properly

### ✅ Permissions
- [ ] Create permissions in database
- [ ] Add permission checks in views (`@can()`)
- [ ] Assign permissions to appropriate roles
- [ ] Test manager vs admin access levels

### ✅ Navigation
- [ ] Add menu item to settings array
- [ ] Create menu seeder if needed
- [ ] Test navigation accessibility

## Example Implementation

Here's a quick example of implementing a new "Item Target" CRUD module:

### 1. Migration
```php
// database/migrations/create_item_targets_table.php
Schema::create('item_targets', function (Blueprint $table) {
    $table->id();
    $table->integer('item_id');
    $table->integer('target_quantity');
    $table->decimal('target_amount', 15, 2);
    $table->date('date');
    $table->enum('level', ['I', 'II', 'III'])->default('I');
    $table->timestamps();
});
```

### 2. Model
```php
// app/Models/ItemTarget.php
class ItemTarget extends Model {
    public $fillable = ['item_id', 'target_quantity', 'target_amount', 'date', 'level'];
    
    public function item() {
        return $this->belongsTo(Item::class);
    }
}
```

### 3. Controller Method
```php
// Add to SettingsController.php
public function item_targets(Request $request) {
    if($this->handleCrud($request, 'ItemTarget')) {
        return back();
    }
    $data = [
        'item_targets' => ItemTarget::all(),
        'items' => Item::all(),
        'levels' => [
            ['name' => 'I'], ['name' => 'II'], ['name' => 'III']
        ]
    ];
    return view('pages.settings.settings_item_targets')->with($data);
}
```

### 4. Route
```php
// routes/web.php
Route::match(['get', 'post'], '/settings/item_targets', 
[SettingsController::class, 'item_targets'])->name('hr_settings_item_targets');
```

### 5. Add to Settings Menu
```php
// In SettingsController index() method, add to $settings_menus array:
['name'=>'Item Targets', 'route'=>'hr_settings_item_targets', 'icon' => 'si si-target', 'badge' => 0],
```

This pattern ensures consistency across all CRUD modules in the application.

## Notes

- Always use the `handleCrud()` method for CRUD operations
- Implement proper permission checks using `@can()` directives
- Follow the modal-based form pattern for consistency
- Use DataTables for listing views
- Include proper error handling and user notifications
- Test with different user roles (admin vs manager)

## Sales Module with RingleSoft Approval Workflow

### Sales Approval Implementation

The Sales module demonstrates the integration of CRUD operations with RingleSoft's approval workflow system. Here's how it's implemented:

#### Model Implementation
```php
// File: app/Models/Sale.php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use RingleSoft\LaravelProcessApproval\Contracts\ApprovableModel;
use RingleSoft\LaravelProcessApproval\Traits\Approvable;

class Sale extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    public $fillable = [
        'efd_id', 'amount', 'net', 'tax', 'turn_over', 
        'date', 'file', 'last_z_report_number'
    ];

    /**
     * Called when approval process is completed successfully
     */
    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->updated_at = now();
        $this->save();
        return true;
    }

    public function efd()
    {
        return $this->belongsTo(Efd::class);
    }
}
```

#### Controller Implementation
```php
// File: app/Http/Controllers/SaleController.php
<?php

namespace App\Http\Controllers;

use App\Services\ApprovalService;

class SaleController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Sales listing with CRUD operations
     */
    public function index(Request $request) {
        // Handle CRUD operations using base controller method
        if($this->handleCrud($request, 'Sale')) {
            return back();
        }

        $sales = Sale::all();
        $efds = Efd::all();

        $data = [
            'efds' => $efds,
            'sales' => $sales
        ];
        return view('pages.sales.sales_index')->with($data);
    }

    /**
     * Individual sale approval page
     */
    public function sale($id, $document_type_id) {
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'sale');

        $approval_data = Sale::find($id);
        
        $details = [
            'Turnover' => number_format($approval_data->amount),
            'NET (A+B+C)' => number_format($approval_data->net),
            'Tax' => number_format($approval_data->tax),
            'Turnover (EX + SR)' => number_format($approval_data->turn_over),
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Sales',
            'approval_data_name' => $approval_data->efd->name,
            'details' => $details,
            'model' => 'Sale',
            'route' => 'sale',
        ];
        
        return view('approvals._approve_page')->with($data);
    }
}
```

#### View Implementation with Approval Components
```php
// File: resources/views/pages/sales/sales_index.blade.php

<!-- Approval Status Summary Column -->
<td class="text-center">
    <x-ringlesoft-approval-status-summary :model="$sale" />
</td>

<!-- Status Badge Column -->
<td class="text-center">
    @php
        $approvalStatus = $sale->approvalStatus?->status ?? 'PENDING';
        $statusClass = [
            'Pending' => 'warning',
            'Submitted' => 'info', 
            'Approved' => 'success',
            'Rejected' => 'danger',
            'Paid' => 'primary',
            'Completed' => 'success',
            'Discarded' => 'danger',
        ][$approvalStatus] ?? 'secondary';

        $statusIcon = [
            'Pending' => '<i class="fas fa-clock"></i>',
            'Submitted' => '<i class="fas fa-paper-plane"></i>',
            'Approved' => '<i class="fas fa-check"></i>',
            'Rejected' => '<i class="fas fa-times"></i>',
            'Paid' => '<i class="fas fa-money-bill"></i>',
            'Completed' => '<i class="fas fa-check-circle"></i>',
            'Discarded' => '<i class="fas fa-trash"></i>',
        ][$approvalStatus] ?? '<i class="fas fa-question-circle"></i>';
    @endphp
    
    <span class="badge badge-{{ $statusClass }} badge-pill" style="font-size: 0.9em; padding: 6px 10px;">
        {!! $statusIcon !!} {{ $approvalStatus }}
    </span>
</td>

<!-- View/Approve Action Button -->
<a class="btn btn-sm btn-success js-tooltip-enabled" 
   href="{{route('sale',['id' => $sale->id,'document_type_id'=>2])}}">
    <i class="fa fa-eye"></i>
</a>
```

#### Routes Configuration
```php
// File: routes/web.php

Route::middleware(['auth'])->group(function () {
    // Sales CRUD operations
    Route::match(['get', 'post'], '/sales', [SaleController::class, 'index'])->name('sales');
    
    // Individual sale approval page
    Route::match(['get', 'post'], '/sale/{id}/{document_type_id}', [SaleController::class, 'sale'])->name('sale');
    
    // AJAX endpoint for EFD data
    Route::match(['get', 'post'], '/getLastEfdNumber', [SaleController::class, 'getLastEfdNumber'])->name('getLastEfdNumber');
});
```

#### Approval Workflow Configuration

The RingleSoft approval system provides:

1. **Multi-step approval workflows** configured in `process_approval_flows` table
2. **Role-based approvers** using Spatie Laravel Permission package
3. **Approval actions**: Submit → Approve → Complete
4. **Status tracking** with visual badges and progress indicators
5. **Notification system** for approvers at each step
6. **Audit trail** of all approval actions

#### Key Components

**x-ringlesoft-approval-status-summary Component:**
- Shows visual progress of approval steps
- Color-coded badges for each approval stage
- Quick status overview in list views

**x-ringlesoft-approval-actions Component:**
- Full approval interface with timeline
- Action buttons (Submit, Approve, Reject, Return, Discard)
- Role-based permissions for each action
- Modal forms for approval comments

#### ApprovalService Integration

```php
// File: app/Services/ApprovalService.php

class ApprovalService
{
    /**
     * Mark approval notification as read
     */
    public function markNotificationAsRead($document_id, $document_type_id, $module)
    {
        // Implementation for notification handling
    }

    /**
     * Get approval timeline data
     */  
    public function getApprovalTimeline($model)
    {
        // Returns approval history with timestamps and approvers
    }

    /**
     * Check if user can approve document
     */
    public function userCanApprove($model, $user)
    {
        // Role-based permission checking
    }
}
```

This implementation demonstrates how the CRUD pattern integrates seamlessly with approval workflows, providing comprehensive document management with proper authorization and audit trails.

---

**Last Updated:** 2025-06-23  
**Version:** 1.1  
**Author:** System Documentation