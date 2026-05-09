# Field Marketing Workflow

**Module:** Field Marketing  
**Role responsible:** Field Marketing Officers; Manager (targets + stats)  
**Route:** `/field-marketing`

---

## What This Workflow Does

Field Marketing Officers conduct door-to-door or area-based marketing sessions. They record each session and log every prospect visit within it. Managers track targets, conversions, and performance statistics by month.

There is **no RingleSoft approval** in this module — it is a data-capture and reporting workflow.

---

## Actors

| Who | What they do |
|---|---|
| Field Marketing Officer | Creates sessions, logs visits |
| Manager / Admin | Sets monthly targets, views statistics |

---

## Data Structure

```
FieldMarketingSession  (one session = one day/area outing)
   └── FieldMarketingVisit  (one visit = one household/prospect contacted)
```

---

## Sessions

A session represents a single field marketing outing (a specific day and area).

### Session fields

| Field | Notes |
|---|---|
| `officer_id` | FK → users (the field officer) |
| `date` | Date of the session |
| `area` | Location / neighbourhood |
| `notes` | General notes |

### Session list view

- Officers see only their own sessions
- Managers see all sessions, filterable by officer

### Session metrics (computed)

- `visits_count` — total prospects contacted
- `interested_count` — visits with `status = interested`
- `converted_count` — visits with `status = converted`

---

## Visits

Each visit within a session records a single prospect interaction.

### Visit fields

| Field | Notes |
|---|---|
| `session_id` | FK → field_marketing_sessions |
| `name` | Prospect name |
| `phone` | Contact number |
| `status` | `visited` `interested` `not_interested` `converted` |
| `services_interested` | Which services the prospect expressed interest in |
| `notes` | Visit notes |

### Lead conversion

When a visit is marked `converted`, a lead can be created from the prospect data.

---

## Targets

Monthly visit targets can be set per officer.

### Target fields

| Field | Notes |
|---|---|
| `officer_id` | FK → users |
| `year` | |
| `month` | |
| `target_visits` | Monthly visit target |

**Route:** `POST /field-marketing/targets` → `storeTarget()`

---

## Services

`FieldMarketingService` is a shared lookup used by both Field Marketing and WhatsApp Marketing to categorise services prospects are interested in.

---

## Views (Tabs)

| Tab | Content |
|---|---|
| **Sessions** | List of sessions with visit/interest/conversion counts |
| **Visits** | All visits across sessions, filterable |
| **Targets** | Officer targets vs. actual for the month |
| **Stats** | Summary statistics charts |
| **Services** | Service type management |

---

## HTTP Routes

| Method | URI | Controller Method |
|---|---|---|
| GET | `/field-marketing` | `index()` |
| POST | `/field-marketing/sessions` | `storeSession()` |
| GET | `/field-marketing/sessions/{id}` | `showSession()` |
| PATCH | `/field-marketing/sessions/{id}` | `updateSession()` |
| DELETE | `/field-marketing/sessions/{id}` | `destroySession()` |
| POST | `/field-marketing/sessions/{id}/visits` | `storeVisit()` |
| PATCH | `/field-marketing/visits/{id}` | `updateVisit()` |
| DELETE | `/field-marketing/visits/{id}` | `destroyVisit()` |
| POST | `/field-marketing/targets` | `storeTarget()` |
| POST | `/field-marketing/services` | `storeService()` |
| PATCH | `/field-marketing/services/{id}` | `updateService()` |
| DELETE | `/field-marketing/services/{id}` | `destroyService()` |

---

## Database Tables

### `field_marketing_sessions`

`id`, `officer_id`, `date`, `area`, `notes`

### `field_marketing_visits`

`id`, `session_id`, `name`, `phone`, `status`, `notes`

### `field_marketing_targets`

`id`, `officer_id`, `year`, `month`, `target_visits`

### `field_marketing_services`

`id`, `name`, `sort_order`, `is_active`

---

## Key Files

```
app/Models/FieldMarketingSession.php
app/Models/FieldMarketingVisit.php
app/Models/FieldMarketingTarget.php
app/Models/FieldMarketingService.php

app/Http/Controllers/FieldMarketingController.php

resources/views/pages/field_marketing/index.blade.php
```
