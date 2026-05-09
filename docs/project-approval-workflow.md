# Project & Client Approval Workflow

**Module:** Projects, Clients
**Role responsible:** Sales and Marketing / BDM (creates); MD/CEO (approves)

---

## What This Workflow Does

Before project work can begin, two records must be approved through separate RingleSoft flows:

1. **Project Client** — registers a new client in the system
2. **Project** — registers the project and links it to the approved client

Both use a simple single-step MD approval.

---

## Part 1 — Project Client Approval

### Trigger

A Sales staff member or Admin creates a new `ProjectClient` from `/clients → New Client`.

### Fields

| Field | Notes |
|---|---|
| `name` | Full client name / company name |
| `email` | Contact email |
| `phone` | Contact phone |
| `address` | Physical address |
| `client_source_id` | How the client was acquired |
| `status` | Starts as `CREATED` |

### Approval flow (RingleSoft, flow ID 3)

```
CREATED (auto-submitted on create)
      │ MD/CEO approves
      ▼
APPROVED ✓  → Client can be used in projects
```

`onApprovalCompleted()`: sets `status = APPROVED`.

### After approval

The client appears in project creation dropdowns and gains portal access (client can log in).

---

## Part 2 — Project Approval

### Trigger

A Sales staff member or Admin creates a new `Project` from `/projects → New Project`, selecting an approved client.

### Fields

| Field | Notes |
|---|---|
| `project_name` | Full project name |
| `client_id` | FK → approved project_clients |
| `project_type_id` | Type of construction |
| `service_type_id` | Service category |
| `project_manager_id` | Assigned PM (FK → users) |
| `salesperson_id` | FK → users |
| `status` | Starts as `pending` |

### Approval flow (RingleSoft, flow ID 1)

```
pending (auto-submitted on create)
      │ MD/CEO approves
      ▼
APPROVED ✓  → Project workflow can begin
```

`onApprovalCompleted()`:
- **Only advances** from `pending` → `APPROVED` — does not overwrite statuses set later in the workflow (e.g. `design_phase`, `structural_approved`, `service_approved`)

### Project status lifecycle

As the project progresses through workflows, `projects.status` advances automatically:

| Status | Set by |
|---|---|
| `pending` | Initial creation |
| `APPROVED` | Project approval (this workflow) |
| `design_phase` | (set manually or by design trigger) |
| `structural_approved` | `ProjectStructuralDesign::onApprovalCompleted()` |
| `service_approved` | `ProjectServiceDesign::onApprovalCompleted()` |

---

## HTTP Routes

| Method | URI | Route Name / Controller |
|---|---|---|
| GET | `/projects` | `projects` → `ProjectController@index` |
| POST | `/projects` (via CRUD handler) | `ProjectController@handleCrud` |
| GET/POST | `/projects/{id}/{dt}` | `project` → `projects()` |
| POST | `/projects/{project}/submit` | `ProjectController@submit` |
| POST | `/projects/{project}/approve` | `ProjectController@approve` |
| POST | `/projects/{project}/reject` | `ProjectController@reject` |
| GET | `/clients` | `clients` → `ProjectClientController@index` |
| POST | `/clients` (via CRUD handler) | `handleCrud` |
| GET/POST | `/clients/{id}` | `client` → `ProjectClientController@client` |
| GET/POST | `/clients/{id}/{dt}` | `project_clients` → `project_clients()` |

---

## Database Tables

### `project_clients`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | string | |
| `email` | string, unique | Also used as portal login |
| `phone` | string, nullable | |
| `address` | text, nullable | |
| `client_source_id` | FK, nullable | |
| `status` | string | `CREATED` `APPROVED` |
| `created_by` | FK → users, nullable | |

### `projects`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `project_name` | string | Use `getNameAttribute()` / `orderBy('project_name')` |
| `client_id` | FK → project_clients | |
| `project_type_id` | FK | |
| `service_type_id` | FK | |
| `project_manager_id` | FK → users, nullable | |
| `salesperson_id` | FK → users, nullable | |
| `status` | string | `pending` `APPROVED` `structural_approved` `service_approved`… |
| `created_by` | FK → users, nullable | |

---

## Key Files

```
app/Models/Project.php              onApprovalCompleted — only advances from pending
app/Models/ProjectClient.php        Client model — portal authentication (Authenticatable)

app/Http/Controllers/ProjectController.php
app/Http/Controllers/ProjectClientController.php
```

---

## RingleSoft Flows

| Flow Name | ID | Approver |
|---|---|---|
| Project Approval | 1 | Managing Director |
| Project Client Approval | 3 | Managing Director |

---

## Related Workflows

- **Downstream:** [Project Schedule](project-schedule-workflow.md) — created after project approval
- **Downstream:** [BOQ](boq-workflow.md) — requires approved project
- **Client Portal:** Approved clients can log in and view their projects
