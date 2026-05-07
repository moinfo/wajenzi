# Wajenzi ERP — Training Notes & Operating Procedures

**Version:** May 2026  
**System:** Wajenzi Construction Management ERP (Laravel Web + Mobile App)  
**Audience:** All staff who interact with the system

---

## Table of Contents

1. [System Overview & Pipeline](#1-system-overview--pipeline)
2. [Role Reference Card](#2-role-reference-card)
3. [Sales / Business Development](#3-sales--business-development)
4. [Finance / Billing](#4-finance--billing)
5. [Managing Director (MD/CEO)](#5-managing-director-mdceo)
6. [Architect](#6-architect)
7. [Structural Engineer](#7-structural-engineer)
8. [Project Manager / Engineer](#8-project-manager--engineer)
9. [Client Portal](#9-client-portal)
10. [System Administration](#10-system-administration)
11. [Common Errors & Solutions](#11-common-errors--solutions)

---

## 1. System Overview & Pipeline

The Wajenzi ERP enforces a **9-stage business pipeline**. Each stage has a gate — you cannot skip forward without completing the previous stage.

```
[1] LEAD           → Sales enters enquiry (name, phone, location, project type)
     │
     ▼
[2] FIRST PAYMENT  → Finance records billing payment
     │               ↳ System auto-converts Lead → Client + creates Project
     │               ↳ System auto-assigns Architect (workload balanced)
     ▼
[3] DESIGN SCHEDULE → Architect builds schedule (activities A0–C4)
     │               ↳ Phase-final activities require MD approval to mark complete
     ▼
[4] STRUCTURAL HANDOFF → B7 activity approved → System creates Structural Design task
     │               ↳ Email sent to all Structural Engineers
     ▼
[5] STRUCTURAL DESIGN → Engineer completes 3 stages + uploads drawings
     │               ↳ Submitted to MD for approval
     ▼
[6] BOQ             → Can only be created AFTER structural design is MD-approved
     │               ↳ BOQ goes through its own approval workflow
     ▼
[7] CLIENT PORTAL   → Client sees only approved/completed items
     ▼
[8] PROCUREMENT     → Material requests, supplier quotations, purchase orders
     ▼
[9] EXECUTION       → Attendance, progress tracking, billing milestones
```

**Key rule:** The system blocks forward movement automatically. If a step seems stuck, check the approval status of the previous stage.

---

## 2. Role Reference Card

| Role | Primary Responsibilities | Key System Areas |
|------|--------------------------|------------------|
| Sales / BD | Create leads, track enquiries | Leads, CRM |
| Finance | Record payments, billing | Billing, Payments |
| Managing Director | Approve design activities, structural design, BOQ | Approvals queue (all modules) |
| Architect | Build & manage design schedules | Project Schedules |
| Structural Engineer | Complete structural design stages | Structural Design module |
| Project Manager | Oversee project progress | Projects, Procurement, Attendance |
| Engineer | Field activities, site updates | Schedule activities, Attendance |
| Client | View progress & approved documents | Client Portal (mobile/web) |
| Admin | User management, roles, system config | Admin panel |

---

## 3. Sales / Business Development

### Creating a New Lead

1. Navigate to **CRM → Leads** (sidebar)
2. Click **+ New Lead**
3. Fill in:
   - **Client Name** *(required)*
   - **Phone Number** *(required)*
   - **Project Location**
   - **Project Type** (Residential, Commercial, etc.)
   - **Budget Estimate** *(optional)*
   - **Source** (Referral, Walk-in, Social Media, etc.)
4. Click **Save**

> **Status after creation:** Lead status = `New`

### Following Up on a Lead

1. Open the lead record
2. Add a **Follow-up Note** with date and outcome
3. Update **Lead Status** as the conversation progresses:
   - `New` → `Contacted` → `Interested` → `Negotiating`

### What Happens at First Payment?

Once Finance records the **first billing payment** for a lead:
- The lead status automatically changes to `Converted`
- A new **Client** record is created
- A new **Project** is created with status `Design Phase`
- An **Architect** is automatically assigned (system picks the one with fewest active projects)
- You will see the lead locked — no more editing needed

> **You do not need to manually convert the lead.** The billing payment triggers everything automatically.

---

## 4. Finance / Billing

### Recording a Payment (Critical: Triggers Conversion)

1. Navigate to **Finance → Billing**
2. Find the Billing Document linked to the lead/client
3. Click **Record Payment**
4. Enter:
   - **Amount**
   - **Payment Date**
   - **Payment Method** (Cash, Bank Transfer, Cheque, M-Pesa)
   - **Reference Number** *(for bank/M-Pesa)*
5. Click **Save Payment**

> **On the FIRST payment for a new lead:** The system automatically converts the lead, creates the project, and assigns an architect. You will see a success notification.

### Attaching Payment Evidence

After recording payment, you can attach:
- Bank slips (PDF or image)
- M-Pesa transaction screenshots

Click the **paperclip icon** on the payment record → upload file.

### Viewing Client Billing History

1. Go to **Finance → Billing Documents**
2. Filter by Client name
3. All invoices and payments are listed chronologically

---

## 5. Managing Director (MD/CEO)

The MD is the **central approval authority** in the workflow. Three different approval queues require your attention:

### 5.1 Approving Design Schedule Activities

Phase-final activities (Survey, 2D Final, 3D Final, Structural & MEP, BOQ, Final Submission) require MD approval before the team can mark them complete.

**How to approve:**
1. Go to **Project Schedules** (sidebar)
2. Click on a project schedule
3. Activities requiring approval show a **"Pending Approval"** badge
4. Click the activity → Review the details
5. Use the **Approve / Return** buttons
   - **Approve** — marks the activity complete, unlocks the next phase
   - **Return** — sends it back with your comment (engineer must re-submit)
6. Add a comment (required for Return, optional for Approve)

> **Key activity codes:**
> - `A0` — Survey Report
> - `A7` — 2D Final Drawing
> - `B7` — 3D Final Drawing *(triggers Structural Design handoff automatically on approval)*
> - `C1` — Structural & MEP
> - `C2` — BOQ Activity
> - `C4` — Final Submission

### 5.2 Approving Structural Design

Once engineers complete all 3 structural stages and submit, you receive the approval request.

1. Go to **Structural Design** (sidebar)
2. Find designs with status **"Submitted"**
3. Click **View** to open the design detail
4. Review:
   - All 3 stages (Structural Analysis, Foundation Design, Structural Drawings)
   - Uploaded drawings (click file links to download/view)
   - Engineer notes
5. Use the **Approve / Reject / Return** buttons in the Approval Status panel
   - **Approve** — unlocks BOQ creation for this project
   - **Reject** — closes the design (engineer must start new one if needed)
   - **Return** — sends back for revision

> **After approval:** Project status changes to `Structural Approved` and the BOQ module becomes unlocked for this project.

### 5.3 Approving BOQ

1. Go to **BOQ** (sidebar) or **Project → BOQ tab**
2. Find BOQs with status **"Submitted"**
3. Click to open → review line items and totals
4. Use **Approve / Reject / Return**

### Approval Notifications

The system does **not yet send email notifications** for design activity approvals — check the approval queues daily:
- Design Schedules → filter by "Pending Approval"
- Structural Design → filter by "Submitted"
- BOQ → filter by "Submitted"

---

## 6. Architect

### Viewing Your Assigned Projects

1. Go to **Project Schedules** (sidebar)
2. Your automatically-assigned schedules appear in the list
3. Each schedule shows the project name, client, and current status

### Building a Design Schedule

1. Click **View** on a project schedule
2. Click **+ Add Activity** or use the **Bulk Activity Template** button
3. For each activity:
   - Select the **Activity Template** (A0, A1, A2... C4)
   - Set **Start Date** and **Duration (days)**
   - Assign to yourself or a team member
4. The Gantt view updates automatically

### Starting & Completing Activities

**To start an activity:**
1. Click the activity row
2. Click **Start Activity** — status changes to `In Progress`

**To complete an activity:**
1. Click the activity row
2. Click **Mark Complete** or **Submit for Approval** (for phase-final activities)
3. For non-approval activities: status becomes `Completed` immediately
4. For approval activities (A0, A7, B7, C1, C2, C4): status becomes `Pending Approval`
   - Wait for MD approval before the activity turns `Completed`

### What Happens When B7 (3D Final) is Approved?

Immediately after the MD approves B7:
- The system **automatically creates a Structural Design** task
- All Structural Engineers receive an **email notification**
- The project status changes to `Structural Phase`
- You do not need to do anything — this is fully automatic

### Changing Activity Duration

1. Click the activity
2. Click **Edit Days**
3. Enter the new duration
4. Save — the timeline adjusts automatically

---

## 7. Structural Engineer

### Receiving a New Assignment

When a structural design is triggered (after B7 approval), you receive an email:
- Subject: `Structural Design Handoff: [Project Name] — Action Required`
- The email lists the 3 stages you need to complete
- Click the link in the email to open the design directly, or go to **Structural Design** in the sidebar

### Working on a Structural Design

1. Navigate to **Structural Design** (sidebar)
2. Your assigned designs appear in the list
3. Click **View** to open the design detail page

The design has **3 stages** to complete in order:

| # | Stage | What to Upload |
|---|-------|---------------|
| 1 | Structural Analysis | Analysis report (PDF) |
| 2 | Foundation Design | Foundation drawings (PDF/DWG) |
| 3 | Structural Drawings | Full structural drawing set (PDF/DWG) |

### Completing a Stage

1. Open the design detail page
2. Find the stage card (right column)
3. Click **Update Stage**
4. Set **Status**:
   - `In Progress` — you've started but not finished
   - `Completed` — stage is fully done
5. Upload the **file** (required when marking Completed)
6. Add **notes** describing what was done or any issues
7. Click **Save**

> **Important:** Stages should be completed in order (1 → 2 → 3). While the system does not enforce this strictly, it is the professional standard.

### Submitting for MD Approval

Once **all 3 stages** show status `Completed`:
1. The **Submit for Approval** button appears at the bottom of the page
2. Click it
3. Design status changes to `Submitted`
4. The MD receives it in their approval queue

> **If Submit button doesn't appear:** Check that all 3 stages are marked `Completed` (not just `In Progress`).

### After MD Decision

- **Approved** → Design is done. BOQ can now be created. Your work here is complete.
- **Returned** → Review the MD's comment, make corrections, re-upload files, re-submit
- **Rejected** → Design is closed. Discuss with the MD/PM to determine next steps

---

## 8. Project Manager / Engineer

### Viewing Project Overview

1. Go to **Projects** (sidebar)
2. Status badges show where each project is:
   - `Design Phase` — architect working on schedule
   - `Structural Phase` — engineers working on structural design
   - `Structural Approved` — structural done, BOQ can be created
   - `In Progress` — active construction

### Creating a BOQ

**Prerequisite:** The project must have an MD-approved Structural Design. If not, the system will block you.

1. Go to **Projects → [Project Name] → BOQ tab**
2. Click **+ New BOQ**
3. Select:
   - **Type:** Client or Internal
   - **Version:** auto-populated (increment from last version)
4. Save → Add line items

> **Blocked?** If you see *"BOQ cannot be created until structural design is approved"*, go to **Structural Design**, check the status for this project, and follow up with the assigned engineer or MD.

### Material Requests

1. Open a BOQ
2. Select materials using the checkboxes
3. Click **Request Selected** (floating button)
4. Confirm quantities and submit
5. The request goes through approval before procurement can proceed

### Procurement Flow

```
Material Request → Approved → Supplier Quotation → 
Quotation Comparison → Purchase Order → Delivery → Payment
```

Each step links to the previous one automatically.

---

## 9. Client Portal

Clients access the system via the **Wajenzi Mobile App** (or mobile web).

### What Clients Can See

| Section | Visibility Rule |
|---------|----------------|
| Project documents | Only `approved` status documents |
| Design schedule | Only `completed` activities |
| BOQ | Only after BOQ is approved |
| Invoices / Billing | Their own billing documents |

> **Design philosophy:** Clients never see work-in-progress or rejected items — only finalized, approved information.

### Client Cannot See Something — Troubleshooting

If a client reports that something is not visible in the portal:
1. Check the **status** of that item in the admin panel
2. Only `approved` / `completed` items are shown
3. If it's still pending MD approval, it will appear once approved
4. Ensure the client's **email/phone is correctly linked** to their Client record

---

## 10. System Administration

### Managing Users and Roles

1. Go to **Admin → Users**
2. Click a user → **Edit Roles**
3. Available roles and what they unlock:

| Role | Approval Rights | Module Access |
|------|----------------|--------------|
| Managing Director | Approves everything | All modules |
| Structural Engineer | — | Structural Design module |
| Engineer | — | Schedule activities, Structural Design (view) |
| Architect | — | Project Schedules |
| Finance | — | Billing, Payments |
| Sales | — | Leads, CRM |
| Admin | — | All modules + Admin panel |

### Approval Flows (Technical Reference)

The following approval flows are configured in the database:

| Model | Flow Name | Approver Role |
|-------|-----------|--------------|
| `ProjectStructuralDesign` | Structural Design Approval | Managing Director |
| `ProjectScheduleActivity` | Design Activity Approval | Managing Director |
| `ProjectBoq` | BOQ Approval | *(as configured)* |

To modify approval chains: **Admin → Process Approval Flows**

### Running Seeders (First-Time Setup)

Run these after a fresh deployment or database reset:

```bash
# Approval flows for design activities (A0, A7, B7, C1, C2, C4)
php artisan db:seed --class=DesignActivityApprovalSeeder

# Approval flow for structural designs
php artisan db:seed --class=StructuralDesignApprovalSeeder
```

### Impersonating a User (Admin Only)

To test the system from another user's perspective:
1. Go to **Admin → Users**
2. Click **Login As** next to a user
3. You are now logged in as that user
4. To return: click **Back to Admin** in the top bar

---

## 11. Common Errors & Solutions

### "BOQ cannot be created until structural design is approved"

**Cause:** No approved structural design exists for this project.  
**Fix:**
1. Go to **Structural Design** → filter by this project
2. If no design exists: check if B7 activity was approved (should trigger automatically)
3. If design exists but status is not `Approved`: follow up with the MD
4. If design exists and is approved: refresh the page and try again

### Lead Not Converting After Payment

**Cause:** The billing payment may not be linked to a lead, or the lead record is missing.  
**Fix:**
1. Go to **Finance → Billing Documents** → find the document
2. Check that it has a `lead_id` linked
3. If missing, open the billing document and manually set the lead
4. Re-save the payment

### Structural Handoff Email Not Received

**Cause:** No user has the `Structural Engineer` or `Engineer` role, or mail is not configured.  
**Fix:**
1. Go to **Admin → Users** → ensure at least one user has `Structural Engineer` role
2. Check `config/mail.php` or `.env` for SMTP settings
3. Check `storage/logs/laravel.log` for mail errors

### Activity Approval Not Appearing for MD

**Cause:** The activity template does not have `requires_approval = true`.  
**Fix:**
1. Check the `project_activity_templates` table: `requires_approval` column
2. Run: `php artisan db:seed --class=DesignActivityApprovalSeeder`
3. Note: existing activities already created before the seeder ran will not be updated — update them manually in the database or via Admin

### Project Status Stuck

**Status flow reference:**

```
pending → design_phase → structural_phase → structural_approved → (BOQ/execution)
```

Each transition is triggered by a system event — check the corresponding approval.

---

## Quick Reference: System Status Values

| Entity | Status Values | Meaning |
|--------|--------------|---------|
| Lead | `New`, `Contacted`, `Interested`, `Negotiating`, `Converted`, `Lost` | Sales pipeline |
| Project | `pending`, `design_phase`, `structural_phase`, `structural_approved`, `in_progress`, `COMPLETED`, `on_hold` | Project lifecycle |
| Schedule Activity | `pending`, `in_progress`, `completed`, `pending_approval` | Design work |
| Structural Design | `pending`, `in_progress`, `submitted`, `approved`, `rejected` | Structural workflow |
| Structural Stage | `pending`, `in_progress`, `completed` | Per-stage progress |
| BOQ | `Draft`, `Submitted`, `Approved`, `Rejected`, `Returned` | BOQ approval |
| Material Request | `draft`, `submitted`, `approved`, `rejected` | Procurement |
| Purchase Order | `draft`, `open`, `received`, `closed`, `cancelled` | Purchasing |

---

## Contacts & Support

For system issues, contact your system administrator or the development team.  
For workflow questions, refer to `WORKFLOW.md` in the system documentation.

---

*Document prepared: May 2026 | Wajenzi Construction ERP*
