# Labor Charge Procurement System Documentation

## Overview

The Labor Charge Procurement System manages the complete lifecycle of hiring artisans/laborers for construction projects. It tracks everything from initial identification through contract execution, work monitoring, inspection, and final payment.

---

## Workflow Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                      LABOR CHARGE PROCUREMENT WORKFLOW                          │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐ │
│   │    STEP 1    │    │    STEP 2    │    │    STEP 3    │    │    STEP 4    │ │
│   │Identification│───▶│ Negotiation  │───▶│   Approval   │───▶│  Contract    │ │
│   │              │    │              │    │              │    │ Preparation  │ │
│   └──────────────┘    └──────────────┘    └──────────────┘    └──────┬───────┘ │
│                                                                       │         │
│   ┌──────────────────────────────────────────────────────────────────┘         │
│   │                                                                             │
│   ▼                                                                             │
│   ┌──────────────┐    ┌──────────────┐    ┌──────────────┐                     │
│   │    STEP 5    │    │    STEP 6    │    │    STEP 7    │                     │
│   │  Execution & │───▶│  Inspection  │───▶│    Final     │                     │
│   │  Monitoring  │    │              │    │   Approval   │                     │
│   └──────────────┘    └──────────────┘    └──────────────┘                     │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## Detailed Process Steps

### Step 1: Identification
| Aspect | Details |
|--------|---------|
| **Responsible** | Procurement Team |
| **Actions** | - Search for suitable labor/artisans<br>- Collect artisan credentials<br>- Request work assessment from artisan<br>- Obtain materials list (if artisan provides materials) |
| **Output** | Labor Request with assessment details |
| **Status** | `draft` → `assessment_received` |

### Step 2: Negotiation
| Aspect | Details |
|--------|---------|
| **Responsible** | Procurement Team |
| **Actions** | - Review work scope and assessment<br>- Negotiate rates and terms<br>- Define payment phases<br>- Prepare proposal for management |
| **Output** | Negotiated proposal with terms |
| **Status** | `negotiating` → `pending_approval` |

### Step 3: Approval (Management)
| Aspect | Details |
|--------|---------|
| **Responsible** | Managing Director (MD) |
| **Actions** | - Review proposal<br>- Accept, reduce budget, or reject<br>- Provide feedback if rejected |
| **Output** | Approved/Modified/Rejected proposal |
| **Status** | `pending_approval` → `approved` / `reduced` / `rejected` |
| **Approval Type** | `ApprovableModel` interface |

### Step 4: Contract Preparation
| Aspect | Details |
|--------|---------|
| **Responsible** | Procurement Team + Supervisor (SPV) |
| **Actions** | - Share approved scope with SPV<br>- SPV drafts contract document<br>- Define payment phases (milestones)<br>- Artisan signs contract<br>- SPV signs contract |
| **Output** | Signed labor contract with payment schedule |
| **Status** | `approved` → `contract_drafted` → `contract_signed` |

### Step 5: Execution & Monitoring
| Aspect | Details |
|--------|---------|
| **Responsible** | Supervisor (SPV) + Procurement Team |
| **Actions** | - Work begins on site<br>- SPV supervises daily activities<br>- Procurement monitors progress<br>- Log work updates and challenges<br>- Track against milestones |
| **Output** | Work progress records |
| **Status** | `in_progress` |

### Step 6: Inspection
| Aspect | Details |
|--------|---------|
| **Responsible** | Supervisor (SPV) |
| **Actions** | - Inspect completed work<br>- Verify quality standards<br>- Document inspection findings<br>- Confirm completion percentage |
| **Output** | Inspection report with quality confirmation |
| **Status** | `inspection_pending` → `inspection_passed` / `inspection_failed` |

### Step 7: Final Approval & Payment
| Aspect | Details |
|--------|---------|
| **Responsible** | Managing Director (MD) |
| **Actions** | - Review inspection report<br>- Final sign-off on completed work<br>- Authorize payment release<br>- Execute payment per agreed phases |
| **Output** | Payment authorization and execution |
| **Status** | `pending_final_approval` → `completed` |
| **Approval Type** | `ApprovableModel` interface |

---

## Database Schema

### Table: `labor_requests`
Primary table for labor/artisan requests.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `request_number` | string(50) | Auto-generated: LR-YYYY-0001 |
| `project_id` | foreignId | Link to projects table |
| `construction_phase_id` | foreignId | Link to project_construction_phases |
| `artisan_id` | foreignId | Link to suppliers (artisan type) |
| `requested_by` | foreignId | User who created request |
| `work_description` | text | Description of work to be done |
| `work_location` | string | Specific location on site |
| `estimated_duration_days` | integer | Expected work duration |
| `start_date` | date | Planned start date |
| `end_date` | date | Planned end date |
| `artisan_assessment` | text | Assessment provided by artisan |
| `materials_list` | json | Materials list if artisan provides |
| `materials_included` | boolean | Whether artisan provides materials |
| `proposed_amount` | decimal(15,2) | Initial proposed amount |
| `negotiated_amount` | decimal(15,2) | Amount after negotiation |
| `approved_amount` | decimal(15,2) | Final approved amount |
| `currency` | string(3) | Currency code (TZS) |
| `payment_terms` | text | Payment terms description |
| `status` | enum | Current status (see below) |
| `rejection_reason` | text | Reason if rejected |
| `approved_by` | foreignId | MD who approved |
| `approved_at` | datetime | Approval timestamp |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Status Values:**
- `draft` - Initial creation
- `assessment_received` - Artisan provided assessment
- `negotiating` - Under negotiation
- `pending_approval` - Awaiting MD approval
- `approved` - MD approved
- `reduced` - MD approved with reduced budget
- `rejected` - MD rejected
- `contract_pending` - Ready for contract

### Table: `labor_contracts`
Contract details after approval.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `contract_number` | string(50) | Auto-generated: LC-YYYY-0001 |
| `labor_request_id` | foreignId | Link to labor_requests |
| `project_id` | foreignId | Link to projects |
| `artisan_id` | foreignId | Link to suppliers |
| `supervisor_id` | foreignId | SPV responsible |
| `contract_date` | date | Date contract signed |
| `start_date` | date | Work start date |
| `end_date` | date | Expected completion |
| `actual_end_date` | date | Actual completion date |
| `scope_of_work` | text | Detailed work scope |
| `terms_conditions` | text | Contract T&C |
| `total_amount` | decimal(15,2) | Contract value |
| `amount_paid` | decimal(15,2) | Total paid so far |
| `balance_amount` | decimal(15,2) | Remaining balance |
| `artisan_signature` | string | Signature file path |
| `supervisor_signature` | string | SPV signature file path |
| `contract_file` | string | Uploaded contract PDF |
| `status` | enum | Contract status |
| `notes` | text | Additional notes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Status Values:**
- `draft` - Being prepared
- `pending_signatures` - Awaiting signatures
- `active` - Work in progress
- `inspection_pending` - Work done, inspection needed
- `completed` - Successfully completed
- `terminated` - Contract terminated early

### Table: `labor_payment_phases`
Payment milestones for contracts.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `labor_contract_id` | foreignId | Link to labor_contracts |
| `phase_number` | integer | Phase sequence (1, 2, 3...) |
| `phase_name` | string | e.g., "Initial", "Midpoint", "Final" |
| `description` | text | What triggers this payment |
| `percentage` | decimal(5,2) | Percentage of total (e.g., 30.00) |
| `amount` | decimal(15,2) | Calculated amount |
| `due_date` | date | Expected payment date |
| `milestone_description` | text | Work milestone to achieve |
| `status` | enum | Phase status |
| `paid_at` | datetime | When payment was made |
| `paid_by` | foreignId | Who authorized payment |
| `payment_reference` | string | Payment reference/receipt |
| `notes` | text | Payment notes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Status Values:**
- `pending` - Not yet due
- `due` - Milestone reached, payment due
- `approved` - Payment approved by MD
- `paid` - Payment completed
- `held` - Payment on hold (issues)

### Table: `labor_work_logs`
Daily/periodic work progress records.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `labor_contract_id` | foreignId | Link to labor_contracts |
| `log_date` | date | Date of log entry |
| `logged_by` | foreignId | User who logged (SPV) |
| `work_done` | text | Description of work completed |
| `workers_present` | integer | Number of workers on site |
| `hours_worked` | decimal(4,1) | Hours worked |
| `progress_percentage` | decimal(5,2) | Overall progress % |
| `challenges` | text | Issues encountered |
| `materials_used` | json | Materials consumed |
| `photos` | json | Array of photo paths |
| `weather_conditions` | string | Weather that day |
| `notes` | text | Additional notes |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Table: `labor_inspections`
Inspection records for completed work.

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `inspection_number` | string(50) | Auto-generated: LI-YYYY-0001 |
| `labor_contract_id` | foreignId | Link to labor_contracts |
| `payment_phase_id` | foreignId | Link to specific phase (optional) |
| `inspection_date` | date | Date of inspection |
| `inspector_id` | foreignId | SPV who inspected |
| `inspection_type` | enum | 'progress', 'milestone', 'final' |
| `work_quality` | enum | 'excellent', 'good', 'acceptable', 'poor' |
| `completion_percentage` | decimal(5,2) | % of work completed |
| `scope_compliance` | boolean | Work matches scope? |
| `defects_found` | text | Any defects identified |
| `rectification_required` | boolean | Needs rework? |
| `rectification_notes` | text | What needs fixing |
| `photos` | json | Inspection photos |
| `inspector_signature` | string | SPV signature |
| `result` | enum | 'pass', 'conditional', 'fail' |
| `notes` | text | Inspection notes |
| `status` | enum | Inspection status |
| `verified_by` | foreignId | MD who verified |
| `verified_at` | datetime | Verification time |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Status Values:**
- `draft` - Being prepared
- `submitted` - Submitted for review
- `pending_verification` - Awaiting MD verification
- `verified` - MD verified/approved
- `rejected` - MD rejected

---

## Models & Relationships

### LaborRequest Model
```
LaborRequest
├── belongsTo: Project
├── belongsTo: ProjectConstructionPhase
├── belongsTo: Supplier (artisan)
├── belongsTo: User (requested_by)
├── belongsTo: User (approved_by)
├── hasOne: LaborContract
└── implements: ApprovableModel
```

### LaborContract Model
```
LaborContract
├── belongsTo: LaborRequest
├── belongsTo: Project
├── belongsTo: Supplier (artisan)
├── belongsTo: User (supervisor)
├── hasMany: LaborPaymentPhase
├── hasMany: LaborWorkLog
└── hasMany: LaborInspection
```

### LaborPaymentPhase Model
```
LaborPaymentPhase
├── belongsTo: LaborContract
├── belongsTo: User (paid_by)
└── hasOne: LaborInspection (optional)
```

### LaborWorkLog Model
```
LaborWorkLog
├── belongsTo: LaborContract
└── belongsTo: User (logged_by)
```

### LaborInspection Model
```
LaborInspection
├── belongsTo: LaborContract
├── belongsTo: LaborPaymentPhase (optional)
├── belongsTo: User (inspector)
├── belongsTo: User (verified_by)
└── implements: ApprovableModel
```

---

## Approval Workflows

### 1. Labor Request Approval
```
Labor Request Created (Procurement)
        │
        ▼
  Status: pending_approval
        │
        ▼
┌───────────────────┐
│   MD Reviews      │
│   ┌───────────┐   │
│   │  Accept   │───┼──▶ Status: approved
│   └───────────┘   │
│   ┌───────────┐   │
│   │  Reduce   │───┼──▶ Status: reduced (with new amount)
│   └───────────┘   │
│   ┌───────────┐   │
│   │  Reject   │───┼──▶ Status: rejected (with reason)
│   └───────────┘   │
└───────────────────┘
```

### 2. Payment Phase Approval
```
Inspection Passed (SPV)
        │
        ▼
  Payment Phase: due
        │
        ▼
┌───────────────────┐
│   MD Reviews      │
│   ┌───────────┐   │
│   │  Approve  │───┼──▶ Payment Phase: approved → paid
│   └───────────┘   │
│   ┌───────────┐   │
│   │   Hold    │───┼──▶ Payment Phase: held
│   └───────────┘   │
└───────────────────┘
```

### 3. Final Inspection Approval
```
Final Inspection Submitted (SPV)
        │
        ▼
  Status: pending_verification
        │
        ▼
┌───────────────────┐
│   MD Reviews      │
│   ┌───────────┐   │
│   │  Verify   │───┼──▶ Contract: completed
│   └───────────┘   │    Final payment released
│   ┌───────────┐   │
│   │  Reject   │───┼──▶ Rectification required
│   └───────────┘   │
└───────────────────┘
```

---

## User Roles & Permissions

| Role | Permissions |
|------|-------------|
| **Procurement** | Create labor request, Negotiate, Monitor progress, View all |
| **Supervisor (SPV)** | Draft contract, Sign contract, Log work, Inspect, Submit inspection |
| **Managing Director (MD)** | Approve/Reject request, Verify inspection, Authorize payment, Final sign-off |
| **Finance** | View payments, Process payments, Generate reports |
| **Admin** | Full access to all functions |

### Permission List
```
- labor_requests.view
- labor_requests.create
- labor_requests.edit
- labor_requests.delete
- labor_requests.approve

- labor_contracts.view
- labor_contracts.create
- labor_contracts.edit
- labor_contracts.sign

- labor_work_logs.view
- labor_work_logs.create
- labor_work_logs.edit

- labor_inspections.view
- labor_inspections.create
- labor_inspections.verify

- labor_payments.view
- labor_payments.approve
- labor_payments.process
```

---

## Routes Structure

```php
// Labor Requests
Route::prefix('labor')->name('labor.')->group(function () {
    // Requests
    Route::match(['get', 'post'], '/requests', [LaborRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/create', [LaborRequestController::class, 'create'])->name('requests.create');
    Route::post('/requests/store', [LaborRequestController::class, 'store'])->name('requests.store');
    Route::get('/requests/{id}', [LaborRequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/edit', [LaborRequestController::class, 'edit'])->name('requests.edit');
    Route::post('/requests/{id}/update', [LaborRequestController::class, 'update'])->name('requests.update');
    Route::match(['get', 'post'], '/request/{id}/{document_type_id}', [LaborRequestController::class, 'request'])->name('requests.approval');

    // Contracts
    Route::match(['get', 'post'], '/contracts', [LaborContractController::class, 'index'])->name('contracts.index');
    Route::get('/contracts/create/{request_id}', [LaborContractController::class, 'create'])->name('contracts.create');
    Route::post('/contracts/store', [LaborContractController::class, 'store'])->name('contracts.store');
    Route::get('/contracts/{id}', [LaborContractController::class, 'show'])->name('contracts.show');
    Route::post('/contracts/{id}/sign', [LaborContractController::class, 'sign'])->name('contracts.sign');
    Route::get('/contracts/{id}/pdf', [LaborContractController::class, 'generatePDF'])->name('contracts.pdf');

    // Work Logs
    Route::get('/contracts/{contract_id}/logs', [LaborWorkLogController::class, 'index'])->name('logs.index');
    Route::post('/contracts/{contract_id}/logs', [LaborWorkLogController::class, 'store'])->name('logs.store');

    // Inspections
    Route::match(['get', 'post'], '/inspections', [LaborInspectionController::class, 'index'])->name('inspections.index');
    Route::get('/inspections/create/{contract_id}', [LaborInspectionController::class, 'create'])->name('inspections.create');
    Route::post('/inspections/store', [LaborInspectionController::class, 'store'])->name('inspections.store');
    Route::match(['get', 'post'], '/inspection/{id}/{document_type_id}', [LaborInspectionController::class, 'inspection'])->name('inspections.approval');

    // Payments
    Route::get('/payments', [LaborPaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments/{phase_id}/approve', [LaborPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{phase_id}/process', [LaborPaymentController::class, 'process'])->name('payments.process');

    // Dashboard
    Route::get('/dashboard', [LaborDashboardController::class, 'index'])->name('dashboard');
});
```

---

## Views Structure

```
resources/views/
└── labor/
    ├── requests/
    │   ├── index.blade.php          # List all labor requests
    │   ├── create.blade.php         # Create new request form
    │   ├── show.blade.php           # View request details
    │   ├── edit.blade.php           # Edit request
    │   └── approval.blade.php       # Approval workflow view
    │
    ├── contracts/
    │   ├── index.blade.php          # List all contracts
    │   ├── create.blade.php         # Create contract from request
    │   ├── show.blade.php           # View contract details
    │   ├── sign.blade.php           # Signature capture
    │   └── pdf.blade.php            # PDF template
    │
    ├── logs/
    │   ├── index.blade.php          # Work logs for contract
    │   └── create.blade.php         # Add work log
    │
    ├── inspections/
    │   ├── index.blade.php          # List all inspections
    │   ├── create.blade.php         # Create inspection
    │   ├── show.blade.php           # View inspection
    │   └── approval.blade.php       # MD verification view
    │
    ├── payments/
    │   ├── index.blade.php          # Payment phases list
    │   └── process.blade.php        # Process payment
    │
    ├── dashboard.blade.php          # Labor procurement dashboard
    │
    └── forms/
        ├── labor_request_form.blade.php
        ├── labor_contract_form.blade.php
        ├── labor_work_log_form.blade.php
        └── labor_inspection_form.blade.php
```

---

## Document Types (for Approval System)

Add to `document_types` table:

| ID | Name | Model | Route |
|----|------|-------|-------|
| X | Labor Request | LaborRequest | labor_request |
| X+1 | Labor Inspection | LaborInspection | labor_inspection |

---

## Menu Structure

```
Procurement (existing)
├── Material Requests
├── Quotations
├── ...
│
Labor Procurement (new)
├── Dashboard
├── Labor Requests
├── Contracts
├── Work Logs
├── Inspections
├── Payments
```

---

## Implementation Phases

### Phase 1: Core Setup (Day 1-2)
- [ ] Create migrations for all tables
- [ ] Create models with relationships
- [ ] Add document types for approval
- [ ] Create menu and permissions seeder

### Phase 2: Labor Requests (Day 2-3)
- [ ] LaborRequestController with CRUD
- [ ] Request form with artisan selection
- [ ] Approval workflow integration
- [ ] Request list and detail views

### Phase 3: Contracts (Day 3-4)
- [ ] LaborContractController
- [ ] Contract creation from approved request
- [ ] Payment phases management
- [ ] Signature capture
- [ ] Contract PDF generation

### Phase 4: Execution & Monitoring (Day 4-5)
- [ ] LaborWorkLogController
- [ ] Daily work log entry
- [ ] Progress tracking
- [ ] Photo upload support

### Phase 5: Inspections (Day 5-6)
- [ ] LaborInspectionController
- [ ] Inspection forms
- [ ] MD verification workflow
- [ ] Inspection reports

### Phase 6: Payments (Day 6-7)
- [ ] LaborPaymentController
- [ ] Payment approval workflow
- [ ] Payment processing
- [ ] Payment history

### Phase 7: Dashboard & Reports (Day 7-8)
- [ ] Labor dashboard with stats
- [ ] Active contracts overview
- [ ] Payment summary
- [ ] Reports generation

---

## Sample Payment Phase Structure

Typical payment phases for a labor contract:

| Phase | Name | Percentage | Milestone |
|-------|------|------------|-----------|
| 1 | Mobilization | 20% | Contract signed, work begins |
| 2 | Progress | 30% | 50% work completed |
| 3 | Substantial | 30% | 90% work completed |
| 4 | Final | 20% | Work completed & inspected |

---

## Integration Points

### With Existing Systems

1. **Projects** - Labor requests linked to projects
2. **Suppliers** - Artisans stored as supplier type 'artisan'
3. **Construction Phases** - Labor linked to project phases
4. **Approval System** - Uses existing ApprovableModel
5. **Users** - Roles for SPV, MD, Procurement
6. **Notifications** - Alerts for approvals, inspections

### New Supplier Type

Add 'artisan' to supplier types:
- Name, Contact, ID Number
- Skills/Trade (masonry, electrical, plumbing, etc.)
- Rate per day/job
- Previous work history
- Rating/feedback

---

## Status Flow Summary

```
LABOR REQUEST:
draft → assessment_received → negotiating → pending_approval → approved/reduced/rejected → contract_pending

LABOR CONTRACT:
draft → pending_signatures → active → inspection_pending → completed/terminated

PAYMENT PHASE:
pending → due → approved → paid / held

LABOR INSPECTION:
draft → submitted → pending_verification → verified/rejected
```

---

## Notes

1. **Artisan as Supplier**: Artisans are stored in the `suppliers` table with a type field indicating 'artisan'
2. **Signature Capture**: Use canvas-based signature pad (similar to material inspection)
3. **Photo Upload**: Support multiple photos for work logs and inspections
4. **PDF Generation**: Contract and inspection PDFs using same pattern as invoices
5. **Currency**: Default to TZS, support multi-currency if needed

---

*Document Version: 1.0*
*Created: February 5, 2026*
*Last Updated: February 5, 2026*
