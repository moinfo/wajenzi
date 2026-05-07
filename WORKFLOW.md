# Wajenzi — Design-to-Construction Workflow Guide

This document maps the intended business workflow against the current system implementation, step by step. It serves as both a functional specification and a developer reference for the complete pipeline from first client contact through BOQ approval.

---

## Pipeline Overview

```
Lead Captured
      │
      ▼
Lead Makes Design Payment
      │  (triggers auto-conversion)
      ▼
Lead → Client + Project Created   [status: Design Phase]
      │
      ▼
Architect Auto-Assigned           [workload-balanced, email notified]
      │
      ▼
Design Schedule Created           [stages generated from template]
      │
      ├── Stage: Concept Design
      ├── Stage: Floor Plans
      ├── Stage: 3D Visualization
      └── Stage: Final Architectural Drawings
               │
               ▼ (each stage: Submit → CEO/MD Review → Approved / Rejected)
               │
               ▼
Architectural Design Complete & Approved
      │  (triggers structural handoff)
      ▼
Structural Design Workflow        [notify structural engineers]
      │
      ├── Structural Analysis
      ├── Foundation Design
      ├── Structural Drawings
      └── CEO/MD Approval
               │
               ▼
Structural Approved
      │  (triggers QS stage)
      ▼
BOQ Prepared by QS
      │
      ▼
CEO/MD Approves BOQ
      │
      ▼
BOQ Shared → Client + Sales + Structural
```

---

## Step-by-Step Reference

### Step 1 — Lead Capture

**What it does:** Sales team records a prospect as a Lead with contact details, service interest, estimated value, and assigned salesperson. Follow-ups are scheduled.

**Key files:**
| File | Purpose |
|---|---|
| `app/Models/Lead.php` | Lead model — auto-generates `LEAD-YYYYMM-###` number on create |
| `app/Http/Controllers/LeadController.php` | Full CRUD + follow-up management |
| `app/Models/SalesLeadFollowup.php` | Scheduled follow-up activities |
| `resources/views/pages/leads/` | Lead list, detail, and form views |

**Lead statuses:** `active` → `converted` → `inactive`

---

### Step 2 — Lead-to-Client Conversion (on Design Payment)

**What it does:** When a lead pays for design services, the system converts the lead into a client and creates a project, setting its status to Design Phase.

**Key files:**
| File | Purpose |
|---|---|
| `app/Http/Controllers/LeadController.php` | `createProject()` — manually creates a project from a lead |
| `app/Services/ProjectScheduleService.php` | `assignArchitectOnFirstPayment()` — intended auto-trigger |
| `app/Models/Project.php` | Project model; `onApprovalCompleted()` sets status to `APPROVED` |

**Current state:** `createProject()` works but requires a manual action. The method `assignArchitectOnFirstPayment()` exists and is the intended hook point but **no event listener connects it to a billing payment event**. Project status after creation is set to `'pending'`, not `'Design Phase'`.

**Missing wiring:**
```php
// In a BillingPayment observer or event listener:
ProjectScheduleService::assignArchitectOnFirstPayment($lead->id);
$lead->update(['status' => 'converted']);
$project->update(['status' => 'design_phase']);
```

---

### Step 3 — Architect Assignment

**What it does:** System auto-assigns an architect by workload. Manager/CEO can override manually. Architect is notified by email.

**Key files:**
| File | Purpose |
|---|---|
| `app/Services/ProjectScheduleService.php` | `createScheduleFromTemplate()` — full auto-assign logic |
| `app/Models/ProjectAssignment.php` | `findArchitectWithLeastWorkload()` — workload algorithm |
| `app/Models/ProjectSchedule.php` | Schedule header, holds `assigned_architect_id` |
| `app/Mail/ArchitectAssignmentMail.php` | Email notification to assigned architect |
| `app/Http/Controllers/ProjectScheduleController.php` | Manual reassignment UI |

**Current state:** Fully implemented. Auto-assignment by workload is live. Email notification fires on assignment. Manual override available.

---

### Step 4 — Design Schedule Creation

**What it does:** A schedule is created from a template with ordered activities (phases). Each activity has a predecessor dependency, duration (working days), assigned role, and start/end dates auto-calculated excluding weekends and holidays.

**Key files:**
| File | Purpose |
|---|---|
| `app/Models/ProjectSchedule.php` | Schedule header with progress tracking |
| `app/Models/ProjectScheduleActivity.php` | Individual stage/activity |
| `app/Models/ProjectActivityTemplate.php` | Template defining default stages and durations |
| `app/Models/ProjectHoliday.php` | Holiday calendar used to skip non-working days |
| `app/Services/ProjectScheduleService.php` | `generateActivitiesFromTemplate()`, `addWorkingDays()` |
| `resources/views/project-schedules/` | Schedule management views |

**Activity fields:** `activity_code`, `name`, `phase`, `discipline`, `start_date`, `end_date`, `duration_days`, `predecessor_code`, `assigned_to`, `status`

**The 4 architectural stages from the guide** (Concept Design, Floor Plans, 3D Visualization, Final Architectural Drawings) correspond to records in the `project_activity_templates` table — seeded during setup.

**Current state:** Fully implemented. Template-driven generation, working-day calculation, predecessor chaining.

---

### Step 5 — Per-Stage CEO/MD Approval

**What it does:** Each design stage is submitted by the architect, reviewed by CEO/MD, and either approved (stage marked complete, next stage unlocks) or rejected (returned for revision).

**Key files (current):**
| File | Purpose |
|---|---|
| `app/Models/ProjectScheduleActivity.php` | `canStart()` checks predecessor completion; `markAsCompleted()` |

**Current state:** Activities can be marked complete. Predecessor-gating exists. However, **there is no approval flow on design stages** — `ProjectScheduleActivity` does not implement `ApprovableModel`. Activities advance on completion, not on CEO/MD approval.

**Missing:** `ProjectScheduleActivity` needs the `Approvable` trait (RingleSoft), a `ProcessApprovalFlow` configuration for `ProjectScheduleActivity`, and a `onApprovalCompleted()` that marks the activity complete and unlocks the next.

**Example pattern (already used in `ProjectBoq`):**
```php
// ProjectBoq already does this correctly:
class ProjectBoq extends Model implements ApprovableModel
{
    use HasFactory, Approvable;

    public function onApprovalCompleted(ProcessApproval $approval): bool
    {
        $this->status = 'APPROVED';
        $this->save();
        return true;
    }
}
```

---

### Step 6 — Client Portal Visibility (Approved Stages Only)

**What it does:** The client portal shows only approved design stages. Rejected or in-progress stages are hidden.

**Key files:**
| File | Purpose |
|---|---|
| `app/Http/Controllers/Api/Client/ProjectController.php` | Client-facing project API |
| `app/Http/Resources/Client/ClientDesignResource.php` | Serializes design for client |
| `app/Http/Resources/Client/ClientScheduleActivityResource.php` | Serializes activities for client |

**Current state:** `documents()` returns all `projectDesigns()` with no filter. `schedule()` returns all activities with no status filter.

**Missing filter (in `ProjectController::documents()`):**
```php
// Currently:
$designs = $project->projectDesigns()->orderBy('created_at', 'desc')->get();

// Should be:
$designs = $project->projectDesigns()
    ->where('status', 'approved')
    ->orderBy('created_at', 'desc')
    ->get();
```

---

### Step 7 — Structural Design Handoff

**What it does:** Once architectural design is fully approved, the system notifies structural engineers. The project appears in the structural engineering dashboard.

**Current state:** **Not implemented.** No model, migration, controller, or views exist for structural design. No event fires on architectural design completion. No structural engineer dashboard.

**What needs to be built:**
- `StructuralDesign` model implementing `ApprovableModel`
- Migration: `project_structural_designs` table
- Controller + views
- Event/listener that fires when the last architectural activity is approved
- Role-based structural dashboard

---

### Step 8 — Structural Design Workflow

**What it does:** Structural engineers complete four stages (Structural Analysis, Foundation Design, Structural Drawings, CEO/MD Approval) before handing off to QS.

**Current state:** **Not implemented.** See Step 7. The `ProjectScheduleActivity` phase system could be extended with a `discipline = 'structural'` filter, but the approval gate and dashboard are absent.

---

### Step 9 — BOQ Preparation & Approval

**What it does:** QS prepares the Bill of Quantities after structural approval. CEO/MD approves. Approved BOQ is shared with client and sales.

**Key files:**
| File | Purpose |
|---|---|
| `app/Models/ProjectBoq.php` | BOQ with full `Approvable` integration |
| `app/Models/ProjectBoqSection.php` | Hierarchical sections (`parent_id` self-referential) |
| `app/Models/ProjectBoqItem.php` | Line items: material/labour, unit, quantity, rate |
| `app/Http/Controllers/ProjectBoqController.php` | BOQ management |
| `app/Http/Controllers/Api/Client/ProjectController.php` | `boq()` — client API endpoint |
| `resources/views/pages/projects/boq.blade.php` | BOQ management view |

**Current state:** Fully implemented. RingleSoft approval is wired. `onApprovalCompleted()` updates status. Client API exposes the BOQ. PDF and CSV exports work.

**Gap:** BOQ is not currently gated behind structural design approval (since structural design does not exist). A check should prevent BOQ creation until structural is approved.

---

## Implementation Status Summary

| Workflow Step | Status | Notes |
|---|---|---|
| Lead capture & follow-ups | **Complete** | Full CRM with follow-up scheduling |
| Lead → Project (manual) | **Complete** | `LeadController::createProject()` |
| Lead → Project (auto on payment) | **Incomplete** | `assignArchitectOnFirstPayment()` exists but not wired to billing events |
| Project status = "Design Phase" | **Incomplete** | Status set to `pending`, not `design_phase` |
| Architect auto-assignment | **Complete** | Workload algorithm + email notification |
| Architect manual override | **Complete** | Via schedule controller |
| Design schedule from template | **Complete** | Working-day calculation, predecessor chaining |
| Per-stage CEO/MD approval | **Not built** | No `Approvable` trait on `ProjectScheduleActivity` |
| Client portal — approved stages only | **Incomplete** | No status filter on client API endpoints |
| Structural design handoff | **Not built** | No model, table, or controller |
| Structural design workflow | **Not built** | No model, table, or controller |
| BOQ preparation | **Complete** | Hierarchical, templates, PDF/CSV export |
| BOQ CEO/MD approval | **Complete** | RingleSoft `Approvable` implemented |
| BOQ → client visibility | **Complete** | Client API exposes BOQ |
| BOQ gated by structural approval | **Not built** | No gate since structural doesn't exist |
| Role-based access control | **Complete** | Spatie permissions + menu filtering |

---

## System Rules — Status

| Rule | Status |
|---|---|
| No stage proceeds without approval | Partial — BOQ, Project, Purchase, MaterialRequest all enforced. Design stages are not. |
| Role-based access control | Complete — Spatie permissions enforced on routes and menus |
| Automation with manual override | Partial — Architect auto-assign exists; payment trigger not wired |
| CEO dashboard shows critical metrics | `ProjectDashboardController` exists — not part of this workflow scope |

---

## Key Approval-Enabled Models

The following models use [RingleSoft Laravel Process Approval](https://github.com/raboragit/laravel-process-approval). Each implements `ApprovableModel`, uses the `Approvable` trait, and defines `onApprovalCompleted()`.

```
Project                 — project approval
ProjectBoq              — BOQ approval before procurement unlocks
ProjectMaterialRequest  — material request approval
Purchase                — purchase order approval
QuotationComparison     — vendor comparison approval
MaterialInspection      — goods receipt quality approval
ProjectSiteVisit        — site visit report approval
Expense, Sale, Payroll, LeaveRequest, Loan, LaborRequest, ...
```

The `ProcessApprovalFlow` and `ProcessApprovalFlowStep` tables configure how many levels each document type requires and which role approves at each level.

---

## What to Build Next (Priority Order)

1. **Wire payment trigger** — Listen for `BillingPayment` creation, call `ProjectScheduleService::assignArchitectOnFirstPayment()`, set project status to `design_phase`.

2. **Per-stage design approval** — Add `Approvable` to `ProjectScheduleActivity`. Configure a `ProcessApprovalFlow` entry. Update `onApprovalCompleted()` to mark the activity done and notify the architect of the next stage.

3. **Client portal filtering** — Add `->where('status', 'approved')` to `ProjectController::documents()` and `ProjectController::schedule()`.

4. **Structural design module** — Create `StructuralDesign` model + migration + controller + views + approval flow. Fire a notification event when the last architectural stage is approved.

5. **BOQ gate** — Prevent `ProjectBoq` creation unless the linked project has a fully approved structural design.
