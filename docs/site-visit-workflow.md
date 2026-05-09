# Site Visit & Site Daily Report Workflow

**Module:** Projects → Site Visits / Site Daily Reports
**Roles responsible:** Site Inspector / Site Supervisor

---

## Part 1 — Project Site Visit

**Route:** `/project_site_visits`

### What it is

A formal site inspection visit that is logged, then approved by MD/CEO. Used to document findings, recommendations, and the site condition at a point in time.

### Actors

| Who | What they do |
|---|---|
| Site Inspector / Admin | Creates and fills in the site visit record |
| Managing Director | Approves via RingleSoft |

### Fields

| Field | Notes |
|---|---|
| `project_id` | FK → projects |
| `client_id` | FK → project_clients |
| `inspector_id` | FK → users — person who conducted the visit |
| `visit_date` | Date of visit |
| `location` | Site location / address |
| `description` | What the visit was about |
| `findings` | Observations from the site |
| `recommendations` | Actions recommended |
| `status` | `CREATED` → `APPROVED` |
| `document_number` | Auto-generated |

### Auto-submit

`ProjectSiteVisit::enableAutoSubmit()` returns `true`, meaning the RingleSoft approval is auto-submitted immediately on record creation. No manual "submit" step is needed.

### Approval flow (RingleSoft, flow ID 12)

```
CREATED (auto-submitted on creation)
      │ MD/CEO approves
      ▼
APPROVED ✓
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Key Files

```
app/Models/ProjectSiteVisit.php
app/Http/Controllers/ProjectSiteVisitController.php
```

---

## Part 2 — Site Daily Report

**Route:** `/site_daily_reports` (approximate)

### What it is

A daily log submitted by the site supervisor documenting work done, labour attendance, materials used, and weather conditions on site.

### Fields

| Field | Notes |
|---|---|
| `project_id` | FK → projects |
| `site_id` | FK → sites |
| `report_date` | Date of the report |
| `weather` | Weather conditions |
| `work_done` | Summary of work completed |
| `labour_count` | Number of workers on site |
| `status` | `CREATED` → `APPROVED` |
| `document_number` | Auto-generated |

### Approval flow (RingleSoft)

```
CREATED
   │ MD/CEO approves
   ▼
APPROVED ✓
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### Key Files

```
app/Models/SiteDailyReport.php
app/Http/Controllers/SiteDailyReportController.php
```

---

## Notes

- Both models use `enableAutoSubmit() = true`, so MD sees them in the approval queue immediately after creation
- Neither has a rejection → revision cycle — the simplest possible RingleSoft flow
