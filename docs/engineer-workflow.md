# Site Engineer Workflow

**Module:** Site Management → Site Daily Reports
**Role responsible:** Site Supervisor / Site Engineer
**Approval ID:** 16 (`SiteDailyReport`)

---

## What This Workflow Does

The Site Engineer (Site Supervisor) submits a **Site Daily Report** for every working day on an active construction site. The report captures work done, labor on site, materials consumed, and any payments made. It goes through MD approval and becomes the official daily record of site progress.

This is distinct from the Site Visit workflow (which is a formal inspection visit). Daily reports are routine operational records — one per day per site.

---

## Actors

| Who | What they do |
|---|---|
| Site Supervisor | Creates and submits daily reports for their assigned sites |
| Managing Director | Approves or rejects |
| Admin / Project Manager | Can view all reports across all sites |
| CEO | Same approval authority as MD |

---

## Permissions

| Permission | Who has it |
|---|---|
| `View All Site Reports` | Admin, Manager, MD |
| `View Own Site Reports` | Site Supervisor (own site only) |
| `Add Site Reports` | Site Supervisor |
| `Edit Own Site Reports` | Site Supervisor (before submission) |
| `Submit Site Reports` | Site Supervisor |
| `Approve Site Reports` | MD / CEO |
| `Reject Site Reports` | MD / CEO |
| `Export Site Reports` | Admin, Manager |
| `Share Site Reports` | Admin, Manager |

---

## Report Structure

A `SiteDailyReport` is the parent record. Four child tables hang off it:

| Child table | Model | What it records |
|---|---|---|
| `site_work_activities` | `SiteWorkActivity` | Tasks done on site (description, %) |
| `site_labor_needed` | `SiteLaborNeeded` | Headcount — type of worker, number |
| `site_materials_used` | `SiteMaterialUsed` | Materials consumed from site stock |
| `site_payments` | `SitePayment` | Payments made on site |

### Parent Fields (`site_daily_reports`)

| Field | Notes |
|---|---|
| `site_id` | FK → sites |
| `supervisor_id` | FK → users |
| `prepared_by` | FK → users (may differ from supervisor) |
| `report_date` | Date of site activity |
| `weather_conditions` | Recorded weather |
| `visitors` | Any visitors on site |
| `general_notes` | Free-text observations |
| `status` | `draft → pending → APPROVED / rejected` |
| `document_number` | Auto-generated |

---

## Status Flow

```
Site Supervisor opens Create Report form
        │  (pre-filled with site and date)
        ▼
Fills in work activities, labor, materials, payments
        │
        ▼
Saves as draft (status: draft)
        │
        ▼
Clicks Submit → status: pending
        │  [sent to RingleSoft approval queue]
        ▼
MD / CEO reviews on approval page (/site_daily_report/{id}/{document_type_id})
        │
        ├── Approve ──→ status: APPROVED — report locked, permanent record
        │
        └── Reject ───→ status: rejected — supervisor notified, can edit and resubmit
```

---

## Site Visibility Rules

The Site Supervisor can only see reports for sites where they are the assigned supervisor (via `site_supervisor_assignments` — the current assignment). They cannot view reports for other sites.

Users with `View All Site Reports` permission see all reports across all sites.

---

## Key Routes

All site daily report routes are under the `/site-daily-reports` resource prefix (plus the legacy approval route):

| Method | URI | Action |
|---|---|---|
| GET | `/site-daily-reports` | Index (filtered by permission) |
| GET | `/site-daily-reports/create` | Create form |
| POST | `/site-daily-reports` | Store new report |
| GET | `/site-daily-reports/{report}` | Show detail |
| GET | `/site-daily-reports/{report}/edit` | Edit (draft only) |
| PUT/PATCH | `/site-daily-reports/{report}` | Update |
| GET | `/site-daily-reports/my-reports` | Supervisor's own reports |
| GET | `/site-daily-reports/export/{report}` | Export PDF |
| GET | `/site-daily-reports/share/{report}` | Share link |
| POST | `/site-daily-reports/submit/{report}` | Submit for approval |
| POST | `/site-daily-reports/approve/{report}` | Approve |
| POST | `/site-daily-reports/reject/{report}` | Reject |
| GET/POST | `/site_daily_report/{id}/{document_type_id}` | Approval detail page |

---

## Key Files

| File | Purpose |
|---|---|
| `app/Models/SiteDailyReport.php` | Parent report model, approval integration |
| `app/Models/SiteWorkActivity.php` | Work activity line items |
| `app/Models/SiteLaborNeeded.php` | Labor headcount line items |
| `app/Models/SiteMaterialUsed.php` | Material usage line items |
| `app/Models/SitePayment.php` | On-site payment records |
| `app/Http/Controllers/SiteDailyReportController.php` | Full CRUD + approval actions |
| `resources/views/pages/sites/reports/` | View directory |

---

## Related: Engineering Design Workflows

The Site Engineer role is separate from the design engineering roles. For design-phase workflows see:

| Workflow | Doc | Role |
|---|---|---|
| Structural Design (3 stages + work schedule) | `structural-design-approval-workflow.md` | Civil Engineer |
| Service Design / MEP (4 disciplines + work schedule) | `service-design-approval-workflow.md` | Service Engineer |