# Finance Workflows

**Module:** Finance
**Covers:** Imprest Request, Petty Cash Refill, VAT Payment, Statutory Payment

All use RingleSoft single-step MD approval.

---

## Imprest Request Workflow

**Module:** Finance → Imprest Management  
**Route:** `/finance/imprest_management/imprest_requests`

### What it is

An imprest is a cash advance given to a staff member for a specific purpose (e.g. field expenses, supplier payment). After spending, the staff member must **retire** the imprest by uploading receipts. Unretired imprests cannot receive new advances.

### Fields

| Field | Notes |
|---|---|
| `document_number` | Auto-generated |
| `description` | Purpose of the advance |
| `amount` | Amount requested |
| `expenses_sub_category_id` | FK → expenses sub-category |
| `project_id` | FK → projects, nullable |
| `date` | Request date |
| `file` | Optional supporting document |
| `status` | `Created` → `approved` |
| `retirement_file` | Upload after spending (retirement) |
| `retirement_notes` | Explanation of how funds were used |
| `retired_at` | Timestamp of retirement |

### Approval flow (RingleSoft, flow ID 14)

```
Created
   │ MD/CEO approves
   ▼
approved ✓  → cash disbursed
      │
      │ staff uploads retirement file + notes
      ▼
retired ✓ (retirement recorded via POST /{id}/retire)
```

`onApprovalCompleted()`: sets `status = approved`.

### Retirement

`POST /finance/imprest_management/imprest_requests/{id}/retire`

Marks the imprest as retired by setting `retirement_file`, `retirement_notes`, and `retired_at`.

`isRetired()` → `!empty($this->retirement_file)`.

### Routes

| Method | URI | Route Name |
|---|---|---|
| GET/POST | `/finance/imprest_management/imprest_requests` | `imprest_requests` |
| GET/POST | `/finance/imprest_management/imprest_requests/{id}/{dt}` | `imprest_request` |
| POST | `/finance/imprest_management/imprest_requests/{id}/retire` | `imprest_request.retire` |

### Key Files

```
app/Models/ImprestRequest.php
app/Http/Controllers/ImprestRequestController.php
app/Http/Controllers/SettingsController.php  (hosts the view routes)
```

---

## Petty Cash Refill Request Workflow

**Module:** Finance → Petty Cash Management  
**Route:** `/finance/petty_cash_management/petty_cash_refill_requests`

### What it is

When the petty cash fund runs low, the cashier submits a refill request for MD approval. Once approved, the petty cash box is replenished.

### Fields

| Field | Notes |
|---|---|
| `document_number` | Auto-generated |
| `description` | Reason for refill |
| `amount` | Amount to refill |
| `date` | Request date |
| `status` | `CREATED` → `APPROVED` |

### Approval flow (RingleSoft, flow ID 13)

```
CREATED
   │ MD/CEO approves
   ▼
APPROVED ✓  → petty cash refilled
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Routes

| Method | URI | Route Name |
|---|---|---|
| GET/POST | `/finance/petty_cash_management/petty_cash_refill_requests` | `petty_cash_refill_requests` |
| GET/POST | `/finance/petty_cash_management/petty_cash_refill_requests/{id}/{dt}` | `petty_cash_refill_request` |

### Key Files

```
app/Models/PettyCashRefillRequest.php
app/Http/Controllers/PettyCashRefillRequestController.php
```

---

## Expense Workflow

**Module:** Finance → Expenses  
**Route:** `/expenses`

### What it is

General business expenses (not procurement) submitted for approval before payment.

### Fields

| Field | Notes |
|---|---|
| `document_number` | Auto-generated |
| `description` | |
| `amount` | |
| `expenses_sub_category_id` | FK |
| `date` | |
| `file` | Receipt scan |
| `status` | `CREATED` → `APPROVED` |

### Approval flow (RingleSoft, flow ID 6)

```
CREATED
   │ MD/CEO approves
   ▼
APPROVED ✓
```

### Key Files

```
app/Models/Expense.php
app/Http/Controllers/ExpenseController.php
```

---

## VAT Payment Workflow

**Module:** Finance → VAT Payments  
**Route:** `/vat_payment`

### What it is

TRA VAT remittance payments submitted to MD for approval before being processed through the bank.

### Fields

| Field | Notes |
|---|---|
| `bank_id` | FK → banks (payment bank account) |
| `amount` | VAT amount to remit |
| `date` | Payment date |
| `description` | Period or description |
| `file` | TRA VAT return / control number document |
| `status` | `CREATED` → `APPROVED` |
| `document_number` | Auto-generated |

### Approval flow (RingleSoft, flow ID 5)

```
CREATED
   │ MD/CEO approves
   ▼
APPROVED ✓  → payment processed
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Routes

| Method | URI | Route Name |
|---|---|---|
| GET/POST | `/vat_payment` | `vat_payment` |
| GET/POST | `/vat_payment/{id}/{dt}` | `individual_vat_payment` |

### Key Files

```
app/Models/VatPayment.php
app/Http/Controllers/VatPaymentController.php
```

### Reports

- `/reports/vat_analysis_report` — VAT analysis
- `/reports/vat_payments_report` — VAT payment listing

---

## Statutory Payment Workflow

**Module:** Finance → Statutory Payments  
**Route:** `/statutory_payments`

### What it is

Statutory deductions remittances (NSSF, PAYE, WCF, SDL, etc.) submitted for MD approval before bank payment.

### Fields

| Field | Notes |
|---|---|
| `sub_category_id` | FK → sub_categories (identifies which statutory payment type) |
| `description` | |
| `issue_date` | |
| `due_date` | When it must be paid by |
| `amount` | |
| `control_number` | TRA/NSSF control number |
| `file` | Payment schedule / EFD slip |
| `status` | `CREATED` → `APPROVED` |
| `document_number` | Auto-generated |

### Approval flow (RingleSoft, flow ID 7)

```
CREATED
   │ MD/CEO approves
   ▼
APPROVED ✓  → payment processed
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Routes

| Method | URI | Route Name |
|---|---|---|
| GET/POST | `/statutory_payments` | `hr_settings_statutory_payments` |
| GET/POST | `/statutory_payments/{id}/{dt}` | `hr_settings_statutory_payment` |

### Reports

- `/reports/statutory_payment_report`
- `/reports/statutory_category_report`
- `/reports/statutory_schedules_report`

### Key Files

```
app/Models/StatutoryPayment.php
app/Http/Controllers/StatutoryPaymentController.php
```

---

## RingleSoft Flows Summary

| Flow Name | ID | Approver | Model |
|---|---|---|---|
| Expense Approval | 6 | Managing Director | `Expense` |
| VAT Payment | 5 | Managing Director | `VatPayment` |
| Statutory Payment | 7 | Managing Director | `StatutoryPayment` |
| Petty Cash Refill | 13 | Managing Director | `PettyCashRefillRequest` |
| Imprest Request | 14 | Managing Director | `ImprestRequest` |
