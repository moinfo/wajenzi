# Client Portal - Setup & Usage Guide

## Overview

The Client Portal is a **read-only** interface where project clients can log in (separately from staff) to view everything about their projects: overview, BOQ, schedule, financials, documents, and reports.

- **URL**: `/client/login`
- **Authentication**: Separate `client` guard — client and staff sessions are completely independent
- **Security**: Clients can only see projects assigned to them (`client_id` ownership check on every request)

---

## Admin Setup

### 1. Creating a Client Portal Account

1. Go to **Projects > Clients** in the staff dashboard
2. Click **New Client** (or edit an existing client)
3. Fill in the standard client details (name, email, phone, etc.)
4. Scroll down to the **Client Portal Access** section:
   - **Portal Password**: Set a password the client will use to log in
   - **Confirm Password**: Re-enter the password
   - **Enable Portal Access**: Check this box to allow the client to log in (checked by default)
5. Click **Submit** (or **Update** for existing clients)

> **Note**: The password is required for portal access. Clients without a password will show "No Account" in the Portal column.

### 2. Managing Portal Access

In the **Clients** table, two columns show portal status:

| Column | Description |
|--------|-------------|
| **Portal** | Shows `Active` (green), `Disabled` (red), or `No Account` (grey) |
| **Last Login** | Shows when the client last logged in (e.g., "2 hours ago") |

To **disable** a client's portal access without deleting their account:
1. Edit the client
2. Uncheck **Enable Portal Access**
3. Update — the client will be logged out and blocked from logging in again

To **change** a client's password:
1. Edit the client
2. Enter a new password in the Portal Password field
3. Update — leave the field blank to keep the current password

### 3. Assigning Projects to Clients

Clients see projects where `projects.client_id` matches their ID. To assign a project to a client:
1. Create or edit a **Project**
2. Set the **Client** field to the desired client
3. The project will automatically appear in that client's portal dashboard

### 4. Uploading Progress Images

Progress images let clients see visual updates of construction work directly in the portal.

1. Open the **Progress Images** form in the admin panel using `loadFormModal('project_progress_image_form', {className: 'ProjectProgressImage'}, 'Upload Progress Image', 'modal-md')`
2. Select the **Project**
3. Optionally select a **Construction Phase** (links the image to a specific phase for filtering)
4. Add a **Title** (e.g. "Foundation pouring Day 3") and **Date Taken**
5. Add a brief **Description** of the work shown
6. Select the **Image** file (PNG, JPG, JPEG)
7. Click **Submit**

The image will appear in the client's **Gallery** tab, sorted by date (newest first). Clients can filter by construction phase and download images.

### 5. Work Progress Setup

The **Overview** tab automatically shows progress charts when a `ProjectSchedule` is linked to the client. To enable:

1. Ensure a `ProjectSchedule` exists with `client_id` set to the client's ID
2. Add `ProjectScheduleActivity` records to the schedule with `phase`, `status` (pending/in_progress/completed), and `duration_days`
3. Progress is calculated automatically — no manual percentage entry needed

---

## Client Experience

### Login

- Visit `/client/login`
- Enter **email address** or **phone number** + password
- The system auto-detects whether the input is an email or phone number

### Dashboard (`/client/dashboard`)

Shows all projects assigned to the client with:
- Summary stats: total projects, active projects, contract value, total invoiced
- Project cards with status, dates, contract value, and quick links
- Click **View Project** to see full details

### Project Tabs

Each project has 7 tabs:

#### Overview (`/client/project/{id}`)
- **Work Progress** (shown when a schedule with activities exists):
  - Overall progress doughnut chart with completion percentage
  - Status breakdown: completed, in progress, pending, and overdue activity counts
  - Horizontal bar chart showing per-phase completion percentages
  - Phase details table with completed/total activities per phase
- Project details: type, service, manager, priority, description
- Timeline: start date, expected end, planned duration, delay warnings
- Contract value
- Construction phases table

> **Note**: Progress data comes from the `ProjectSchedule` model linked to the client. If no schedule exists or it has no activities, the progress section is hidden.

#### BOQ (`/client/project/{id}/boq`)
- Full Bill of Quantities with hierarchical sections
- Items grouped by section with type badges (material/labour)
- Quantities, unit prices, totals
- Grand total per BOQ

#### Schedule (`/client/project/{id}/schedule`)
- Construction phases with status badges
- Schedule activities with codes, phases, durations, and status
- Overdue activities highlighted

#### Financials (`/client/project/{id}/financials`)
- Summary cards: contract value, invoiced, paid, balance due
- Invoices table with amounts, paid amounts, and balances
- Payments table with dates, methods, and references

#### Documents (`/client/project/{id}/documents`)
- Project designs with type, version, status, and download links
- Project file download (if uploaded)

#### Gallery (`/client/project/{id}/gallery`)
- Grid of progress images (3 per row, responsive)
- Each card shows thumbnail, title, date taken, and construction phase badge
- **Phase filter**: dropdown buttons to show images from a specific construction phase
- Click any image to open full-size in a lightbox modal
- **Download** button on each image in the lightbox

#### Reports (`/client/project/{id}/reports`)
- **Daily Reports**: Accordion view with date, weather, work completed, materials used, labor hours, and issues
- **Site Visits**: Inspector name, location, findings, and recommendations
- **PDF Download**: Each site visit has a small download button that generates a professional PDF report with company header, visit details, findings, and recommendations

---

## Technical Architecture

### Authentication

```
config/auth.php
├── Guard: 'client' (session driver, 'clients' provider)
├── Provider: 'clients' (eloquent, ProjectClient::class)
└── Passwords: 'clients' (password_resets table)
```

The `ProjectClient` model extends `Illuminate\Foundation\Auth\User` (Authenticatable) instead of `Model`, enabling Laravel's built-in auth features.

### Middleware

| Middleware | Purpose |
|-----------|---------|
| `client.auth` | Checks `Auth::guard('client')` + `portal_access_enabled` |
| `guest:client` | Prevents authenticated clients from seeing login page |
| `Authenticate` | Modified to redirect `client/*` routes to `client.login` |
| `RedirectIfAuthenticated` | Modified to redirect `client` guard to `/client/dashboard` |

### Routes

```
GET  /client/login              → ClientAuthController@showLoginForm
POST /client/login              → ClientAuthController@login
POST /client/logout             → ClientAuthController@logout
GET  /client/dashboard          → ClientPortalController@dashboard
GET  /client/project/{id}       → ClientPortalController@projectShow
GET  /client/project/{id}/boq   → ClientPortalController@projectBoq
GET  /client/project/{id}/schedule   → ClientPortalController@projectSchedule
GET  /client/project/{id}/financials → ClientPortalController@projectFinancials
GET  /client/project/{id}/documents  → ClientPortalController@projectDocuments
GET  /client/project/{id}/gallery    → ClientPortalController@projectGallery
GET  /client/project/{id}/reports    → ClientPortalController@projectReports
GET  /client/project/{id}/site-visit/{visitId}/pdf → ClientPortalController@siteVisitPdf
```

### Security

- **Ownership check**: Every controller method calls `clientProject($id)` which enforces `WHERE client_id = auth('client')->id()`. If a client guesses another project's ID, they get a 404.
- **Portal access toggle**: `ClientAuth` middleware checks `portal_access_enabled` on every request. Disabled clients are logged out immediately.
- **Session isolation**: The `client` guard uses a separate session namespace — staff and client logins don't interfere.

### Database

Migration adds `project_progress_images` table:

| Column                   | Type              | Description                                     |
|--------------------------|-------------------|-------------------------------------------------|
| `project_id`             | foreignId         | FK to projects (cascade delete)                 |
| `uploaded_by`            | foreignId, null   | FK to users (admin who uploaded)                |
| `title`                  | string, nullable  | Image title (e.g. "Foundation Day 3")           |
| `description`            | text, nullable    | Brief description of progress shown             |
| `file`                   | string, nullable  | Stored file path (`/storage/uploads/...`)       |
| `file_name`              | string, nullable  | Original uploaded filename                      |
| `taken_at`               | date, nullable    | When the photo was taken                        |
| `construction_phase_id`  | foreignId, null   | FK to project_construction_phases               |

Migration adds to `project_clients` table:

| Column | Type | Description |
|--------|------|-------------|
| `password` | string, nullable | Hashed password for portal login |
| `remember_token` | string(100), nullable | Laravel remember me token |
| `portal_access_enabled` | boolean, default true | Toggle portal access on/off |
| `last_login_at` | timestamp, nullable | Last successful login time |

### Key Files

```
app/
├── Http/
│   ├── Controllers/Client/
│   │   ├── ClientAuthController.php      # Login/logout
│   │   └── ClientPortalController.php    # All portal pages + PDF + gallery
│   ├── Middleware/
│   │   └── ClientAuth.php                # Auth + portal access check
│   └── Kernel.php                        # Registers client.auth
├── Models/
│   ├── ProjectClient.php                 # Now extends Authenticatable
│   └── ProjectProgressImage.php          # Progress image model
resources/views/
├── layouts/
│   └── client.blade.php                  # Portal layout (sidebar + Gallery link)
├── client/
│   ├── auth/login.blade.php              # Login page
│   ├── dashboard.blade.php               # Project cards
│   ├── site_visit_pdf.blade.php          # Site visit PDF template (dompdf)
│   ├── partials/
│   │   ├── project_tabs.blade.php        # Tab navigation (7 tabs incl. Gallery)
│   │   └── boq_section.blade.php         # Recursive BOQ sections
│   └── projects/
│       ├── show.blade.php                # Overview + progress charts (Chart.js)
│       ├── boq.blade.php                 # Bill of Quantities
│       ├── schedule.blade.php            # Phases & activities
│       ├── financials.blade.php          # Invoices & payments
│       ├── documents.blade.php           # Designs & files
│       ├── gallery.blade.php             # Progress image gallery + lightbox
│       └── reports.blade.php             # Daily reports & site visits + PDF btn
├── forms/
│   └── project_progress_image_form.blade.php  # Admin upload form
config/
└── auth.php                              # Client guard & provider
database/migrations/
└── 2026_02_14_..._create_project_progress_images_table.php
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Client can't log in | Check: has password been set? Is `portal_access_enabled` true? Is email/phone correct? |
| Client sees no projects | Verify `projects.client_id` matches the client's ID |
| Staff login broken | Client portal uses separate guard — check `config/auth.php` hasn't changed the `web` guard |
| "Portal access disabled" error | Admin needs to re-enable the checkbox in client edit form |
| Password not working after edit | Ensure the password field was filled when updating — blank = keep current |
| Progress charts not showing | Verify a `ProjectSchedule` exists with `client_id` matching the client, and that it has activities |
| Site visit PDF download fails | Check the visit belongs to the client's project and that `barryvdh/laravel-dompdf` is installed |
| Gallery shows no images | Admin needs to upload progress images via the admin form for the correct project |
| Gallery images broken | Check that the storage symlink exists (`php artisan storage:link`) and file paths are correct |
