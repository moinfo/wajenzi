# Project BOQ ↔ Procurement Flow Connection

## Current Architecture Understanding

Your ERP has a sophisticated **BOQ Template System** that defines materials needed for construction. The procurement process should be **driven by the Project BOQ**, not independent of it.

---

## Existing Model Hierarchy

### BOQ Template Structure (Reusable Templates)

```
BoqTemplate (e.g., "3 Bedroom House - Type A")
│   ├── name, description
│   ├── building_type_id → BuildingType
│   ├── roof_type, no_of_rooms
│   └── square_metre, run_metre
│
└── BoqTemplateStage (Construction Stages)
    │   ├── construction_stage_id → ConstructionStage
    │   └── sort_order
    │
    └── BoqTemplateActivity (Activities per Stage)
        │   └── BoqTemplateSubActivity (Sub-Activities)
        │       │
        │       └── SubActivityMaterial ←──────┐
        │           ├── sub_activity_id        │
        │           ├── boq_item_id ───────────┼→ BoqTemplateItem (MATERIAL)
        │           └── quantity               │      ├── name
        │                                      │      ├── description
        └──────────────────────────────────────┘      ├── unit
                                                      ├── base_price
                                                      └── category_id → BoqItemCategory
```

### Project Structure (Actual Project Instance)

```
Project
│   ├── project_name, description
│   ├── client_id → ProjectClient
│   ├── contract_value
│   └── status (CREATED → PENDING → APPROVED)
│
├── ProjectBoq (Bill of Quantities for this project)
│   │   ├── project_id
│   │   ├── version, type
│   │   ├── total_amount
│   │   └── status
│   │
│   └── ProjectBoqItem (Materials needed)
│       ├── boq_id
│       ├── description
│       ├── quantity (TOTAL NEEDED)
│       ├── unit
│       ├── unit_price
│       └── total_price
│
├── ProjectConstructionPhase (Phases of work)
│   ├── phase_name
│   ├── start_date, end_date
│   └── status
│
├── ProjectMaterialRequest (CURRENT - disconnected!)
│   ├── project_id ✅
│   ├── requester_id ✅
│   ├── status ✅
│   └── ❌ NO LINK TO BOQ ITEM
│
└── ProjectMaterialInventory (Stock at site)
    ├── project_id ✅
    ├── material_id → ProjectMaterial
    └── quantity (CURRENT STOCK)
```

---

## The Missing Connection

### Current Problem

```
ProjectBoqItem (What we NEED)     ProjectMaterialRequest (What we REQUEST)
      ↓                                    ↓
   [NO LINK]  ─────────────────────── [NO LINK]
      ↓                                    ↓
   DISCONNECTED!                      DISCONNECTED!
```

**Result:** Material requests are created independently without knowing:
- Which BOQ item is being requested
- How much of the BOQ quantity has already been requested/received
- Whether the request exceeds the BOQ allocation

---

## Correct Flow: BOQ-Driven Procurement

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        BOQ-DRIVEN PROCUREMENT FLOW                          │
└─────────────────────────────────────────────────────────────────────────────┘

  PROJECT
     │
     ├──→ ProjectBoq (approved budget)
     │         │
     │         └──→ ProjectBoqItem (materials & quantities needed)
     │                   │
     │                   │ ┌─────────────────────────────────┐
     │                   │ │ BOQ Item: Cement                │
     │                   │ │ Quantity Needed: 500 bags       │
     │                   │ │ Unit Price: 25,000 TZS          │
     │                   │ │ Total: 12,500,000 TZS           │
     │                   │ └─────────────────────────────────┘
     │                   │
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │            MATERIAL REQUEST (Step 1)                         │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ project_id → Project                                    │ │
     │    │  │ boq_item_id → ProjectBoqItem ← NEW CONNECTION!          │ │
     │    │  │ construction_phase_id → ProjectConstructionPhase (opt)  │ │
     │    │  │ quantity_requested: 100 bags                            │ │
     │    │  │ required_date: 2026-02-15                                │ │
     │    │  │ requester_id → User (Site Supervisor)                   │ │
     │    │  │ purpose: "Foundation work - Phase 1"                    │ │
     │    │  │ status: PENDING → APPROVED                              │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │                   │
     │                   │ (After MD Approval)
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │         SUPPLIER QUOTATION REQUEST (Step 4)                  │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ material_request_id → ProjectMaterialRequest            │ │
     │    │  │ rfq_number: "RFQ-2026-0045"                              │ │
     │    │  │ items: (from material request)                          │ │
     │    │  │ requested_suppliers: [Supplier A, B, C]                  │ │
     │    │  │ deadline: 2026-02-10                                     │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │                   │
     │                   │ (Suppliers respond)
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │         QUOTATION COMPARISON (Step 5)                        │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ Supplier A: 24,500 TZS/bag, 3 days delivery             │ │
     │    │  │ Supplier B: 25,000 TZS/bag, 2 days delivery             │ │
     │    │  │ Supplier C: 23,800 TZS/bag, 5 days delivery ← SELECTED  │ │
     │    │  │                                                          │ │
     │    │  │ Recommendation: Supplier C (best price, acceptable time) │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │                   │
     │                   │ (MD Approves Supplier)
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │            PURCHASE ORDER (Step 6)                           │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ material_request_id → ProjectMaterialRequest            │ │
     │    │  │ quotation_comparison_id → QuotationComparison           │ │
     │    │  │ supplier_id → Supplier C                                 │ │
     │    │  │ items: 100 bags cement @ 23,800                          │ │
     │    │  │ total: 2,380,000 TZS                                     │ │
     │    │  │ status: APPROVED                                         │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │                   │
     │                   │ (Supplier delivers)
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │         DELIVERY & RECEIVING (Step 7)                        │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ purchase_id → Purchase                                   │ │
     │    │  │ project_id → Project                                     │ │
     │    │  │ delivery_note_number: "DN-2026-0089"                      │ │
     │    │  │ quantity_ordered: 100 bags                               │ │
     │    │  │ quantity_delivered: 98 bags                              │ │
     │    │  │ condition: GOOD (96), DAMAGED (2)                        │ │
     │    │  │ signatures: supplier ✓, supervisor ✓, technician ✓       │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │                   │
     │                   │ (Inspection completed)
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │          INSPECTION RECORD (Step 8)                          │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ quantity_delivered: 98 bags                              │ │
     │    │  │ quantity_accepted: 96 bags                               │ │
     │    │  │ quantity_rejected: 2 bags (damaged)                      │ │
     │    │  │ result: PARTIALLY ACCEPTED                               │ │
     │    │  │ inspector_signature ✓, verifier_signature ✓              │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │                   │
     │                   │ (Auto-update on inspection approval)
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │         STOCK UPDATE (Step 9)                                │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ ProjectMaterialInventory                                 │ │
     │    │  │ project_id → Project                                     │ │
     │    │  │ boq_item_id → ProjectBoqItem (Cement)                    │ │
     │    │  │ quantity: +96 bags                                       │ │
     │    │  │                                                          │ │
     │    │  │ Movement Record:                                         │ │
     │    │  │ type: RECEIVED, ref: Inspection #123, qty: 96            │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │                   │
     │                   ▼
     │    ┌──────────────────────────────────────────────────────────────┐
     │    │         BOQ TRACKING DASHBOARD                               │
     │    │  ┌─────────────────────────────────────────────────────────┐ │
     │    │  │ Cement (BOQ Item)                                        │ │
     │    │  │ ├── BOQ Quantity:     500 bags                           │ │
     │    │  │ ├── Requested:        100 bags (this request)            │ │
     │    │  │ ├── Received:          96 bags                           │ │
     │    │  │ ├── Used/Issued:        0 bags                           │ │
     │    │  │ ├── In Stock:          96 bags                           │ │
     │    │  │ └── Remaining to Order: 404 bags                         │ │
     │    │  │                                                          │ │
     │    │  │ Budget Status:                                           │ │
     │    │  │ ├── BOQ Budget:    12,500,000 TZS                        │ │
     │    │  │ ├── Spent:          2,284,800 TZS (96 × 23,800)          │ │
     │    │  │ └── Remaining:     10,215,200 TZS                        │ │
     │    │  └─────────────────────────────────────────────────────────┘ │
     │    └──────────────────────────────────────────────────────────────┘
     │
     └──→ Project Completion (all BOQ items fulfilled)
```

---

## Required Model Changes

### 1. ProjectMaterialRequest Enhancement

**Current:**
```php
protected $fillable = [
    'project_id',
    'requester_id',
    'status',
    'approved_date'
];
```

**Proposed:**
```php
protected $fillable = [
    'project_id',
    'boq_item_id',              // NEW: Link to BOQ item
    'construction_phase_id',     // NEW: Optional phase reference
    'request_number',            // NEW: Auto-generated reference
    'quantity_requested',        // NEW: How much needed
    'quantity_approved',         // NEW: MD may adjust
    'unit',                      // NEW: Unit of measure
    'required_date',             // NEW: When needed
    'purpose',                   // NEW: Justification
    'priority',                  // NEW: NORMAL/URGENT
    'requester_id',
    'approved_by',               // NEW: Who approved
    'approved_date',
    'status',                    // DRAFT → PENDING → APPROVED → ORDERED → RECEIVED
    'notes'                      // NEW: Additional notes
];

// Relationships
public function boqItem(): BelongsTo
{
    return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
}

public function constructionPhase(): BelongsTo
{
    return $this->belongsTo(ProjectConstructionPhase::class, 'construction_phase_id');
}

// Calculate remaining quantity from BOQ
public function getRemainingBoqQuantityAttribute()
{
    $boqQuantity = $this->boqItem->quantity;
    $totalRequested = ProjectMaterialRequest::where('boq_item_id', $this->boq_item_id)
        ->where('status', '!=', 'REJECTED')
        ->sum('quantity_approved');
    return $boqQuantity - $totalRequested;
}
```

### 2. ProjectMaterialInventory Enhancement

**Current:**
```php
protected $fillable = [
    'project_id',
    'material_id',  // Links to ProjectMaterial
    'quantity',
];
```

**Proposed:**
```php
protected $fillable = [
    'project_id',
    'boq_item_id',          // NEW: Link to BOQ item instead
    'quantity_in_stock',    // Renamed for clarity
    'quantity_used',        // NEW: Track usage
    'last_updated_at',      // NEW: Timestamp
];

// Relationships
public function boqItem(): BelongsTo
{
    return $this->belongsTo(ProjectBoqItem::class, 'boq_item_id');
}

// Calculate BOQ fulfillment percentage
public function getFulfillmentPercentageAttribute()
{
    $boqQuantity = $this->boqItem->quantity;
    $received = $this->quantity_in_stock + $this->quantity_used;
    return round(($received / $boqQuantity) * 100, 2);
}
```

### 3. ProjectBoqItem Enhancement

**Current:**
```php
protected $fillable = [
    'boq_id',
    'description',
    'quantity',
    'unit',
    'unit_price',
    'total_price'
];
```

**Proposed:**
```php
protected $fillable = [
    'boq_id',
    'boq_template_item_id',  // NEW: Link to template item (catalog)
    'category_id',           // NEW: BoqItemCategory reference
    'item_code',             // NEW: For easy identification
    'description',
    'specification',         // NEW: Technical specs
    'quantity',              // Total needed
    'unit',
    'unit_price',
    'total_price',
    'quantity_requested',    // NEW: Tracking field
    'quantity_received',     // NEW: Tracking field
    'quantity_used',         // NEW: Tracking field
    'status'                 // NEW: NOT_STARTED → IN_PROGRESS → COMPLETE
];

// Relationships
public function materialRequests(): HasMany
{
    return $this->hasMany(ProjectMaterialRequest::class, 'boq_item_id');
}

public function inventory(): HasOne
{
    return $this->hasOne(ProjectMaterialInventory::class, 'boq_item_id');
}

// Status helpers
public function getProcurementStatusAttribute()
{
    $pct = $this->quantity > 0
        ? ($this->quantity_received / $this->quantity) * 100
        : 0;

    if ($pct == 0) return 'NOT_STARTED';
    if ($pct < 100) return 'IN_PROGRESS';
    return 'COMPLETE';
}
```

### 4. Purchase Enhancement

**Current:**
```php
public $fillable = [
    'id', 'supplier_id', 'is_expense', 'item_id',
    'tax_invoice', 'invoice_date', 'create_by_id',
    'total_amount', 'amount_vat_exc', 'vat_amount',
    'purchase_type', 'file', 'date', 'status', 'document_number'
];
```

**Proposed:**
```php
public $fillable = [
    'project_id',                // NEW: Link to project
    'material_request_id',       // NEW: Link to request
    'quotation_comparison_id',   // NEW: Link to comparison
    'supplier_id',
    'document_number',           // PO number
    'date',
    'expected_delivery_date',    // NEW
    'total_amount',
    'amount_vat_exc',
    'vat_amount',
    'payment_terms',             // NEW
    'delivery_address',          // NEW: Site address
    'status',                    // PENDING → APPROVED → ORDERED → DELIVERED
    'file',
    'notes',                     // NEW
    'create_by_id',
];

// Line items relationship (NEW)
public function items(): HasMany
{
    return $this->hasMany(PurchaseItem::class);
}

public function materialRequest(): BelongsTo
{
    return $this->belongsTo(ProjectMaterialRequest::class, 'material_request_id');
}

public function project(): BelongsTo
{
    return $this->belongsTo(Project::class);
}
```

---

## New Models Required

### 1. PurchaseItem (Line Items)

```php
class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'boq_item_id',      // Link to what BOQ item this fulfills
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total_price',
        'vat_amount',
    ];
}
```

### 2. SupplierQuotation

```php
class SupplierQuotation extends Model
{
    protected $fillable = [
        'material_request_id',
        'supplier_id',
        'quotation_number',
        'quotation_date',
        'valid_until',
        'delivery_time_days',
        'payment_terms',
        'total_amount',
        'vat_amount',
        'file',             // Attached quotation document
        'status',           // RECEIVED → SELECTED → REJECTED
        'notes'
    ];
}
```

### 3. QuotationComparison

```php
class QuotationComparison extends Model
{
    protected $fillable = [
        'material_request_id',
        'comparison_number',
        'comparison_date',
        'recommended_supplier_id',
        'recommendation_reason',
        'prepared_by',
        'approved_by',
        'approved_date',
        'status',           // DRAFT → PENDING → APPROVED
    ];

    public function quotations(): HasMany
    {
        return $this->hasMany(SupplierQuotation::class, 'comparison_id');
    }
}
```

### 4. MaterialInspection

```php
class MaterialInspection extends Model
{
    protected $fillable = [
        'supplier_receiving_id',
        'project_id',
        'boq_item_id',
        'inspection_number',
        'inspection_date',
        'quantity_delivered',
        'quantity_accepted',
        'quantity_rejected',
        'overall_condition',    // GOOD → PARTIAL → DAMAGED
        'overall_result',       // ACCEPTED → PARTIAL → REJECTED
        'rejection_reason',
        'inspector_id',
        'verifier_id',
        'stock_updated',        // Boolean flag
    ];
}
```

---

## Database Schema Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           PROJECT BOQ PROCUREMENT SCHEMA                         │
└─────────────────────────────────────────────────────────────────────────────────┘

┌──────────────┐       ┌──────────────┐       ┌────────────────────┐
│   Project    │       │  ProjectBoq  │       │  ProjectBoqItem    │
├──────────────┤       ├──────────────┤       ├────────────────────┤
│ id           │──┐    │ id           │──┐    │ id                 │
│ project_name │  │    │ project_id   │◄─┘    │ boq_id             │◄──┐
│ client_id    │  │    │ version      │       │ description        │   │
│ status       │  │    │ total_amount │       │ quantity           │   │
└──────────────┘  │    │ status       │       │ unit               │   │
                  │    └──────────────┘       │ unit_price         │   │
                  │                           │ quantity_requested │   │
                  │                           │ quantity_received  │   │
                  │                           └────────────────────┘   │
                  │                                     │              │
                  │           ┌─────────────────────────┼──────────────┤
                  │           │                         │              │
                  │           ▼                         ▼              │
                  │    ┌────────────────────┐   ┌──────────────────────┴───┐
                  │    │ ProjectMaterial    │   │ ProjectMaterialRequest   │
                  │    │ Request            │   ├──────────────────────────┤
                  └───►├────────────────────┤   │ id                       │
                       │ project_id         │   │ project_id               │
                       │ boq_item_id        │◄──┤ boq_item_id   ──────────►│
                       │ quantity_requested │   │ construction_phase_id    │
                       │ required_date      │   │ quantity_requested       │
                       │ status             │   │ status                   │
                       └────────────────────┘   └──────────────────────────┘
                                │                          │
                                │                          │
                                ▼                          ▼
                ┌───────────────────────────┐    ┌────────────────────────┐
                │   SupplierQuotation       │    │  QuotationComparison   │
                ├───────────────────────────┤    ├────────────────────────┤
                │ material_request_id       │◄───┤ material_request_id    │
                │ supplier_id               │    │ recommended_supplier_id│
                │ quotation_number          │    │ status                 │
                │ total_amount              │    └────────────────────────┘
                │ delivery_time_days        │              │
                │ status                    │              │
                └───────────────────────────┘              │
                                                          ▼
                                              ┌────────────────────────┐
                                              │      Purchase          │
                                              ├────────────────────────┤
                                              │ project_id             │
                                              │ material_request_id    │
                                              │ quotation_comparison_id│
                                              │ supplier_id            │
                                              │ status                 │
                                              └────────────────────────┘
                                                          │
                                                          ▼
                                              ┌────────────────────────┐
                                              │   SupplierReceiving    │
                                              ├────────────────────────┤
                                              │ purchase_id            │
                                              │ project_id             │
                                              │ delivery_note_number   │
                                              │ quantity_delivered     │
                                              └────────────────────────┘
                                                          │
                                                          ▼
                                              ┌────────────────────────┐
                                              │  MaterialInspection    │
                                              ├────────────────────────┤
                                              │ supplier_receiving_id  │
                                              │ boq_item_id            │
                                              │ quantity_accepted      │
                                              │ quantity_rejected      │
                                              │ overall_result         │
                                              └────────────────────────┘
                                                          │
                                                          │ (auto-update)
                                                          ▼
                                              ┌────────────────────────┐
                                              │ProjectMaterialInventory│
                                              ├────────────────────────┤
                                              │ project_id             │
                                              │ boq_item_id            │
                                              │ quantity_in_stock      │
                                              │ quantity_used          │
                                              └────────────────────────┘
```

---

## Key Benefits of BOQ-Linked Procurement

### 1. Budget Control
- Material requests cannot exceed BOQ quantities
- Real-time tracking of BOQ budget consumption
- Alert when approaching BOQ limits

### 2. Progress Tracking
- Know exactly what % of BOQ materials are procured
- Track per-item fulfillment status
- Identify procurement bottlenecks

### 3. Construction Phase Alignment
- Link requests to specific construction phases
- Ensure materials arrive before phase starts
- Schedule deliveries based on construction timeline

### 4. Variance Analysis
- Compare actual prices vs BOQ estimates
- Track quantity variances (requested vs received)
- Identify savings or overruns per item

### 5. Audit Trail
- Complete traceability: BOQ → Request → Quote → PO → Delivery → Inspection → Stock
- All documents linked to original BOQ item
- Easy reconciliation for project closeout

---

## Implementation Priority

### Phase 1: Core Linkages (Week 1-2)
1. Add `boq_item_id` to `ProjectMaterialRequest`
2. Add `boq_item_id` to `ProjectMaterialInventory`
3. Add tracking fields to `ProjectBoqItem`
4. Create migration scripts

### Phase 2: Request Workflow (Week 3)
1. Update request form to select BOQ item
2. Show remaining BOQ quantity
3. Validate request doesn't exceed BOQ
4. Show BOQ budget impact

### Phase 3: Quotation System (Week 4-5)
1. Create `SupplierQuotation` model
2. Create `QuotationComparison` model
3. Link to material request
4. Build comparison UI

### Phase 4: Full Integration (Week 6-8)
1. Link Purchase to request and comparison
2. Link SupplierReceiving to Purchase and Project
3. Create MaterialInspection with BOQ link
4. Auto-update inventory from inspection
5. Build BOQ tracking dashboard

---

## Summary

The key insight is that **procurement should be BOQ-driven**:

```
WITHOUT BOQ LINK:                    WITH BOQ LINK:
─────────────────                    ─────────────────
Request: "100 bags cement"           Request: "100 bags from BOQ Item #45"
                                              ↓
Problem: No context                  Context: BOQ allows 500 bags
         No budget check                      Already ordered 200
         No tracking                          Remaining: 300
                                              Budget: Within limit ✓
```

This ensures:
- ✅ Requests are justified by BOQ
- ✅ Budget is controlled
- ✅ Progress is trackable
- ✅ Materials align with construction phases
- ✅ Complete audit trail exists