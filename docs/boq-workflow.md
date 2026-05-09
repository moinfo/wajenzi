# BOQ (Bill of Quantities) Workflow

**Module:** Projects → BOQ
**Role responsible:** Quantity Surveyor (QS)
**Document number format:** `BOQ-PLAN-0001 / Project Name` (plan), `BOQ-0001` (BOQ itself)

---

## What This Workflow Does

After the Structural Design is approved, the QS can prepare the Bill of Quantities. The workflow has two layers:

1. **BOQ Preparation Plan** (`ProjectBoqPlan`) — a formal plan approved by MD/CEO that authorises the QS to begin BOQ preparation
2. **BOQ** (`ProjectBoq`) — the actual itemised bill, which also goes through MD/CEO approval before being shared

---

## Trigger

### Gate: Structural Design must be approved

`ProjectBoqPlanController::store()` checks `ProjectStructuralDesign::isApprovedForProject($projectId)` before allowing plan creation. If structural design is not yet approved, creation is blocked with an error.

**Note:** After Service Design approval, the QS and Sales teams are notified again (via `ProjectServiceDesign::onApprovalCompleted()`), but the BOQ gate is the structural design, not the service design.

---

## Stage 1 — BOQ Preparation Plan

### Who creates it

Quantity Surveyor (or MD/CEO/Admin).

### What is submitted

| Field | Validation |
|---|---|
| `project_id` | Required, must have approved structural design |
| `planned_start` | Required date |
| `planned_end` | Required date, ≥ planned_start |
| `scope_description` | Optional, max 3,000 chars |

**Guard:** One active (non-rejected) plan per project.

### Status flow

```
draft (created)
   │ QS clicks "Submit for Approval"
   ▼
submitted ──── MD/CEO approves ──▶ approved ✓
   │
   └────── MD/CEO rejects ──▶ rejected
                                   │ QS revises and resubmits
                                   ▼
                               submitted (again)
```

### Notifications

| Action | Who is notified |
|---|---|
| Plan submitted | All Managing Directors + CEOs |
| Plan approved | QS who created the plan |

`onApprovalCompleted()`: sets `status = approved`, `approved_at = now()`, notifies plan creator.

### After approval

The QS can now prepare the actual BOQ for this project.

---

## Stage 2 — BOQ Preparation

### Who prepares it

Quantity Surveyor (or Admin).

### BOQ structure

Each BOQ has:
- **Header** — `project_id`, `version`, `prepared_by`, `status`
- **Sections** (`project_boq_sections`) — hierarchical (self-referential `parent_id`), e.g. "Foundation", "Superstructure"
- **Items** (`project_boq_items`) — with `section_id`, `item_type` (material/labour), `item_code`, `description`, `quantity`, `unit`, `unit_rate`

### Version management

Each project can have multiple BOQ versions (v1, v2…). `getNextVersion()` computes the next version number. Only one BOQ can be `approved` at a time per project.

### BOQ features

- **CSV import** — bulk load items from a spreadsheet
- **Template apply** — copy items from a saved BOQ template
- **Save as template** — save current BOQ structure as a reusable template
- **PDF export** — `GET /project_boq/{id}/pdf`
- **CSV export** — `GET /project_boq/{id}/csv`

### Approval flow (RingleSoft, flow ID 22)

```
draft
   │ QS submits
   ▼
SUBMITTED ──── MD/CEO approves ──▶ APPROVED ✓
   │
   └────── MD/CEO rejects ──▶ REJECTED
```

`onApprovalCompleted()`: sets `status = APPROVED`, `approved_at = now()`.

### After BOQ approval

- BOQ items become available for Material Requests
- BOQ `quantity_requested` and `quantity_received` fields track procurement progress per item
- Client can view the approved BOQ (if enabled in client portal)

---

## BOQ Item Procurement Status

Each `ProjectBoqItem` tracks its procurement lifecycle:

| Field | Meaning |
|---|---|
| `quantity` | Total planned quantity |
| `quantity_requested` | Cumulative from approved Material Requests |
| `quantity_received` | Cumulative from approved Material Inspections |
| `procurement_status` | Derived: `not_started` `partial` `complete` |

`updateProcurementStatus()` recalculates this whenever a MR is approved or inspection is approved.

---

## HTTP Routes

| Method | URI | Route Name | Controller Method |
|---|---|---|---|
| GET | `/project-boq-plans` | `project-boq-plans.index` | `ProjectBoqPlanController@index` |
| POST | `/project-boq-plans` | `project-boq-plans.store` | `store()` |
| GET | `/project-boq-plans/{id}` | `project-boq-plans.show` | `show()` |
| POST | `/project-boq-plans/{id}/submit` | `project-boq-plans.submit` | `submit()` |
| GET/POST | `/project_boqs` | `project_boqs` | `ProjectBoqController@index` |
| GET/POST | `/project_boq/show/{id}` | `project_boq.show` | `show()` |
| POST | `/project_boq/store` | `project_boq.store` | `store()` |
| GET/POST | `/project_boq/{id}/{dt}` | `project_boq` | `boq()` |
| GET | `/project_boq/{id}/pdf` | `project_boq.pdf` | `exportPdf()` |
| GET | `/project_boq/{id}/csv` | `project_boq.csv` | `exportCsv()` |
| POST | `/project_boq/{id}/import-csv` | `project_boq.import_csv` | `importCsv()` |

---

## Database Tables

### `project_boq_plans`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | |
| `planned_start` | date | |
| `planned_end` | date | |
| `scope_description` | text, nullable | |
| `status` | string | `draft` `submitted` `approved` `rejected` |
| `submitted_at` | datetime, nullable | |
| `approved_at` | datetime, nullable | |
| `created_by` | FK → users | |

### `project_boqs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | |
| `version` | tinyint | 1, 2, 3… |
| `prepared_by` | FK → users | |
| `status` | string | `draft` `SUBMITTED` `APPROVED` `REJECTED` |
| `approved_at` | datetime, nullable | |

### `project_boq_sections`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `boq_id` | FK → project_boqs | |
| `parent_id` | FK → self, nullable | Hierarchical |
| `name` | string | |
| `sort_order` | int | |

### `project_boq_items`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `boq_id` | FK → project_boqs | |
| `section_id` | FK → project_boq_sections, nullable | |
| `item_code` | string | |
| `description` | text | |
| `item_type` | enum | `material` `labour` |
| `quantity` | decimal | Planned quantity |
| `unit` | string | |
| `unit_rate` | decimal | |
| `quantity_requested` | decimal | From MR approvals |
| `quantity_received` | decimal | From inspection approvals |
| `procurement_status` | string | `not_started` `partial` `complete` |

---

## Key Files

```
app/Models/ProjectBoqPlan.php              Plan — onApprovalCompleted, isApprovedForProject()
app/Models/ProjectBoq.php                  BOQ — RingleSoft approvable, templates
app/Models/ProjectBoqItem.php              Item — updateProcurementStatus()
app/Models/ProjectBoqSection.php           Hierarchical section

app/Http/Controllers/ProjectBoqPlanController.php   index, show, store, submit
app/Http/Controllers/ProjectBoqController.php        Full BOQ CRUD + templates + CSV import/export

resources/views/pages/boq_plans/            BOQ plan views
resources/views/pages/projects/boq*.blade.php  BOQ editor + approval views
resources/views/partials/boq_section_rows.blade.php  Recursive section rendering

database/seeders/BoqApprovalSeeder.php      RingleSoft flow ID 22 (BOQ)
database/seeders/BoqPlanApprovalSeeder.php  RingleSoft flow ID 27 (BOQ Plan)
```

---

## RingleSoft Flows

| Flow Name | ID | Approver |
|---|---|---|
| BOQ Approval | 22 | Managing Director |
| BOQ Plan Approval | 27 | Managing Director |

---

## Related Workflows

- **Upstream:** [Structural Design](structural-design-approval-workflow.md) — must be approved before BOQ plan creation
- **Downstream:** [Procurement](procurement-workflow.md) — BOQ items are the basis for Material Requests
