# HR Workflows

**Module:** Human Resources
**Covers:** Leave Request, Advance Salary, Loan

All three use RingleSoft single-step MD approval.

---

## Leave Request Workflow

**Module:** HR → Leave Management  
**Role responsible:** Any staff member (request); HR/Admin (manage); MD/CEO (approve)  
**Route:** `/leaves/leave_request`

### Actors

| Who | What they do |
|---|---|
| Staff Member | Submits a leave request |
| HR / Admin | Reviews and manages leave balance |
| Managing Director | Approves or rejects via RingleSoft |

### Fields

| Field | Validation |
|---|---|
| `leave_type_id` | FK → leave_types (Annual, Sick, Maternity, etc.) |
| `start_date` | Required date |
| `end_date` | Required date, ≥ start_date |
| `total_days` | Auto-calculated |
| `reason` | Required text |
| `status` | `CREATED` → `APPROVED` or `REJECTED` |

### Approval flow (RingleSoft, flow ID 11)

```
CREATED (auto-submitted on creation)
      │ MD/CEO approves
      ▼
APPROVED ✓
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Routes

| Method | URI | Route Name |
|---|---|---|
| GET/POST | `/leaves/leave_request` | `leave_request` |
| POST | `/leaves/add_leave_request` | `leaves.store` |
| GET/POST | `/leaves/leave_dashboard` | `leave_dashboard` |
| GET/POST | `/leaves/leave_managements` | `leave_managements` |
| GET/POST/PUT | `/leaves/{leaveRequest}` | `admin.leaves.update` |

### Key Files

```
app/Models/LeaveRequest.php
app/Http/Controllers/LeaveRequestController.php
```

---

## Advance Salary Workflow

**Module:** HR → Advance Salary  
**Route:** `/settings/advance_salaries`

### What it is

An employee requests an advance on their salary before the normal payroll date. Once approved, the amount is deducted in the next payroll run.

### Fields

| Field | Notes |
|---|---|
| `staff_id` | FK → users |
| `amount` | Requested advance amount |
| `date` | Request date |
| `description` | Reason for advance |
| `status` | `CREATED` → `APPROVED` |
| `document_number` | Auto-generated |

### Approval flow (RingleSoft, flow ID 9)

```
CREATED
   │ MD/CEO approves
   ▼
APPROVED ✓  → deducted in payroll
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Payroll integration

`AdvanceSalary::getTotalAdvanceSalaryPerDay($date)` — used by payroll to total advances for a given date.

### Routes

| Method | URI | Route Name |
|---|---|---|
| GET/POST | `/settings/advance_salaries` | `hr_settings_advance_salary` |
| GET/POST | `/settings/advance_salaries/{id}/{dt}` | `advance_salary` |

### Key Files

```
app/Models/AdvanceSalary.php
app/Http/Controllers/AdvanceSalaryController.php
```

---

## Loan Workflow

**Module:** HR → Loans  
**Route:** `/loans` (approximate)

### What it is

An employee requests a formal loan (larger amount, repaid over multiple payroll periods via deductions). Once approved, the loan is disbursed and a repayment schedule is created.

### Fields

| Field | Notes |
|---|---|
| `staff_id` | FK → users |
| `amount` | Loan amount requested |
| `description` | Purpose of loan |
| `status` | `CREATED` → `APPROVED` |
| `document_number` | Auto-generated |

### Approval flow (RingleSoft, flow ID 10)

```
CREATED
   │ MD/CEO approves
   ▼
APPROVED ✓  → loan disbursed; repayment deductions begin in payroll
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Payroll integration

`Loan` and `PayrollLoanDeduction` models track the outstanding balance and monthly repayment amounts. The payroll run deducts the configured instalment from the employee's net salary.

### Key Files

```
app/Models/Loan.php
app/Http/Controllers/LoanController.php
app/Http/Controllers/PayrollLoanController.php
app/Http/Controllers/PayrollLoanDeductionController.php
```

---

## RingleSoft Flows Summary

| Flow Name | ID | Approver | Model |
|---|---|---|---|
| Leave Request | 11 | Managing Director | `LeaveRequest` |
| Advance Salary | 9 | Managing Director | `AdvanceSalary` |
| Loan | 10 | Managing Director | `Loan` |
