# Procurement Workflow

**Module:** Procurement (Projects → Procurement)
**Roles responsible:** Site Manager / Project Manager (requests), Procurement Officer (quotations + comparison), Finance (payment)

---

## What This Workflow Does

After a BOQ is prepared, project staff can request materials against it. Each request goes through a multi-stage procurement chain:

```
Material Request → Supplier Quotations (×3 min) → Quotation Comparison → Purchase Order → Delivery → Material Inspection → Finance Payment → Close
```

---

## Stage 1 — Material Request

### Who creates it

Anyone with the `Create Material Request` permission (typically Site Manager, Project Manager, or QS).

### What is submitted

| Field | Validation |
|---|---|
| `project_id` | Required, must exist |
| `items[].boq_item_id` | Optional FK to BOQ item — if omitted, `description` is required |
| `items[].quantity_requested` | Required, > 0 |
| `items[].unit` | Required |
| `required_date` | Required date |
| `priority` | `low` `medium` `high` `urgent` |

**Guards:**
- A BOQ item cannot have two simultaneous pending requests
- Quantity must not exceed `boq_item.quantity - boq_item.quantity_requested` (available balance)

### Document number format

`MR-YYYY-0001` (auto-generated on creation)

### Approval flow (RingleSoft, flow ID 17)

```
pending (auto-submitted on create)
      │ MD/CEO approves
      ▼
APPROVED ✓
```

`onApprovalCompleted()`: sets `status = APPROVED`, updates `quantity_approved` on each item, increments `quantity_requested` on the linked BOQ items.

### After approval

The request becomes available for supplier quoting. The Procurement Officer sees it in the pending-quoting queue.

---

## Stage 2 — Supplier Quotations

The Procurement Officer invites ≥ 3 suppliers to quote and records their quotations in the system.

### Per-quotation fields

| Field | Notes |
|---|---|
| `supplier_id` | FK → suppliers |
| `material_request_id` | Parent request |
| `grand_total` | Total amount |
| `items[]` | Line items with `unit_price` and `material_request_item_id` |

**Route:** `GET /supplier_quotations` — lists all quotations  
**Create:** `POST /supplier_quotations`

---

## Stage 3 — Quotation Comparison

Once ≥ 3 quotations exist for a request, the Procurement Officer prepares a comparison.

### What the comparison includes

| Field | Notes |
|---|---|
| `material_request_id` | |
| `selected_quotation_id` | The recommended quotation |
| `recommended_supplier_id` | Derived from selected quotation |
| `recommendation_reason` | Required, ≥ 10 chars |

**Guard:** At least 3 non-rejected quotations must exist before a comparison can be created.

### Document number format

`QC-YYYY-0001`

### Approval flow (RingleSoft, flow ID 18)

```
pending (auto-submitted on create)
      │ MD/CEO approves
      ▼
approved ✓  → Purchase Order can be generated
```

### After approval

"Create Purchase Order" button appears on the comparison page. It calls `Purchase::createFromComparison($comparison)` which copies supplier + items to a new Purchase record.

---

## Stage 4 — Purchase Order

### Creation

Auto-created from the approved comparison (`Purchase::createFromComparison()`), or manually for one-off purchases not tied to a material request.

### Document number format

`PO-0001` (or `DOC-0001` depending on type)

### Approval flow (RingleSoft, Purchase model)

```
draft
   │ submitter clicks "Submit"
   ▼
pending ──── MD/CEO approves ──▶ APPROVED ✓
   │
   └────── MD/CEO rejects ──▶ rejected
```

**Routes:**
- `POST /purchases/{purchase}/submit` → `submit()`
- `POST /purchases/{purchase}/approve` → `approve()`
- `POST /purchases/{purchase}/reject` → `reject()`

### After approval

The purchase order is sent to the supplier. Delivery recording becomes available.

---

## Stage 5 — Delivery Recording

When goods arrive, the site team records the delivery against the Purchase Order.

**Route:** `GET /procurement/purchase-orders/{id}/record-delivery` → `recordDelivery()`  
**Record:** `POST /procurement/purchase-orders/{id}/store-delivery` → `storeDelivery()`

A `SupplierReceiving` record is created with:
- Quantity delivered
- Delivery date
- Delivery note / receipt scan

---

## Stage 6 — Material Inspection

After delivery, the Inspector verifies quality and quantity.

### Document number format

`MI-YYYY-0001`

### Fields

| Field | Notes |
|---|---|
| `supplier_receiving_id` | FK to the delivery record |
| `project_id` | |
| `boq_item_id` | |
| `quantity_delivered` | Matches delivery record |
| `quantity_accepted` | ≤ quantity_delivered |
| `overall_condition` | `excellent` `good` `acceptable` `poor` `rejected` |
| `criteria_checklist` | JSON array of pass/fail checks |
| `inspection_notes` | |

**Auto-calculated:**
- `quantity_rejected = quantity_delivered - quantity_accepted`
- `overall_result`: `pass` (all accepted) / `conditional` (partial) / `fail` (none accepted)

### Approval flow (RingleSoft, flow ID 19)

```
draft (inspector fills form)
   │ inspector submits
   ▼
pending ──── MD/CEO approves ──▶ APPROVED ✓
```

`onApprovalCompleted()`:
1. Sets `status = APPROVED`
2. Calls `updateStock()` — adds `quantity_accepted` to `ProjectMaterialInventory`, creates `ProjectMaterialMovement` record
3. Increments `boq_item.quantity_received`
4. Updates `SupplierReceiving.status = inspected`

---

## Stage 7 — Finance Payment

Once the PO is approved and delivery confirmed, Finance uploads proof of payment.

### Payment fields

| Field | Notes |
|---|---|
| `payment_status` | `pending` → `paid` |
| `payment_date` | |
| `payment_reference` | Bank reference number |
| `payment_attachment` | Scan of bank transfer / receipt |
| `payment_note` | Optional note |
| `payment_uploaded_by` | FK → user |

**Route:** `POST /procurement/purchase-orders/{id}/close`

---

## Stage 8 — Material Transfer

After inspection, if materials need to be moved between sites:

### Document number format

`MT-YYYY-0001`

**Approval flow:** RingleSoft (flow ID 23)

**Route:** `GET /material_transfers` → `index()`

---

## Status Summary

| Stage | Model | Final Status |
|---|---|---|
| Material Request | `ProjectMaterialRequest` | `APPROVED` |
| Quotation Comparison | `QuotationComparison` | `approved` |
| Purchase Order | `Purchase` | `APPROVED` |
| Material Inspection | `MaterialInspection` | `APPROVED` |

---

## HTTP Routes

| Method | URI | Route Name | Controller |
|---|---|---|---|
| GET | `/project_material_requests` | `project_material_requests` | `ProjectMaterialRequestController@index` |
| POST | `/project_material_request/bulk/{project_id}` | `project_material_request.bulk` | `storeBulk()` |
| GET/POST | `/project_material_requests/{id}/request` | `project_material_request` | `request()` |
| GET | `/quotation_comparisons` | `quotation_comparisons` | `QuotationComparisonController@index` |
| GET | `/quotation_comparisons/{id}/create` | — | `create()` |
| POST | `/quotation_comparisons` | — | `store()` |
| GET/POST | `/quotation_comparison/{id}/{dt}` | `quotation_comparison` | `comparison()` |
| POST | `/quotation_comparisons/{id}/create-purchase` | — | `createPurchase()` |
| GET/POST | `/purchases` | `purchases` | `PurchaseController@index` |
| POST | `/purchases/{purchase}/submit` | `purchase.submit` | `submit()` |
| POST | `/purchases/{purchase}/approve` | `purchase.approve` | `approve()` |
| POST | `/purchases/{purchase}/reject` | `purchase.reject` | `reject()` |
| GET | `/procurement/purchase-orders/{id}/record-delivery` | — | `recordDelivery()` |
| POST | `/procurement/purchase-orders/{id}/store-delivery` | — | `storeDelivery()` |
| GET | `/material_inspections` | — | `MaterialInspectionController@index` |
| POST | `/material_inspections` | — | `store()` |
| POST | `/material_inspections/{id}/submit` | — | `submit()` |
| GET | `/material_transfers` | — | `MaterialTransferController@index` |
| POST | `/material_transfers` | — | `store()` |

---

## Database Tables (key columns)

### `project_material_requests`

`id`, `request_number`, `project_id`, `requester_id`, `approved_by`, `status`, `required_date`, `priority`, `purpose`

### `project_material_request_items`

`id`, `material_request_id`, `boq_item_id`, `quantity_requested`, `quantity_approved`, `unit`, `description`

### `supplier_quotations`

`id`, `material_request_id`, `supplier_id`, `grand_total`, `status`

### `quotation_comparisons`

`id`, `comparison_number`, `material_request_id`, `selected_quotation_id`, `recommended_supplier_id`, `recommendation_reason`, `prepared_by`, `status`

### `purchases`

`id`, `document_number`, `supplier_id`, `project_id`, `material_request_id`, `quotation_comparison_id`, `total_amount`, `status`, `payment_status`, `payment_date`, `payment_reference`, `payment_attachment`

### `supplier_receivings`

`id`, `purchase_id`, `quantity_delivered`, `delivery_date`, `status`

### `material_inspections`

`id`, `inspection_number`, `supplier_receiving_id`, `project_id`, `boq_item_id`, `quantity_delivered`, `quantity_accepted`, `quantity_rejected`, `overall_condition`, `overall_result`, `criteria_checklist`, `status`, `stock_updated`

### `material_transfers`

`id`, `transfer_number`, `from_project_id`, `to_project_id`, `boq_item_id`, `quantity`, `status`

---

## Key Files

```
app/Models/ProjectMaterialRequest.php          MR model — onApprovalCompleted updates BOQ quantities
app/Models/ProjectMaterialRequestItem.php      Line item model
app/Models/SupplierQuotation.php               Supplier quotation
app/Models/QuotationComparison.php             Comparison + selection + RingleSoft approval
app/Models/Purchase.php                        PO — createFromComparison(), payment fields
app/Models/SupplierReceiving.php               Delivery recording
app/Models/MaterialInspection.php              Inspection + updateStock() — auto stock update on approval
app/Models/MaterialTransfer.php                Inter-site transfer

app/Http/Controllers/ProjectMaterialRequestController.php
app/Http/Controllers/QuotationComparisonController.php
app/Http/Controllers/PurchaseController.php
app/Http/Controllers/MaterialInspectionController.php
app/Http/Controllers/MaterialTransferController.php
```

---

## RingleSoft Flows

| Flow Name | ID | Approver |
|---|---|---|
| Material Request Approval | 17 | Managing Director |
| Quotation Comparison Approval | 18 | Managing Director |
| Material Inspection Approval | 19 | Managing Director |
| Material Transfer Approval | 23 | Managing Director |

---

## Related Workflows

- **Upstream:** [BOQ Preparation](boq-workflow.md) — items must exist in BOQ before being requested
- **Downstream:** Material inventory is updated; BOQ `quantity_received` advances toward `quantity`
