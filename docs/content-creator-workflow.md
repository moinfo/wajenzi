# Content Creator Workflow

**Module:** Content Creator
**Role responsible:** Content creator and IT / Digital Marketing and Content Creator
**Route:** `/content-creator`

---

## What This Workflow Does

The Content Creator module manages social media content production tasks. A manager assigns tasks to content creators across platforms (Instagram, TikTok, Facebook, LinkedIn, YouTube), tracks progress through a Kanban board and calendar, and approves completed work before publication.

---

## Actors

| Who | What they do |
|---|---|
| Manager / Admin | Creates tasks, sets targets, approves completed tasks |
| Content Creator | Updates progress, adds comments, marks tasks complete |

---

## Task Lifecycle

```
pending (task created)
   │ creator updates progress (0-100%)
   ▼
in_progress
   │ creator marks 100% progress ("Submit for Review")
   ▼
completed
   │ manager approves
   ▼
published ✓
```

If rejected:
```
completed → rejected → pending (creator revises)
```

---

## Task Fields

| Field | Values / Notes |
|---|---|
| `title` | Required, max 255 chars |
| `description` | Optional |
| `assigned_to` | FK → users (any active user) |
| `deadline` | Date |
| `deadline_time` | Time of deadline |
| `priority` | `high` `medium` `low` |
| `platform` | `instagram` `tiktok` `facebook` `linkedin` `youtube` `general` |
| `task_type` | `video_shoot` `post_publish` `design_task` `review_approval` `other` |
| `instructions` | Detailed brief for the creator |
| `status` | `pending` `in_progress` `completed` `published` `rejected` |
| `progress` | 0–100 integer |
| `approved_by` | FK → users, nullable |

### Status badge colours

| Status | Badge |
|---|---|
| `pending` | secondary |
| `in_progress` | primary |
| `completed` | warning |
| `published` | success |
| `rejected` | danger |

---

## Views

The index page has four tabs:

| Tab | What it shows |
|---|---|
| **Calendar** | Weekly calendar view of tasks by deadline |
| **Kanban** | Tasks grouped by status columns |
| **Targets** | Platform target vs. actual post counts for the month |
| **Workability** | Crew workload and availability heatmap |

---

## Crew Management

`ContentCreatorCrew` records link users to the content creator team. The crew list drives:
- The workability calendar (who is available per day)
- The default assignee pool in the task form

`PATCH /content-creator/crew/{user}/status` — toggle a user's crew status (active/inactive).

---

## Platform Targets

The manager sets monthly targets per platform (e.g. "30 Instagram posts in May"). `ContentCreatorPlatformTarget` stores `platform`, `month`, `year`, `target_count`.

**Route:** `POST /content-creator/target` → `setTarget()`

---

## HTTP Routes

| Method | URI | Route Name | Controller Method |
|---|---|---|---|
| GET | `/content-creator` | `content_creator.index` | `index()` |
| POST | `/content-creator/tasks` | `content_creator.tasks.store` | `storeTask()` |
| PATCH | `/content-creator/tasks/{task}` | `content_creator.tasks.update` | `updateTask()` |
| PATCH | `/content-creator/tasks/{task}/progress` | `content_creator.tasks.progress` | `updateProgress()` |
| POST | `/content-creator/tasks/{task}/comment` | `content_creator.tasks.comment` | `addComment()` |
| POST | `/content-creator/tasks/{task}/approve` | `content_creator.tasks.approve` | `approveTask()` |
| GET | `/content-creator/tasks/{task}` | `content_creator.tasks.get` | `getTask()` |
| DELETE | `/content-creator/tasks/{task}` | `content_creator.tasks.destroy` | `destroyTask()` |
| POST | `/content-creator/target` | `content_creator.target` | `setTarget()` |
| PATCH | `/content-creator/crew/{user}/status` | `content_creator.crew.status` | `updateCrewStatus()` |

---

## Database Tables

### `content_creator_tasks`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `title` | string | |
| `description` | text, nullable | |
| `assigned_to` | FK → users, nullable | |
| `created_by` | FK → users | |
| `approved_by` | FK → users, nullable | |
| `deadline` | date, nullable | |
| `deadline_time` | time, nullable | |
| `priority` | enum | `high` `medium` `low` |
| `platform` | string | |
| `task_type` | string | |
| `instructions` | text, nullable | |
| `status` | string | `pending` `in_progress` `completed` `published` `rejected` |
| `progress` | tinyint | 0–100 |

### `content_creator_task_comments`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `task_id` | FK → content_creator_tasks | |
| `user_id` | FK → users | |
| `comment` | text | |

### `content_creator_crews`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK → users | |
| `is_active` | boolean | |

### `content_creator_platform_targets`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `platform` | string | |
| `month` | tinyint | |
| `year` | year | |
| `target_count` | int | |

---

## Key Files

```
app/Models/ContentCreatorTask.php              Task model — status helpers, isOverdue()
app/Models/ContentCreatorTaskComment.php       Comment model
app/Models/ContentCreatorCrew.php              Crew membership
app/Models/ContentCreatorPlatformTarget.php    Monthly targets

app/Http/Controllers/ContentCreatorController.php   All 10 actions + 4 private build* helpers

resources/views/pages/content_creator/index.blade.php   Calendar / Kanban / Targets / Workability tabs
```

---

## Notes

- This module does **not** use RingleSoft approvals — approval is a simple `approveTask()` method that sets `status = published` and `approved_by = auth()->id()`
- Any user can be assigned a task regardless of role; the "creator roles" filter only applies to the crew/calendar view
