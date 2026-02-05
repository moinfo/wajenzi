# Labor Charge Procurement System - Test Procedures

## Prerequisites

Before testing, ensure the system is properly set up:

```bash
# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed --class=LaborApprovalSeeder
php artisan db:seed --class=LaborMenuSeeder

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## Test Data Setup

### 1. Create Test Artisan

Navigate to **Suppliers** and create a new supplier with:

| Field | Value |
|-------|-------|
| Name | John Mfundi |
| Is Artisan | Yes (checked) |
| Trade Skill | Mason |
| Phone | 0712345678 |
| Daily Rate | 50000 |
| ID Number | 19850101-12345-00001-01 |
| NMB Account | 12345678901 |
| Account Name | John Mfundi |

> **Note:** Artisans are identified by the `is_artisan` boolean field, not supplier_type.

### 2. Verify Project Exists

Ensure at least one approved project exists with construction phases defined.

---

## Test Workflow

### Phase 1: Labor Request Creation

**Objective:** Create and submit a labor request for approval

**Steps:**

1. Navigate to **Labor Procurement → Requests**
2. Click **+ New Request**
3. Fill in the form:
   - Project: Select an active project
   - Construction Phase: Select appropriate phase
   - Artisan: Select "John Mfundi" (or leave blank for later)
   - Work Description: "Plastering works for Block A ground floor - internal walls"
   - Duration: 14 days
   - Start Date: Tomorrow's date
   - Proposed Amount: 2,500,000
   - Currency: TZS
   - Payment Terms: "20% mobilization, 30% at 50% completion, 30% at 90%, 20% after final inspection"
4. Click **Create Request**

**Expected Result:**
- Request created with status `draft`
- Request number generated (format: LR-YYYY-NNNN)
- Redirected to request details page

**Verification Query:**
```sql
SELECT id, request_number, status, proposed_amount
FROM labor_requests
ORDER BY id DESC LIMIT 1;
```

---

### Phase 2: Request Negotiation & Submission

**Objective:** Update negotiation details and submit for MD approval

**Steps:**

1. Open the created labor request
2. Click **Edit** to add negotiation details:
   - Negotiated Amount: 2,200,000
   - Artisan Assessment: "Site visit completed. Artisan confirmed scope and timeline. Quality of previous work verified at Site B."
3. Save changes
4. Click **Submit for Approval**

**Expected Result:**
- Status changes to `pending`
- Approval workflow initiated
- Request appears in MD's approval queue

**Verification:**
```sql
SELECT lr.request_number, lr.status, pa.status as approval_status
FROM labor_requests lr
LEFT JOIN process_approvals pa ON pa.approvable_id = lr.id
  AND pa.approvable_type = 'App\\Models\\LaborRequest'
WHERE lr.id = [REQUEST_ID];
```

---

### Phase 3: MD Approval

**Objective:** MD reviews and approves the labor request

**Steps:**

1. Login as MD user
2. Navigate to **Labor Procurement → Requests**
3. Find the pending request
4. Click to open approval view
5. Review details:
   - Verify work description
   - Check proposed vs negotiated amounts
   - Review artisan assessment
6. Set **Approved Amount**: 2,200,000
7. Add approval comment: "Approved. Proceed with contract."
8. Click **Approve**

**Expected Result:**
- Request status changes to `approved`
- `approved_by` and `approved_at` populated
- Ready for contract creation

---

### Phase 4: Contract Creation

**Objective:** Create labor contract from approved request

**Steps:**

1. Navigate to **Labor Procurement → Contracts**
2. Click **+ New Contract** (or from request page click "Create Contract")
3. Select the approved request
4. Verify auto-populated data:
   - Project, Artisan, Amount from request
   - Start/End dates
5. Configure payment phases (default 4-phase structure):

   | Phase | Name | Percentage | Milestone |
   |-------|------|------------|-----------|
   | 1 | Mobilization | 20% | Contract signed |
   | 2 | Progress | 30% | 50% complete |
   | 3 | Substantial | 30% | 90% complete |
   | 4 | Final | 20% | Inspected & approved |

6. Add Scope of Work details
7. Add Terms & Conditions
8. Click **Create Contract**

**Expected Result:**
- Contract created with status `draft`
- Contract number generated (LC-YYYY-NNNN)
- 4 payment phases created automatically
- Total phases amount = contract amount

**Verification:**
```sql
SELECT lc.contract_number, lc.total_amount, lc.status,
       COUNT(lpp.id) as phase_count, SUM(lpp.amount) as phases_total
FROM labor_contracts lc
LEFT JOIN labor_payment_phases lpp ON lpp.labor_contract_id = lc.id
WHERE lc.id = [CONTRACT_ID]
GROUP BY lc.id;
```

---

### Phase 5: Contract Signing

**Objective:** Capture signatures and activate contract

**Steps:**

1. Open the contract
2. Click **Sign Contract**
3. Upload/capture artisan signature
4. Upload/capture supervisor signature
5. Click **Confirm Signatures**

**Expected Result:**
- Contract status changes to `active`
- Signature files stored
- Contract PDF generated
- First payment phase (Mobilization) status = `due`

---

### Phase 6: Work Log Entry (Day 1)

**Objective:** Record daily work progress

**Steps:**

1. Navigate to **Labor Procurement → Work Logs**
2. Click **+ Add Work Log**
3. Select the active contract
4. Fill in:
   - Log Date: Today
   - Workers Present: 3
   - Hours Worked: 8
   - Progress Percentage: 10%
   - Work Done: "Site preparation completed. Scaffolding erected for internal walls Block A."
   - Challenges: "Minor delay waiting for scaffolding materials"
   - Weather: Sunny
5. Upload progress photos (optional)
6. Click **Save Work Log**

**Expected Result:**
- Work log created
- Contract progress updated
- Photos stored in `storage/uploads/labor_logs/`

**Repeat for multiple days:**
- Day 3: 25% progress
- Day 7: 50% progress
- Day 10: 75% progress
- Day 14: 95% progress

---

### Phase 7: Progress Inspection (at 50%)

**Objective:** Conduct progress inspection to release Phase 2 payment

**Steps:**

1. Navigate to **Labor Procurement → Inspections**
2. Click **+ New Inspection**
3. Select the active contract
4. Fill in:
   - Inspection Type: Progress Inspection
   - Associated Payment Phase: Phase 2 (Progress - 30%)
   - Completion Percentage: 52%
   - Work Quality: Good
   - Scope Compliance: Yes (checked)
   - Defects Found: "Minor surface imperfections on wall section C - acceptable"
   - Result: Pass
5. Upload inspection photos
6. Click **Create & Submit Inspection**

**Expected Result:**
- Inspection created with status `pending`
- Submitted to SPV for verification

---

### Phase 8: Inspection Approval (SPV → MD)

**Objective:** Two-step approval for inspection

**SPV Verification:**
1. Login as SPV user
2. Navigate to **Labor Procurement → Inspections**
3. Open pending inspection
4. Review inspection details and photos
5. Add verification comment
6. Click **Verify**

**MD Approval:**
1. Login as MD user
2. Open the verified inspection
3. Review SPV verification
4. Click **Approve**

**Expected Result:**
- Inspection status = `verified`
- Payment Phase 2 status changes to `due`
- Contract amount_paid remains unchanged (not yet processed)

---

### Phase 9: Payment Processing

**Objective:** Process payment for approved phase

**Step 1: Approve Payment**
1. Navigate to **Labor Procurement → Payments**
2. Find Phase 2 with status `due`
3. Click **Approve**

**Step 2: Process Payment**
1. Find Phase 2 with status `approved`
2. Click **Process**
3. Enter payment reference: "NMB TRF-20260206-123456"
4. Add notes: "Bank transfer completed"
5. Click **Confirm Payment**

**Expected Result:**
- Phase status = `paid`
- `paid_at`, `paid_by`, `payment_reference` populated
- Contract `amount_paid` increased by phase amount
- Contract `balance_amount` decreased

**Verification:**
```sql
SELECT lc.contract_number, lc.total_amount, lc.amount_paid, lc.balance_amount,
       lpp.phase_name, lpp.status, lpp.paid_at, lpp.payment_reference
FROM labor_contracts lc
JOIN labor_payment_phases lpp ON lpp.labor_contract_id = lc.id
WHERE lc.id = [CONTRACT_ID]
ORDER BY lpp.phase_number;
```

---

### Phase 10: Final Inspection & Completion

**Objective:** Complete the contract with final inspection

**Steps:**

1. Create work log showing 100% completion
2. Create **Final Inspection**:
   - Inspection Type: Final Inspection
   - Associated Payment Phase: Phase 4 (Final - 20%)
   - Completion Percentage: 100%
   - Work Quality: Good
   - Result: Pass
3. Submit for approval
4. SPV verifies → MD approves

**Expected Result:**
- Contract status changes to `completed`
- Final payment phase becomes `due`
- All work logs preserved

**Process Final Payment:**
1. Approve and process Phase 4 payment
2. Verify contract balance = 0

---

## Edge Case Testing

### Test Case: Inspection Failure

1. Create inspection with Result = `Fail`
2. Verify payment phase remains `pending`
3. Add rectification notes
4. Create follow-up inspection after corrections
5. Verify pass releases payment

### Test Case: Payment Hold

1. Navigate to Payments
2. Find a `due` or `approved` phase
3. Click **Hold**
4. Verify status = `held`
5. Click **Release**
6. Verify status returns to previous state

### Test Case: Contract Termination

1. Open an active contract
2. Click **Terminate**
3. Enter termination reason
4. Verify:
   - Status = `terminated`
   - Unpaid phases cancelled
   - Paid phases preserved

### Test Case: Conditional Pass

1. Create inspection with Result = `Conditional Pass`
2. Add rectification notes
3. Verify payment phase status (should remain pending or require follow-up)

---

## Validation Checklist

### Database Integrity

```sql
-- Check all contracts have valid payment phases
SELECT lc.id, lc.contract_number, lc.total_amount,
       COALESCE(SUM(lpp.amount), 0) as phases_total,
       lc.total_amount - COALESCE(SUM(lpp.amount), 0) as difference
FROM labor_contracts lc
LEFT JOIN labor_payment_phases lpp ON lpp.labor_contract_id = lc.id
GROUP BY lc.id
HAVING difference != 0;

-- Check payment tracking accuracy
SELECT lc.id, lc.contract_number,
       lc.amount_paid as recorded_paid,
       COALESCE(SUM(CASE WHEN lpp.status = 'paid' THEN lpp.amount ELSE 0 END), 0) as actual_paid
FROM labor_contracts lc
LEFT JOIN labor_payment_phases lpp ON lpp.labor_contract_id = lc.id
GROUP BY lc.id
HAVING recorded_paid != actual_paid;

-- Check inspection-payment phase links
SELECT li.inspection_number, li.result, lpp.phase_name, lpp.status
FROM labor_inspections li
JOIN labor_payment_phases lpp ON lpp.id = li.payment_phase_id
WHERE li.result = 'pass' AND lpp.status = 'pending';
```

### Permission Testing

Test each role has appropriate access:

| Role | Can Create Request | Can Approve Request | Can Create Contract | Can Approve Payment |
|------|-------------------|--------------------|--------------------|---------------------|
| Site Engineer | ✓ | ✗ | ✗ | ✗ |
| Project Manager | ✓ | ✗ | ✓ | ✗ |
| SPV | ✓ | Verify Only | ✓ | ✗ |
| MD | ✓ | ✓ | ✓ | ✓ |
| Finance | ✗ | ✗ | ✗ | Process Only |

---

## Performance Testing

### Large Dataset Test

```sql
-- Create 100 test labor requests
-- Verify index performance on common queries
EXPLAIN SELECT * FROM labor_requests WHERE project_id = 1 AND status = 'approved';
EXPLAIN SELECT * FROM labor_payment_phases WHERE status = 'due' ORDER BY created_at;
```

### Concurrent Access Test

1. Open same contract in two browser sessions
2. Submit work logs simultaneously
3. Verify no data corruption

---

## Cleanup

After testing, optionally clean test data:

```sql
-- WARNING: Only run in test environment
DELETE FROM labor_work_logs WHERE labor_contract_id IN (SELECT id FROM labor_contracts WHERE contract_number LIKE 'LC-TEST%');
DELETE FROM labor_inspections WHERE labor_contract_id IN (SELECT id FROM labor_contracts WHERE contract_number LIKE 'LC-TEST%');
DELETE FROM labor_payment_phases WHERE labor_contract_id IN (SELECT id FROM labor_contracts WHERE contract_number LIKE 'LC-TEST%');
DELETE FROM labor_contracts WHERE contract_number LIKE 'LC-TEST%';
DELETE FROM labor_requests WHERE request_number LIKE 'LR-TEST%';
```

---

## Sign-Off

| Test Phase | Tester | Date | Status |
|------------|--------|------|--------|
| Request Creation | | | ☐ |
| Request Approval | | | ☐ |
| Contract Creation | | | ☐ |
| Contract Signing | | | ☐ |
| Work Logs | | | ☐ |
| Progress Inspection | | | ☐ |
| Inspection Approval | | | ☐ |
| Payment Processing | | | ☐ |
| Final Inspection | | | ☐ |
| Contract Completion | | | ☐ |
| Edge Cases | | | ☐ |
| Permission Testing | | | ☐ |
