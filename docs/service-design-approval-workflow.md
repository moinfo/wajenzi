# Service Design Approval Workflow

**Module:** Engineering Design → Service Design
**Role responsible:** Service Engineer
**Disciplines covered:** Electrical, Fire Alarm Detection (FADS), ICT, HVAC
**Document number format:** `SVC-0001 / Project Name`

---

## What This Workflow Does

After the Structural Design is fully approved by the CEO/MD, the system automatically creates a Service Design record for the MEP (Mechanical, Electrical, Plumbing) engineering work. The Service Engineer must then:

1. Submit a **Work Schedule** covering all four service disciplines for management approval
2. Complete and get each of the **4 discipline stages** individually approved
3. Submit the **complete service design** for final CEO/MD sign-off

After final sign-off the Quantity Surveyor and Sales team are notified, and the Client Portal tab for Service Design becomes visible.

---

## Trigger

### Automatic — Structural Design Final Approval

The Service Design record is created **automatically** inside `ProjectStructuralDesign::onApprovalCompleted()` the instant the CEO/MD approves the final structural design:

```php
// app/Models/ProjectStructuralDesign.php
public function onApprovalCompleted(ProcessApproval $approval): bool
{
    // ...structural approval logic...

    if (!ProjectServiceDesign::where('project_id', $this->project_id)->exists()) {
        $serviceDesign = ProjectServiceDesign::create([...]);
        // Creates 4 default stages
        // Notifies all Service Engineers
    }
}
```

**Guard:** One service design per project. If a record already exists, no duplicate is created.

### Manual — Admin Override

A System Administrator or MD/CEO can manually create a service design from `/service-design → New Service Design` for projects where the trigger was missed.

---

## Actors

| Who | What they do |
|---|---|
| Service Engineer | Fills schedule, works on stages, submits for approval |
| Managing Director | Approves/rejects work schedule and each stage |
| CEO / Chief Executive Officer | Same authority as MD |
| System Administrator | Full access — can act as any role |
| Quantity Surveyor | Notified after final approval; includes service design in BOQ |
| Sales and Marketing / BDM | Notified after final approval; shares with client |
| Client | Views approved drawings and submits feedback via portal |

---

## Step 1 — Work Schedule

**Who acts:** Service Engineer (assigned) or MD/CEO/Admin

### What the engineer submits

| Field | Validation |
|---|---|
| `schedule_description` | Required, max 3,000 chars. Must cover the plan for all four disciplines. |
| `schedule_planned_start` | Required date |
| `schedule_planned_end` | Required date, must be ≥ start |

**Tip for engineers:** Describe the approach and timeline for each discipline separately within the description — Electrical first, then FADS, ICT, and HVAC.

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

- **Stage work is locked** until `schedule_status = approved`
- **Final submission is locked** until `schedule_status = approved`

### Notifications

| Action | Who is notified |
|---|---|
| Engineer submits schedule | Managing Director, CEO, Chief Executive Officer |
| MD approves schedule | Assigned Service Engineer |
| MD rejects schedule | Assigned Service Engineer (with rejection reason) |

---

## Step 2 — Service Design Stages

**Gate:** Schedule must be approved before any stage can be worked on.

### The four stages

| Order | Stage Name | Discipline |
|---|---|---|
| 1 | Electrical Drawings | Full electrical layout, wiring diagrams, load schedules |
| 2 | Fire Alarm Detection (FADS) | Fire detection and alarm system layout, panel locations |
| 3 | ICT Drawings | Data, voice, network, CCTV, access control layouts |
| 4 | HVAC Drawings | Heating, ventilation and air conditioning duct layouts |

Each stage is **independent** — it has its own status and approval cycle. They can be worked on simultaneously.

### Per-stage lifecycle

```
pending
   │ engineer starts work
   ▼
in_progress
   │ engineer marks complete + uploads drawing file
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
| `approval_status = approved` | No — locked permanently |

### File upload requirements

- **Required before submitting for approval** — the submit button is disabled if no file is attached
- **Accepted formats:** PDF, DWG, DXF, JPG, JPEG, PNG, ZIP
- **Max size:** 20 MB per file
- **Storage path:** `storage/app/public/service_designs/{design_id}/`
- **Public URL:** `Storage::url($stage->file_path)`

### Notifications per stage

| Action | Who is notified |
|---|---|
| Stage submitted for approval | Managing Director, CEO, Chief Executive Officer |
| Stage approved | Assigned Service Engineer |
| Stage rejected | Assigned Service Engineer (with rejection reason) |

---

## Step 3 — Final CEO/MD Approval

**Gate:** All four stages must have `approval_status = approved` before the final submit button appears.

### How final submission works

The Service Engineer clicks **Submit Final Service Design**. Internally:

```php
$design->submit(Auth::user());   // RingleSoft records submission
$design->update(['submitted_at' => now(), 'status' => 'submitted']);
```

The RingleSoft approval component (`<x-ringlesoft-approval-actions :model="$design" />`) becomes visible on the show page. The Managing Director gives the final sign-off.

### RingleSoft approval flow

The flow is seeded by `ServiceDesignApprovalSeeder`:

```
Flow name: "Service Design Approval"
Step 1:    Managing Director → APPROVE
```

Run seeder: `php artisan db:seed --class=ServiceDesignApprovalSeeder`

### On final approval — `onApprovalCompleted()`

```php
// app/Models/ProjectServiceDesign.php
public function onApprovalCompleted(ProcessApproval $approval): bool
```

What happens automatically:

1. `status = approved`, `approved_at = now()`
2. `projects.status = 'service_approved'`
3. Notifies all `Quantity Surveyor (QS)` role users — include service drawings in BOQ
4. Notifies all `Sales and Marketing` and `Business Development Manager` users — ready to share

---

## Permissions

| Permission | Sys Admin | MD / CEO | Service Engineer | PM | QS | Sales / BDM |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| View Service Designs | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create Service Design | ✓ | ✓ | — | — | — | — |
| Edit Service Design | ✓ | ✓ | ✓ | — | — | — |
| Delete Service Design | ✓ | ✓ | — | — | — | — |
| Submit Service Design | ✓ | ✓ | ✓ | — | — | — |
| Approve Service Design Schedule | ✓ | ✓ | — | — | — | — |
| Approve Service Design Stage | ✓ | ✓ | — | — | — | — |
| Reassign Service Engineer | ✓ | ✓ | — | — | — | — |

> `CEO` and `Chief Executive Officer` are separate roles but both receive the same permissions as `Managing Director`.

---

## Menu

```
Engineering Design  (sidebar parent)
└── Service Design → /service-design     [fas fa-tools]
```

Menu is visible to any role with **View Service Designs** permission.

---

## Dashboard Cards

| Role | Variable | What it shows |
|---|---|---|
| Service Engineer | `$serviceHandoffs` | Own assigned designs with status `pending` or `in_progress` |
| Quantity Surveyor | `$qsReadyServiceDesigns` | All designs with `status = approved` |
| Sales / BDM | `$salesApprovedServiceDesigns` | All designs with `status = approved` |

---

## Client Portal

The **Service Design** tab appears on the client's project page **only after** `status = approved`.

**Route:** `GET /client/project/{id}/service-design` → `client.project.service_design`

**What the client sees:**

1. Approval header with document number, engineer name, and approval date
2. A row for each of the four disciplines with a colour-coded icon and a **Download** button for the uploaded file
3. A comment/feedback form — submissions saved to `service_design_feedbacks`

| Stage | Icon |
|---|---|
| Electrical Drawings | `fa-bolt` |
| Fire Alarm Detection (FADS) | `fa-fire-alt` |
| ICT Drawings | `fa-network-wired` |
| HVAC Drawings | `fa-wind` |

**Feedback route:** `POST /client/project/{id}/service-design/feedback` → `client.project.service_design.feedback`

All client routes are guarded by `auth:client` middleware.

---

## HTTP Routes

| Method | URI | Route Name | Controller Method |
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
| GET | `/client/project/{id}/service-design` | `client.project.service_design` | `projectServiceDesign()` |
| POST | `/client/project/{id}/service-design/feedback` | `client.project.service_design.feedback` | `submitServiceFeedback()` |

---

## Database Tables

### `project_service_designs`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_id` | FK → projects | |
| `triggered_by_structural_design_id` | FK → project_structural_designs, nullable | Structural design that triggered creation |
| `assigned_engineer_id` | FK → users, nullable | Service Engineer |
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

### `project_service_design_stages`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `service_design_id` | FK → project_service_designs | |
| `name` | string | Discipline label |
| `stage_order` | tinyint | 1, 2, 3, or 4 |
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

### `service_design_feedbacks`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `service_design_id` | FK → project_service_designs | |
| `client_id` | FK → project_clients | |
| `comment` | text | Client's review comment |
| `created_at` / `updated_at` | datetime | |

---

## Key Files

```
app/Models/ProjectServiceDesign.php              Main model — Approvable, relationships, onApprovalCompleted
app/Models/ProjectServiceDesignStage.php         Stage model — isApproved/isSubmitted/isRejected helpers, defaultStages()
app/Models/ServiceDesignFeedback.php             Client feedback model

app/Models/ProjectStructuralDesign.php           onApprovalCompleted() — triggers this workflow

app/Http/Controllers/ProjectServiceDesignController.php   All 12 workflow actions

resources/views/pages/service_design/index.blade.php      List + create modal
resources/views/pages/service_design/show.blade.php       3-step workflow UI
resources/views/client/projects/service_design.blade.php  Client portal tab

database/seeders/ServiceDesignApprovalSeeder.php          RingleSoft flow setup

database/migrations/2026_05_09_200000_add_service_engineer_role.php
database/migrations/2026_05_09_200001_create_project_service_designs_table.php
database/migrations/2026_05_09_200002_create_project_service_design_stages_table.php
database/migrations/2026_05_09_200003_create_service_design_feedbacks_table.php
database/migrations/2026_05_09_210000_add_engineering_design_menus_and_permissions.php
```

---

## Related Workflows

- **Upstream:** [Structural Design Approval Workflow](structural-design-approval-workflow.md) — must complete before this workflow starts
- **Downstream:** BOQ Preparation — QS uses both structural and service approved designs to prepare the bill of quantities
