# Wajenzi ERP Procurement Implementation Analysis

## Overview

This document analyzes how well the current Wajenzi ERP system implements the Company Procurement Process SOP. Each SOP step is evaluated against existing models, controllers, and functionality.

---

## Summary Matrix

| SOP Step | ERP Status | Compatibility | Action Required |
|----------|------------|---------------|-----------------|
| 1. Purchase Request | ⚠️ Partial | 60% | Enhancement needed |
| 2. Management Approval | ✅ Implemented | 90% | Minor improvements |
| 3. Feedback | ✅ Implemented | 85% | Notification enhancement |
| 4. Supplier Sourcing | ⚠️ Partial | 40% | **Major gap** |
| 5. Quotation Evaluation | ❌ Missing | 10% | **New module needed** |
| 6. Supplier Approval | ⚠️ Partial | 50% | Enhancement needed |
| 7. Delivery to Site | ⚠️ Partial | 55% | Enhancement needed |
| 8. Inspection & Verification | ❌ Missing | 5% | **New module needed** |
| 9. Stock Update | ⚠️ Partial | 65% | Enhancement needed |

**Legend:**
- ✅ Implemented = 80%+ compatible
- ⚠️ Partial = 40-79% compatible
- ❌ Missing = Below 40% compatible

---

## Detailed Analysis by SOP Step

### Step 1: Purchase Request

**SOP Requirement:**
> Site Supervisor submits a material request specifying type, quantity, and purpose.

**Current ERP Implementation:**

| Component | File | Status |
|-----------|------|--------|
| Model | `ProjectMaterialRequest.php` | ✅ Exists |
| Controller | `ProjectMaterialRequestController.php` | ✅ Exists |
| View | `pages/projects/project_material_requests.blade.php` | ✅ Exists |

**Model Analysis (`ProjectMaterialRequest.php`):**
```php
protected $fillable = [
    'project_id',
    'requester_id',
    'status',
    'approved_date'
];
```

**Gaps Identified:**

| SOP Field | ERP Field | Status |
|-----------|-----------|--------|
| Material type | ❌ Missing | Need `material_id` or items relationship |
| Quantity | ❌ Missing | Need `quantity` field |
| Purpose/justification | ❌ Missing | Need `purpose` or `description` field |
| Required delivery date | ❌ Missing | Need `required_date` field |
| Site/Project | `project_id` ✅ | Exists |
| Requester | `requester_id` ✅ | Exists |
| Status | `status` ✅ | Exists |

**Compatibility Score: 60%**

**Recommendations:**
1. Add `quantity`, `description`, `required_date` fields to migration
2. Create relationship to `ProjectMaterial` or items
3. Add line items capability (multiple materials per request)

---

### Step 2: Management Approval

**SOP Requirement:**
> Request is forwarded to the Managing Director for approval, adjustment, or rejection.

**Current ERP Implementation:**

| Component | File | Status |
|-----------|------|--------|
| Approval Model | `Approval.php` | ✅ Exists |
| Approval Levels | `ApprovalLevel.php` | ✅ Exists |
| Process Flow | `ProcessApprovalFlow.php` | ✅ Exists |
| Service | `ApprovalService.php` | ✅ Exists |

**Strengths:**
- Multi-level approval workflow via RingleSoft package
- Approval levels configurable per document type
- User group-based approval routing
- Status tracking (PENDING → APPROVED/REJECTED)

**Controller Support (`ProjectMaterialRequestController.php`):**
```php
public function request($id, $document_type_id){
    $approvalStages = Approval::getApprovalStages($id, $document_type_id);
    $nextApproval = Approval::getNextApproval($id, $document_type_id);
    $approvalCompleted = Approval::isApprovalCompleted($id, $document_type_id);
    $rejected = Approval::isRejected($id, $document_type_id);
    // ...
}
```

**Gaps Identified:**
- No "adjustment" option (only approve/reject)
- MD role not explicitly enforced in material requests

**Compatibility Score: 90%**

**Recommendations:**
1. Add "ADJUSTED" status option with modified quantities
2. Configure approval flow to include MD as mandatory approver

---

### Step 3: Feedback

**SOP Requirement:**
> Approved or adjusted request is communicated back to the Site Supervisor.

**Current ERP Implementation:**

| Component | File | Status |
|-----------|------|--------|
| Notification Model | `Notification.php` | ✅ Exists |
| Approval Service | `ApprovalService.php` | ✅ Exists |

**Strengths:**
- Notification system exists
- Notifications linked to approval workflow
- `markNotificationAsRead()` functionality

**Gaps Identified:**
- No automatic notification on approval completion
- No email notification to requester
- Feedback loop not explicitly implemented

**Compatibility Score: 85%**

**Recommendations:**
1. Add event listener for approval completion → notify requester
2. Implement email notification for status changes
3. Add in-app notification to Site Supervisor dashboard

---

### Step 4: Supplier Sourcing

**SOP Requirement:**
> At least three (3) approved suppliers are requested to submit quotations.

**Current ERP Implementation:**

| Component | File | Status |
|-----------|------|--------|
| Supplier Model | `Supplier.php` | ✅ Exists |
| Supplier Controller | `SupplierController.php` | ✅ Exists |
| Supplier Contacts | `SupplierContact.php` | ✅ Exists |

**Supplier Model Fields:**
```php
public $fillable = ['id', 'name', 'phone', 'address', 'email', 'vrn',
    'supplier', 'system_id', 'account_name', 'nmb_account', 'nbc_account', 'crdb_account'];
```

**Gaps Identified:**

| SOP Requirement | ERP Status | Notes |
|-----------------|------------|-------|
| Request for Quotation (RFQ) | ❌ Missing | No RFQ generation system |
| Minimum 3 suppliers rule | ❌ Missing | No enforcement |
| Quotation collection | ❌ Missing | No inbound quotation storage |
| Supplier approval status | ❌ Missing | No `is_approved` field |
| Supplier categories | ❌ Missing | No material-type categorization |

**Compatibility Score: 40%**

**Recommendations:**
1. Create `SupplierQuotation` model to store received quotations
2. Create `RequestForQuotation` (RFQ) model with line items
3. Add `is_approved`, `approval_date`, `category_id` to Supplier
4. Implement RFQ email/PDF generation
5. Add minimum supplier validation (≥3 quotations required)

---

### Step 5: Evaluation & Recommendation

**SOP Requirement:**
> Quotations are compared based on price, quality, and reliability. A recommendation is made to the MD.

**Current ERP Implementation:**

| Component | Status | Notes |
|-----------|--------|-------|
| Quotation Comparison Model | ❌ Missing | No dedicated model |
| Comparison Sheet Generation | ❌ Missing | Manual process only |
| Supplier Rating System | ❌ Missing | No quality/reliability tracking |

**Existing Related System (Billing Module):**
The ERP has a `QuotationController` in the Billing module, but this is for **outbound quotations to clients**, not **inbound quotations from suppliers**.

```php
// This is for CLIENT quotations, not SUPPLIER quotations
class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $quotations = BillingDocument::with(['client', 'creator'])
            ->where('document_type', 'quote')
            // ...
    }
}
```

**Gaps Identified:**

| SOP Requirement | ERP Status |
|-----------------|------------|
| Store multiple supplier quotes | ❌ Missing |
| Compare prices side-by-side | ❌ Missing |
| Evaluate delivery time | ❌ Missing |
| Track material quality rating | ❌ Missing |
| Payment terms comparison | ❌ Missing |
| Recommendation with justification | ❌ Missing |
| Generate comparison sheet PDF | ❌ Missing |

**Compatibility Score: 10%**

**Recommendations:**
1. **Create new models:**
   - `SupplierQuotation` - Store incoming quotations
   - `QuotationComparison` - Group quotations for evaluation
   - `QuotationComparisonItem` - Line item comparison
   - `SupplierRating` - Track supplier performance

2. **Create new controller:**
   - `SupplierQuotationController` - Manage supplier quotes
   - `QuotationComparisonController` - Comparison workflow

3. **Implement features:**
   - Side-by-side comparison view
   - Auto-calculate price differences
   - Recommendation form with justification
   - PDF comparison sheet generation
   - Minimum 3-quote validation before proceeding

---

### Step 6: Supplier Approval and Purchase

**SOP Requirement:**
> MD approves final supplier and purchase is executed.

**Current ERP Implementation:**

| Component | File | Status |
|-----------|------|--------|
| Purchase Model | `Purchase.php` | ✅ Exists |
| Purchase Controller | `PurchaseController.php` | ✅ Exists |
| Approval Integration | `Approvable` trait | ✅ Exists |

**Purchase Model Strengths:**
```php
class Purchase extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    public $fillable = ['id', 'supplier_id', 'is_expense', 'item_id',
        'tax_invoice', 'invoice_date', 'create_by_id', 'total_amount',
        'amount_vat_exc', 'vat_amount', 'purchase_type', 'file', 'date',
        'status', 'document_number'];

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->save();
        return true;
    }
}
```

**Gaps Identified:**

| SOP Requirement | ERP Status | Notes |
|-----------------|------------|-------|
| Link to quotation comparison | ❌ Missing | No `quotation_comparison_id` |
| Link to material request | ❌ Missing | No `material_request_id` |
| Multiple line items | ❌ Missing | Single `item_id` only |
| Purchase Order PDF | ❌ Unknown | Need to verify |
| MD approval enforced | ⚠️ Partial | Configurable but not enforced |

**Compatibility Score: 50%**

**Recommendations:**
1. Add `quotation_comparison_id` foreign key
2. Add `material_request_id` foreign key
3. Create `PurchaseItem` model for line items
4. Generate Purchase Order PDF
5. Configure approval flow to require MD

---

### Step 7: Delivery to Site

**SOP Requirement:**
> Materials are delivered to the respective site. Delivery Note signed by supplier, Site Supervisor, and Fundi.

**Current ERP Implementation:**

| Component | File | Status |
|-----------|------|--------|
| Supplier Receiving | `SupplierReceiving.php` | ✅ Exists |
| Controller | `SupplierReceivingController.php` | ✅ Exists |

**SupplierReceiving Model:**
```php
public $fillable = ['id', 'supplier_id', 'amount', 'date', 'description', 'file'];
```

**Gaps Identified:**

| SOP Requirement | ERP Field | Status |
|-----------------|-----------|--------|
| Delivery Note No | ❌ Missing | Need `delivery_note_number` |
| Purchase Order reference | ❌ Missing | Need `purchase_id` |
| Project/Site reference | ❌ Missing | Need `project_id` |
| Quantity ordered vs delivered | ❌ Missing | Need line items |
| Condition (Good/Damaged) | ❌ Missing | Need `condition` field |
| Supplier signature | ❌ Missing | Need `supplier_signature` |
| Site Supervisor signature | ❌ Missing | Need `supervisor_signature` |
| Fundi/Technician signature | ❌ Missing | Need `technician_signature` |
| Delivery Note PDF | ❌ Missing | Need template |

**Compatibility Score: 55%**

**Recommendations:**
1. Enhance `SupplierReceiving` with additional fields
2. Create `SupplierReceivingItem` for line items
3. Add digital signature capture
4. Link to `Purchase` and `Project`
5. Generate Delivery Note PDF

---

### Step 8: Inspection and Verification

**SOP Requirement:**
> Site Supervisor and Fundi inspect and verify quantity and quality. Complete Inspection Record with acceptance status.

**Current ERP Implementation:**

| Component | Status | Notes |
|-----------|--------|-------|
| Inspection Model | ❌ Missing | No dedicated inspection entity |
| Quality Check Fields | ❌ Missing | No condition/quality tracking |
| Acceptance Workflow | ❌ Missing | No accept/reject/partial flow |

**Existing `Receiving` Model (different purpose):**
```php
// This tracks cash receiving, not material inspection
class Receiving extends Model
{
    public $fillable = ['id', 'efd_id', 'description', 'date', 'amount'];
}
```

**Gaps Identified:**

| SOP Requirement | ERP Status |
|-----------------|------------|
| Quantity delivered vs accepted vs rejected | ❌ Missing |
| Condition/Quality status per item | ❌ Missing |
| Inspection result (Accept/Partial/Reject) | ❌ Missing |
| Rejection reason documentation | ❌ Missing |
| Artisan/Technician signature | ❌ Missing |
| Site Supervisor verification | ❌ Missing |
| Photo evidence for damaged items | ❌ Missing |
| Link to Delivery Note | ❌ Missing |

**Compatibility Score: 5%**

**Recommendations:**
1. **Create new models:**
   - `MaterialInspection` - Main inspection record
   - `MaterialInspectionItem` - Line item inspection details

2. **Required fields:**
   ```
   - supplier_receiving_id (FK)
   - project_id (FK)
   - inspection_date
   - overall_result (ACCEPTED/PARTIAL/REJECTED)
   - rejection_reason
   - inspector_id (artisan/technician)
   - verifier_id (site supervisor)
   - inspector_signature
   - verifier_signature
   ```

3. **Line item fields:**
   ```
   - material_id
   - quantity_delivered
   - quantity_accepted
   - quantity_rejected
   - condition (GOOD/DAMAGED/DEFECTIVE)
   - quality_status
   - remarks
   - photos (JSON array)
   ```

4. Create inspection workflow with approval

---

### Step 9: Stock Update

**SOP Requirement:**
> Accepted materials are recorded in the site stock register. Track Opening Stock → Received → Issued → Closing Stock.

**Current ERP Implementation:**

| Component | File | Status |
|-----------|------|--------|
| Stock Model | `Stock.php` | ⚠️ Basic |
| Project Material Inventory | `ProjectMaterialInventory.php` | ⚠️ Basic |
| Material Movement | `ProjectMaterialMovement.php` | ✅ Exists (empty) |

**Stock Model:**
```php
class Stock extends Model
{
    public $fillable = ['id', 'stock_type', 'amount', 'date', 'file'];

    public static function getTotalOpeningStock($start_date, $end_date);
    public static function getTotalClosingStock($start_date, $end_date);
}
```

**ProjectMaterialInventory Model:**
```php
class ProjectMaterialInventory extends Model
{
    protected $fillable = [
        'project_id',
        'material_id',
        'quantity',
    ];
}
```

**Gaps Identified:**

| SOP Requirement | ERP Status | Notes |
|-----------------|------------|-------|
| Opening stock tracking | ⚠️ Basic | `Stock` model has type field |
| Quantity received | ⚠️ Partial | Not linked to inspection |
| Quantity issued | ❌ Missing | No issue tracking |
| Closing stock calculation | ⚠️ Basic | Manual calculation |
| Stock reference number | ❌ Missing | No reference field |
| Per-site stock tracking | ⚠️ Partial | `ProjectMaterialInventory` exists |
| Movement history | ❌ Empty | `ProjectMaterialMovement` model empty |
| Store Keeper signature | ❌ Missing | No signature field |
| Auto-update from inspection | ❌ Missing | No integration |

**Compatibility Score: 65%**

**Recommendations:**
1. Implement `ProjectMaterialMovement` model properly:
   ```php
   - project_id
   - material_id
   - movement_type (RECEIVED/ISSUED/ADJUSTMENT)
   - quantity
   - reference_type (inspection/requisition/adjustment)
   - reference_id
   - performed_by
   - verified_by
   - date
   - notes
   ```

2. Auto-create stock movement on inspection approval
3. Implement stock level alerts (low stock warning)
4. Add stock reference numbering system
5. Create daily stock report generation

---

## Gap Summary

### Critical Gaps (New Modules Needed)

1. **Supplier Quotation Management**
   - Store incoming quotations from suppliers
   - Link quotations to RFQs
   - Track quotation validity periods

2. **Quotation Comparison System**
   - Side-by-side comparison
   - Scoring/recommendation workflow
   - Minimum 3-quote enforcement
   - PDF comparison sheet generation

3. **Material Inspection Module**
   - Inspection record with line items
   - Accept/Partial/Reject workflow
   - Quality condition tracking
   - Photo evidence attachment
   - Signature capture

### Enhancement Gaps (Existing Modules)

| Module | Priority | Effort |
|--------|----------|--------|
| `ProjectMaterialRequest` - Add detail fields | High | Low |
| `Purchase` - Add line items, link to comparison | High | Medium |
| `SupplierReceiving` - Add delivery note fields | High | Medium |
| `Supplier` - Add approval status, categories | Medium | Low |
| `ProjectMaterialMovement` - Implement fully | High | Medium |
| `Stock` - Integrate with inspection | Medium | Medium |

---

## Recommended Implementation Priority

### Phase 1: Foundation (Weeks 1-2)
1. Enhance `ProjectMaterialRequest` model with required fields
2. Enhance `Supplier` model with approval status
3. Implement `ProjectMaterialMovement` properly
4. Configure approval workflows for material requests

### Phase 2: Quotation System (Weeks 3-4)
1. Create `SupplierQuotation` model and controller
2. Create `QuotationComparison` model and controller
3. Implement 3-quote minimum validation
4. Create comparison sheet PDF template

### Phase 3: Receiving & Inspection (Weeks 5-6)
1. Enhance `SupplierReceiving` with delivery note fields
2. Create `MaterialInspection` model and controller
3. Implement inspection workflow with approval
4. Auto-update stock on inspection approval

### Phase 4: Integration & Reporting (Weeks 7-8)
1. Link all modules (Request → Comparison → Purchase → Delivery → Inspection → Stock)
2. Create procurement dashboard
3. Generate procurement reports
4. Implement email notifications throughout workflow

---

## Database Schema Recommendations

### New Tables Required

```sql
-- Supplier Quotations
CREATE TABLE supplier_quotations (
    id BIGINT PRIMARY KEY,
    rfq_id BIGINT,
    supplier_id BIGINT,
    quotation_number VARCHAR(50),
    quotation_date DATE,
    valid_until DATE,
    total_amount DECIMAL(15,2),
    delivery_time VARCHAR(100),
    payment_terms TEXT,
    file VARCHAR(255),
    status ENUM('pending', 'selected', 'rejected'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Quotation Comparison
CREATE TABLE quotation_comparisons (
    id BIGINT PRIMARY KEY,
    material_request_id BIGINT,
    comparison_number VARCHAR(50),
    comparison_date DATE,
    recommended_supplier_id BIGINT,
    recommendation_reason TEXT,
    prepared_by BIGINT,
    approved_by BIGINT,
    approved_date DATETIME,
    status ENUM('draft', 'pending_approval', 'approved', 'rejected'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Quotation Comparison Items
CREATE TABLE quotation_comparison_items (
    id BIGINT PRIMARY KEY,
    comparison_id BIGINT,
    quotation_id BIGINT,
    is_selected BOOLEAN,
    selection_reason TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Material Inspection
CREATE TABLE material_inspections (
    id BIGINT PRIMARY KEY,
    supplier_receiving_id BIGINT,
    project_id BIGINT,
    inspection_number VARCHAR(50),
    inspection_date DATE,
    overall_result ENUM('accepted', 'partial', 'rejected'),
    rejection_reason TEXT,
    inspector_id BIGINT,
    verifier_id BIGINT,
    inspector_signature VARCHAR(255),
    verifier_signature VARCHAR(255),
    stock_updated BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Material Inspection Items
CREATE TABLE material_inspection_items (
    id BIGINT PRIMARY KEY,
    inspection_id BIGINT,
    material_id BIGINT,
    quantity_delivered DECIMAL(10,2),
    quantity_accepted DECIMAL(10,2),
    quantity_rejected DECIMAL(10,2),
    condition ENUM('good', 'damaged', 'defective'),
    quality_status VARCHAR(100),
    remarks TEXT,
    photos JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Table Modifications Required

```sql
-- Enhance project_material_requests
ALTER TABLE project_material_requests ADD COLUMN quantity DECIMAL(10,2);
ALTER TABLE project_material_requests ADD COLUMN description TEXT;
ALTER TABLE project_material_requests ADD COLUMN required_date DATE;
ALTER TABLE project_material_requests ADD COLUMN material_id BIGINT;

-- Enhance suppliers
ALTER TABLE suppliers ADD COLUMN is_approved BOOLEAN DEFAULT FALSE;
ALTER TABLE suppliers ADD COLUMN approved_date DATE;
ALTER TABLE suppliers ADD COLUMN category_id BIGINT;
ALTER TABLE suppliers ADD COLUMN rating DECIMAL(3,2);

-- Enhance purchases
ALTER TABLE purchases ADD COLUMN quotation_comparison_id BIGINT;
ALTER TABLE purchases ADD COLUMN material_request_id BIGINT;

-- Enhance supplier_receivings
ALTER TABLE supplier_receivings ADD COLUMN delivery_note_number VARCHAR(50);
ALTER TABLE supplier_receivings ADD COLUMN purchase_id BIGINT;
ALTER TABLE supplier_receivings ADD COLUMN project_id BIGINT;
ALTER TABLE supplier_receivings ADD COLUMN condition ENUM('good', 'damaged', 'mixed');
ALTER TABLE supplier_receivings ADD COLUMN supplier_signature VARCHAR(255);
ALTER TABLE supplier_receivings ADD COLUMN supervisor_signature VARCHAR(255);
ALTER TABLE supplier_receivings ADD COLUMN technician_signature VARCHAR(255);
```

---

## Conclusion

The current Wajenzi ERP has a **solid foundation** for procurement with:
- ✅ Multi-level approval workflows
- ✅ Supplier management basics
- ✅ Purchase order processing
- ✅ Basic stock tracking

However, **significant gaps exist** in:
- ❌ Quotation collection and comparison (most critical)
- ❌ Material inspection workflow
- ❌ Delivery note management
- ❌ Full traceability from request to stock

Implementing the recommended changes will bring the ERP to **full SOP compliance** and provide:
- Complete audit trail
- Enforced 3-quote rule
- Quality control through inspection
- Real-time stock visibility
- Automated notifications

**Estimated Total Effort: 6-8 weeks** for full implementation
