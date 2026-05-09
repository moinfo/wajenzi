# Structural Design Approval Workflow

**Module:** Engineering Design → Structural Design
**Role responsible:** Civil Engineer
**Document number format:** `STR-0001 / Project Name`

---

## What This Workflow Does

When the architect completes the final architectural drawings (activity B7), the system automatically creates a Structural Design record and assigns it to the Civil Engineering team. The Civil Engineer must then:

1. Submit a **Work Schedule** (timeline and approach) for management approval
2. Complete and get each of the **3 design stages** individually approved
3. Submit the **complete design** for final CEO/MD sign-off

Only after the final sign-off does the Quantity Surveyor and Sales team get notified, and the Client Portal tab becomes visible to the client.

---

## Trigger

### Automatic — B7 Activity Approval

The record is created automatically by `StructuralHandoffService` when a project schedule activity with code **B7** (Final Architectural Drawings) is approved.

```
app/Services/StructuralHandoffService.php
```

All users with the `Civil Engineer` role receive an in-app notification with a direct link to the new design.

### Manual — Admin Override

An administrator can manually create a structural design from the index page (`/structural-design → New Structural Design`) in cases where the B7 trigger was missed for older projects.

**Guard:** Only one structural design is allowed per project. The store action rejects duplicates.

---

## Actors

| Who | What they do |
|---|---|
| Civil Engineer | Fills schedule, works on stages, submits for approval |
| Managing Director | Approves/rejects work schedule and each stage |
| CEO / Chief Executive Officer | Same authority as MD |
| System Administrator | Full access — can act as any role |
| Quantity Surveyor | Notified after final approval; prepares BOQ |
| Sales and Marketing / BDM | Notified after final approval; shares with client |
| Client | Views approved drawings and submits feedback via portal |

---

## Step 1 — Work Schedule

**Who acts:** Civil Engineer (assigned) or MD/CEO/Admin

### What the engineer submits

| Field | Validation |
|---|---|
| `schedule_description` | Required, max 3,000 chars. Describe the full plan for all three stages. |
| `schedule_planned_start` | Required date |
| `schedule_planned_end` | Required date, must be ≥ start |

### Status flow

```
not_submitted
      │ engineer submits
      ▼
  submitted ──── MD/CEO approves ──▶ approved ✓
      │
      └────────── MD/CEO rejects ──▶ rejected
                                         │ engineer revises & resubmits
                                         ▼
                                     submitted (again)
```

### Gates enforced

- **Stage work is locked** (`status = pending`, edit forms hidden) until `schedule_status = approved`
- **Final submission is locked** until `schedule_status = approved`

### Notifications

| Action | Who is notified |
|---|---|
| Engineer submits schedule | Managing Director, CEO |
| MD approves schedule | Assigned Civil Engineer |
| MD rejects schedule | Assigned Civil Engineer (with rejection reason) |

---

## Step 2 — Design Stages

**Gate:** Schedule must be approved before any stage work can begin.

### The three stages

| Order | Stage Name |
|---|---|
| 1 | Structural Analysis |
| 2 | Foundation Design |
| 3 | Structural Drawings |

Each stage is **independent** — it has its own status and its own approval cycle. Stages can be worked on in any order once the schedule is approved.

### Per-stage lifecycle

```
pending
   │ engineer starts work
   ▼
in_progress
   │ engineer marks complete + uploads file
   ▼
completed (file attached)
   │ engineer clicks "Submit for Approval"
   ▼
submitted ──── MD/CEO approves ──▶ approved ✓  (stage locked, cannot edit)
   │
   └────────── MD/CEO rejects ──▶ rejected
                                      │ stage reverts to in_progress
                                      │ engineer corrects and re-uploads
                                      ▼
                                  completed → submit again
```

### Stage edit rules

| Condition | Can engineer edit? |
|---|---|
| `approval_status = pending` | Yes |
| `approval_status = rejected` | Yes |
| `approval_status = submitted` | No — awaiting approval |
| `approval_status = approved` | No — locked |

### File upload requirements

- **Required before submitting for approval** — submit button is disabled if no file attached
- **Accepted formats:** PDF, DWG, DXF, JPG, JPEG, PNG, ZIP
- **Max size:** 20 MB per file
- **Storage path:** `storage/app/public/structural_designs/{design_id}/`
- **Access:** `Storage::url($stage->file_path)` returns the public URL

### Notifications per stage

| Action | Who is notified |
|---|---|
| Stage submitted for approval | Managing Director, CEO |
| Stage approved | Assigned Civil Engineer |
| Stage rejected | Assigned Civil Engineer (with rejection reason) |

---

## Step 3 — Final CEO/MD Approval

**Gate:** All three stages must have `approval_status = approved` before the submit button appears.

### How final submission works

The Civil Engineer clicks **Submit Final Structural Design**. This calls:

```php
$design->submit(Auth::user());         // RingleSoft — records submission
$design->update(['submitted_at' => now(), 'status' => 'submitted']);
```

The RingleSoft approval component (`<x-ringlesoft-approval-actions :model="$design" />`) then becomes visible on the show page. The Managing Director uses it to approve or reject.

### RingleSoft approval flow

The flow is seeded by `StructuralDesignApprovalSeeder`:

```
Flow name: "Structural Design Approval"
Step 1:    Managing Director → APPROVE
```

Run seeder: `php artisan db:seed --class=StructuralDesignApprovalSeeder`

### On final approval — `onApprovalCompleted()`

```php
// app/Models/ProjectStructuralDesign.php
public function onApprovalCompleted(ProcessApproval $approval): bool
```

What happens automatically:

1. `status = approved`, `approved_at = now()`
2. `projects.status = 'structural_approved'` (unlocks BOQ preparation gate)
3. **Auto-creates** `ProjectServiceDesign` + 4 MEP stages (if not already exists)
4. Notifies all `Service Engineer` role users — service workflow begins
5. Notifies all `Quantity Surveyor (QS)` role users — BOQ preparation can start
6. Notifies all `Sales and Marketing` and `Business Development Manager` users

---

## Permissions

| Permission | Sys Admin | MD / CEO | Civil Engineer | PM | QS | Sales / BDM |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| View Structural Designs | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create Structural Design | ✓ | ✓ | — | — | — | — |
| Edit Structural Design | ✓ | ✓ | ✓ | — | — | — |
| Delete Structural Design | ✓ | ✓ | — | — | — | — |
| Submit Structural Design | ✓ | ✓ | ✓ | — | — | — |
| Approve Structural Design Schedule | ✓ | ✓ | — | — | — | — |
| Approve Structural Design Stage | ✓ | ✓ | — | — | — | — |
| Reassign Structural Engineer | ✓ | ✓ | — | — | — | — |

> `CEO` and `Chief Executive Officer` are separate roles but both receive the same permissions as `Managing Director`.

---

## Menu

```
Engineering Design  (sidebar parent)
└── Structural Design → /structural-design     [fas fa-drafting-compass]
```

Menu is visible to any role with **View Structural Designs** permission.

---

## Dashboard Cards

| Role | Variable | What it shows |
|---|---|---|
| Civil Engineer | `$structuralHandoffs` | Own assigned designs with status `pending` or `in_progress` |
| Quantity Surveyor | `$qsReadyDesigns` | All designs with `status = approved` |
| Sales / BDM | `$salesApprovedDesigns` | All designs with `status = approved` |

---

## Client Portal

The **Structural Design** tab appears on the client's project page **only after** `status = approved`.

**Route:** `GET /client/project/{id}/structural-design` → `client.project.structural_design`

**What the client sees:**

1. Approval badge with document number, engineer name, and approval date
2. A row for each stage showing the stage name and a **Download** button for the uploaded file
3. A comment/feedback form — submissions saved to `structural_design_feedbacks`

**Feedback route:** `POST /client/project/{id}/structural-design/feedback` → `client.project.structural_design.feedback`

---

## HTTP Routes

| Method | URI | Route Name | Controller Method |
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
| GET | `/client/project/{id}/structural-design` | `client.project.structural_design` | `projectStructuralDesign()` |
| POST | `/client/project/{id}/structural-design/feedback` | `client.project.structural_design.feedback` | `submitStructuralFeedback()` |

---

## Database Tables

### `project_structural_designs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | |
| `triggered_by_activity_id` | FK → project_schedule_activities, nullable | B7 activity |
| `assigned_engineer_id` | FK → users, nullable | Civil Engineer |
| `status` | enum | `pending` `in_progress` `submitted` `approved` `rejected` |
| `notes` | text, nullable | Admin notes at creation |
| `schedule_description` | text, nullable | Engineer's work plan narrative |
| `schedule_planned_start` | date, nullable | |
| `schedule_planned_end` | date, nullable | |
| `schedule_status` | enum | `not_submitted` `submitted` `approved` `rejected` |
| `schedule_submitted_at` | datetime, nullable | |
| `schedule_approved_at` | datetime, nullable | |
| `schedule_approved_by` | FK → users, nullable | |
| `schedule_rejection_notes` | text, nullable | |
| `submitted_at` | datetime, nullable | Final submission to RingleSoft |
| `approved_at` | datetime, nullable | RingleSoft final approval |
| `created_by` | FK → users, nullable | |

### `project_structural_design_stages`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `structural_design_id` | FK → project_structural_designs | |
| `name` | string | Stage label |
| `stage_order` | tinyint | 1, 2, or 3 |
| `status` | enum | `pending` `in_progress` `completed` |
| `file_path` | string, nullable | Path in public storage |
| `file_name` | string, nullable | Original filename |
| `notes` | text, nullable | Engineer's stage notes |
| `completed_at` | datetime, nullable | |
| `completed_by` | FK → users, nullable | |
| `approval_status` | enum | `pending` `submitted` `approved` `rejected` |
| `submitted_at` | datetime, nullable | |
| `approved_at` | datetime, nullable | |
| `approved_by` | FK → users, nullable | |
| `rejected_at` | datetime, nullable | |
| `rejection_notes` | text, nullable | MD's rejection reason |

### `structural_design_feedbacks`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `structural_design_id` | FK → project_structural_designs | |
| `client_id` | FK → project_clients | |
| `comment` | text | Client's review comment |
| `created_at` / `updated_at` | datetime | |

---

## Key Files

```
app/Models/ProjectStructuralDesign.php            Main model — Approvable, relationships, onApprovalCompleted
app/Models/ProjectStructuralDesignStage.php       Stage model — isApproved/isSubmitted/isRejected helpers, defaultStages()
app/Models/StructuralDesignFeedback.php           Client feedback model

app/Services/StructuralHandoffService.php         Auto-creates design on B7 approval

app/Http/Controllers/ProjectStructuralDesignController.php   All 12 workflow actions

resources/views/pages/structural_design/index.blade.php      List + create modal
resources/views/pages/structural_design/show.blade.php        3-step workflow UI
resources/views/client/projects/structural_design.blade.php  Client portal tab

database/seeders/StructuralDesignApprovalSeeder.php          RingleSoft flow setup

database/migrations/2026_05_07_000002_create_project_structural_designs_table.php
database/migrations/2026_05_07_000003_create_project_structural_design_stages_table.php
database/migrations/2026_05_09_105633_create_structural_design_feedbacks_table.php
database/migrations/2026_05_09_112508_add_schedule_to_structural_designs_table.php
database/migrations/2026_05_09_112508_add_approval_to_structural_design_stages_table.php
database/migrations/2026_05_09_210000_add_engineering_design_menus_and_permissions.php
```
