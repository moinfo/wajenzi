# Project Schedule Workflow

**Module:** Project Schedules
**Role responsible:** Architect (lead), activity assignees (various roles)
**Document number format:** (no external doc number — referenced by lead number)

---

## What This Workflow Does

When a lead is converted to a project (or directly created), the Sales/MD creates a Project Schedule that lists all construction activities organised by phase (A, B, C…). The Architect must:

1. Review and adjust the **start date** (which cascades to all activity dates)
2. Submit the schedule for **MD/CEO approval**
3. Once approved, begin executing activities one by one

The schedule is the master timeline the whole project team works against. Activity B7 ("Final Architectural Drawings") is a special trigger — completing it kicks off the Structural Design workflow.

---

## Trigger

### Automatic — Lead Created or Project Assigned

`ProjectScheduleService` auto-creates a schedule when a lead is created with a project type that has pre-configured activities (via `activity_settings` and `sub_activity_settings` tables).

### Manual — Sales / MD

A schedule can also be manually created from:
- `/project-schedules → New Schedule` (for an existing lead)
- Lead detail page — "Create Schedule" button
- Project detail page — "Create Schedule" button

**Guard:** One schedule per lead. Duplicate creation is blocked.

---

## Actors

| Who | What they do |
|---|---|
| Sales and Marketing / BDM | Creates schedule for a lead |
| Managing Director | Creates schedule; approves/rejects submitted schedule |
| System Administrator | Full access |
| Architect | Adjusts start date; executes activities |
| Activity Assignee | Starts and completes their assigned activities |

---

## Step 1 — Schedule Setup

### What the Architect/Manager sets

| Field | Validation |
|---|---|
| `start_date` | Required, ≥ today. All activity dates cascade from this. |
| `notes` | Optional — reason for adjustment |

`ProjectScheduleService::recalculateSchedule()` shifts all activity start/end dates by the delta between old and new start date.

### Status flow

```
draft (auto-created)
      │ architect reviews & adjusts start date (optional)
      │ architect/manager submits
      ▼
pending_confirmation ──── MD/CEO approves ──▶ confirmed ✓
                │
                └────── MD/CEO rejects ──▶ (back to draft — architect revises)
```

**Gate:** Activities cannot be started until `status = confirmed`.

### Notifications

| Action | Who is notified |
|---|---|
| Schedule submitted | All Managing Directors |
| Schedule approved | Assigned Architect |

---

## Step 2 — Activity Execution

**Gate:** `schedule.status` must be `confirmed` (or `in_progress`/`completed`) before any activity can start.

### Activity statuses

```
pending
   │ assigned user clicks "Start"
   ▼
in_progress
   │ assigned user clicks "Complete" (notes + optional file upload)
   ▼
completed
```

Additionally, activities have an `approval_status` (pending → submitted → approved/rejected) when the activity itself requires verification — but this is handled via the existing `ProjectScheduleActivity` model's own Approvable trait (flow ID 24).

### Activity lifecycle rules

| Condition | Can start? |
|---|---|
| `schedule.status = confirmed/in_progress` | Yes, if predecessor completed |
| `schedule.status = draft/pending_confirmation` | No |
| Predecessor activity not completed | No |

### Activity B7 — Special Trigger

When activity code **B7** ("Final Architectural Drawings") is completed, `StructuralHandoffService` auto-creates a `ProjectStructuralDesign` record and notifies all Civil Engineers. This is the downstream trigger for the [Structural Design Workflow](structural-design-approval-workflow.md).

### File upload on completion

- **Optional** (unlike design stage files which are required)
- **Accepted formats:** PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, ZIP, DWG
- **Max size:** 50 MB
- **Storage path:** `activity-attachments/{schedule_id}/`

### Notifications per activity

| Action | Who is notified |
|---|---|
| Activity started | Assigned Architect + Assigned User (if different from starter) |
| Activity completed | Assigned Architect + Assigned User (if different from completer) |
| Activity reassigned | Newly assigned user (email + in-app) |

---

## Step 3 — Schedule Completion

When all activities reach `status = completed`, the schedule automatically transitions to `status = completed`.

Progress is tracked at:
- **Overall** — `$schedule->progress` (percentage)
- **Per phase** — `$schedule->progress_by_phase`

---

## Reassigning Activities

Only users with the `Assign Project Activities` permission (or MD/CEO/Admin) can reassign.

- **Single reassign** — individual activity → assign modal → `POST /project-schedules/{schedule}/activities/{activity}/assign`
- **Bulk reassign** — checkboxes + floating bar → `POST /project-schedules/{schedule}/bulk-assign`

Both send `ActivityReassignedNotification` (email + in-app) to the newly assigned user.

---

## Permissions

| Permission | Sys Admin | MD / CEO | Architect | Sales / BDM | PM |
|---|:---:|:---:|:---:|:---:|:---:|
| View Project Schedules | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create Project Schedule | ✓ | ✓ | — | ✓ | — |
| Edit Project Schedule | ✓ | ✓ | ✓ | — | — |
| Delete Project Schedule | ✓ | ✓ | — | — | — |
| Submit Project Schedule | ✓ | ✓ | ✓ | — | — |
| Assign Project Activities | ✓ | ✓ | — | — | — |

---

## HTTP Routes

| Method | URI | Route Name | Controller Method |
|---|---|---|---|
| GET | `/project-schedules` | `project-schedules.index` | `index()` |
| GET | `/project-schedules/{id}` | `project-schedules.show` | `show()` |
| GET | `/project-schedules/{id}/edit` | `project-schedules.edit` | `edit()` |
| PATCH | `/project-schedules/{id}` | `project-schedules.update` | `update()` |
| DELETE | `/project-schedules/{id}` | `project-schedules.destroy` | `destroy()` |
| POST | `/project-schedules/{id}/submit` | `project-schedules.submit` | `submit()` |
| POST | `/project-schedules/{id}/activities/{activity}/start` | `project-schedules.activities.start` | `startActivity()` |
| POST | `/project-schedules/{id}/activities/{activity}/complete` | `project-schedules.activities.complete` | `completeActivity()` |
| POST | `/project-schedules/{id}/activities/{activity}/assign` | `project-schedules.activities.assign` | `assignActivity()` |
| POST | `/project-schedules/{id}/bulk-assign` | `project-schedules.bulk-assign` | `bulkAssignActivities()` |
| GET | `/leads/{lead}/schedule` | `leads.schedule` | `showForLead()` |
| POST | `/leads/{lead}/schedule` | `leads.schedule.create` | `createForLead()` |
| POST | `/projects/{project}/schedule` | `projects.schedule.create` | `createForProject()` |
| POST | `/project-schedules/{id}/activities/{activity}/update-days` | — | `updateActivityDays()` |
| POST | `/project-schedules/{id}/activities/{activity}/remove` | — | `removeActivity()` |
| POST | `/project-schedules/{id}/change-architect` | — | `changeArchitect()` |

---

## Database Tables

### `project_schedules`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `lead_id` | FK → leads | |
| `client_id` | FK → project_clients, nullable | |
| `assigned_architect_id` | FK → users, nullable | |
| `start_date` | date | Anchor for all activity dates |
| `end_date` | date, nullable | Last activity end date |
| `status` | enum | `draft` `pending_confirmation` `confirmed` `in_progress` `completed` |
| `confirmed_at` | datetime, nullable | |
| `confirmed_by` | FK → users, nullable | |
| `notes` | text, nullable | |
| `created_by` | FK → users, nullable | |

### `project_schedule_activities`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_schedule_id` | FK → project_schedules | |
| `activity_code` | string | e.g. `B7` |
| `name` | string | Activity label |
| `phase` | string | Phase grouping |
| `duration_days` | int | Estimated working days |
| `start_date` | date | |
| `end_date` | date | |
| `predecessor_id` | FK → self, nullable | Must complete before this can start |
| `sort_order` | int | |
| `assigned_to` | FK → users, nullable | Override assignee |
| `status` | enum | `pending` `in_progress` `completed` `skipped` |
| `started_at` | datetime, nullable | |
| `started_by` | FK → users, nullable | |
| `completed_at` | datetime, nullable | |
| `completed_by` | FK → users, nullable | |
| `completion_notes` | text, nullable | |
| `attachment_path` | string, nullable | |
| `attachment_name` | string, nullable | |

---

## Key Files

```
app/Models/ProjectSchedule.php                     Main model — onApprovalCompleted confirms schedule
app/Models/ProjectScheduleActivity.php             Activity model — canStart(), markAsStarted()
app/Services/ProjectScheduleService.php            recalculateSchedule() — cascades dates
app/Services/StructuralHandoffService.php          Triggered on B7 completion → creates structural design
app/Http/Controllers/ProjectScheduleController.php All 16 workflow actions
app/Notifications/ActivityReassignedNotification.php Email + in-app for reassignment

resources/views/project-schedules/index.blade.php  List
resources/views/project-schedules/show.blade.php   Activity execution UI (per-phase accordion)
resources/views/project-schedules/edit.blade.php   Start date adjustment form

database/seeders/ScheduleApprovalSeeder.php        RingleSoft flow: ProjectSchedule (#26)
```

---

## RingleSoft Flow

```
Flow name: "Project Schedule Approval" (ID 26)
Step 1:    Managing Director → APPROVE
```

Run seeder: `php artisan db:seed --class=ScheduleApprovalSeeder`

---

## Related Workflows

- **Downstream (B7 trigger):** [Structural Design](structural-design-approval-workflow.md)
- **Upstream:** Lead creation / project assignment
