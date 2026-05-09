# Sales Daily Report Workflow

**Module:** Sales → Daily Reports  
**Role responsible:** Sales and Marketing / BDM (creates); MD/CEO (approves)  
**Document number format:** auto-generated  
**Route:** `/sales_daily_reports`

---

## What This Workflow Does

Sales staff submit a daily activity report documenting client follow-ups, leads progressed, and the day's summary. The report is reviewed and approved (or rejected) by the MD/CEO via RingleSoft.

---

## Actors

| Who | What they do |
|---|---|
| Sales and Marketing / BDM | Creates and submits daily report |
| Managing Director / CEO | Approves or rejects via RingleSoft |

---

## Fields

### Report header

| Field | Validation |
|---|---|
| `report_date` | Required, format `Y-m-d` |
| `department_id` | Required, FK → departments |
| `daily_summary` | Required text — overview of the day |
| `notes_recommendations` | Optional — next day actions |
| `status` | `CREATED` → `APPROVED` or `REJECTED` |

### Lead follow-ups (repeated per lead)

| Field | Notes |
|---|---|
| `lead_id` | FK → leads |
| `action_taken` | What was done with this lead today |
| `followup_date` | Next follow-up date (auto-parsed from flat date string) |
| `followup_notes` | Notes for the next follow-up |

---

## Approval Flow (RingleSoft, flow ID 15)

```
CREATED (auto-submitted on creation)
      │ MD/CEO approves
      ▼
APPROVED ✓
      │
      └── MD/CEO rejects ──▶ REJECTED
```

`onApprovalCompleted()`: sets `status = APPROVED`.  
`onApprovalRejected()`: sets `status = REJECTED`.

---

## Report Creation

```php
POST /sales_daily_reports

// Validates:
$request->validate([
    'report_date'             => 'required|date_format:Y-m-d',
    'department_id'           => 'required|exists:departments,id',
    'daily_summary'           => 'required|string',
    'lead_followups'          => 'array',
    'lead_followups.*.lead_id' => 'required|exists:leads,id',
]);
```

Follow-up dates use `Carbon::parse()` for flexible format handling.

---

## HTTP Routes

| Method | URI | Route Name | Controller Method |
|---|---|---|---|
| GET | `/sales_daily_reports` | `sales_daily_reports` | `index()` |
| GET | `/sales_daily_reports/create` | `sales_daily_reports.create` | `create()` |
| POST | `/sales_daily_reports` | `sales_daily_reports.store` | `store()` |
| GET | `/sales_daily_reports/{id}` | `sales_daily_reports.show` | `show()` |
| GET | `/sales_daily_reports/{id}/edit` | — | `edit()` |
| POST | `/sales_daily_reports/{id}` | — | `update()` |
| GET | `/sales_daily_reports/{id}/export-pdf` | — | `exportPDF()` |

---

## Database Tables

### `sales_daily_reports`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `report_date` | date | |
| `prepared_by` | FK → users | |
| `department_id` | FK → departments | |
| `daily_summary` | text | |
| `notes_recommendations` | text, nullable | |
| `status` | string | `CREATED` `APPROVED` `REJECTED` |

### `sales_lead_followups`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `sales_daily_report_id` | FK → sales_daily_reports | |
| `lead_id` | FK → leads | |
| `action_taken` | text | |
| `followup_date` | date, nullable | |
| `followup_notes` | text, nullable | |

---

## Key Files

```
app/Models/SalesDailyReport.php               onApprovalCompleted + onApprovalRejected
app/Models/SalesLeadFollowup.php              Follow-up line item

app/Http/Controllers/SalesDailyReportController.php   All 6 actions

resources/views/pages/sales/daily_reports/   Views
```

---

## RingleSoft Flow

```
Flow name: "Sales Daily Report Approval" (ID 15)
Step 1:    Managing Director → APPROVE
```
