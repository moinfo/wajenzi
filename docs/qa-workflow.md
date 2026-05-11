# QA Inspection Workflow

**Module:** Quality Assurance — Material Inspection & Labor Inspection
**Roles responsible:** Inspector (procurement), Site Supervisor / Site Engineer (labor)

---

## Overview

Two parallel QA streams run in Wajenzi:

| Stream | Trigger | What is inspected | Approval ID |
|---|---|---|---|
| **Material Inspection** | Goods received from supplier | Quantity, condition, spec compliance | 19 |
| **Labor Inspection** | Active artisan contract with pending payment phase | Work quality, completion %, scope compliance | — |

Both follow the same pattern: Inspector fills in the form → auto-submitted to RingleSoft → MD approves → downstream action fires.

---

## Part 1 — Material Inspection

### What it does

After a Purchase Order is delivered by the supplier (a `SupplierReceiving` record is created), the system flags it as **pending inspection**. An inspector visits the site, checks the delivered materials against the delivery note and BOQ specs, and records the result.

On MD approval the accepted quantity is added to site inventory and the BOQ item's `quantity_received` counter is incremented.

### Actors

| Who | What they do |
|---|---|
| Inspector / Procurement Officer | Creates the inspection form from a pending receiving |
| Managing Director | Approves or rejects via RingleSoft |
| System Administrator | Full access |

### Trigger

A `SupplierReceiving` record enters `pendingInspection()` scope — i.e., it has been created (delivery note logged) but no inspection record exists for it yet.

The inspection list page (`/material_inspections`) shows a **Pending Receivings** panel. The inspector clicks **Inspect** next to a receiving to open the create form.

### Fields

| Field | Type | Notes |
|---|---|---|
| `inspection_number` | auto | Format: `MI-YYYY-0001` |
| `supplier_receiving_id` | FK | The delivery being inspected |
| `project_id` | FK | Project the materials are for |
| `boq_item_id` | FK | BOQ line item being received |
| `inspection_date` | date | Set to `now()` on create |
| `quantity_delivered` | decimal | From delivery note |
| `quantity_accepted` | decimal | Inspector's count after check |
| `quantity_rejected` | decimal | Auto = delivered − accepted |
| `overall_condition` | enum | `excellent / good / acceptable / poor / rejected` |
| `rejection_reason` | text | Required if any rejected |
| `inspection_notes` | text | Additional observations |
| `criteria_checklist` | JSON | 6 boolean checks (packaging intact, qty correct, spec match, no defects, labeling, storage suitability) |
| `overall_result` | enum | Auto-set: `pass / conditional / fail` |
| `stock_updated` | boolean | Set to true after stock write |
| `status` | enum | `pending → APPROVED / rejected` |

### Result Logic (auto-calculated on save)

```
quantity_accepted == 0        → overall_result = 'fail'
quantity_rejected > 0         → overall_result = 'conditional'
quantity_accepted == delivered → overall_result = 'pass'
```

### Status Flow

```
Inspector creates form
       │
       ▼
Inspection saved (status: pending)
       │  [auto-submitted to RingleSoft on create]
       ▼
MD / CEO reviews on approval page
       │
       ├── Approve ──→ onApprovalCompleted() fires:
       │                  • status = 'APPROVED'
       │                  • Stock updated (inventory + movement record)
       │                  • BOQ item quantity_received incremented
       │                  • SupplierReceiving status → 'inspected'
       │
       └── Reject ───→ status = 'rejected', reason recorded
```

### Stock Update (on approval)

`MaterialInspection::updateStock()` runs inside a DB transaction:

1. Finds or creates a `ProjectMaterialInventory` record for `[project_id, boq_item_id]`
2. Increments `inventory.quantity` by `quantity_accepted`
3. Creates a `ProjectMaterialMovement` record (`movement_type = 'received'`)
4. Calls `boqItem->updateProcurementStatus()` — may advance `procurement_status` to `complete`
5. Sets `stock_updated = true`, `stock_updated_at = now()`

If the stock update was missed (e.g., a system error), an admin can re-trigger it manually via the **Update Stock** button on the inspection detail page (`/material_inspection/{id}/update_stock`).

### Key Files

| File | Purpose |
|---|---|
| `app/Models/MaterialInspection.php` | Model, result logic, `updateStock()`, `onApprovalCompleted()` |
| `app/Http/Controllers/MaterialInspectionController.php` | Index (pending panel), create, store, approval actions |
| `resources/views/pages/procurement/material_inspections.blade.php` | Inspection list + pending receivings |
| `resources/views/pages/procurement/create_inspection.blade.php` | Inspection form |
| `resources/views/approvals/_approve_page.blade.php` | Shared approval detail page |

### HTTP Routes

| Method | URI | Action |
|---|---|---|
| GET/POST | `/material_inspections` | Index (with CRUD handler) |
| GET | `/material_inspection/create/{receiving_id}` | Create form pre-filled from receiving |
| POST | `/material_inspection/store` | Store new inspection |
| GET/POST | `/material_inspection/{id}/{document_type_id}` | Approval detail page |
| POST | `/material_inspection/{inspection}/submit` | Re-submit draft |
| POST | `/material_inspection/{inspection}/approve` | MD approve |
| POST | `/material_inspection/{inspection}/reject` | MD reject |
| POST | `/material_inspection/{id}/update_stock` | Manual stock update trigger |

---

## Part 2 — Labor Inspection

### What it does

Artisans work under `LaborContract` records. Contracts have payment phases that unlock when a milestone is reached. A **Labor Inspection** is the formal quality check that unlocks a payment phase.

Types:
- `progress` — mid-contract check
- `milestone` — specific milestone gate
- `final` — completion of the contract

On approval, the payment phase is unlocked for processing.

### Actors

| Who | What they do |
|---|---|
| Site Supervisor / Labor Manager | Creates the inspection for an active contract |
| Managing Director | Approves via RingleSoft |

### Trigger

A `LaborContract` is `active` and has a payment phase in `pending` or `due` status. The inspection list page shows a **Contracts Pending Inspection** panel. The supervisor clicks **Inspect** to open the form.

### Fields

| Field | Type | Notes |
|---|---|---|
| `inspection_number` | auto | Format: `LI-YYYY-0001` |
| `labor_contract_id` | FK | The artisan contract |
| `payment_phase_id` | FK | Which phase this unlocks (optional) |
| `inspection_date` | date | Set to `now()` on create |
| `inspection_type` | enum | `progress / milestone / final` |
| `work_quality` | enum | `excellent / good / acceptable / poor / unacceptable` |
| `completion_percentage` | decimal | 0–100% |
| `scope_compliance` | boolean | Is work within agreed scope? |
| `defects_found` | text | Description of defects |
| `rectification_required` | boolean | Does work need fixing? |
| `rectification_notes` | text | What needs to be fixed |
| `photos` | JSON array | File paths to site photos |
| `result` | enum | `pass / conditional / fail` |
| `notes` | text | Additional notes |
| `status` | enum | `draft → pending → APPROVED / rejected` |

### Result Logic

| Condition | Result |
|---|---|
| `work_quality = unacceptable` OR `scope_compliance = false` | `fail` |
| `work_quality = poor` OR `rectification_required = true` | `conditional` |
| Otherwise | `pass` |

### Status Flow

```
Supervisor creates form
       │
       ▼
Inspection saved (status: pending)
       │  [auto-submitted on create]
       ▼
MD reviews on approval page
       │
       ├── Approve ──→ Payment phase unlocked for processing
       │
       └── Reject ───→ Supervisor notified; may edit and resubmit
```

### Key Files

| File | Purpose |
|---|---|
| `app/Models/LaborInspection.php` | Model, result logic, `onApprovalCompleted()` |
| `app/Http/Controllers/LaborInspectionController.php` | Index, create, store, edit, approval |
| `resources/views/labor/inspections/` | View directory |

### HTTP Routes (under `/labor-procurement` prefix)

| Method | URI | Action |
|---|---|---|
| GET/POST | `/labor-procurement/inspections` | Index |
| GET | `/labor-procurement/inspections/create/{contract_id}` | Create form |
| POST | `/labor-procurement/inspections/store` | Store |
| GET | `/labor-procurement/inspections/{id}` | Show detail |
| GET | `/labor-procurement/inspections/{id}/edit` | Edit (draft only) |
| POST | `/labor-procurement/inspections/{id}/update` | Update |
| POST | `/labor-procurement/inspections/{id}/submit` | Submit for approval |
| GET/POST | `/labor-procurement/inspections/{id}/{document_type_id}` | Approval page |
| POST | `/labor-procurement/inspections/{inspection}/approve` | Approve |
| POST | `/labor-procurement/inspections/{inspection}/reject` | Reject |

---

## Inspection Number Formats

| Type | Format | Example |
|---|---|---|
| Material | `MI-YYYY-NNNN` | `MI-2026-0012` |
| Labor | `LI-YYYY-NNNN` | `LI-2026-0003` |
