# Procurement Workflow Test Procedure

## Prerequisites

Before testing, ensure:
- [ ] Migrations have run successfully (`php artisan migrate`)
- [ ] Approval seeder has run (`php artisan db:seed --class=ProcurementApprovalSeeder`)
- [ ] At least one Project with BOQ exists
- [ ] At least 3 Suppliers exist in the system
- [ ] Users with roles: Managing Director, Site Supervisor exist

---

## Test 1: Material Request Creation

**Actor:** Site Supervisor

**Steps:**
1. Navigate to `/procurement_dashboard`
2. Click on a project or go to project material requests
3. Click "Add Material Request"
4. Fill in the form:
   - Select Project
   - Select BOQ Item (should show available items)
   - Enter Quantity Requested (should not exceed BOQ quantity)
   - Select Priority (low/medium/high/urgent)
   - Enter Required Date
   - Enter Purpose/Description
5. Submit the form

**Expected Results:**
- [ ] Request number auto-generated (MR-YYYY-0001 format)
- [ ] Status is "pending"
- [ ] Request appears in pending requests list
- [ ] BOQ Item's `quantity_requested` is updated

**Verification Query:**
```sql
SELECT request_number, status, quantity_requested, boq_item_id
FROM project_material_requests
ORDER BY id DESC LIMIT 1;
```

---

## Test 2: Material Request Approval

**Actor:** Managing Director

**Steps:**
1. Login as MD
2. Navigate to approval inbox or `/procurement_dashboard`
3. Find the pending material request
4. Review request details
5. Click "Approve" button

**Expected Results:**
- [ ] Status changes to "APPROVED"
- [ ] `approved_by` is set to MD's user ID
- [ ] BOQ Item's `quantity_requested` is incremented
- [ ] Notification sent to Site Supervisor (if configured)

**Verification Query:**
```sql
SELECT mr.request_number, mr.status, mr.approved_by, bi.quantity_requested
FROM project_material_requests mr
JOIN project_boq_items bi ON mr.boq_item_id = bi.id
WHERE mr.id = [REQUEST_ID];
```

---

## Test 3: Add Supplier Quotations (3 minimum)

**Actor:** Procurement Officer

**Steps:**
1. Navigate to `/supplier_quotations`
2. Find the approved material request
3. Click "View Quotations" or navigate to `/supplier_quotations/request/{id}`
4. Add 3 quotations from different suppliers:

**Quotation 1:**
- Supplier: Supplier A
- Unit Price: 10,000
- Quantity: (match request)
- Delivery Time: 7 days
- Valid Until: (future date)

**Quotation 2:**
- Supplier: Supplier B
- Unit Price: 12,000
- Quantity: (match request)
- Delivery Time: 5 days
- Valid Until: (future date)

**Quotation 3:**
- Supplier: Supplier C
- Unit Price: 9,500
- Quantity: (match request)
- Delivery Time: 10 days
- Valid Until: (future date)

**Expected Results:**
- [ ] Each quotation gets unique number (SQ-YYYY-0001 format)
- [ ] Grand total calculated automatically (unit_price × quantity + VAT)
- [ ] Quotations sorted by price (lowest first)
- [ ] "Create Comparison" button appears after 3 quotations

**Verification Query:**
```sql
SELECT quotation_number, supplier_id, unit_price, grand_total, status
FROM supplier_quotations
WHERE material_request_id = [REQUEST_ID]
ORDER BY grand_total;
```

---

## Test 4: Create Quotation Comparison

**Actor:** Procurement Officer

**Steps:**
1. From the quotations list, click "Create Comparison"
2. Navigate to `/quotation_comparison/create/{material_request_id}`
3. Review price analysis (lowest, highest, average, variance)
4. Select the recommended quotation (click on card)
5. Enter justification reason (minimum 10 characters)
6. Submit comparison

**Expected Results:**
- [ ] Comparison number generated (QC-YYYY-0001 format)
- [ ] Selected quotation highlighted
- [ ] Status is "pending" (awaiting approval)
- [ ] Price analysis shows correct calculations

**Verification Query:**
```sql
SELECT qc.comparison_number, qc.status, qc.selected_quotation_id,
       sq.supplier_id, sq.grand_total
FROM quotation_comparisons qc
JOIN supplier_quotations sq ON qc.selected_quotation_id = sq.id
WHERE qc.material_request_id = [REQUEST_ID];
```

---

## Test 5: Approve Quotation Comparison

**Actor:** Managing Director

**Steps:**
1. Login as MD
2. Navigate to `/quotation_comparisons`
3. Find the pending comparison
4. Review comparison details and recommendation
5. Click "Approve"

**Expected Results:**
- [ ] Status changes to "APPROVED"
- [ ] Selected quotation status changes to "selected"
- [ ] Other quotations status changes to "rejected"
- [ ] "Create Purchase Order" button appears

**Verification Query:**
```sql
SELECT status FROM quotation_comparisons WHERE id = [COMPARISON_ID];
SELECT quotation_number, status FROM supplier_quotations
WHERE material_request_id = [REQUEST_ID];
```

---

## Test 6: Create Purchase Order

**Actor:** Procurement Officer

**Steps:**
1. From approved comparison, click "Create Purchase Order"
2. Or navigate to `/quotation_comparison/{id}/create_purchase`
3. Verify purchase details are pre-filled from quotation
4. Add expected delivery date
5. Submit purchase order

**Expected Results:**
- [ ] Purchase created with link to comparison
- [ ] `project_id` and `material_request_id` populated
- [ ] Purchase items created with BOQ item link
- [ ] BOQ Item's `quantity_ordered` updated

**Verification Query:**
```sql
SELECT p.id, p.project_id, p.material_request_id, p.quotation_comparison_id,
       pi.boq_item_id, pi.quantity
FROM purchases p
JOIN purchase_items pi ON p.id = pi.purchase_id
WHERE p.quotation_comparison_id = [COMPARISON_ID];
```

---

## Test 7: Record Delivery (Supplier Receiving)

**Actor:** Site Supervisor / Store Keeper

**Steps:**
1. When materials arrive, record delivery
2. Navigate to supplier receivings or create from purchase
3. Fill in:
   - Delivery Note Number
   - Quantity Delivered
   - Condition (good/damaged/partial_damage)
4. Save receiving

**Expected Results:**
- [ ] Receiving number generated
- [ ] `quantity_delivered` recorded
- [ ] Status is "pending" (awaiting inspection)
- [ ] Appears in "Deliveries Pending Inspection" list

**Verification Query:**
```sql
SELECT receiving_number, quantity_ordered, quantity_delivered,
       condition, status
FROM supplier_receivings
WHERE purchase_id = [PURCHASE_ID];
```

---

## Test 8: Create Material Inspection

**Actor:** Site Supervisor

**Steps:**
1. Navigate to `/material_inspections`
2. Find delivery in "Pending Inspection" section
3. Click "Inspect"
4. Fill inspection form:
   - Verify Quantity Delivered
   - Enter Quantity Accepted (may be less if damaged)
   - Select Overall Condition (excellent/good/fair/poor)
   - Select Overall Result (accepted/partial/rejected)
   - If rejected/partial, enter rejection reason
5. Submit inspection

**Expected Results:**
- [ ] Inspection number generated (MI-YYYY-0001 format)
- [ ] `quantity_rejected` auto-calculated
- [ ] Acceptance rate displayed
- [ ] Status is "pending" (awaiting verification)

**Verification Query:**
```sql
SELECT inspection_number, quantity_delivered, quantity_accepted,
       quantity_rejected, overall_condition, overall_result, status
FROM material_inspections
WHERE supplier_receiving_id = [RECEIVING_ID];
```

---

## Test 9: Verify & Approve Inspection

**Actor:** Site Supervisor (VERIFY) → Managing Director (APPROVE)

**Step 9a - Verify:**
1. Login as Site Supervisor
2. Find pending inspection
3. Click "Verify"

**Step 9b - Approve:**
1. Login as Managing Director
2. Find verified inspection
3. Click "Approve"

**Expected Results:**
- [ ] After verify: `verifier_id` set
- [ ] After approve: Status is "APPROVED"
- [ ] `stock_updated` becomes TRUE
- [ ] `stock_updated_at` is set
- [ ] Supplier receiving status changes to "inspected"

---

## Test 10: Verify Stock Update (CRITICAL)

**Actor:** System

**Automatic Actions on Inspection Approval:**
1. Inventory record created/updated
2. Material movement logged
3. BOQ tracking updated

**Verification Queries:**

**Check Inventory:**
```sql
SELECT pmi.project_id, pmi.boq_item_id, pmi.quantity,
       pmi.quantity_used, pmi.quantity_available
FROM project_material_inventory pmi
WHERE pmi.project_id = [PROJECT_ID]
  AND pmi.boq_item_id = [BOQ_ITEM_ID];
```

**Check Movement Record:**
```sql
SELECT movement_number, movement_type, quantity,
       reference_type, reference_id, balance_after
FROM project_material_movements
WHERE boq_item_id = [BOQ_ITEM_ID]
ORDER BY id DESC LIMIT 1;
```

**Check BOQ Item Tracking:**
```sql
SELECT item_code, quantity, quantity_requested, quantity_ordered,
       quantity_received, quantity_used, procurement_status
FROM project_boq_items
WHERE id = [BOQ_ITEM_ID];
```

**Expected Results:**
- [ ] Inventory `quantity` increased by accepted amount
- [ ] Movement record exists with type "received"
- [ ] Movement `reference_type` = "App\Models\MaterialInspection"
- [ ] BOQ Item `quantity_received` increased
- [ ] BOQ Item `procurement_status` updated (in_progress or complete)

---

## Test 11: Dashboard Verification

**Steps:**
1. Navigate to `/procurement_dashboard`
2. Verify statistics are accurate

**Check:**
- [ ] Total Requests count
- [ ] Total Quotations count
- [ ] Pending Deliveries count
- [ ] Completed Inspections count
- [ ] Actions Required counts
- [ ] Active Projects list
- [ ] Low Stock Alerts (if any items below minimum)

---

## Test 12: Project Dashboard

**Steps:**
1. Navigate to `/procurement_dashboard/project/{project_id}`
2. Verify project-specific data

**Check:**
- [ ] Overall progress percentage
- [ ] Budget utilization
- [ ] BOQ items grouped by phase
- [ ] Each item shows procurement status
- [ ] Pending actions for this project
- [ ] Recent activity timeline

---

## Test 13: BOQ Item Detail

**Steps:**
1. Navigate to `/procurement_dashboard/boq_item/{boq_item_id}`
2. Verify complete procurement history

**Check Tabs:**
- [ ] Material Requests tab shows all requests
- [ ] Quotations tab shows all quotes
- [ ] Purchases tab shows all purchases
- [ ] Deliveries tab shows all receivings
- [ ] Stock Movements tab shows all movements

**Check Flow Diagram:**
- [ ] BOQ Quantity shown correctly
- [ ] Requested quantity accurate
- [ ] Ordered quantity accurate
- [ ] Received quantity accurate
- [ ] Used quantity accurate

---

## Automated Test Script

Run this in tinker to verify the workflow programmatically:

```php
php artisan tinker

// Test 1: Check models exist
echo "Models Check:\n";
echo "ProjectMaterialRequest: " . (class_exists('App\Models\ProjectMaterialRequest') ? '✓' : '✗') . "\n";
echo "SupplierQuotation: " . (class_exists('App\Models\SupplierQuotation') ? '✓' : '✗') . "\n";
echo "QuotationComparison: " . (class_exists('App\Models\QuotationComparison') ? '✓' : '✗') . "\n";
echo "MaterialInspection: " . (class_exists('App\Models\MaterialInspection') ? '✓' : '✗') . "\n";
echo "ProjectMaterialMovement: " . (class_exists('App\Models\ProjectMaterialMovement') ? '✓' : '✗') . "\n";

// Test 2: Check tables exist
echo "\nTables Check:\n";
$tables = ['supplier_quotations', 'quotation_comparisons', 'purchase_items', 'material_inspections', 'project_material_movements'];
foreach ($tables as $t) {
    echo "$t: " . (Schema::hasTable($t) ? '✓' : '✗') . "\n";
}

// Test 3: Check approval flows exist
echo "\nApproval Flows:\n";
$flows = DB::table('process_approval_flows')
    ->whereIn('approvable_type', [
        'App\Models\ProjectMaterialRequest',
        'App\Models\QuotationComparison',
        'App\Models\MaterialInspection'
    ])->get();
foreach ($flows as $f) {
    $steps = DB::table('process_approval_flow_steps')
        ->where('process_approval_flow_id', $f->id)->count();
    echo "$f->name: $steps step(s) ✓\n";
}
```

---

## Troubleshooting

### Issue: Approval not working
- Check `process_approval_flows` has entry for the model
- Check `process_approval_flow_steps` has steps with valid role_ids
- Verify user has the required role assigned

### Issue: Stock not updating
- Check `MaterialInspection::onApprovalCompleted()` is being called
- Verify `stock_updated` is FALSE before approval
- Check `quantity_accepted` > 0

### Issue: BOQ quantities not tracking
- Verify `boq_item_id` is set on material request
- Check `ProjectBoqItem::updateProcurementStatus()` method
- Verify relationships are loaded correctly

---

## Sign-off

| Test | Tester | Date | Status |
|------|--------|------|--------|
| Test 1: Material Request | | | |
| Test 2: Request Approval | | | |
| Test 3: Add Quotations | | | |
| Test 4: Create Comparison | | | |
| Test 5: Approve Comparison | | | |
| Test 6: Create Purchase | | | |
| Test 7: Record Delivery | | | |
| Test 8: Create Inspection | | | |
| Test 9: Approve Inspection | | | |
| Test 10: Stock Update | | | |
| Test 11: Dashboard | | | |
| Test 12: Project Dashboard | | | |
| Test 13: BOQ Item Detail | | | |

**Overall Status:** ________________

**Notes:**
