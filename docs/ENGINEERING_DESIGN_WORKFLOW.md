# Engineering Design Workflow

This document covers the full implementation of two parallel engineering design workflows in the Wajenzi Construction ERP:

1. **Structural Design Workflow** — handled by the Civil Engineer
2. **Service Design Workflow** — handled by the Service Engineer (Electrical, FADS, ICT, HVAC)

Both workflows share the same three-gate approval pattern and were designed intentionally in sequence: Structural Design must be fully approved before the Service Design is triggered.

---

## Table of Contents

- [Overview](#overview)
- [How Workflows Are Triggered](#how-workflows-are-triggered)
- [Structural Design Workflow](#structural-design-workflow)
- [Service Design Workflow](#service-design-workflow)
- [Role Permissions Matrix](#role-permissions-matrix)
- [Menu Structure](#menu-structure)
- [Client Portal](#client-portal)
- [Dashboard Visibility](#dashboard-visibility)
- [Notifications](#notifications)
- [Database Schema](#database-schema)
- [HTTP Routes Reference](#http-routes-reference)
- [Key Files](#key-files)

---

## Overview

```
Architectural Drawing Approved (B7 Activity)
        │
        ▼
┌─────────────────────────────┐
│  STRUCTURAL DESIGN WORKFLOW │  ← Civil Engineer
│                             │
│  Step 1: Work Schedule      │  Engineer → MD approves/rejects
│  Step 2: 3 Stage Approvals  │  Each stage: Engineer → MD approves/rejects
│  Step 3: Final CEO/MD Approval (RingleSoft)
└─────────────┬───────────────┘
              │ On Final Approval
              ▼
┌─────────────────────────────┐
│  SERVICE DESIGN WORKFLOW    │  ← Service Engineer (auto-triggered)
│                             │
│  Step 1: Work Schedule      │  Engineer → MD approves/rejects
│  Step 2: 4 Stage Approvals  │  Each stage: Engineer → MD approves/rejects
│  Step 3: Final CEO/MD Approval (RingleSoft)
└─────────────┬───────────────┘
              │ On Final Approval
              ▼
     ┌────────┴─────────┐
     │                  │
   QS Dashboard    Sales Dashboard
   (BOQ Prep)      (Share with Client)
                        │
                        ▼
                  Client Portal Tab
                  (Download + Feedback)
```

---

## How Workflows Are Triggered

### Structural Design — Triggered by B7 Activity Approval

The structural design record is **auto-created** by `StructuralHandoffService` when a project schedule activity with code `B7` (Final Architectural Drawings) is approved. The service sends a notification to all users with the `Civil Engineer` role.

```php
// app/Services/StructuralHandoffService.php
// Called from ProjectScheduleActivity approval listener
```

An administrator can also **manually create** a structural design from `/structural-design` if the automatic trigger was missed (e.g., projects started before this feature was deployed).

### Service Design — Triggered by Structural Design Final Approval

The service design record is **auto-created** inside `ProjectStructuralDesign::onApprovalCompleted()` the moment the CEO/MD approves the final structural design via RingleSoft. All users with the `Service Engineer` role are notified immediately.

```php
// app/Models/ProjectStructuralDesign.php → onApprovalCompleted()
if (!ProjectServiceDesign::where('project_id', $this->project_id)->exists()) {
    // Creates ProjectServiceDesign + 4 default stages
    // Notifies all Service Engineers
}
```

A guard prevents duplicate records: if a service design already exists for the project, no second record is created.

---

## Structural Design Workflow

### Actors

| Actor | Role |
|---|---|
| Civil Engineer | Executes the design work |
| Managing Director / CEO | Approves schedule and each stage |
| Quantity Surveyor | Receives notification after final approval |
| Sales Team | Receives notification to share with client |

### Step 1 — Work Schedule Submission

**Who:** Civil Engineer (or any MD/CEO/Admin)

Before any stage work can begin, the engineer must submit a **Work Schedule** describing their plan for all three stages plus the expected timeline.

**Fields:**
- `schedule_description` — free-text plan (max 3,000 characters)
- `schedule_planned_start` — date
- `schedule_planned_end` — date (must be ≥ start)

**Status flow:**
```
not_submitted → submitted → approved
                         ↘ rejected → [engineer revises] → submitted
```

**Gates:**
- Stage work is **blocked** while `schedule_status ≠ 'approved'`
- Final submission is **blocked** while `schedule_status ≠ 'approved'`

**Notifications sent when submitted:** MD + CEO are notified via `SystemActionNotification`.

**Notifications sent when approved/rejected:** Assigned engineer is notified.

### Step 2 — Three Design Stages (Each With Its Own Approval)

**Stages (in order):**

| # | Name |
|---|---|
| 1 | Structural Analysis |
| 2 | Foundation Design |
| 3 | Structural Drawings |

Each stage follows this independent lifecycle:

```
pending → in_progress → completed + file uploaded → [submit for approval]
                                                          │
                                         ┌────────────────┴────────────────┐
                                         ▼                                 ▼
                                   approved ✓                     rejected ✗
                                                                  engineer revises
                                                                  re-submits
```

**Per-stage gates:**
- Engineer can only edit a stage when `approval_status ∈ {pending, rejected}`
- Submit button appears when: `status = completed` AND `file_path is set` AND `approval_status ∉ {submitted, approved}`
- MD/CEO Approve and Reject buttons appear only when `approval_status = submitted`

**On stage rejection:** The stage status reverts from `completed` → `in_progress` so the engineer must redo the work.

**File uploads:** Accepted formats are PDF, DWG, DXF, JPG, JPEG, PNG, ZIP (max 20 MB). Files are stored in `storage/app/public/structural_designs/{design_id}/`.

### Step 3 — Final CEO/MD Approval (RingleSoft)

**Gate:** All three stages must have `approval_status = approved` before the final submit button appears.

The engineer clicks **Submit Final Structural Design**. This calls `ProjectStructuralDesign::submit(Auth::user())` (RingleSoft) and sets `status = submitted`.

The RingleSoft approval component (`<x-ringlesoft-approval-actions>`) then appears on the show page for the Managing Director to give final sign-off.

**On final approval (`onApprovalCompleted`):**
1. Sets `status = approved`, `approved_at = now()`
2. Updates `projects.status = 'structural_approved'`
3. Auto-creates the `ProjectServiceDesign` record + 4 stages
4. Notifies all `Service Engineer` role users
5. Notifies all `Quantity Surveyor (QS)` role users
6. Notifies all `Sales and Marketing` + `Business Development Manager` role users

---

## Service Design Workflow

### Actors

| Actor | Role |
|---|---|
| Service Engineer | Executes the MEP design work |
| Managing Director / CEO | Approves schedule and each stage |
| Quantity Surveyor | Receives notification after final approval |
| Sales Team | Receives notification to share with client |

### Step 1 — Work Schedule Submission

Identical pattern to structural design. The engineer describes their plan covering all four service disciplines with planned start/end dates.

**Notifications sent when submitted:** MD + CEO + Chief Executive Officer are notified.

### Step 2 — Four Service Design Stages (Each With Its Own Approval)

**Stages (in order):**

| # | Name | Description |
|---|---|---|
| 1 | Electrical Drawings | Full electrical layout and wiring diagrams |
| 2 | Fire Alarm Detection (FADS) | Fire detection and alarm system drawings |
| 3 | ICT Drawings | Information and communications technology layout |
| 4 | HVAC Drawings | Heating, ventilation and air conditioning drawings |

The lifecycle for each stage is **identical** to the structural design stages:

```
pending → in_progress → completed + file uploaded → submit → approved / rejected
```

**File storage:** `storage/app/public/service_designs/{design_id}/`

### Step 3 — Final CEO/MD Approval (RingleSoft)

Gate: All **four** stages must be individually approved.

On final approval (`onApprovalCompleted`):
1. Sets `status = approved`, `approved_at = now()`
2. Updates `projects.status = 'service_approved'`
3. Notifies all `Quantity Surveyor (QS)` role users
4. Notifies all `Sales and Marketing` + `Business Development Manager` role users

---

## Role Permissions Matrix

### Structural Design

| Permission | Sys Admin | MD / CEO | Civil Engineer | Project Manager | QS | Sales / BDM |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| View Structural Designs | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create Structural Design | ✓ | ✓ | — | — | — | — |
| Edit Structural Design | ✓ | ✓ | ✓ | — | — | — |
| Delete Structural Design | ✓ | ✓ | — | — | — | — |
| Submit Structural Design | ✓ | ✓ | ✓ | — | — | — |
| Approve Structural Design Schedule | ✓ | ✓ | — | — | — | — |
| Approve Structural Design Stage | ✓ | ✓ | — | — | — | — |
| Reassign Structural Engineer | ✓ | ✓ | — | — | — | — |

### Service Design

| Permission | Sys Admin | MD / CEO | Service Engineer | Project Manager | QS | Sales / BDM |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| View Service Designs | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create Service Design | ✓ | ✓ | — | — | — | — |
| Edit Service Design | ✓ | ✓ | ✓ | — | — | — |
| Delete Service Design | ✓ | ✓ | — | — | — | — |
| Submit Service Design | ✓ | ✓ | ✓ | — | — | — |
| Approve Service Design Schedule | ✓ | ✓ | — | — | — | — |
| Approve Service Design Stage | ✓ | ✓ | — | — | — | — |
| Reassign Service Engineer | ✓ | ✓ | — | — | — | — |

> **Note:** `CEO` and `Chief Executive Officer` are two separate roles in the system. Both receive the same permissions as `Managing Director`.

---

## Menu Structure

```
Sidebar
└── Engineering Design  (fas fa-hard-hat)  [parent_id: null]
    ├── Structural Design  (fas fa-drafting-compass)  → /structural-design
    └── Service Design     (fas fa-tools)             → /service-design
```

The parent item has `route = '#'` and acts as a collapsible section header. Both sub-menus are always visible to any user whose role includes `View Structural Designs` or `View Service Designs` respectively.

---

## Client Portal

Both approved designs surface in the client-facing portal under the project tabs.

### Tab Visibility

Tabs only appear after the respective design has received **final approval**:

```blade
{{-- project_tabs.blade.php --}}
@if(ProjectStructuralDesign::where('project_id', $project->id)->where('status','approved')->exists())
    <a href="...">Structural Design</a>
@endif

@if(ProjectServiceDesign::where('project_id', $project->id)->where('status','approved')->exists())
    <a href="...">Service Design</a>
@endif
```

### What Clients See

Each design tab shows:

1. **Header card** — document number, engineer name, approval date, stage count
2. **Design Documents list** — one row per stage with a **Download** button (links directly to the uploaded file in public storage)
3. **Comments & Feedback form** — client submits free-text comments stored in `structural_design_feedbacks` / `service_design_feedbacks` tables

### Client Portal Routes

```
GET  /client/project/{id}/structural-design          → projectStructuralDesign()
POST /client/project/{id}/structural-design/feedback → submitStructuralFeedback()

GET  /client/project/{id}/service-design             → projectServiceDesign()
POST /client/project/{id}/service-design/feedback    → submitServiceFeedback()
```

All client routes are guarded by `auth:client` middleware.

---

## Dashboard Visibility

The `DashboardController` injects role-scoped variables into the main dashboard view:

| Variable | Visible to | Contents |
|---|---|---|
| `$structuralHandoffs` | Civil Engineer | Own pending/in-progress structural designs |
| `$serviceHandoffs` | Service Engineer | Own pending/in-progress service designs |
| `$qsReadyDesigns` | QS / can View All | Approved structural designs |
| `$qsReadyServiceDesigns` | QS / can View All | Approved service designs |
| `$qsBoqPlans` | QS / can View All | Own BOQ plans |
| `$salesApprovedDesigns` | Sales / BDM | Approved structural designs to share |
| `$salesApprovedServiceDesigns` | Sales / BDM | Approved service designs to share |
| `$salesApprovedBoqs` | Sales / BDM | Approved BOQs to share |

---

## Notifications

All notifications use `App\Notifications\SystemActionNotification` which creates an in-app notification with a title, message body, and a direct link to the resource.

### Structural Design Notification Events

| Event | Notified Parties |
|---|---|
| Work schedule submitted | Managing Director, CEO |
| Work schedule approved | Assigned Civil Engineer |
| Work schedule rejected | Assigned Civil Engineer |
| Stage submitted for approval | Managing Director, CEO |
| Stage approved | Assigned Civil Engineer |
| Stage rejected | Assigned Civil Engineer |
| Final design submitted | (RingleSoft handles this internally) |
| Final design approved | QS team, Sales team, Service Engineers (new design created) |

### Service Design Notification Events

| Event | Notified Parties |
|---|---|
| Service design auto-created | All Service Engineers |
| Work schedule submitted | Managing Director, CEO, Chief Executive Officer |
| Work schedule approved | Assigned Service Engineer |
| Work schedule rejected | Assigned Service Engineer |
| Stage submitted for approval | Managing Director, CEO, Chief Executive Officer |
| Stage approved | Assigned Service Engineer |
| Stage rejected | Assigned Service Engineer |
| Final design approved | QS team, Sales team |

---

## Database Schema

### `project_structural_designs`

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | |
| `triggered_by_activity_id` | FK → project_schedule_activities, nullable | B7 activity that triggered creation |
| `assigned_engineer_id` | FK → users, nullable | Civil Engineer |
| `status` | enum | `pending`, `in_progress`, `submitted`, `approved`, `rejected` |
| `notes` | text, nullable | Admin notes |
| `schedule_description` | text, nullable | Engineer's work plan |
| `schedule_planned_start` | date, nullable | |
| `schedule_planned_end` | date, nullable | |
| `schedule_status` | enum | `not_submitted`, `submitted`, `approved`, `rejected` |
| `schedule_submitted_at` | datetime, nullable | |
| `schedule_approved_at` | datetime, nullable | |
| `schedule_approved_by` | FK → users, nullable | Who approved the schedule |
| `schedule_rejection_notes` | text, nullable | MD's rejection reason |
| `submitted_at` | datetime, nullable | Final submission timestamp |
| `approved_at` | datetime, nullable | RingleSoft final approval timestamp |
| `created_by` | FK → users, nullable | |

### `project_structural_design_stages`

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `structural_design_id` | FK → project_structural_designs | |
| `name` | string | Stage name |
| `stage_order` | tinyint | 1, 2, 3 |
| `status` | enum | `pending`, `in_progress`, `completed` |
| `file_path` | string, nullable | Relative path in public storage |
| `file_name` | string, nullable | Original filename shown to users |
| `notes` | text, nullable | |
| `completed_at` | datetime, nullable | |
| `completed_by` | FK → users, nullable | |
| `approval_status` | enum | `pending`, `submitted`, `approved`, `rejected` |
| `submitted_at` | datetime, nullable | |
| `approved_at` | datetime, nullable | |
| `approved_by` | FK → users, nullable | |
| `rejected_at` | datetime, nullable | |
| `rejection_notes` | text, nullable | MD's rejection reason |

### `structural_design_feedbacks`

| Column | Type | Description |
|---|---|---|
| `id` | bigint PK | |
| `structural_design_id` | FK → project_structural_designs | |
| `client_id` | FK → project_clients | |
| `comment` | text | Client's review comment |

### `project_service_designs`

Identical structure to `project_structural_designs` except:
- `triggered_by_structural_design_id` replaces `triggered_by_activity_id` (FK → project_structural_designs)

### `project_service_design_stages`

Identical structure to `project_structural_design_stages` except:
- `service_design_id` (FK → project_service_designs) instead of `structural_design_id`
- Four stages by default instead of three

### `service_design_feedbacks`

Identical structure to `structural_design_feedbacks` except:
- `service_design_id` (FK → project_service_designs) instead of `structural_design_id`

---

## HTTP Routes Reference

### Structural Design (internal)

| Method | URI | Name | Controller Method |
|---|---|---|---|
| GET | `/structural-design` | `structural_design.index` | `index()` |
| POST | `/structural-design` | `structural_design.store` | `store()` |
| GET | `/structural-design/{design}` | `structural_design.show` | `show()` |
| POST | `/structural-design/{design}/submit` | `structural_design.submit` | `submit()` |
| POST | `/structural-design/{design}/reassign` | `structural_design.reassign` | `reassignEngineer()` |
| POST | `/structural-design/{design}/schedule/submit` | `structural_design.schedule.submit` | `submitSchedule()` |
| POST | `/structural-design/{design}/schedule/approve` | `structural_design.schedule.approve` | `approveSchedule()` |
| POST | `/structural-design/{design}/schedule/reject` | `structural_design.schedule.reject` | `rejectSchedule()` |
| POST | `/structural-design/{design}/stage/{stage}` | `structural_design.stage` | `updateStage()` |
| POST | `/structural-design/{design}/stage/{stage}/submit` | `structural_design.stage.submit` | `submitStage()` |
| POST | `/structural-design/{design}/stage/{stage}/approve` | `structural_design.stage.approve` | `approveStage()` |
| POST | `/structural-design/{design}/stage/{stage}/reject` | `structural_design.stage.reject` | `rejectStage()` |

### Service Design (internal)

| Method | URI | Name | Controller Method |
|---|---|---|---|
| GET | `/service-design` | `service_design.index` | `index()` |
| POST | `/service-design` | `service_design.store` | `store()` |
| GET | `/service-design/{design}` | `service_design.show` | `show()` |
| POST | `/service-design/{design}/submit` | `service_design.submit` | `submit()` |
| POST | `/service-design/{design}/reassign` | `service_design.reassign` | `reassignEngineer()` |
| POST | `/service-design/{design}/schedule/submit` | `service_design.schedule.submit` | `submitSchedule()` |
| POST | `/service-design/{design}/schedule/approve` | `service_design.schedule.approve` | `approveSchedule()` |
| POST | `/service-design/{design}/schedule/reject` | `service_design.schedule.reject` | `rejectSchedule()` |
| PATCH | `/service-design/{design}/stage/{stage}` | `service_design.stage.update` | `updateStage()` |
| POST | `/service-design/{design}/stage/{stage}/submit` | `service_design.stage.submit` | `submitStage()` |
| POST | `/service-design/{design}/stage/{stage}/approve` | `service_design.stage.approve` | `approveStage()` |
| POST | `/service-design/{design}/stage/{stage}/reject` | `service_design.stage.reject` | `rejectStage()` |

### Client Portal

| Method | URI | Name |
|---|---|---|
| GET | `/client/project/{id}/structural-design` | `client.project.structural_design` |
| POST | `/client/project/{id}/structural-design/feedback` | `client.project.structural_design.feedback` |
| GET | `/client/project/{id}/service-design` | `client.project.service_design` |
| POST | `/client/project/{id}/service-design/feedback` | `client.project.service_design.feedback` |

---

## Key Files

### Models
```
app/Models/ProjectStructuralDesign.php       — Main structural design model (RingleSoft Approvable)
app/Models/ProjectStructuralDesignStage.php  — Per-stage model with approval helpers
app/Models/StructuralDesignFeedback.php      — Client feedback on structural designs

app/Models/ProjectServiceDesign.php          — Main service design model (RingleSoft Approvable)
app/Models/ProjectServiceDesignStage.php     — Per-stage model with approval helpers
app/Models/ServiceDesignFeedback.php         — Client feedback on service designs
```

### Controllers
```
app/Http/Controllers/ProjectStructuralDesignController.php
app/Http/Controllers/ProjectServiceDesignController.php
app/Http/Controllers/Client/ClientPortalController.php   — projectStructuralDesign(), projectServiceDesign()
app/Http/Controllers/DashboardController.php             — Role-scoped dashboard variables
```

### Views
```
resources/views/pages/structural_design/index.blade.php  — Structural design list
resources/views/pages/structural_design/show.blade.php   — 3-step workflow UI
resources/views/pages/service_design/index.blade.php     — Service design list
resources/views/pages/service_design/show.blade.php      — 3-step workflow UI
resources/views/client/projects/structural_design.blade.php  — Client portal structural tab
resources/views/client/projects/service_design.blade.php     — Client portal service tab
resources/views/client/partials/project_tabs.blade.php       — Tab visibility logic
```

### Migrations
```
database/migrations/2026_05_07_000002_create_project_structural_designs_table.php
database/migrations/2026_05_07_000003_create_project_structural_design_stages_table.php
database/migrations/2026_05_09_105633_create_structural_design_feedbacks_table.php
database/migrations/2026_05_09_112508_add_schedule_to_structural_designs_table.php
database/migrations/2026_05_09_112508_add_approval_to_structural_design_stages_table.php

database/migrations/2026_05_09_200000_add_service_engineer_role.php
database/migrations/2026_05_09_200001_create_project_service_designs_table.php
database/migrations/2026_05_09_200002_create_project_service_design_stages_table.php
database/migrations/2026_05_09_200003_create_service_design_feedbacks_table.php
database/migrations/2026_05_09_210000_add_engineering_design_menus_and_permissions.php
```

### Seeders
```
database/seeders/StructuralDesignApprovalSeeder.php   — RingleSoft flow: MD approves final structural
database/seeders/ServiceDesignApprovalSeeder.php      — RingleSoft flow: MD approves final service
```

> Run seeders with: `php artisan db:seed --class=StructuralDesignApprovalSeeder`
> and: `php artisan db:seed --class=ServiceDesignApprovalSeeder`
