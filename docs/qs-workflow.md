# Quantity Surveyor (QS) Workflow

**Module:** Projects → BOQ Plan → BOQ Preparation
**Role responsible:** Quantity Surveyor (QS)
**Approval IDs:** 27 (`ProjectBoqPlan`), 22 (`ProjectBoq`)

---

## What the QS Does in the System

The Quantity Surveyor operates in two stages after design approval:

```
Structural Design Approved (by MD)
        │  [QS notified automatically]
        ▼
1. QS submits BOQ Preparation Plan   [Approval ID 27]
        │  [MD approves plan]
        ▼
2. QS prepares the Bill of Quantities [Approval ID 22]
        │  [MD approves BOQ]
        ▼
BOQ shared with Client + Sales
```

---

## Trigger — Structural Design Approval

When the MD approves the final structural design, `ProjectStructuralDesign::onApprovalCompleted()` fires and:

- All users with role `Quantity Surveyor (QS)` receive an in-app notification with a direct link to the structural design
- The project status is set to `structural_approved`
- The BOQ Plan index page (`/project-boq-plans`) shows the project as ready

Similarly, when the service design is approved, QS users are also notified so they can update the BOQ if MEP work adds scope.

---

## Step 1 — BOQ Preparation Plan

### What it is

Before building the BOQ, the QS submits a **preparation plan** that commits to a timeline (planned start and end dates) and a brief description of scope. This plan goes to the MD for approval before the QS starts the actual BOQ work.

**Guard:** The project must have `status = 'structural_approved'`. The plan index only lists projects in this state for creation.

### Fields

| Field | Notes |
|---|---|
| `project_id` | FK → projects |
| `planned_start` | Date QS plans to start BOQ work |
| `planned_end` | Date QS plans to deliver the BOQ |
| `scope_description` | What the BOQ will cover |
| `status` | `pending → approved / rejected` |
| `document_number` | Auto: `BOQ-PLAN-0001 / Project Name` |

### Status Flow

```
QS creates plan → submits → MD approves
        │
        └──→ QS notified: "You may now prepare the BOQ"
```

On approval `onApprovalCompleted()`:
- Sets `status = 'approved'`
- Notifies the QS who created the plan (if different from approver)

### Routes

| Method | URI | Action |
|---|---|---|
| GET | `/project-boq-plans` | List plans (own for QS; all for MD/admin) |
| POST | `/project-boq-plans` | Create plan |
| GET | `/project-boq-plans/{plan}` | Show plan detail |
| POST | `/project-boq-plans/{plan}/submit` | Submit for approval |

---

## Step 2 — Bill of Quantities (BOQ)

### What it is

The main deliverable. A hierarchical breakdown of all materials and labour for the project, with quantities, units, and rates. The approved BOQ becomes the basis for procurement and client billing.

**Gate:** The BOQ cannot be created unless the structural design is approved (`ProjectStructuralDesign::isApprovedForProject($projectId)` must return `true`).

### Structure

```
ProjectBoq (parent — one per project)
    └── ProjectBoqSection (hierarchical — self-referential parent_id)
            └── ProjectBoqItem (line items: material or labour)
```

### BOQ Parent Fields

| Field | Notes |
|---|---|
| `project_id` | FK → projects |
| `status` | `draft → pending → APPROVED / rejected` |
| `document_number` | Auto-generated |
| `notes` | QS notes on the BOQ |

### BOQ Item Fields (per line)

| Field | Notes |
|---|---|
| `section_id` | FK → boq section (nullable) |
| `item_type` | `material / labour` |
| `description` | What the item is |
| `unit` | Unit of measure |
| `quantity` | Planned quantity |
| `rate` | Unit rate (TZS) |
| `total` | Computed: `quantity × rate` |
| `sort_order` | Display order |
| `quantity_requested` | Accumulates as MRs are raised |
| `quantity_ordered` | Accumulates as POs are issued |
| `quantity_received` | Accumulates as deliveries are inspected |
| `quantity_used` | Accumulates as materials are consumed |
| `procurement_status` | `not_started / in_progress / complete` |

### Status Flow

```
QS creates BOQ and adds sections/items
        │
        ▼
QS submits for MD approval
        │
        ▼
MD approves on approval page
        │
        └──→ onApprovalCompleted():
               • status = 'APPROVED'
               • Quantity Surveyors notified
               • Sales / BDM notified (to share with client)
               • Client Portal BOQ tab becomes visible
```

### PDF & CSV Export

Both are available from the BOQ detail page:
- **PDF** — uses `barryvdh/laravel-dompdf`, inline CSS, hierarchical sections
- **CSV** — UTF-8 BOM for Excel compatibility, mirrors the PDF structure

---

## QS Dashboard (Home Page)

When a QS logs in, the dashboard shows three panels specifically for them:

| Panel | What it shows |
|---|---|
| **QS Ready — Structural** | Approved structural designs without a BOQ yet |
| **QS Ready — Service** | Approved service designs (MEP scope additions) |
| **My BOQ Plans** | Plans created by the QS and their approval status |
| **Approved BOQs** | BOQs already approved (for Sales to share) |

---

## Downstream from the BOQ

Once the BOQ is approved, the QS's involvement continues into procurement:

| Stage | What QS does |
|---|---|
| **Material Requests** | BOQ items drive what can be requested; quantities are gated by BOQ |
| **Procurement Tracking** | BOQ item counters (`quantity_requested`, `quantity_ordered`, `quantity_received`, `quantity_used`) show live status |
| **Procurement Dashboard** | `/procurement-dashboard` — project-by-project view of BOQ vs. procurement progress |
| **Valuation / Billing** | Approved BOQ is the basis for client invoicing via the billing module |

---

## Key Files

| File | Purpose |
|---|---|
| `app/Models/ProjectBoqPlan.php` | BOQ Plan model, approval integration |
| `app/Models/ProjectBoq.php` | BOQ parent, `onApprovalCompleted()` |
| `app/Models/ProjectBoqSection.php` | Hierarchical sections (`parent_id` self-referential) |
| `app/Models/ProjectBoqItem.php` | Line items, quantity counters, `updateProcurementStatus()` |
| `app/Http/Controllers/ProjectBoqPlanController.php` | Plan CRUD |
| `app/Http/Controllers/ProjectBoqController.php` | BOQ CRUD, PDF, CSV |
| `resources/views/pages/boq_plans/` | BOQ Plan views |
| `resources/views/pages/projects/boq.blade.php` | BOQ management view |

---

## Related Workflows

| Workflow | Doc |
|---|---|
| BOQ full technical detail | `boq-workflow.md` |
| Structural design (precondition) | `structural-design-approval-workflow.md` |
| Procurement (downstream) | `procurement-workflow.md` |
| Material Inspection (QA on received goods) | `qa-workflow.md` |
