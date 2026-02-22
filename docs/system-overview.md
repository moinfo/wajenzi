# Wajenzi - System Module Documentation

## System Overview

Wajenzi is a comprehensive construction project management and ERP system built for construction companies in East Africa. The platform manages the full lifecycle of construction operations — from project bidding and BOQ estimation through procurement, site operations, billing, HR/payroll, and financial reporting.

### Platform Components

| Component | Technology | Description |
|-----------|-----------|-------------|
| Web Application | Laravel 11, PHP 8.2+, Bootstrap 5 | Main admin panel for all operations |
| Mobile Application | Flutter (iOS & Android) | Field operations with offline-first support |
| Client Portal | Separate auth, REST API | Project transparency for construction clients |
| REST API | Laravel Sanctum | Powers mobile app and client portal |

### Core Capabilities

- Multi-project management with team assignments and role-based access
- Hierarchical Bill of Quantities (BOQ) with templates and section nesting
- End-to-end procurement pipeline (request → quote → compare → purchase → receive → stock)
- Professional billing/invoicing system with PDF generation and email delivery
- Full HR suite: payroll, leave, loans, attendance, recruitment
- Labor procurement for construction workforce management
- Multi-level approval workflows on 20+ document types
- Financial management with bank reconciliation, chart of accounts, tax compliance
- Real-time notifications via Pusher
- Comprehensive reporting across all modules

---

## Module Map

The system is organized into **16 top-level modules** accessible via the sidebar navigation. Each module contains sub-features accessible as child menu items.

```
┌──────────────────────────────────────────────────────────────────┐
│                        WAJENZI MODULES                           │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────┐  ┌──────────┐  ┌──────────────┐               │
│  │  Projects    │  │   VAT    │  │   Finance    │               │
│  │  (17 subs)   │  │ (6 subs) │  │  (7 subs)    │               │
│  └─────────────┘  └──────────┘  └──────────────┘               │
│                                                                  │
│  ┌─────────────┐  ┌──────────────────┐  ┌──────────────┐       │
│  │BOQ Templates│  │Employee Management│  │   Billing    │       │
│  │  (7 subs)   │  │    (9 subs)      │  │  (7 subs)    │       │
│  └─────────────┘  └──────────────────┘  └──────────────┘       │
│                                                                  │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐           │
│  │ Procurement │  │   Expenses   │  │Labor Procure.│           │
│  │  (9 subs)   │  │  (standalone)│  │  (6 subs)    │           │
│  └─────────────┘  └──────────────┘  └──────────────┘           │
│                                                                  │
│  ┌─────────┐  ┌───────────┐  ┌──────────┐  ┌──────────┐       │
│  │Statutory│  │   eSMS    │  │Prov. Tax │  │ Reports  │       │
│  └─────────┘  └───────────┘  └──────────┘  └──────────┘       │
│                                                                  │
│  ┌──────────────┐  ┌───────────────┐                            │
│  │  Settings    │  │Employee Profile│                           │
│  └──────────────┘  └───────────────┘                            │
│                                                                  │
│  ┌──────────────────────────────────────────┐                   │
│  │        CROSS-CUTTING FEATURES            │                   │
│  │  Approval Workflows · Permissions/Roles  │                   │
│  │  Notifications · PDF Export · CSV Export  │                   │
│  └──────────────────────────────────────────┘                   │
│                                                                  │
│  ┌──────────────────────────────────────────┐                   │
│  │        EXTERNAL PLATFORMS                │                   │
│  │  Mobile App (Flutter) · Client Portal    │                   │
│  └──────────────────────────────────────────┘                   │
└──────────────────────────────────────────────────────────────────┘
```

---

## Module Summary Table

| # | Module | Sub-features | Views | Forms | Models | Controllers | Approval | Complexity |
|---|--------|-------------|-------|-------|--------|-------------|----------|------------|
| 1 | Home (Dashboard) | 1 | 1 | 0 | - | 1 | No | Low |
| 2 | Employee Profile | 15 | 1 | 2 | ~15 | ~8 | No | Medium |
| 3 | VAT | 6 | 1 | 1 | ~5 | 1-2 | Yes | Low-Medium |
| 4 | Projects | 17 | 22 | 10+ | ~30 | 15+ | Yes | Very High |
| 5 | Finance | 7 | 19 | 6 | ~10 | 3+ | Yes | High |
| 6 | BOQ Templates | 7 | 30+ | 3 | ~8 | 2 | No | Medium-High |
| 7 | Employee Management | 9 | 3 | 2 | ~15 | ~8 | Yes | Medium |
| 8 | Billing | 7 | 37 | 12+ | ~15 | 11 | No | Very High |
| 9 | Statutory | 1 | 2 | 0 | ~5 | 1-2 | Yes | Low |
| 10 | Procurement | 9 | 19 | 5+ | ~12 | 5-8 | Yes | High |
| 11 | Expenses | 1 | 2 | 3 | ~8 | 2-3 | Yes | Medium |
| 12 | Labor Procurement | 6 | 12+ | 3+ | ~7 | 6 | Yes | Medium-High |
| 13 | eSMS | 1 | 1 | 1 | ~2 | 1 | No | Low |
| 14 | Provision Tax | 1 | 1 | 1 | ~3 | 1 | No | Low |
| 15 | Reports | 1 | 54 | 0 | - | 5-8 | No | Medium-High |
| 16 | Settings | 1 | 62 | 65+ | ~30 | 15-20 | No | Very High |

**Additional standalone pages:** Withholding Taxes, Adjusted Assessment Taxes, Bank Reconciliation, Receiving

---

## Module Details

### 1. Home (Dashboard)

The main dashboard is the central command center, providing a real-time overview of all company operations. It is the landing page after login and is organized into distinct sections from top to bottom.

![Dashboard Screenshot](screenshots/dashboard-full.png)

#### 1.1 Welcome Header
- Personalized greeting: "Welcome back, [User Name]!"
- Subtitle: "Here's what's happening with your construction projects today"
- Two action buttons: **+ New Project** and **View Reports**
- Gradient background (blue-to-green)

#### 1.2 Key Metrics Cards (4 cards)

| Card | Data Source | Description |
|------|-----------|-------------|
| **Total Revenue** | `Sale::getTotalTax()` | Monthly revenue in TZS with trend indicator (+12.5%) |
| **Active Projects** | Static | Count of currently running projects |
| **Team Members** | `User::getUserCounts()` | Total staff with male/female breakdown |
| **Budget Utilization** | Static | Progress bar showing budget consumption percentage |

#### 1.3 Pending Approvals Panel

Displays approval items requiring the user's attention, with color-coded category icons and count badges:
- Payroll (live count from `Payroll::countUnapproved()`)
- Advance Salary (live count from `AdvanceSalary::countUnapproved()`)
- Staff Loan (live count from `Loan::countUnapproved()`)
- Material Request, Project BOQ, Project Expense, Site Visit

Each item is clickable and links to the relevant approval page. Only items with count > 0 are displayed. The header shows the total pending count.

#### 1.4 Follow-up To-Do List

**Permission:** "View All Follow-ups" (without it, shows only the user's own leads)

Shows sales follow-up tasks with status indicators:

| Stat | Color | Description |
|------|-------|-------------|
| Overdue | Red | Follow-ups past their scheduled date |
| Today | Orange | Follow-ups due today |
| Upcoming | Blue | Future follow-ups |
| Completed | Green | Finished follow-ups |

Each follow-up card shows:
- Date badge (day/month) with color coding (red = overdue, orange = today)
- Lead name and next action step (truncated)
- Assigned salesperson name
- Status label (Overdue, Today, Tomorrow, Done)

Scrollable list (max 20 items), with "View All Leads" link at the bottom.

#### 1.5 Project Activities To-Do List

**Permission:** "View All Project Activities" (without it, shows only activities assigned to the user)

Shows construction schedule activities from active project schedules:

| Stat | Color | Description |
|------|-------|-------------|
| Overdue | Red | Activities past their start date |
| Due Today | Orange | Activities scheduled for today |
| Pending | Gray | Upcoming activities not yet started |
| In Progress | Blue | Activities currently being worked on |

Each activity card shows:
- Date badge with color coding
- Activity code and name (e.g., "A0: Drone Survey and Data Analysis")
- Project lead number and client name
- Construction phase name
- Status label

Shows first 8 activities ordered by start date, with "View All Schedules" link.

#### 1.6 Invoice Due Dates Panel

**Permission:** "View All Invoice Due Dates" (entire section hidden without this permission)

Tracks unpaid billing invoices with due date urgency:

| Stat | Color | Description |
|------|-------|-------------|
| Overdue | Red | Invoices past due date |
| Due Today | Orange | Invoices due today |
| Upcoming | Blue | Invoices due in the future |
| Paid This Month | Green | Recently settled invoices |

Each invoice card shows:
- Date badge (due date, red if overdue)
- Invoice number (e.g., INV-2026-00003)
- Client name and balance amount in TZS
- "Partial" badge if partially paid
- Days overdue or days remaining
- **Attend button** — opens a modal to take action
- **Google Calendar link** — add to calendar directly

**Attend Invoice Modal** — allows three actions:
1. **Mark Paid** — records full payment, updates status to "paid"
2. **Partial Payment** — enter amount paid, recalculates balance
3. **Reschedule** — set a new due date with reason

All actions trigger notifications to users with the "View All Invoice Due Dates" permission.

**Export:** "Export to Google Calendar" button generates an ICS file with all pending invoice due dates for calendar import.

Shows up to 20 invoices, with "View All Invoices" link.

#### 1.7 Project Progress Overview

Shows active project schedules with progress metrics:
- **Overall progress ring** — SVG circular chart showing completion percentage
- **Summary stats:** Completed, In Progress, Pending, Overdue activity counts
- **Per-project breakdown:** Each active project shows:
  - Lead number and client name
  - Color-coded progress bar (green ≥75%, blue ≥50%, yellow ≥25%, red <25%)
  - Completed / In Progress / Pending / Overdue counts

Links to "View All Projects" at the bottom.

#### 1.8 Follow-up Calendar (Monthly)

A full month calendar grid showing events across three categories:

| Event Type | Dot Color | Source |
|-----------|-----------|--------|
| Follow-up | Orange | `SalesLeadFollowup` |
| Activity | Blue | `ProjectScheduleActivity` |
| Invoice | Gold | `BillingDocument` (unpaid with due date) |
| Done | Green | Completed items |
| Overdue | Red | Past-due items |

**Calendar features:**
- Month navigation (previous/next arrows, "Today" button)
- Hover tooltips showing up to 3 items per category with details
- "+X more" indicator for days with more than 4 events
- Google Calendar add button per item in tooltips
- "Export to Google Calendar" button for bulk ICS download
- Legend bar below the calendar grid

**Today's Follow-ups** and **Today's Activities** detail sections appear below the calendar when items exist for today.

#### 1.9 Financial Analytics Charts

Three Chart.js visualizations:

| Chart | Type | Data |
|-------|------|------|
| **Sales Revenue** | Line chart | Weekly sales revenue trend (Mon-Sun), live from `Sale::getTotalTax()` per day |
| **BOQ Analytics** | Bar chart | Budgeted vs. Actual spending per project (static demo data) |
| **Statutory & Compliance** | Doughnut chart | VAT (live from `VatAnalysis`), PAYE, NSSF, Other taxes breakdown |

Each chart has filter tabs (Week/Month/Year for revenue; Projects/Materials/Labor for BOQ; VAT/PAYE/NSSF for statutory) and summary statistics below.

#### 1.10 Project Status (Static Demo)

Three hardcoded project cards showing project name, description, status badge (On Track / At Risk / Completed), and circular progress percentage. This section demonstrates the intended UI for future live integration.

#### 1.11 Recent Activities Timeline (Static Demo)

Timeline of 4 hardcoded activity entries with color-coded icons (green = new, blue = update, purple = milestone, red = alert) and relative timestamps.

#### 1.12 Department Overview

Grid of department cards showing:
- Department name (e.g., "Sales and Marketing", "Technical", "Finance and Accounts")
- Member count
- Active status indicator (green dot)

Data sourced live from `User::getDepartmentMemberCounts()`.

#### 1.13 Quick Actions Bar

Six shortcut buttons at the bottom of the dashboard:

| Action | Icon | Purpose |
|--------|------|---------|
| Add Project | Plus | Navigate to new project creation |
| Manage Team | Users | Navigate to team management |
| Create Invoice | Invoice | Navigate to billing invoices |
| Schedule Visit | Calendar | Navigate to site visit scheduling |
| View Reports | Chart | Navigate to reports module |
| Settings | Cog | Navigate to system settings |

#### Dashboard Data Summary

| Component | Data Source | Live/Static |
|-----------|-----------|-------------|
| Total Revenue | `Sale::getTotalTax()` | Live |
| Active Projects | Hardcoded (24) | Static |
| Team Members | `User::getUserCounts()` | Live |
| Budget Utilization | Hardcoded (68%) | Static |
| Pending Approvals | `Payroll`, `AdvanceSalary`, `Loan` + hardcoded | Partial |
| Follow-ups | `SalesLeadFollowup` with permission filter | Live |
| Project Activities | `ProjectScheduleActivity` with permission filter | Live |
| Invoice Due Dates | `BillingDocument::unpaidWithDueDate()` | Live |
| Attend Invoice | AJAX form submission | Live |
| Sales Revenue Chart | `Sale::getTotalTax()` per day | Live |
| BOQ Chart | Hardcoded 5 projects | Static |
| Statutory Chart | `VatAnalysis::getTaxPayable()` + hardcoded | Partial |
| Calendar Events | Follow-ups + Activities + Invoices | Live |
| Project Progress | `ProjectSchedule` with activity calculations | Live |
| Department Cards | `User::getDepartmentMemberCounts()` | Live |

#### Key Permissions

| Permission | Controls |
|-----------|----------|
| `View All Follow-ups` | See all follow-ups vs. only own leads |
| `View All Project Activities` | See all activities vs. only assigned |
| `View All Invoice Due Dates` | See invoice panel, attend modal, export, reminders |

---

### 2. Employee Management

The Employee Management module is the full HR and payroll suite for managing staff records, compensation, deductions, leave, loans, and attendance. It combines a self-service **Employee Profile** page with 15 administrative sub-pages accessible from the sidebar under "Employee Management".

![Employee Profile Screenshot](screenshots/employee-profile-full.png)

**Sidebar Menu Items (15 sub-pages):**

| # | Menu Item | Route | Purpose |
|---|-----------|-------|---------|
| 1 | Staff Bank Details | `/payroll/staff_bank_details` | Employee bank account info for salary deposits |
| 2 | Payroll Administration | `/payroll/payroll_administration` | Create, preview, and approve monthly payrolls |
| 3 | Deductions | `/settings/deductions` | Define statutory deduction types (PAYE, NSSF, etc.) |
| 4 | Deduction Subscriptions | `/settings/deduction_subscriptions` | Enroll employees in deduction schemes |
| 5 | Salary Slips | `/payroll/salary_slips` | Generate and print/export individual payslips |
| 6 | Employee Allowances | `/settings/allowances` | Define and assign allowance types to staff |
| 7 | Advance Salary | `/settings/advance_salaries` | Advance salary requests with approval workflow |
| 8 | Salaries & Salary Arrears | `/settings/staff_salaries` | Set base salary amounts per employee |
| 9 | Loan Details | `/settings/staff_loans` | Staff loan management with approval workflow |
| 10 | Leave Dashboard | `/leaves/leave_dashboard` | Visual leave balance overview for logged-in user |
| 11 | Leave Request | `/leaves/leave_request` | Submit and track personal leave requests |
| 12 | Leave Managements | `/leaves/leave_managements` | Admin view to approve/reject all leave requests |
| 13 | Leave Types | `/settings/leave_types` | Configure leave categories and entitlements |
| 14 | CRDB Bank File | `/payroll/crdb_bank_file` | Generate bank transfer file for bulk salary payments |
| 15 | Attendance Types | `/settings/attendance_types` | Define attendance location/type categories |

#### 2.1 Employee Profile Page

**Route:** `/employee_profile` (GET/POST)
**Page Title:** "Employee Details Profile"

A comprehensive single-employee financial analysis page. Users select an employee and date range, and the system calculates and displays all compensation data for that period.

**Search / Filter Bar:**

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| Start Date | Date picker | January 1 of current year | Beginning of analysis period |
| End Date | Date picker | Today's date | End of analysis period |
| Employee | Select2 dropdown | First employee | Searchable staff dropdown |
| Search | Button | — | Submits form (POST) to reload data |

**Employee Card (left column):**
- Circular profile photo (150×150px) with gradient header (blue-to-teal)
- Employee full name (large heading)
- Employee Number badge (e.g., `HRM/HRM/284`)
- Basic Salary amount (green badge) — sum from `staff_salaries` table
- Loan Balance amount (red badge) — outstanding loan amount

**General Information Table (right column):**

| Field | Data Source | Example |
|-------|-----------|---------|
| Employed Date | `users.created_at` | 2021-05-04 10:21:52 |
| Address | `users.address` | KILIMANJARO |
| Designation | `users.designation` | FINANCE |
| Gender | `users.gender` | MALE |
| Birth of Date | `users.dob` | 1993-02-25 |
| Mobile Number | `users.phone_number` | +255 xxx xxx xxx |
| Department | `departments.name` | Information Technology (IT) |
| System | `systems.name` | (system assignment) |
| NIDA | `users.national_id` | 19930225611020000221 |
| TIN | `users.tin` | 964682049 |
| Account Number | `staff_bank_details.account_number` | 0152398039400 |
| Employee Status | `users.status` + `users.updated_at` | ACTIVE badge + timestamp |

**Financial Summary Cards (4-column grid):**

| Card | Calculation | Trend |
|------|------------|-------|
| **Gross Pay** | Basic Salary + All Allowances for period | +16% indicator |
| **Deductions** | NSSF + NHIF + other enrolled deductions | -3.4% indicator |
| **Allowances** | Sum of subscribed allowances (MONTHLY fixed, DAILY × working days) | +24% indicator |
| **Net Pay** | Gross − Deductions − Advances − Loan Deductions | +8.2% indicator |

**Salary Calculation Logic:**

The system uses a multi-step calculation to derive net pay:

1. **Basic Salary** = Sum of `staff_salaries.amount` for the employee
2. **Allowances** = Per-type calculation:
   - `MONTHLY` type → fixed amount
   - `DAILY` type → `working_days_in_month × daily_amount` (excludes Sundays)
3. **Gross Pay** = Basic Salary + Total Allowances
4. **Pension (NSSF)** = Based on deduction nature:
   - If nature = `GROSS` → `gross_pay × (employee_percentage / 100)`
   - If nature = `BASIC` → `basic_salary × (employee_percentage / 100)`
5. **Health (NHIF)** = Same formula as Pension, using NHIF deduction settings
6. **Taxable Income** = Gross Pay − (Pension + Health)
7. **Total Deductions** = Pension + Health + Loan Deduction + Advance Salary
8. **Net Pay** = Taxable − (Advance Salary + Loan Deduction)

**Loan History Table:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Loan issue date |
| Deduct | Monthly deduction amount |
| Amount | Total loan amount |
| **Total Loan** | Footer row with sum |

Only shows APPROVED loans within the selected date range.

**Advance Salary History Table:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Advance date |
| Description | Purpose/reason for advance |
| Amount | Advance amount |
| **Total Advance Salary** | Footer row with sum |

Only shows APPROVED advance salaries within the selected date range.

**Payroll History Table (12 columns):**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Payroll month (e.g., "February 2025") |
| Salary | Basic salary paid |
| Allowance | Allowances paid |
| Gross | Total gross pay |
| NSSF | Pension deduction |
| PAYE | Income tax deduction |
| Advance | Advance salary deducted |
| Loan | Loan deduction |
| Deduction | Other deductions |
| Balance | Remaining balance |
| Net | Final net pay |
| Action | Link to individual Salary Slip |

Shows all APPROVED payrolls within the date range. Footer row shows column totals. Each row links to the salary slip detail page.

**Assets & Benefits History Table:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Asset property name |
| Description | Property description |
| Asset | Parent asset category name |

Displays company assets (equipment, vehicles, tools) assigned to the employee.

#### 2.2 Staff Bank Details

**Route:** `/payroll/staff_bank_details` (GET/POST)
**Page Title:** "Employee Management"

Manages employee bank account information used for salary direct deposits.

**Add Button:** "New Staff Bank Detail" — permission: `Add Staff Bank Detail`

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Staff | Employee name (from `users` relationship) |
| Bank | Bank name (from `banks` relationship) |
| Account Number | Bank account number |
| Branch | Bank branch name |
| Actions | Edit (permission: `Edit Staff Bank Detail`) / Delete (permission: `Delete Staff Bank Detail`) |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Staff | Dropdown (staff list) | Yes | Select employee |
| Bank | Dropdown (banks list) | Yes | Select bank |
| Account Number | Text | Yes | Bank account number |
| Branch | Textarea | Yes | Branch name/location |

Each employee can have multiple bank accounts. The primary account (marked `default=1`) is used for payroll deposits.

#### 2.3 Payroll Administration

**Route:** `/payroll/payroll_administration` (GET/POST)
**Page Title:** "Payroll Administration"

The central payroll processing hub where administrators create, preview, and manage monthly payrolls.

**Top Action Buttons:**
- **Preview Current Payroll** — navigates to `/payroll/preview` to review calculations before committing
- **Create New Payroll** — dropdown button to generate a new monthly payroll

**Filter Bar:**
- Start Date and End Date pickers (default: current month)
- "Show" button to filter payroll list

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number (sorted descending by default) |
| Document Number | Auto-generated (e.g., `PRL/2025/1`) |
| Payroll Number | Timestamp-based unique identifier |
| Payroll Month | Display month (e.g., "February 2025") |
| Payroll Amount | Total payroll sum, formatted currency (e.g., 9,610,528.81) |
| Status | Processing status |
| Approvals | Approval workflow badge (`PENDING` / `APPROVED`) |
| Action | "View" link to payroll detail page |

**Export:** Print, Excel, PDF

**Approval Workflow:** Payroll uses RingleSoft approval. Status progresses `CREATED → PENDING → APPROVED`. Only approved payrolls appear in salary slips and bank file generation.

#### 2.4 Deductions

**Route:** `/settings/deductions` (GET/POST)
**Page Title:** "Deductions"

Defines the statutory and voluntary deduction types that can be applied to employee salaries. These are Tanzania-specific statutory deductions.

**Add Button:** "New Deduction"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Deduction name (e.g., "PAYE", "NSSF") |
| Nature | Calculation basis: `TAXABLE`, `GROSS`, or `NET` |
| Abbreviation | Short code (e.g., "NSSF", "NHIF") |
| Description | Full description |
| Registration Number | Company's registration with the authority |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Pre-configured Deductions (6 types):**

| # | Name | Nature | Abbreviation | Description |
|---|------|--------|-------------|-------------|
| 1 | PAYE | TAXABLE | PAYE | Pay As You Earn (income tax) |
| 2 | NSSF | GROSS | NSSF | National Social Security Fund (pension) |
| 3 | WCF | GROSS | WFC | Workers Compensation Fund |
| 4 | HESLB | NET | HESLB | Higher Education Students Loans Board |
| 5 | SDL | GROSS | SDL | Skills & Development Levy |
| 6 | NHIF | NET | NHIF | National Health Insurance Fund |

**Nature determines the calculation base:**
- `GROSS` → deduction percentage applied to gross pay
- `BASIC` → deduction percentage applied to basic salary only
- `TAXABLE` → deduction applied to taxable income
- `NET` → deduction applied after tax

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Nature | Dropdown (TAXABLE/GROSS/NET) | Yes |
| Deduction Name | Text | Yes |
| Abbreviation | Text | Yes |
| Description | Textarea | No |
| Registration Number | Text | No |

#### 2.5 Deduction Subscriptions

**Route:** `/settings/deduction_subscriptions` (GET/POST)
**Page Title:** "Deduction Subscription"

Enrolls individual employees into specific deduction schemes. Each subscription links an employee to a deduction type with their membership number.

**Add Button:** "New Deduction Subscription"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Employee name |
| Deduction | Deduction type (e.g., PAYE, NSSF, WFC, SDL) |
| Membership Number | Employee's membership/registration number with the authority |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

Typical setup: each employee is enrolled in 3-4 deductions (PAYE, NSSF, WCF, SDL). The system shows ~69 entries for 20 employees.

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Staff | Dropdown (staff list) | Yes |
| Deduction | Dropdown (deduction types) | Yes |
| Membership Number | Text | No |

#### 2.6 Salary Slips

**Route:** `/payroll/salary_slips` (GET/POST)
**Page Title:** "Salary Slip"

Generates individual employee payslips from approved payroll data. Includes a printable/PDF-exportable payslip view.

**Filter Form:**

| Field | Type | Description |
|-------|------|-------------|
| Month | Dropdown (January–December) | Payroll month |
| Year | Dropdown | Payroll year |
| Employee | Select2 dropdown | Searchable staff picker |
| Show | Button | Loads the payslip |

**Payslip Layout (when payroll exists):**

The payslip is a professional document with company branding:

- **Header:** Company logo, name, address, phone, email
- **Label:** "Payslip"
- **Employee Details (2-column table):**
  - Payroll Number | Payroll Month
  - Employee Number | Employee Name
  - Department | Designation
  - Bank Name | Account Number

- **Salary Details (2-column layout):**

| Left Column: Employee Income | Right Column: Deductions |
|------------------------------|--------------------------|
| Basic Salary | Advance Salary |
| Allowance 1 (name + amount) | Loan Deduction |
| Allowance 2 (name + amount) | Loan Balance |
| ... | PAYE |
| | NSSF |
| | WCF |
| | SDL |
| | ... |
| **Gross Salary** | **Total Deductions** |

- **Footer:** **NET SALARY** (highlighted, large font)
- **Disclaimer:** "This is a computer-generated payslip and does not require a signature."

**Action Buttons:**
- **Print Payslip** — triggers browser print dialog with print-friendly CSS
- **Export to PDF** — client-side PDF generation using jsPDF + html2canvas. File named `Payslip_{EmployeeName}_{Month_Year}.pdf`

#### 2.7 Employee Allowances

**Route:** `/settings/allowances` (GET/POST)
**Page Title:** "Payroll Allowances"

A tabbed interface for managing all aspects of employee allowances.

**5 Tabs:**

| Tab | Purpose |
|-----|---------|
| **Allowance** (default) | Define allowance types (e.g., Transport, Housing, Lunch) |
| **Allowance Subscriptions** | Assign allowances to specific employees with amounts |
| **Allowance Payments** | Track historical allowance payments |
| **Allowance Subscriptions Summary** | Overview of who gets what allowances |
| **Settings** (gear icon) | Configuration options |

**Tab 1 — Allowance Types:**

**Add Button:** "New Allowance"

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Allowance name |
| Type | `MONTHLY` (fixed per month) or `DAILY` (per working day) |
| Description | Details |
| Actions | Edit / Delete |

**Daily Allowance Calculation:** For DAILY type, the system automatically calculates `working_days × daily_rate`, excluding Sundays from the month.

**Tab 2 — Allowance Subscriptions:**

**Add Button:** "New Allowance Subscription"

| Field | Type | Required |
|-------|------|----------|
| Staff | Dropdown | Yes |
| Allowance | Dropdown (allowance types) | Yes |
| Amount | Number (currency formatted) | Yes |
| Date | Datepicker (effective date) | Yes |

#### 2.8 Advance Salary

**Route:** `/settings/advance_salaries` (GET/POST)
**Page Title:** "Advanced Salaries"

Manages salary advance requests with an approval workflow.

**Add Button:** "New AdvanceSalary"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Request date |
| Name | Employee name |
| Description | Reason for advance |
| Amount | Advance amount (formatted currency) |
| Approvals | Approval workflow status |
| Status | `PENDING` / `APPROVED` / `REJECTED` badge |
| Actions | View (approval page) / Edit / Delete |

**Export:** Print, Excel, PDF
**Footer Row:** Total advance salary amount

**Approval Workflow:** Uses RingleSoft approval (document_type_id = 6). Status progresses: `CREATED → PENDING → APPROVED`. The "View" button links to the approval page at `/settings/advance_salaries/{id}/6`.

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Staff | Dropdown | Yes | Select employee |
| Amount | Number (auto-formatted) | Yes | Advance amount |
| Description | Textarea | No | Purpose of advance |
| Date | Datepicker (default: today) | Yes | Request date |

Hidden fields: `document_number` (auto-generated `ADVS/{id}/{year}`), `document_id`, `document_type_id=6`, `link` (for approval routing).

#### 2.9 Salaries & Salary Arrears

**Route:** `/settings/staff_salaries` (GET/POST)
**Page Title:** "Staff Salaries"

Sets and manages the base salary for each employee. This is the foundational figure used in all payroll calculations.

**Add Button:** "New Staff Salary"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Employee name |
| Amount | Monthly base salary (formatted currency) |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF
**Footer Row:** Total salary bill (e.g., 16,833,895 TZS for 20 employees)

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Staff | Dropdown | Yes |
| Amount | Number (auto-formatted with thousands separator) | Yes |

An employee can have multiple salary records (to track arrears/adjustments). The `Staff::getStaffSalary()` method returns the SUM of all salary amounts for a given employee.

#### 2.10 Loan Details

**Route:** `/settings/staff_loans` (GET/POST)
**Page Title:** "Staff Loan"

Manages employee loans with approval workflow and automatic monthly deductions from payroll.

**Add Button:** "New Staff Loan"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Loan issue date |
| Name | Employee name |
| Deduction | Monthly deduction amount |
| Amount | Total loan amount |
| Status | `PENDING` / `APPROVED` / `REJECTED` / `PAID` / `COMPLETED` badge |
| Approvals | Approval workflow badge |
| Actions | View (approval page) / Edit / Delete |

**Export:** Print, Excel, PDF

**Approval Workflow:** Uses RingleSoft approval (document_type_id = 7). On approval completion, `onApprovalCompleted()` callback sets status to `APPROVED` and triggers notification.

**Business Rules:**
- System tracks if employee already has an active loan (`Loan::isStaffHasLoan()`)
- Loan balance is calculated as `loan_amount - sum(deductions_paid)`
- Monthly deduction is automatically applied during payroll processing
- Status lifecycle: `CREATED → PENDING → APPROVED → PAID → COMPLETED`

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Staff | Dropdown | Yes | Select employee |
| Loan Amount | Number | Yes | Total loan amount |
| Deduction Per Month | Number | Yes | Monthly repayment amount |
| Date | Datepicker | Yes | Loan issue date |

Hidden fields: `document_id`, `document_type_id=7`, `link` (for approval routing).

#### 2.11 Leave Dashboard

**Route:** `/leaves/leave_dashboard` (GET)
**Page Title:** "Leave Dashboard"

A self-service visual overview of the logged-in employee's leave balances and recent requests.

**Leave Balance Cards (4 gradient cards):**

| Leave Type | Total Entitlement | Remaining | Card Color |
|-----------|-------------------|-----------|------------|
| Annual Leave | 28 days | Calculated | Blue gradient |
| Sick Leave | 10 days | Calculated | Green gradient |
| Compassionate Leave | 4 days | Calculated | Orange gradient |
| Maternity Leave | 84 days | Calculated | Pink gradient |

Each card shows total entitlement vs. remaining days for the current year.

**Recent Leave Requests Table:**

| Column | Description |
|--------|-------------|
| Type | Leave type name |
| Dates | Start → End date range |
| Days | Number of leave days |
| Status | Approval status badge |
| Submitted | Submission date |

#### 2.12 Leave Request

**Route:** `/leaves/leave_request` (GET/POST)
**Page Title:** "My Leave Requests"

Employee self-service page to submit and track personal leave requests.

**Add Button:** "New Leave Request" (opens inline modal, not Ajax-based)

**Table Columns:**

| Column | Description |
|--------|-------------|
| Type | Leave type name |
| Start Date | First day of leave |
| End Date | Last day of leave |
| Days | Calculated duration |
| Status | Color-coded badge: Green = Approved, Red = Rejected, Yellow = Pending |
| Remarks | Admin comments on the request |

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Leave Type | Dropdown (from `leave_types`) | Yes | Selects the leave category |
| Start Date | Date input | Yes | Dynamically validated against notice days |
| End Date | Date input | Yes | Must be ≥ start date |
| Reason | Textarea (3 rows) | Yes | Justification for leave |

**Dynamic Validation:** When a leave type is selected, JavaScript reads the `notice_days` attribute and updates the minimum allowed start date. For example, Annual Leave requires 5 days notice, so the earliest start date is set to today + 5 days. Sick Leave has 0 notice days.

#### 2.13 Leave Managements

**Route:** `/leaves/leave_managements` (GET/POST)
**Page Title:** "Leave Requests" (admin view)

Admin/manager view showing all employee leave requests for approval or rejection.

**Table Columns:**

| Column | Description |
|--------|-------------|
| Employee | Staff member's name |
| Type | Leave type |
| Date Range | Start → End date |
| Days | Number of days |
| Reason | Employee's stated reason |
| Status | Current approval status |
| Action | Approve / Reject buttons |

Managers can approve or reject requests directly from this page. Status changes are reflected in the employee's Leave Dashboard and Leave Request pages.

#### 2.14 Leave Types

**Route:** `/settings/leave_types` (GET/POST)
**Page Title:** "Leave Types"

Configures the leave categories available in the system, including entitlements and notice requirements.

**Add Button:** "New Leave Type"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Leave type name |
| Days Allowed | Annual entitlement (days per year) |
| Description | Explanation of the leave type |
| Notice Days | Minimum advance notice required |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Pre-configured Leave Types:**

| Type | Days | Notice | Description |
|------|------|--------|-------------|
| Annual Leave | 28 | 5 days | Regular annual leave entitlement |
| Sick Leave | 10 | 0 days | Leave for medical reasons |
| Compassionate Leave | 4 | varies | Bereavement/family emergency |
| Maternity Leave | 84 | varies | Maternity leave (12 weeks) |

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Name | Text | Yes |
| Description | Textarea | No |
| Days Allowed | Number | Yes |
| Notice Days | Number | Yes |

#### 2.15 CRDB Bank File

**Route:** `/payroll/crdb_bank_file` (GET/POST)
**Page Title:** "CRDB Bank File"

Generates a bank transfer data file for CRDB Bank to process bulk salary payments. This automates the salary disbursement process.

**Filter Bar:**
- Start Date (date picker)
- End Date (date picker)
- "Show" button

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| ID | Employee ID |
| Name | Employee name |
| Account | CRDB bank account number (e.g., 0152398039400) |
| Amount | Net salary amount for the period |
| Bank | Bank ID reference |
| Branch | Branch ID reference |
| Details | Always "SALARY" |

**Export:** Print, Excel, PDF (for bank submission)

This feature maps each employee's bank account to their net salary for a given period, allowing bulk transfer file generation compatible with CRDB Bank's electronic payment system.

#### 2.16 Attendance Types

**Route:** `/settings/attendance_types` (GET/POST)
**Page Title:** "Attendance Types"

Configures the attendance location/type categories used to classify employee check-ins (e.g., HQ office, field site, remote).

**Add Button:** "New Attendance Type"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Attendance type name (e.g., "HQ") |
| Description | Details about the attendance type |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Name | Text | Yes |
| Description | Textarea (3 rows) | No |

#### 2.17 Employee Data Model

The employee system is built on a layered data model centered around the `User` model:

**Core Employee Fields (users table):**

| Field | Type | Description |
|-------|------|-------------|
| name | String | Full name |
| email | String | Email address |
| employee_number | String | Auto-generated (e.g., HRM/HRM/284) |
| type | Enum | STAFF, INTERN, EXTERNAL |
| status | Enum | ACTIVE, DORMANT, INACTIVE |
| gender | Enum | MALE, FEMALE, OTHER |
| employment_type | Enum | FULL_TIME, CONTRACT, TEMPORARY, INTERN |
| marital_status | Enum | SINGLE, MARRIED, DIVORCED, OTHER |
| dob | Date | Date of birth |
| national_id | String | NIDA number |
| tin | String | Tax identification number |
| address | String | Postal address |
| designation | String | Job title/position |
| department_id | FK | Department assignment |
| supervisor_id | FK | Direct supervisor |
| profile | File | Profile photo path |
| file | File | Signature file path |
| contract | File | Employment contract file path |

**Relationships:**

```
User (Employee)
├── Department (many-to-one)
├── Position (many-to-one)
├── StaffBankDetail (one-to-many)
│   └── Bank (many-to-one)
├── StaffSalary (one-to-many) — base salary records
├── AllowanceSubscription (one-to-many)
│   └── Allowance (many-to-one) — MONTHLY or DAILY type
├── DeductionSubscription (one-to-many)
│   └── Deduction (many-to-one) — PAYE/NSSF/WCF/HESLB/SDL/NHIF
├── Loan (one-to-many) — with approval workflow
├── AdvanceSalary (one-to-many) — with approval workflow
├── AssetProperty (one-to-many) — assigned company assets
│   └── Asset (many-to-one)
├── LeaveRequest (one-to-many)
├── Attendance (one-to-many)
└── Project (many-to-many via project_team_members)
```

#### 2.18 Permissions Summary

| Permission | Controls |
|------------|----------|
| `Add Staff Bank Detail` | Show "New" button on bank details page |
| `Edit Staff Bank Detail` | Show edit button per row |
| `Delete Staff Bank Detail` | Show delete button per row |
| `Add Adjustment` | Create payroll adjustments |
| `Edit Adjustment` | Modify adjustments |
| `Delete Adjustment` | Remove adjustments |
| `Approve Document` (on Payroll) | Approve payroll submissions |
| `Approve Document` (on Loan) | Approve loan requests |
| `Approve Document` (on AdvanceSalary) | Approve advance salary requests |

#### 2.19 Approval Workflows

Three models in this module use RingleSoft approval:

| Model | Document Type ID | Auto-Number Format | Status Lifecycle |
|-------|-----------------|-------------------|-----------------|
| Payroll | (configured in seeder) | `PRL/{year}/{seq}` | CREATED → PENDING → APPROVED |
| AdvanceSalary | 6 | `ADVS/{id}/{year}` | CREATED → PENDING → APPROVED → PAID → COMPLETED |
| Loan | 7 | `LN/{id}/{year}` | CREATED → PENDING → APPROVED → PAID → COMPLETED |

On approval completion, each model's `onApprovalCompleted()` callback updates the status to `APPROVED` and dispatches notifications.

---

### 3. VAT

The VAT (Value Added Tax) module is a comprehensive tax compliance system for managing Tanzania Revenue Authority (TRA) obligations. It handles sales recording, purchase input VAT tracking, EFD (Electronic Fiscal Device) receipt capture, VAT payments to the tax authority, and VAT liability analysis. The standard VAT rate is **18%** (hard-coded).

**Sidebar Menu Items (4 sub-pages + 2 related pages):**

| # | Menu Item | Route | Purpose |
|---|-----------|-------|---------|
| 1 | Sales | `/sales` | Record daily EFD sales with tax breakdowns |
| 2 | Purchases | `/purchases` | Record supplier purchases with VAT input tracking |
| 3 | Auto Purchases | `/auto_purchases` | View EFD-captured receipt data (auto-imported) |
| 4 | VAT Payments | `/vat_payment` | Record payments made to TRA |
| 5 | Provision Tax | `/provision_tax` | Track provisional tax payments (standalone menu item) |
| 6 | Expense Adjustable | `/expense_adjustable` | VAT adjustment expenses (accessed via Purchases page link) |

**Approval Workflows:** Sale, Purchase, and VatPayment models all use RingleSoft approval (two-level: System Administrator → Managing Director).

#### 3.1 VAT Sales

**Route:** `/sales` (GET/POST)
**Page Title:** "All Sales"

Records daily sales figures from EFD machines. Each entry captures turnover, net amounts, tax, and exempt sales for a specific EFD device and date.

**Filter Bar:**

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| Start Date | Date picker | Today | Filter start |
| End Date | Date picker | Today | Filter end |
| EFD | Dropdown | "All EFD" | Filter by EFD machine (e.g., "HQ EFD MACHINE") |
| Show | Button | — | Apply filters |

**Add Button:** "New Sales" — permission: `Add Sales`

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number (sorted descending) |
| Date | Transaction date |
| EFD Name | EFD machine name (e.g., "HQ EFD MACHINE") |
| Turnover | Total sales amount (formatted currency, teal color) |
| NET (A+B+C) | Net sales — sum of Standard (A), Special (B), and Zero-rated (C) |
| Tax | Tax amount collected |
| Turnover (EX + SR) | Exempt and Special Relief sales |
| Attachment | Link to uploaded file (e.g., EFD Z-report PDF) |
| Approvals | Avatar icons showing approval chain status |
| Status | Badge: CREATED / PENDING / APPROVED |
| Actions | View (approval page) / Edit (`Edit Sales`) / Delete (`Delete Sales`) |

**Export:** Print, Excel, PDF
**Footer Row:** Totals for Turnover, NET, Tax, and Turnover (EX+SR)

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| EFD Name | Dropdown | Yes | Select EFD machine |
| Turnover | Number | Yes | Total sales amount |
| NET (A+B+C) | Number | Yes | Net taxable sales |
| Tax | Number | Yes | Tax amount from EFD |
| Turnover (EX + SR) | Number | Yes | Exempt + Special Relief amount |
| Date | Date picker | Yes | Transaction date |
| File | File upload | No | Attach Z-report or statement |

**Auto-generated:** `document_number` format `SALE/{ID}/{YEAR}`

**Sales VAT Calculation:**
```
Taxable Sales = Total Sales (amount) − Exempt Sales (turn_over)
Sales VAT Exclusive = Taxable Sales × 100 / 118
Sales VAT Amount = Sales VAT Exclusive × 18 / 100
```

#### 3.2 VAT Purchases

**Route:** `/purchases` (GET/POST)
**Page Title:** "All Purchases"

Records supplier purchases with VAT input tracking. Each purchase captures the supplier, invoice details, total amount, and automatically calculates VAT-exclusive and VAT amounts based on purchase type.

**Filter Bar:**
- Start Date / End Date / "Show" button (defaults to today)

**Add Button:** "New Purchase" — permission: `Add Purchases`
**Special Link:** "Expense Adjustable" (red text, links to `/expense_adjustable`)

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Record date |
| Supplier Name | Supplier company name |
| Supplier VRN | VAT Registration Number |
| Tax Invoice | Supplier's invoice number |
| Invoice Date | Date on supplier's invoice |
| Goods | Item/goods purchased |
| Total Amount | Total including VAT |
| Amount VAT EXC | Amount before VAT |
| VAT Amount | VAT at 18% (or 0 for exempt) |
| Is Expenses | YES/NO — marks as adjustable expense |
| Attachment | Link to uploaded invoice |
| Approvals | Approval chain status |
| Status | Badge: CREATED / PENDING / APPROVED |
| Actions | View / Edit (`Edit Purchases`) / Delete (`Delete Purchases`) |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Is Expenses? | Dropdown (YES/NO) | No | Default: NO. If YES, included in cost of sales adjustments |
| Supplier | Select2 searchable | Yes | From suppliers table |
| Item | Select2 searchable | Yes | From items table |
| Purchase Type | Dropdown | Yes | **VAT** (type 1) or **EXEMPT** (type 2) |
| Total Amount | Number | Yes | Total including VAT |
| Tax Invoice | Text | Yes | Supplier's invoice number |
| Invoice Date | Date picker | Yes | Date on supplier's invoice |
| Date | Date picker | Yes | Record date |
| File | File upload | No | Attach supplier invoice |

**Auto-generated:** `document_number` format `PCHS/{ID}/{YEAR}`

**Real-time VAT Calculation (JavaScript):**
When purchase type = **VAT** and total amount is entered:
```
Amount VAT EXC = Total Amount × 100 / 118
VAT Amount    = Amount VAT EXC × 18 / 100

Example: Total = 1,180,000
  Amount VAT EXC = 1,180,000 × 100/118 = 1,000,000
  VAT Amount     = 1,000,000 × 18/100  = 180,000
```

When purchase type = **EXEMPT**:
```
Amount VAT EXC = 0
VAT Amount     = 0
```

#### 3.3 Auto Purchases (EFD Receipts)

**Route:** `/auto_purchases` (GET/POST)
**Page Title:** "All Purchases"

Displays receipts automatically captured from Electronic Fiscal Devices (EFD). This is a **read-only import** — data comes from the EFD system, not manual entry. No "New" button is provided.

**Filter Bar:**
- Start Date / End Date / "Show" button (default range: June 1 of current year to today)

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| InsertedDate | Date receipt was captured |
| SupplierName | Vendor company name from receipt |
| SupplierVRN | Vendor's VAT Registration Number |
| TaxInvoice | Receipt/invoice number |
| InvoiceDate | Date on the receipt |
| Goods | Items purchased (clickable — opens item detail modal) |
| AmountVATEXC | Amount excluding VAT |
| VATAmount | VAT amount from receipt |
| TotalAmount | Total including VAT |
| Discount | Any discount applied |
| VerificationCode | TRA verification code (links to `https://verify.tra.go.tz/`) |
| Is Expenses | YES/NO flag for cost adjustments |
| Actions | Edit (`Edit Auto Purchase`) / Delete (`Delete Auto Purchase`) |

**Export:** Print, Excel, PDF
**Footer Row:** Totals for AmountVATEXC, VATAmount, TotalAmount

**Key Differences from Manual Purchases:**
- No "New" button — data is auto-imported from EFD
- No Approval workflow or Status column
- Has Discount and VerificationCode columns
- Column headers use concatenated names (no spaces)
- VerificationCode links to TRA verification system

**Receipt Data Model (EFD fields):**

| Field | Description |
|-------|-------------|
| company_name | Supplier business name |
| vrn | VAT Registration Number |
| uin | Unique Identification Number |
| tin | Tax Identification Number |
| receipt_number | EFD receipt number |
| receipt_date / receipt_time | Transaction timestamp |
| receipt_z_number | Z-report number |
| receipt_verification_code | TRA verification code |
| receipt_total_excl_of_tax | Amount before VAT |
| receipt_total_tax | VAT amount |
| receipt_total_incl_of_tax | Total with VAT |
| receipt_total_discount | Discount amount |
| is_tanesco | Flag for utility (TANESCO) receipts |
| is_expense | Flag for cost-of-sales adjustment |

Utility-specific fields: `kwh_charge`, `kva_charge`, `service_charge`, `interest_amount`, `receipt_rea`, `receipt_ewura`, `receipt_property_tax`, `tax_rate`

#### 3.4 VAT Payments

**Route:** `/vat_payment` (GET/POST)
**Page Title:** "All Payments"

Records payments made to the Tanzania Revenue Authority for VAT obligations.

**Filter Bar:**
- Start Date / End Date / "Show" button (defaults to today)

**Add Button:** "New VatPayment"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Payment date |
| Bank Name | Bank account used for payment |
| Description | Payment purpose/memo |
| Amount | Payment amount |
| Attachment | Link to payment proof |
| Approvals | Approval chain status |
| Status | Badge: CREATED / PENDING / APPROVED |
| Actions | View (approval page) / Edit / Delete |

**Export:** Print, Excel, PDF
**Footer Row:** Total for Amount

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Bank Name | Dropdown | Yes | CRDB BANK, NBC BANK, NMB BANK, AZANIA BANK, CASH IN HAND |
| Amount | Number | Yes | Payment amount |
| Description | Text | No | Purpose/reference |
| Date | Date picker | Yes | Payment date |
| File | File upload | No | Attach payment proof |

**Auto-generated:** `document_number` format `VATP/{ID}/{YEAR}`

#### 3.5 Provision Tax

**Route:** `/provision_tax` (GET/POST)
**Page Title:** "Provision Taxes"
**Sidebar:** Standalone menu item (not under VAT submenu)

Tracks provisional (estimated) income tax payments made to TRA throughout the year.

**Filter Bar:**
- Start Date / End Date / "Show" button

**Add Button:** "New ProvisionTax"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Payment date |
| Debit Number | Bank debit reference number |
| Description | Payment description |
| Amount | Payment amount |
| Bank | Bank account used |
| Attachment | Link to payment proof |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF
**Footer Row:** Total for Amount

**Key Difference from VAT Payments:** No approval workflow — Provision Tax entries don't go through an approval chain.

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Bank | Dropdown | Yes | CRDB BANK, NBC BANK, NMB BANK, AZANIA BANK, CASH IN HAND |
| Debit Number | Number | Yes | Bank debit reference |
| Amount | Number | Yes | Payment amount |
| Description | Text | No | Purpose/reference |
| Date | Date picker | Yes | Payment date |
| File | File upload | No | Attach payment proof |

#### 3.6 Expense Adjustable

**Route:** `/expense_adjustable` (GET/POST)
**Access:** Via "Expense Adjustable" link on the Purchases page (not in sidebar)
**Page Title:** "All Adjustment Expenses"

Records adjustments to the cost of sales for VAT calculation purposes (e.g., stock write-offs, damage, wastage).

**Filter Bar:**
- Start Date (default: 1st of current month) / End Date (default: today) / "Show" button

**Add Button:** "New AdjustmentExpense"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Adjustment date |
| Amount | Adjustment amount |
| Actions | Edit / Delete |

**Footer Row:** Total for Amount

**Modal Form (simplest in module — 2 fields only):**

| Field | Type | Required |
|-------|------|----------|
| Amount | Number | Yes |
| Date | Date picker | Yes |

#### 3.7 VAT Calculation Engine

The VAT module's core purpose is calculating the company's VAT liability. The system tracks all components and computes the net amount owed or refundable.

**VAT Liability Formula:**

```
CURRENT VAT PAYABLE = Sales Output VAT − (Purchase Input VAT + Auto Purchase Input VAT)

Where:
  Sales Output VAT    = (Total Sales − Exempt Sales) × 100/118 × 18%
  Purchase Input VAT  = Sum of vat_amount from approved purchases
  Auto Purchase VAT   = Sum of receipt_total_tax from EFD receipts

NET VAT PAYABLE = Current VAT Payable − VAT Payments Made
  (Positive = owed to TRA, Negative = refund due)

TOTAL VAT PAYABLE = Net VAT Payable + Carried Forward from Previous Period
```

**Cost of Sales Calculation (for adjustments):**
```
COGS = Opening Stock + Purchases − Adjustments − Adjustable Exempt − Closing Stock
```

**VAT Analysis Report (accessible from Reports module):**

| Line Item | Calculation |
|-----------|-------------|
| Total Purchases (Normal) | Sum of approved purchase amounts for period |
| Total Purchases (Auto/EFD) | Sum of EFD receipt totals for period |
| Total Purchases Combined | Normal + Auto |
| Total Sales | Sum of approved sale amounts for period |
| Current VAT Payable/(Refund) | Sales VAT − (Purchase VAT + Auto VAT) |
| Current VAT Payment | Sum of approved VAT payments in period |
| Actual VAT Payable/(Refund) | Current Payable − Current Payment |
| Old VAT Payable/(Refund) | Cumulative liability from prior periods (from 2020-01-01) |
| **Total VAT Payable/(Refund)** | **Actual + Old = cumulative liability** |

#### 3.8 Data Model & Relationships

```
Sale ──────────────── BelongsTo ──── Efd (Electronic Fiscal Device)
  └── status, document_number (SALE/{ID}/{YEAR})
  └── Approval: ApprovableModel → CREATED → PENDING → APPROVED

Purchase ──────────── BelongsTo ──── Supplier
  ├── BelongsTo ──── Item
  ├── BelongsTo ──── Project (optional, for procurement)
  ├── HasMany ────── PurchaseItem (line items for POs)
  └── status, document_number (PCHS/{ID}/{YEAR})
  └── Approval: ApprovableModel → CREATED → PENDING → APPROVED

Receipt (Auto Purchase) ── HasMany ──── ReceiptItem (line items)
  └── receipt_verification_code → links to TRA verify

VatPayment ────────── BelongsTo ──── Bank
  └── status, document_number (VATP/{ID}/{YEAR})
  └── Approval: ApprovableModel → CREATED → PENDING → APPROVED

VatAnalysis (helper, no DB table) ── calculates liability from all above
```

#### 3.9 Permissions Summary

| Permission | Controls |
|------------|----------|
| `Add Sales` | Show "New Sales" button |
| `Edit Sales` | Show edit button per sale row |
| `Delete Sales` | Show delete button per sale row |
| `Add Purchases` | Show "New Purchase" button |
| `Edit Purchases` | Show edit button per purchase row |
| `Delete Purchases` | Show delete button per purchase row |
| `Edit Auto Purchase` | Show edit button per receipt row |
| `Delete Auto Purchase` | Show delete button per receipt row |
| `Add VatPayment` | Show "New VatPayment" button |
| `Edit VatPayment` | Show edit button per payment row |
| `Delete VatPayment` | Show delete button per payment row |

#### 3.10 Mobile API Endpoints

The VAT module is fully accessible from the mobile app via REST API:

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/v1/vat/reference-data` | GET | Returns EFDs, Suppliers, Items, Banks, Purchase Types |
| `/api/v1/vat/sales` | GET/POST | List and create sales |
| `/api/v1/vat/sales/{id}` | GET/PUT/DELETE | View, update, delete sale |
| `/api/v1/vat/purchases` | GET/POST | List and create purchases |
| `/api/v1/vat/purchases/{id}` | GET/PUT/DELETE | View, update, delete purchase |
| `/api/v1/vat/auto-purchases` | GET | List EFD receipts (read-only) |
| `/api/v1/vat/payments` | GET/POST | List and create VAT payments |
| `/api/v1/vat/payments/{id}` | GET/PUT/DELETE | View, update, delete payment |

#### 3.11 VAT Reports (in Reports Module)

| Report | Route | Description |
|--------|-------|-------------|
| Sales Report | `/reports/sales_report` | Sales breakdown by date range and EFD |
| Purchases Report | `/reports/purchases_report` | Purchase breakdown by date range |
| Purchases by Supplier | `/reports/purchases_by_supplier_report` | Purchase totals grouped by supplier |
| VAT Analysis | `/reports/vat_analysis_report` | Full VAT liability calculation |
| VAT Payments Report | `/reports/vat_payments_report` | Payment history with bank details |

---

### 4. Projects

The Projects module is the core of Wajenzi — the largest module with 17 sidebar sub-pages managing the full lifecycle of construction projects. It covers client management, project creation, BOQ estimation, site operations, material tracking, scheduling, lead/sales pipeline, and daily progress reporting.

**Sidebar Menu Items (17 sub-pages):**

| # | Menu Item | Route | Purpose |
|---|-----------|-------|---------|
| 1 | Project Clients | `/project_clients` | Client registry with portal access |
| 2 | Project List | `/projects` | All projects with status, budgets, timelines |
| 3 | Site Visits | `/project_site_visits` | Scheduled site inspections |
| 4 | BOQ Management | `/project_boqs` | Bill of Quantities with hierarchical sections |
| 5 | Project Expenses | `/project_expenses` | Track project-specific costs |
| 6 | Materials | `/project_materials` | Material master data and pricing |
| 7 | Material Inventory | `/project_material_inventory` | Per-project stock tracking |
| 8 | Daily Reports | `/project_daily_reports` | Daily project progress reports |
| 9 | Project Types | `/project_types` | Configuration of project categories |
| 10 | Project Documents | `/project_documents` | Project-scoped file uploads |
| 11 | Project Reports | `/project_reports` | Project-specific reporting |
| 12 | Sales Daily Report | `/sales_daily_reports` | Salesperson daily activity reports |
| 13 | Lead Management | `/leads` | Sales leads, follow-ups, and pipeline |
| 14 | Project Schedules | `/project-schedules` | Activity scheduling from templates |
| 15 | Sites | `/projects/sites/sites` | Physical construction site registration |
| 16 | Supervisor Assignments | `/projects/sites/site-supervisor-assignments` | Assign supervisors to sites |
| 17 | Site Daily Reports | `/projects/site-reports/site-daily-reports` | Daily site progress and payments |

**Approval Workflows (6 models):** Project, ProjectBoq, ProjectMaterialRequest, ProjectSiteVisit, ProjectClient, SiteDailyReport

**Key Integrations:**
- BOQ items feed into Procurement (material requests → supplier quotations → purchase orders)
- Project costs tie to Finance (chart of accounts)
- Project clients link to Client Portal (authenticated access)
- Leads link to Billing (quotations, proformas, invoices)
- Schedules built from Activity Templates (BOQ Templates module)

#### 4.1 Project Clients

**Route:** `/project_clients` (GET/POST)
**Page Title:** "All Clients"

Central client registry. Clients are linked to projects, leads, and billing documents. Includes a self-service **Client Portal** with separate authentication.

**Add Button:** "New Client"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Document Number | Auto-generated client ID |
| Name | Full name (first + last) |
| Email | Email address |
| Phone | Phone number |
| Source | Acquisition channel (Referral, Social Media, Walk-in/Leads) |
| Projects | Count of linked projects |
| Documents | Count of uploaded documents |
| Portal | "Active" or "No Account" badge |
| Last Login | Last portal login timestamp |
| Approvals | RingleSoft approval chain (System Admin → MD) |
| Status | PENDING / APPROVED badge |
| Actions | View / Edit / Delete |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| First Name | Text | Yes | Client first name |
| Last Name | Text | Yes | Client last name |
| Email | Text | No | Pre-filled with current user's email |
| Phone Number | Text | No | Contact phone |
| Address | Text | No | Physical address |
| Identification Number | Text | No | National ID |
| Client Source | Dropdown | Yes | Referral Customers, Social Media, Walking in customers/Leads |
| Portal Password | Text | No | Default: "123456" |
| Confirm Password | Text | No | Must match password |
| Enable Portal Access | Checkbox | No | Checked by default |

**Approval:** RingleSoft workflow. Status: CREATED → PENDING → APPROVED.

**Client Portal:** When portal access is enabled, clients get their own login to view project progress, documents, invoices, and payments via the Client Portal API.

#### 4.2 Project List

**Route:** `/projects` (GET/POST)
**Page Title:** "All Projects"

Central project registry with statistics dashboard and comprehensive filtering.

**Summary Cards (5):**

| Card | Description |
|------|-------------|
| Total Projects | Count of all projects |
| Active | Projects with active status |
| Completed | Finished projects |
| Delayed | Projects where actual duration > planned duration |
| Total Value (TZS) | Sum of all contract values (e.g., 549.5M) |

**Filters (collapsible):**
- Project Category (Residential, Commercial, Industrial, Infrastructure)
- Service Type (Site Visit, Architectural Design, Structural Design, BOQ Preparation, Landscape Design, Service Design, Combo Option)
- Status, Date Range, Salesperson, Project Manager

**Add Button:** "New Project"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| Project ID | Document number (e.g., PCT/1/2026) |
| Project Name | Name of the project |
| Client | Linked client name |
| Category | Project type (Residential, etc.) |
| Service Type | Service category |
| Status | CREATED / APPROVED badge |
| Start Date | Project start date |
| Expected End | Planned completion |
| Actual End | Actual completion (blank if ongoing) |
| Planned (Days) | `expected_end_date - start_date` |
| Actual (Days) | `actual_end_date (or today) - start_date` |
| Delay (Days) | `actual - planned` (negative = ahead of schedule) |
| Contract Value | Project value in TZS |
| Salesperson | Assigned salesperson |
| Project Manager | Assigned PM |
| Actions | View / Edit / Delete |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Project Name | Text | Yes | Descriptive project name |
| Client | Dropdown | Yes | Select from approved clients |
| Description | Textarea | No | Project description |
| Project Category | Dropdown | Yes | Residential, Commercial, Industrial, Infrastructure |
| Service Type | Dropdown | No | Type of service being provided |
| Start Date | Date | Yes | Defaults to today |
| Expected End Date | Date | Yes | Planned completion |
| Actual End Date | Date | No | Set when completed |
| Contract Value (TZS) | Number | No | Project monetary value |
| Priority | Dropdown | No | Low, Normal (default), High, Urgent |
| Salesperson | Dropdown | No | From staff list |
| Project Manager | Dropdown | No | From staff list |

**Auto-generated:** `document_number` format `PCT/{id}/{year}`

**Duration Calculations:**
```
planned_duration = expected_end_date − start_date (days)
actual_duration  = (actual_end_date or today) − start_date (days)
delay            = actual_duration − planned_duration
isDelayed        = delay > 0
```

**Approval:** RingleSoft workflow. `onApprovalCompleted()` sets status = APPROVED.

#### 4.3 Site Visits

**Route:** `/project_site_visits` (GET/POST)
**Page Title:** "All Site Visits"

Tracks scheduled and completed site inspections linked to projects.

**Filter Bar:** Start Date, End Date, Project dropdown, "Show" button

**Add Button:** "New Visit"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Project Name | Linked project |
| Location | Visit location |
| Description | Visit purpose |
| Visit Date | Scheduled/actual date |
| Status | CREATED / PENDING / APPROVED |
| Approvals | Approval chain status |
| Actions | View / Edit / Delete |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Project | Dropdown | Yes |
| Location | Text | Yes |
| Description | Text | Yes |
| Visit Date | Date (default: today) | Yes |

**Special Features:**
- Conflict detection for inspector availability when scheduling
- Notifications sent on schedule/assign (SiteVisitScheduled, SiteVisitAssigned)
- Completed visits can auto-create a ProjectDailyReport from findings
- Reschedule allowed only for scheduled/cancelled visits

**Approval:** RingleSoft workflow. `onApprovalCompleted()` sets status = APPROVED.

#### 4.4 BOQ Management

**Route:** `/project_boqs` (GET/POST)
**Page Title:** "All BOQs"

The Bill of Quantities (BOQ) system is a cornerstone feature — a hierarchical, version-controlled cost estimation tool for construction projects. BOQs can have nested sections, material and labour line items, templates, CSV import/export, and PDF generation.

**Top Action Buttons:**
- **BOQ Templates** — manage reusable templates
- **New BOQ** — create new BOQ

**Filter Bar:** Project, Type (Client/Internal), Status (Draft/Submitted/Approved), "Show" button

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Project | Project name (clickable link to BOQ detail) |
| Version | BOQ version number |
| Type | Client or Internal |
| Total Amount | Sum of all items (e.g., 587,408,000.00) |
| Approvals | Approval chain icons (green check / orange clock) |
| Status | DRAFT / SUBMITTED / APPROVED |
| Actions | View / Edit / Delete |

**Footer Row:** Total amount across all BOQs

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Project | Dropdown | Yes |
| Version | Number (default: 1) | Yes |
| Type | Dropdown (Client / Internal) | Yes |
| Status | Dropdown (Draft / Submitted / Approved) | No |
| Total Amount | Number | No |

**BOQ Detail Page (show):**

The detail page displays the full hierarchical BOQ with sections and items:

**Section Hierarchy:** Sections can nest infinitely (parent_id self-reference). Each section shows its subtotal (recursive sum of items + children).

**Item Fields per Row:**

| Field | Description |
|-------|-------------|
| Item Code | Auto-generated (BOQ-{boq_id}-###) |
| Description | Item description |
| Item Type | Material or Labour |
| Specification | Technical specification |
| Unit | Unit of measure |
| Quantity | Required quantity |
| Unit Price | Price per unit |
| Total Price | quantity × unit_price (auto-calculated) |

**Procurement Tracking Columns (on BOQ items):**

| Field | Calculation |
|-------|------------|
| Qty Requested | Quantity in material requests |
| Qty Ordered | Quantity in purchase orders |
| Qty Received | Quantity delivered from suppliers |
| Qty Used | Quantity consumed on site |
| Qty Remaining | quantity − quantity_requested |
| Procurement % | (quantity_received / quantity) × 100 |
| Budget Used | quantity_ordered × unit_price |
| Budget Remaining | total_price − budget_used |

**Material Request from BOQ:** Checkboxes on material items allow bulk selection. A floating "Request Selected" button creates a ProjectMaterialRequest with the selected items.

**CSV Import/Export:**
- Export format: `Section | Description | Type | Specification | Unit | Qty | Unit Price`
- Import supports nested sections using `/` separator (e.g., "SUPERSTRUCTURE/WALLS")
- UTF-8 BOM for Excel compatibility

**PDF Export:** Hierarchical rendering with section subtotals, company branding, and formatted currency.

**Templates:**
- Save any BOQ as a reusable template
- Apply a template to a new BOQ (clones all sections and items)
- Templates have their own CSV import/export

**Approval:** RingleSoft workflow. `onApprovalCompleted()` sets status = approved.

#### 4.5 Project Expenses

**Route:** `/project_expenses` (GET/POST)
**Page Title:** "All Project Costs"

Tracks project-specific costs categorized by cost type.

**Filter Bar:** Start Date, End Date, Project, Category, "Show" button

**Summary Cards (3):**

| Card | Description |
|------|-------------|
| Total Records | Number of expense entries |
| Total Cost Amount | Sum in TZS |
| Projects with Costs | Count of projects with expenses |

**Add Button:** "New Cost"

**Table Columns:**

| Column | Description |
|--------|-------------|
| Cost ID | Expense record ID |
| Project ID | Project reference |
| Project Name | Project name |
| Cost Category | Expense category |
| Cost Description | Description text |
| Cost Date | Date of expense |
| Cost Amount (TZS) | Amount |
| Remarks | Additional notes |
| Actions | Edit / Delete |

**Footer Row:** Total cost amount

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Project | Dropdown | Yes |
| Cost Category | Dropdown | Yes |
| Cost Date | Date (default: today) | Yes |
| Cost Amount (TZS) | Number | Yes |
| Cost Description | Textarea | Yes |
| Remarks | Textarea | No |

#### 4.6 Materials

**Route:** `/project_materials` (GET/POST)
**Page Title:** "All Materials"

Material master data — defines materials available for use across all projects.

**Filter Bar:** Name (text search), Unit (kg/pieces/meters/liters/boxes), "Show" button

**Add Button:** "New Material"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Material name |
| Description | Material description |
| Unit | Unit of measure |
| Current Price | Latest price per unit |
| Total Inventory | Sum of inventory across all projects |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

#### 4.7 Material Inventory

**Route:** `/project_material_inventory`
**Status:** Partially implemented — the main index route requires a project selection first.

**Functional Routes:**
- `/stock_register/{project_id}` — Stock register view for specific project
- `/stock_register/{project_id}/movements` — Material movement history
- `/stock_register/{project_id}/issue` — Issue materials from stock
- `/stock_register/{project_id}/adjust/{inventory_id}` — Stock adjustments

**Stock Register Stats (4 cards):**

| Stat | Description |
|------|-------------|
| Total | Total inventory items |
| In Stock | Items with available quantity |
| Low Stock | Items below threshold |
| Out of Stock | Items with zero quantity |

**Movement Types:**
- **Receipt** — goods received from supplier (linked to purchase)
- **Issue** — materials issued to site (validates available quantity)
- **Adjustment** — manual stock correction (up or down)

**Stock Status:** `in_stock`, `low_stock`, `out_of_stock` (auto-calculated from quantities)

#### 4.8 Daily Reports

**Route:** `/project_daily_reports` (GET/POST)
**Page Title:** "All Daily Reports"

Project-level daily progress reports submitted by site supervisors.

**Filter Bar:** Start Date, End Date, Project, "Show" button

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Project | Project name |
| Report Date | Date of report |
| Supervisor | Reporting supervisor |
| Weather | Weather conditions |
| Labor Hours | Total labor hours |
| Actions | View |

**Export:** Print, Excel, PDF

**Report Fields:** project_id, supervisor_id, report_date, weather_conditions, work_completed, materials_used, labor_hours, issues_faced

#### 4.9 Project Types

**Route:** `/project_types` (GET/POST)
**Page Title:** "All Project Types"

Configuration of project category types.

**Add Button:** "New Type"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Type name (Residential, Commercial, Industrial, Infrastructure) |
| Description | Type description |
| Total Projects | Count of projects using this type |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Pre-configured Types:** Residential (2 projects), Commercial (0), Industrial (0), Infrastructure (0)

#### 4.10 Project Documents

**Route:** `GET/POST /project_documents`
**Controller:** `ProjectDocumentController@index`
**Sidebar:** Projects > Project Documents
**Permission:** `Project Documents`

Project-scoped file management for uploading, categorizing, and downloading construction documents. Documents are classified by type (Contract, Drawing, Report, Other) and linked to approved projects.

**Page Layout:**
- Heading: "Project Documents"
- Section heading: "All Documents"
- "New Document" button (visible if user has `Add Document` permission)
- Filter bar + DataTable with export buttons (Print, Excel, PDF)

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Project | dropdown | All Projects + list of approved projects |
| Type | dropdown | All Types, Contract, Drawing, Report, Other |

**DataTable Columns:**

| # | Column | Description |
|---|--------|-------------|
| 1 | # | Row number |
| 2 | Project | Project name (from relationship) |
| 3 | Type | Document type (Contract/Drawing/Report/Other) |
| 4 | File Name | Original uploaded filename |
| 5 | Size | File size in KB |
| 6 | Uploaded By | User who uploaded (from relationship) |
| 7 | Status | Badge: active / archived |
| 8 | Actions | Download link, Edit (pencil), Delete (X) |

**Action Buttons per Row:**

| Button | Permission | Action |
|--------|-----------|--------|
| Download | — | Direct file download via `/project_document/download/{id}` |
| Edit | `Edit Document` | `loadFormModal('project_document_form', {className: 'ProjectDocument', id: ID}, 'Edit Document', 'modal-md')` |
| Delete | `Delete Document` | `deleteModelItem('ProjectDocument', ID, ...)` |

**Upload Form Modal:**

**Form:** `project_document_form`
**Modal Size:** `modal-md`
**Encoding:** `multipart/form-data`

| # | Field | Name | Type | Required | Notes |
|---|-------|------|------|----------|-------|
| 1 | Project | `project_id` | select dropdown | Yes | Approved projects only (`Project::where('status','APPROVED')`) |
| 2 | Document Type | `document_type` | select dropdown | Yes | Options: Contract, Drawing, Report, Other |
| 3 | Document File | `file` | file upload | Yes (create) | Max 10MB. Types: png, jpg, jpeg, csv, txt, xlx, xls, xlsx, doc, docx, pdf |
| 4 | Description | `description` | textarea | No | Optional notes |

**Submit:** `addItem` with `value="ProjectDocument"` (create) / `updateItem` (edit)

**File Storage:**
- Location: `storage/project_documents/`
- Naming: `{time()}_{original_filename}`
- Storage disk: `public`
- Max size: 10MB per file

**Additional Controller Methods:**

| Method | Route | Purpose |
|--------|-------|---------|
| `download($id)` | `GET /project_document/download/{id}` | Download file from storage |
| `archive($id)` | — | Set document status to 'archived' (JSON endpoint) |
| `getProjectDocuments($projectId, $type)` | — | JSON API: get active documents for a project |
| `bulkUpload()` | — | Upload multiple files at once (JSON endpoint) |

**Data Model — `project_documents` table:**

| Column | Type | Nullable | Description |
|--------|------|----------|-------------|
| id | bigint unsigned | No | Primary key |
| project_id | bigint unsigned | No | FK → projects.id |
| uploaded_by | bigint unsigned | No | FK → users.id |
| document_type | string(50) | No | Contract, Drawing, Report, Other |
| file_name | string(255) | No | Original filename |
| file_path | string(255) | No | Storage path |
| mime_type | string(100) | Yes | File MIME type |
| file_size | bigint | Yes | File size in bytes |
| description | text | Yes | Optional description |
| status | string(20) | No | active / archived (default: active) |
| created_at | timestamp | Yes | Upload timestamp |
| updated_at | timestamp | Yes | Last update |

**Model:** `App\Models\ProjectDocument`
- **Relationships:** `project()` → belongsTo(Project), `uploader()` → belongsTo(User, 'uploaded_by')
- **No approval workflow** — documents are uploaded directly without review.

**Permissions:**

| Permission | Purpose |
|------------|---------|
| Project Documents | Access the documents index page |
| Add Document | Upload new documents |
| Edit Document | Modify document metadata |
| Delete Document | Remove documents |

**Related: Project Client Documents**

A separate `ProjectClientDocument` model exists for client-specific documents:
- **Route:** `/project_client_documents` (routes exist but controller methods are stubs)
- **Table:** `project_client_documents` with columns: id, client_id (FK → project_clients), document_type, file, uploaded_at, status
- **Status:** Not implemented — controller returns blank page

**Notes:**
- There is no standalone "Documents" top-level module in the sidebar. Document management is project-scoped under the Projects section.
- A plan exists for a standalone Documents module with category-based sub-menus (Contracts, Drawings & Plans, Permits & Licenses, Certificates, Site Reports, Specifications, Insurance) with expiry tracking and approval workflows, but this has not been implemented yet.

#### 4.11 Project Reports

**Route:** `/project_reports`
**Status:** Controller `index` method not yet implemented.

**Planned:** Project-specific reporting and analytics.

#### 4.12 Sales Daily Reports

**Route:** `/sales_daily_reports` (GET/POST)
**Page Title:** "Sales Daily Reports"
**Breadcrumb:** Projects > Sales Daily Reports

Daily activity reports submitted by salespeople, capturing lead follow-ups, sales activities, client concerns, and customer acquisition costs.

**Add Button:** "+ New Report" (navigates to create page)

**Filter Bar:** Start Date, End Date, Status (All), Prepared By (All Users), "Filter" / "Clear" buttons

**Table Columns:**

| Column | Description |
|--------|-------------|
| Date | Report date |
| Prepared By | Salesperson name |
| Department | Staff department |
| Lead Follow-ups | Count of follow-ups in report |
| Sales Activities | Count of activities |
| Client Concerns | Count of concerns |
| Actions | View / Edit / Delete |

**Report Creation Form (full page, not modal):**

The report captures 4 nested entity types in a single form:

**1. Lead Follow-ups:**

| Field | Description |
|-------|-------------|
| Lead Name | Client/lead name |
| Client | Dropdown (existing clients) |
| Lead | Dropdown (existing leads) |
| Client Source | Source of lead |
| Details | Discussion details |
| Outcome | Result of follow-up |
| Next Step | Planned next action |
| Follow-up Date | Next follow-up date |

**2. Sales Activities:**

| Field | Description |
|-------|-------------|
| Invoice No | Related invoice number |
| Invoice Sum | Invoice amount |
| Activity | Activity description |
| Status | Activity status |
| Payment Amount | Amount collected |

**3. Client Concerns:**

| Field | Description |
|-------|-------------|
| Client Name | Client with concern |
| Client | Dropdown (existing clients) |
| Issue/Concern | Description of issue |
| Action Taken | Resolution steps |

**4. Customer Acquisition Cost (CAC):**

| Field | Calculation |
|-------|------------|
| Marketing Cost | Direct marketing spend |
| Sales Cost | Sales team cost |
| Other Cost | Miscellaneous costs |
| Total Cost | marketing + sales + other |
| New Customers | Customers acquired |
| CAC Value | total_cost / new_customers |
| Notes | Additional notes |

**Status Flow:** DRAFT → PENDING → APPROVED → REJECTED

**PDF Export:** Available for approved reports.

#### 4.13 Lead Management

**Route:** `/leads` (GET/POST)
**Page Title:** "Lead Management"
**Breadcrumb:** Projects > Leads

Full CRM pipeline for construction sales leads — from initial contact through follow-up, quotation, and project conversion.

**Add Button:** "+ New Lead" (navigates to create page)

**Filter Bar:** Search (name/ID/phone/city), Lead Status, Lead Source, Salesperson, "Filter" / "Clear" buttons

**Table Columns:**

| Column | Description |
|--------|-------------|
| Lead Number | Auto-generated (LEAD-YYYYMM-###) |
| Name | Lead/client name |
| Phone | Contact phone |
| Email | Contact email |
| Source | Lead acquisition channel |
| Service | Service of interest |
| Status | Pipeline status |
| Salesperson | Assigned salesperson |
| Follow-up | Next follow-up date |
| Actions | View / Edit / Delete |

**Export:** Print, Excel, PDF
**Pagination:** 20 per page

**Lead Creation Form (full page):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Client | Dropdown | No | Existing client (or auto-create) |
| Name | Text | Yes | Lead name |
| Phone | Text | No | Contact phone |
| Email | Email | No | Contact email |
| Address | Text | No | Physical address |
| Lead Source | Dropdown | Yes | Acquisition channel |
| Service Interested | Dropdown | Yes | Service type of interest |
| Site Location | Text | No | Construction site location |
| City | Text | No | City |
| Estimated Value | Number | No | Estimated project value |
| Lead Status | Dropdown | Yes | Pipeline stage |
| Salesperson | Dropdown | Yes | Assigned sales rep |
| Lead Date | Date | Yes | Lead creation date |
| Notes | Textarea | No | Additional notes |

**Auto-generated:** `lead_number` format `LEAD-YYYYMM-###`
**Auto-client:** If no existing client is selected, a new ProjectClient is created automatically.

**Lead Detail Page Features:**

The lead detail view is a comprehensive single-page dashboard:

- **Lead Info Card** — all lead fields with status badge
- **Follow-up Timeline** — chronological list of all follow-ups
  - Add Follow-up: followup_date, details_discussion, outcome, next_step
  - Attend Follow-up: mark as completed/cancelled/rescheduled, update lead status, schedule next
- **Linked Billing Documents** — quotations, proformas, and invoices created for this lead
- **Project Link** — link existing project or create new project from lead
- **Project Costs** — add/edit/delete project expenses from lead page
- **Schedule** — view or create project schedule from activity templates

**Lead → Project Conversion:** Creates a new Project auto-populated with client, salesperson, estimated value, and generates document number `PCT/{id}/{year}`.

#### 4.14 Project Schedules

**Route:** `/project-schedules` (GET)
**Page Title:** "Project Schedules"

Activity-based project scheduling built from configurable templates. Each schedule is linked to a lead and assigned to an architect.

**Filter Bar:** Status (All Statuses)

**Table Columns:**

| Column | Description |
|--------|-------------|
| Lead | Lead number (e.g., LEAD-202602-011) |
| Client | Client name |
| Architect | Assigned architect |
| Start Date | Schedule start |
| End Date | Schedule end |
| Progress | Percentage bar (completed / total activities) |
| Status | pending / confirmed / in_progress / completed |

**Schedules are auto-created from leads** via the "Create Schedule" action on a lead's detail page. The system uses `ProjectScheduleService::createScheduleFromTemplate()` to clone activities from activity templates.

**Schedule Detail Page:**

Displays activities grouped by construction phase (e.g., Pre-Design, Schematic Design, Construction Documents):

**Activity Fields:**

| Field | Description |
|-------|-------------|
| Activity Code | Reference code (e.g., A0, B1, C2) |
| Name | Activity name |
| Phase | Construction phase |
| Discipline | Technical discipline |
| Start Date | Calculated from predecessor |
| Duration (Days) | 1–60 days (editable) |
| End Date | start_date + duration_days |
| Predecessor | Code of prerequisite activity |
| Assigned To | Staff member |
| Role | Required role |
| Status | pending / in_progress / completed / skipped |

**Activity Actions:**
- **Start** — marks as in_progress (prerequisite: predecessor must be completed)
- **Complete** — marks as completed with optional notes + attachment
- **Assign** — assign/reassign user (permission: `Assign Project Activities`)
- **Update Days** — change duration (1–60 days, auto-recalculates all dates)
- **Remove** — delete activity (updates dependent predecessors)

**Date Recalculation:** When any activity's duration changes, `ProjectScheduleService::recalculateSchedule()` cascades updates to all dependent activities.

**Confirmation:** Admin can "Confirm" a schedule, changing status to `confirmed` and notifying the assigned architect.

**Access Control:** Non-admin architects see only schedules assigned to them. System Administrator and Managing Director see all schedules.

**Progress Calculation:**
```
progress_percentage = (completed_activities / total_activities) × 100
progress_by_phase  = {phase: (completed / total) × 100}
isOverdue          = status ≠ completed AND end_date < today
```

**Notifications:** Email + database notifications for: schedule confirmation, activity start/complete, activity reassignment, architect change.

#### 4.15 Sites

**Route:** `/projects/sites/sites` (GET/POST)
**Page Title:** "Sites Management"
**Breadcrumb:** Projects > Sites

Physical construction site registration and management.

**Add Button:** "+ New Site" (navigates to create page, not modal)

**Filter Bar:** Search (name/location), Status (All), "Filter" / "Clear" buttons

**Table Columns:**

| Column | Description |
|--------|-------------|
| Name | Site name (e.g., "Shine Embassy Commercial Building 6 Storey") |
| Location | Physical location (e.g., "Makumbusho") |
| Status | ACTIVE / INACTIVE / COMPLETED |
| Current Supervisor | Currently assigned supervisor |
| Progress | Progress percentage from latest report |
| Created | Creation date |

**Permissions:** `View Sites`, `Add Sites`, `Edit Sites`, `Delete Sites`

**Create Form (full page):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Name | Text | Yes | Site name (unique) |
| Location | Text | Yes | Physical address |
| Description | Textarea | No | Site details |
| Status | Dropdown | Yes | ACTIVE, INACTIVE, COMPLETED |
| Start Date | Date | No | Construction start |
| Expected End Date | Date | No | Planned completion |

**Deletion Rule:** Sites cannot be deleted if they have daily reports.

#### 4.16 Supervisor Assignments

**Route:** `/projects/sites/site-supervisor-assignments` (GET/POST)
**Page Title:** "Site Supervisor Assignments"
**Breadcrumb:** Projects > Sites > Assignments

Assigns supervisors to construction sites. Each site can have one active supervisor at a time.

**Add Button:** "+ New Assignment"

**Filter Bar:** Site (All Sites), Supervisor (All Supervisors), "Filter" / "Clear" buttons

**Table Columns:**

| Column | Description |
|--------|-------------|
| Site | Construction site name |
| Supervisor | Assigned staff member |
| Assigned From | Assignment start date |
| Assigned To | Assignment end date (blank if ongoing) |
| Assigned By | Admin who made the assignment |

**Permissions:** `View Site Assignments`, `Add Site Assignments`, `Edit Site Assignments`, `Delete Site Assignments`

**Business Rules:**
- Only one active supervisor per site at a time
- Creating a new assignment auto-deactivates the previous one
- Setting `assigned_to` date marks assignment as inactive
- "Delete" soft-deactivates (sets `is_active = false`) instead of hard delete
- Assignment history viewable per site

**Create Form (full page):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Site | Dropdown (unassigned sites only) | Yes | Sites without active supervisor |
| Supervisor | Dropdown | Yes | Staff members |
| Assigned From | Date (dd/mm/yyyy) | Yes | Start date |
| Notes | Textarea | No | Assignment notes |

#### 4.17 Site Daily Reports

**Route:** `/projects/site-reports/site-daily-reports` (GET/POST)
**Page Title:** "Site Daily Reports"
**Breadcrumb:** Projects > Site Daily Reports

Comprehensive daily progress reports for construction sites with nested work activities, materials, payments, and labor requirements.

**Add Button:** "+ New Report" (navigates to create page, not modal)

**Filter Bar:** Site, Start Date, End Date, Status (All), Supervisor, "Filter" / "Clear" buttons

**Table Columns:**

| Column | Description |
|--------|-------------|
| Date | Report date |
| Site | Construction site |
| Supervisor | Reporting supervisor |
| Progress | Progress percentage |
| Work Activities | Count of activities recorded |
| Payments | Count/sum of payments |
| Status | DRAFT / PENDING / APPROVED / REJECTED |
| Actions | View / Edit / Submit / Approve / Delete |

**Permissions (granular):**

| Permission | Description |
|------------|-------------|
| `View All Site Reports` | See all sites' reports |
| `View Own Site Reports` | See only supervised sites |
| `Add Site Reports` | Create new reports |
| `Edit All Site Reports` | Edit any report |
| `Edit Own Site Reports` | Edit own reports only |
| `Delete All/Own Site Reports` | Delete permissions |
| `Submit Site Reports` | Submit for approval |
| `Approve Site Reports` | Approve/reject reports |
| `Export Site Reports` | Export as text |
| `Share Site Reports` | Share to WhatsApp |

**Create Form (full page with 4 nested sections):**

The report form is the most complex in the system — it captures 4 types of nested entities in a single atomic transaction:

**Header:**

| Field | Type | Required |
|-------|------|----------|
| Report Date | Date | Yes |
| Site | Dropdown | Yes |
| Progress % | Number (0–100) | No |
| Next Steps | Textarea | No |
| Challenges | Textarea | No |

**1. Work Activities (repeatable):**

| Field | Description |
|-------|-------------|
| Work Description | What work was performed |
| Order Number | Sequence/reference number |

**2. Materials Used (repeatable):**

| Field | Description |
|-------|-------------|
| Material Name | Material description |
| Quantity | Amount used |
| Unit | Unit of measure |

**3. Payments (repeatable):**

| Field | Description |
|-------|-------------|
| Payment Description | What the payment was for |
| Amount | Payment amount (TZS) |
| Payment To | Recipient name |

**4. Labor Needed (repeatable):**

| Field | Description |
|-------|-------------|
| Labor Type | Type of worker needed |
| Description | Details of labor requirement |

**Status Flow:** DRAFT → PENDING (on submit) → APPROVED / REJECTED

**Approval:** RingleSoft workflow. `onApprovalCompleted()` sets status = APPROVED. `onApprovalRejected()` sets status = REJECTED.

**WhatsApp Sharing:** Reports can be formatted as text with Swahili labels + emoji and shared directly to WhatsApp.

**Editing Rules:**
- Can only edit DRAFT or REJECTED reports
- Can only submit DRAFT reports
- Can only delete DRAFT reports
- On update, all nested entities are deleted and recreated (atomic transaction)

#### 4.18 Project Data Model

**Core Relationships:**

```
ProjectClient (authenticatable for portal)
  └── HasMany → Project
       ├── HasMany → ProjectBoq
       │    ├── HasMany → ProjectBoqSection (self-referential parent_id)
       │    └── HasMany → ProjectBoqItem
       │         ├── quantity tracking (requested → ordered → received → used)
       │         └── feeds into Procurement pipeline
       ├── HasMany → ProjectExpense
       ├── HasMany → ProjectSiteVisit
       ├── HasMany → ProjectDailyReport
       ├── HasMany → ProjectMaterialRequest
       │    └── HasMany → ProjectMaterialRequestItem → links to ProjectBoqItem
       ├── HasMany → ProjectMaterialInventory
       │    └── HasMany → ProjectMaterialMovement (receipt/issue/adjustment)
       └── BelongsToMany → User (via project_team_members)

Lead
  ├── BelongsTo → ProjectClient
  ├── BelongsTo → Project (optional link)
  ├── HasMany → SalesLeadFollowup
  ├── HasMany → BillingDocument (quotations, proformas, invoices)
  └── HasOne → ProjectSchedule
       └── HasMany → ProjectScheduleActivity (predecessor chain)

Site
  ├── HasMany → SiteSupervisorAssignment
  ├── HasOne → currentSupervisor (through active assignment)
  └── HasMany → SiteDailyReport
       ├── HasMany → SiteWorkActivity
       ├── HasMany → SiteMaterialUsed
       ├── HasMany → SitePayment
       └── HasMany → SiteLaborNeeded

SalesDailyReport
  ├── HasMany → SalesLeadFollowup
  ├── HasMany → SalesReportActivity
  ├── HasMany → SalesClientConcern
  └── HasMany → SalesCustomerAcquisitionCost
```

#### 4.19 Approval Workflows Summary

| Model | Approval Chain | On Completion |
|-------|---------------|---------------|
| Project | System Admin → MD | status = APPROVED |
| ProjectClient | System Admin → MD | status = APPROVED |
| ProjectBoq | MD | status = approved |
| ProjectMaterialRequest | Configurable | status = APPROVED, updates BOQ item quantities |
| ProjectSiteVisit | Configurable | status = APPROVED |
| SiteDailyReport | Configurable | status = APPROVED |

#### 4.20 Key Calculations

**BOQ Item Procurement Tracking:**
```
quantity_remaining         = quantity − quantity_requested
quantity_available_for_order = quantity_requested − quantity_ordered
quantity_pending_delivery  = quantity_ordered − quantity_received
quantity_in_stock          = quantity_received − quantity_used
procurement_percentage     = (quantity_received / quantity) × 100
budget_used               = quantity_ordered × unit_price
budget_remaining          = total_price − budget_used
```

**Schedule Progress:**
```
progress = (completed_activities / total_activities) × 100
```

**Sales CAC:**
```
total_cost = marketing_cost + sales_cost + other_cost
cac_value  = total_cost / new_customers
```

---

### 5. Finance

The Finance module manages the company's financial infrastructure — chart of accounts, petty cash, imprest (advance funds), exchange rates, expenses, statutory payments, and bank reconciliation. It provides the accounting backbone for all other modules.

**Sidebar Menu Items (7 sub-pages under Finance + related standalone pages):**

| # | Menu Item | Route | Purpose |
|---|-----------|-------|---------|
| 1 | Account Types | `/finance/financial_settings/account_types` | Define account classifications (Assets, Liabilities, etc.) |
| 2 | Charts of Accounts | `/finance/financial_settings/charts_of_accounts` | Hierarchical chart of accounts with parent-child nesting |
| 3 | Charts of Account Usage | `/finance/financial_settings/charts_of_account_usages` | Map chart accounts to system usage contexts |
| 4 | Petty Cash Refill Request | `/finance/petty_cash_management/petty_cash_refill_requests` | Request and approve petty cash replenishment |
| 5 | Imprest Request | `/finance/imprest_management/imprest_requests` | Advance fund requests for field operations |
| 6 | Exchange Rates | `/finance/financial_settings/exchange_rates` | Multi-currency exchange rate tracking by month/year |
| 7 | Chart of Account Variables | `/finance/financial_settings/chart_of_account_variables` | System configuration variables (e.g., PETTY_CASH_LIMIT) |

**Related Standalone Pages (separate sidebar items):**

| Menu Item | Route | Purpose |
|-----------|-------|---------|
| Expenses | `/expenses` | Company-wide expense tracking with approval |
| Statutory (parent) | — | Statutory payment compliance |
| → Statutory Payments | `/statutory_payments` | Record statutory obligation payments |
| → Statutory Category Report | `/reports/statutory_category_report` | Annual report by category |
| → Statutory Sub Category Report | `/reports/statutory_payment_report` | Annual report by sub-category |
| → Statutory Schedules Report | `/reports/statutory_schedules_report` | Monthly payment schedule matrix |

**Approval Workflows:** Expense, PettyCashRefillRequest, ImprestRequest, StatutoryPayment

#### 5.1 Account Types

**Route:** `/finance/financial_settings/account_types` (GET/POST)
**Page Title:** "Account Types"

Defines the top-level classification of all financial accounts in the system.

**Add Button:** "New Account Type"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Type | Account type name |
| Code | Numeric code |
| Normal Balance | DR (Debit) or CR (Credit) |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Pre-configured Account Types (6):**

| Type | Code | Normal Balance |
|------|------|---------------|
| ASSETS | 100000 | DR |
| LIABILITIES | 200000 | CR |
| EQUITY AND RESERVE | 300000 | CR |
| REVENUE | 400000 | CR |
| EXPENSE | 500000 | DR |
| COST OF GOODS SOLD | 600000 | DR |

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Type | Text | Yes |
| Code | Text | Yes |
| Normal Balance | Text (DR/CR) | Yes |

#### 5.2 Charts of Accounts

**Route:** `/finance/financial_settings/charts_of_accounts` (GET/POST)
**Page Title:** "Charts of Accounts"

Hierarchical chart of accounts supporting parent-child nesting, multiple currencies, and active/inactive status. Accounts are grouped by account type with visual indentation.

**Add Button:** "New Chart Account"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Code | Account code (e.g., 110500) |
| Account Name | Name of the account |
| Currency | Currency (TZS, USD) |
| Option | Edit / Delete actions |

Accounts are displayed with section headers for each account type (A=ASSETS through F=COST OF GOODS SOLD), with 27 accounts organized hierarchically.

**Modal Form Fields:**

| Field | Type | Required | Options |
|-------|------|----------|---------|
| Account Type | Dropdown | Yes | ASSETS(100000), LIABILITIES(200000), EQUITY AND RESERVE(300000), REVENUE(400000), EXPENSE(500000), COST OF GOODS SOLD(600000) |
| Parent Account | Dropdown | No | None (Top Level) + all existing accounts |
| Account Code | Text | Yes | Numeric code |
| Currency | Dropdown | Yes | TZS - Tanzanian Shillings, USD - United States Dollar |
| Account Name | Text | Yes | Account name |
| Status | Dropdown | Yes | Active, Inactive |

**Hierarchical Structure:** Accounts can nest to any depth. The parent dropdown shows all existing accounts, allowing multi-level nesting (e.g., ASSETS → Cash and Cash Equivalents → Petty Cash).

**AJAX Filtering:** `getChartAccountsByType($accountTypeId)` endpoint filters accounts by type for dynamic form updates.

#### 5.3 Charts of Account Usage

**Route:** `/finance/financial_settings/charts_of_account_usages` (GET/POST)
**Page Title:** "Charts of Account Usage"

Maps chart accounts to named usage contexts in the system. This allows the system to reference accounts by purpose (e.g., "BANK_TZS") rather than by code.

**Add Button:** "New Charts Account Usage"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Usage identifier (e.g., BANK_TZS, ACCOUNT_PAYABLE_TZS) |
| Charts Account | Linked chart account name |
| Description | Explanation of usage |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Pre-configured Usages:**

| Name | Charts Account | Description |
|------|---------------|-------------|
| BANK_TZS | Crdb Bank | Bank account for Local currency |
| ACCOUNT_PAYABLE_TZS | Account payable | Payable account for TZS |

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Name | Text | Yes |
| Chart of Account | Dropdown (all accounts) | Yes |
| Description | Textarea | No |

#### 5.4 Petty Cash Refill Request

**Route:** `/finance/petty_cash_management/petty_cash_refill_requests` (GET/POST)
**Page Title:** "Petty Cash Refill Requests"

Manages petty cash replenishment with approval workflow. The system automatically calculates the current balance and required refill amount based on a configurable limit.

**Filter Bar:** Start Date, End Date, "Show" button

**Add Button:** "New Petty Cash Refill Request"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Request date |
| Document Number | Auto-generated (PCRF/{year}/{id}) |
| Balance on Request | Petty cash balance at time of request |
| Refill Amount | Amount to refill |
| Requested User | Employee who made the request |
| Attachment | Uploaded file |
| Approvals | Approval chain status |
| Status | CREATED / PENDING / APPROVED |
| Actions | View / Edit / Delete |

**Export:** Print, Excel, PDF
**Footer Row:** Total refill amount

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Account | Dropdown (read-only) | Yes | "1105 : Petty cash" |
| Balance | Number (read-only) | Yes | Current petty cash balance (auto-calculated) |
| Refill Amount | Number (read-only) | Yes | Pre-filled from PETTY_CASH_LIMIT variable |
| Date | Date picker | Yes | Request date |
| File | File upload | No | Supporting document |

**Balance Calculation:**
```
Current Balance = Sum(approved refill requests) − Sum(approved imprest requests)
Refill Amount   = PETTY_CASH_LIMIT − Current Balance
```

The `PETTY_CASH_LIMIT` is set in Chart of Account Variables (currently 2,000,000 TZS). The Submit button only appears when refill_amount > 0.

**Auto-generated:** `document_number` format `PCRF/{year}/{id}`
**Approval:** RingleSoft workflow (document_type_id = 12)

#### 5.5 Imprest Request

**Route:** `/finance/imprest_management/imprest_requests` (GET/POST)
**Page Title:** "Imprest Requests"

Manages advance fund requests for field operations. Imprest amounts are drawn from the petty cash balance and must not exceed it.

**Filter Bar:** Start Date, End Date, "Show" button

**Add Button:** "New Imprest Request"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Request date |
| Document Number | Auto-generated (IMPT/{year}/{id}) |
| Description | Purpose of the advance |
| Amount | Requested amount |
| Requested User | Employee making request |
| Project | Linked project (optional) |
| Attachment | Uploaded file |
| Approvals | Approval chain status |
| Status | CREATED / PENDING / APPROVED |
| Actions | View / Edit / Delete |

**Export:** Print, Excel, PDF
**Footer Row:** Total amount

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Expenses Sub Category | Dropdown | Yes | SITE EXPENSES, Current Expense, Stationeries, Miscellaneous, Utilities, Communication, Transfer Charges, Maintenance Fee, Fixed Expenses, Interim Statement Charge |
| Project | Dropdown | Yes | Select project (default: General) |
| Description | Textarea | Yes | Purpose of the imprest |
| Balance | Number (read-only) | — | Current petty cash balance (auto-shown) |
| Amount | Number | Yes | Requested amount (validated: cannot exceed balance) |
| Date | Date picker | Yes | Request date |
| File | File upload | No | Supporting document |

**Validation:** Real-time client-side + server-side validation ensures the requested amount does not exceed the current petty cash balance.

**Auto-generated:** `document_number` format `IMPT/{year}/{id}`
**Approval:** RingleSoft workflow (document_type_id = 13)

#### 5.6 Exchange Rates

**Route:** `/finance/financial_settings/exchange_rates` (GET/POST)
**Page Title:** "Exchange Rates"

Tracks foreign-to-base currency exchange rates on a monthly basis for multi-currency financial operations.

**Add Button:** "New Exchange Rate"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Foreign Currency | Foreign currency name |
| Base Currency | Base currency name |
| Rate | Exchange rate |
| Month | Calendar month |
| Year | Calendar year |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Foreign Currency | Dropdown | Yes |
| Base Currency | Dropdown | Yes |
| Rate | Number | Yes |
| Month | Number (1–12) | Yes |
| Year | Number | Yes |

**Scopes:** `forPeriod($year, $month)`, `forCurrencies($foreignId, $baseId)` — for targeted lookups.

**Supported Currencies:** TZS (Tanzanian Shillings) as base, USD (United States Dollar) as foreign.

#### 5.7 Chart of Account Variables

**Route:** `/finance/financial_settings/chart_of_account_variables` (GET/POST)
**Page Title:** "Chart of Account Variables"

System configuration key-value store for finance-related settings.

**Add Button:** "New Chart Account Variable"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Variable | Configuration key name |
| Value | Configuration value |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Pre-configured Variables:**

| Variable | Value | Usage |
|----------|-------|-------|
| PETTY_CASH_LIMIT | 2000000 | Maximum petty cash balance — drives refill calculations |

**Modal Form Fields:**

| Field | Type | Required |
|-------|------|----------|
| Variable | Text | Yes |
| Value | Text | Yes |

#### 5.8 Expenses

**Route:** `/expenses` (GET/POST)
**Sidebar:** Standalone menu item (not under Finance submenu)
**Page Title:** "Expenses"

Company-wide expense tracking with category/sub-category classification and approval workflow.

**Filter Bar:**

| Field | Type | Options |
|-------|------|---------|
| Start Date | Date picker | Default: today |
| End Date | Date picker | Default: today |
| Category | Dropdown | All, ADMINISTRATION EXPENSES, FINANCIAL EXPENSES, DEPRECIATION EXPENSES |
| Sub Category | Dropdown | All, SITE EXPENSES, Current Expense, Stationeries, Miscellaneous, Utilities, Communication, Transfer Charges, Maintenance Fee, Fixed Expenses, Interim Statement Charge |
| Show | Button | Apply filters |

**Add Button:** "New Expenses"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Expense date |
| Expenses Sub Category | Sub-category name |
| Expenses Category | Parent category name |
| Description | Expense description |
| Amount | Expense amount |
| Attachment | Uploaded receipt/proof |
| Approvals | Approval chain status |
| Status | CREATED / PENDING / APPROVED |
| Actions | View (approval page) / Edit / Delete |

**Export:** Print, Excel, PDF
**Footer Row:** Total amount

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Expense Sub Category | Dropdown | Yes | From expenses_sub_categories table |
| Description | Textarea | Yes | Expense description |
| Amount | Number (formatted) | Yes | Shows formatted on display, raw number on focus |
| Date | Date picker | Yes | Expense date |
| File | File upload | No | Receipt/proof attachment |

**Auto-generated:** `document_number` format `EXPS/{id}/{year}`
**Approval:** RingleSoft workflow (document_type_id = 5)

**Expense Categories and Aggregation:**

| Category | Code | Used For |
|----------|------|----------|
| ADMINISTRATION EXPENSES | 1 | General admin costs |
| FINANCIAL EXPENSES | 2 | Bank charges, interest |
| DEPRECIATION EXPENSES | 3 | Asset depreciation |

Sub-categories have an `is_financial` flag — when YES, expenses are included in financial charge calculations separately from regular expenses.

**Key Calculation Methods:**
```
getTotalAdministrativeExpenses()  — Category ID 1 sum (approved only)
getTotalFinancialCharges()        — Category ID 2 sum
getTotalDepreciation()            — Category ID 3 sum
getTotalExpense()                 — All approved expenses
```

#### 5.9 Statutory Payments

**Route:** `/statutory_payments` (GET/POST)
**Sidebar:** Under "Statutory" submenu
**Page Title:** "Statutory Payments"

Records statutory obligation payments (rent, internet, communications, stamping fees, etc.) with control number tracking and approval workflow.

**Add Button:** "New Statutory Payment"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Issue date |
| Control Number | Government/authority control number |
| Statutory Payments | Sub-category name |
| Description | Payment description |
| Amount | Payment amount |
| Due Date | Payment deadline |
| Status | CREATED / PENDING / APPROVED |
| Actions | View (approval page) / Edit / Delete |

**Export:** Print, Excel, PDF

**Modal Form Fields:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| Issue Date | Date picker | Yes | Date of issue |
| Sub Category | Dropdown | Yes | rent, Internet, Communication, Stamping |
| Billing Cycle | Dropdown | No | One-time, Monthly, Quarterly, Semi-Annual, Annual |
| Amount | Number | Yes | Payment amount |
| Asset | Dropdown | No | Related asset |
| Asset Property | Dropdown | No | Related property |
| Description | Text | Yes | Payment description |
| Control Number | Number | No | Government reference number |
| Due Date | Date picker | Yes | Payment deadline |
| File | File upload | No | Payment proof |

**Approval:** RingleSoft workflow.

**Billing Cycle Tracking:** The system supports different billing cycles for recurring statutory obligations:
- `0` = One-time payment
- `1` = Monthly
- `3` = Quarterly
- `6` = Semi-Annual
- `12` = Annual

Monthly allocation for billing cycle payments is calculated by `StatutoryInvoicePayment::getPaidAmountByDate()`.

#### 5.10 Statutory Reports (3 reports)

**5.10.1 Statutory Category Report**

**Route:** `/reports/statutory_category_report`
**Filter:** Year dropdown (2019–2024)

Annual report showing statutory payments grouped by category across 12 months.

| Column | Description |
|--------|-------------|
| Date | Month (January–December) |
| Rent | Monthly rent total |
| Internet | Monthly internet total |
| Communication | Monthly communication total |
| Stamping | Monthly stamping total |
| Total | Row total |

**Footer Row:** Column totals for the year.

**5.10.2 Statutory Sub Category Report**

**Route:** `/reports/statutory_payment_report`
**Filter:** Year dropdown

Same structure as Category Report but at the sub-category breakdown level.

**5.10.3 Statutory Schedules Report**

**Route:** `/reports/statutory_schedules_report`
**Filter:** Year dropdown (2019–2025)

The most detailed statutory report — a wide matrix (19 columns) showing:

| Column | Description |
|--------|-------------|
| No | Row number |
| Statutory | Category name |
| Sub Category | Sub-category name |
| Per Annually | Annual obligation amount |
| Per Monthly | Monthly obligation amount |
| Per Bill | Per-bill amount |
| Billing Cycle | Payment frequency |
| January–December | Monthly actual payments (12 columns) |
| Total | Annual total |

This report requires horizontal scrolling due to the wide column layout.

#### 5.11 Finance Data Model

```
AccountType (6 types: Assets, Liabilities, Equity, Revenue, Expense, COGS)
  └── HasMany → ChartAccount (hierarchical, parent-child nesting)
       ├── BelongsTo → Currency (TZS, USD)
       └── HasMany → ChartAccountUsage (maps to system contexts)

ChartAccountVariable (key-value config, e.g., PETTY_CASH_LIMIT=2000000)

PettyCashRefillRequest ── Approval workflow (type 12)
  └── Balance = Sum(approved refills) − Sum(approved imprests)

ImprestRequest ── Approval workflow (type 13)
  ├── BelongsTo → ExpensesSubCategory
  └── BelongsTo → Project (optional)
  └── Validation: amount ≤ current petty cash balance

ExchangeRate
  ├── BelongsTo → Currency (foreign)
  └── BelongsTo → Currency (base)
  └── Tracked by month/year

Expense ── Approval workflow (type 5)
  ├── BelongsTo → ExpensesCategory (Admin, Financial, Depreciation)
  └── BelongsTo → ExpensesSubCategory (is_financial flag)

StatutoryPayment ── Approval workflow
  └── BelongsTo → SubCategory → Category
       └── HasMany → StatutoryInvoicePayment (billing cycle aware)
```

#### 5.12 Permissions Summary

| Permission | Controls |
|------------|----------|
| `Add Expenses` | Create new expenses |
| `Edit Expenses` | Modify expenses |
| `Delete Expenses` | Remove expenses |
| `Approve Expenses` | Approve expense workflow |
| `Add Petty Cash Refill Request` | Create refill requests |
| `Approve Petty Cash Refill Request` | Approve refill workflow |
| `Add Imprest Request` | Create imprest requests |
| `Approve Imprest Request` | Approve imprest workflow |
| `Add Statutory Payment` | Create statutory payments |
| `Approve Statutory Payment` | Approve payment workflow |

#### 5.13 Key Calculations

**Petty Cash Balance:**
```
balance = Sum(approved refill amounts from beginning) − Sum(approved imprest amounts from beginning)
refill_needed = PETTY_CASH_LIMIT − balance
```

**Expense Aggregation (approved only):**
```
Total Administrative = Sum(expenses WHERE category_id = 1 AND status = 'APPROVED')
Total Financial      = Sum(expenses WHERE category_id = 2 AND status = 'APPROVED')
Total Depreciation   = Sum(expenses WHERE category_id = 3 AND status = 'APPROVED')
```

**Statutory Monthly Allocation (for billing cycles):**
```
If billing_cycle = 0 (one-time): full amount in issue month
If billing_cycle = 1 (monthly): amount / 1 per month
If billing_cycle = 3 (quarterly): amount / 3 per month for 3 months
If billing_cycle = 6 (semi-annual): amount / 6 per month for 6 months
If billing_cycle = 12 (annual): amount / 12 per month for 12 months
```

---

### 6. BOQ Templates

Reusable Bill of Quantities template system for standardizing cost estimation across construction projects. This module provides the master reference data (building types, item categories, construction stages, activities, sub-activities, BOQ items) and a visual template builder that assembles these into reusable BOQ structures. Templates can be applied when creating new project BOQs to pre-populate sections and items.

**Sidebar Menu Items:**

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Building Types | `/settings/building_types` | Building Types |
| 2 | BOQ Item Categories | `/settings/boq_item_categories` | Boq Item Categories |
| 3 | Construction Stages | `/settings/construction_stages` | Construction Stages |
| 4 | Activities | `/settings/activities` | Activities |
| 5 | Sub-Activities | `/settings/sub_activities` | Sub Activities |
| 6 | BOQ Items | `/settings/boq_items` | Boq Items |
| 7 | BOQ Templates Design | `/settings/boq_templates` | Boq Templates |

**Data Model Hierarchy:**

```
BuildingType (self-referential parent_id)
├── BoqItemCategory (self-referential parent_id)
├── ConstructionStage (self-referential parent_id)
│   └── Activity (belongs to ConstructionStage)
│       └── SubActivity (belongs to Activity)
│           └── SubActivityMaterial (belongs to SubActivity + BoqItem)
├── BoqItem (belongs to BoqItemCategory)
│   ├── item_type: material | labour
│   └── unit, rate, description
└── BoqTemplate (belongs to BuildingType)
    └── BoqTemplateStage (belongs to ConstructionStage)
        └── BoqTemplateActivity (belongs to Activity)
            └── BoqTemplateSubActivity (belongs to SubActivity)
```

**Two Template Systems:**

1. **Settings-level `BoqTemplate`** — The template builder in this module. Defines stages → activities → sub-activities hierarchy per building type. Used as blueprints.
2. **Project-level `ProjectBoqTemplate`** — Cloned from actual project BOQs with items. Used to replicate a real BOQ onto other projects. Created via "Save as Template" on a project BOQ page.

**Key Integrations:**
- Templates applied to new project BOQs to pre-populate sections and items
- Sub-activity materials link BOQ items to activities for material requirements
- Activity templates (`ProjectActivityTemplate`) link activities to project types for scheduling
- BOQ items referenced throughout procurement, material requests, and cost tracking

#### 6.1 Building Types

**Route:** `/settings/building_types` (GET/POST)
**Page Title:** "Building Types"
**Controller:** `SettingsController@building_types`

Hierarchical classification of building/construction types. Supports parent-child nesting for sub-types (e.g., "Residential" → "Apartments", "Villas").

**Add Button:** "New Building Type"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Building type name (e.g., Residential, Commercial, Industrial) |
| Parent | Parent type (if sub-type), blank for top-level |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Sample Data:**

| Name | Parent |
|------|--------|
| Office Building | — |
| Residential House | — |
| Warehouse | — |
| Hospital | — |
| School | — |

**Modal Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Name | Text | Yes | Building type name |
| Parent | Dropdown | No | Select parent type for nesting; "None" for top-level |

**Model:** `BuildingType` — Self-referential via `parent_id`. Relationships: `parent()`, `children()`, `templates()`.

#### 6.2 BOQ Item Categories

**Route:** `/settings/boq_item_categories` (GET/POST)
**Page Title:** "BOQ Item Categories"
**Controller:** `SettingsController@boq_item_categories`

Hierarchical classification system for BOQ items. Categories support unlimited parent-child nesting for detailed material/labour categorization.

**Add Button:** "New Boq Item Category"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Category name |
| Parent | Parent category (blank for top-level) |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Sample Data (12 categories):**

| Name | Parent |
|------|--------|
| Concrete Works | — |
| Electrical Works | — |
| Finishing Works | — |
| Foundation Works | — |
| General | — |
| Landscaping | — |
| Masonry Works | — |
| Mechanical Works | — |
| Plumbing Works | — |
| Roofing Works | — |
| Steel Works | — |
| Woodwork | — |

**Modal Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Name | Text | Yes | Category name |
| Parent | Dropdown | No | Parent category for nesting |

**Model:** `BoqItemCategory` — Self-referential via `parent_id`. Relationships: `parent()`, `children()`, `boqItems()`.

#### 6.3 Construction Stages

**Route:** `/settings/construction_stages` (GET/POST)
**Page Title:** "Construction Stages"
**Controller:** `SettingsController@construction_stages`

Defines the major phases of a construction project. Stages are ordered sequentially and can be nested. Each stage groups related activities.

**Add Button:** "New Construction Stage"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Stage name |
| Parent | Parent stage (if sub-stage) |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Sample Data (7 stages):**

| Name | Parent |
|------|--------|
| Finishing | — |
| Foundation | — |
| Landscaping | — |
| MEP (Mechanical, Electrical, Plumbing) | — |
| Roofing | — |
| Substructure | — |
| Superstructure | — |

**Modal Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Name | Text | Yes | Stage name |
| Parent | Dropdown | No | Parent stage for nesting |

**Model:** `ConstructionStage` — Self-referential via `parent_id`. Relationships: `parent()`, `children()`, `activities()`.

#### 6.4 Activities

**Route:** `/settings/activities` (GET/POST)
**Page Title:** "Activities"
**Controller:** `SettingsController@activities`

Construction activities grouped under stages. Activities represent the work items performed during each construction stage.

**Add Button:** "New Activity"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Activity name |
| Construction Stage | Parent stage this activity belongs to |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Sample Data (10 activities):**

| Name | Construction Stage |
|------|-------------------|
| Brickwork | Superstructure |
| Column Construction | Superstructure |
| Concrete Pouring | Foundation |
| Electrical Wiring | MEP |
| Excavation | Foundation |
| Floor Tiling | Finishing |
| Painting | Finishing |
| Plastering | Finishing |
| Plumbing Installation | MEP |
| Roofing Installation | Roofing |

**Modal Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Name | Text | Yes | Activity name |
| Construction Stage | Dropdown | Yes | Links activity to a stage |

**Model:** `Activity` — Belongs to `ConstructionStage`. Relationships: `constructionStage()`, `subActivities()`.

#### 6.5 Sub-Activities

**Route:** `/settings/sub_activities` (GET/POST)
**Page Title:** "Sub-Activities"
**Controller:** `SettingsController@sub_activities`

Granular breakdown of activities into specific tasks. Each sub-activity belongs to a parent activity and can have associated materials (BOQ items) linked via `SubActivityMaterial`.

**Add Button:** "New Sub Activity"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Sub-activity name |
| Activity | Parent activity |
| Stage | Construction stage (via activity) |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Sample Data (17 entries):**

| Name | Activity | Stage |
|------|----------|-------|
| Backfilling | Excavation | Foundation |
| Beam Formwork | Column Construction | Superstructure |
| Block Laying | Brickwork | Superstructure |
| Ceiling Painting | Painting | Finishing |
| Ceramic Tile Laying | Floor Tiling | Finishing |
| Column Formwork | Column Construction | Superstructure |
| Conduit Installation | Electrical Wiring | MEP |
| External Painting | Painting | Finishing |
| Foundation Concrete | Concrete Pouring | Foundation |
| Internal Plastering | Plastering | Finishing |
| Mortar Mixing | Brickwork | Superstructure |
| Pipe Fitting | Plumbing Installation | MEP |
| Rebar Placement | Concrete Pouring | Foundation |
| Slab Concrete | Concrete Pouring | Foundation |
| Tile Grouting | Floor Tiling | Finishing |
| Trench Digging | Excavation | Foundation |
| Wire Pulling | Electrical Wiring | MEP |

**Modal Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Name | Text | Yes | Sub-activity name |
| Activity | Dropdown | Yes | Parent activity |

**Model:** `SubActivity` — Belongs to `Activity`. Relationships: `activity()`, `materials()` (via `SubActivityMaterial`).

**`SubActivityMaterial` pivot model** — Links sub-activities to BOQ items with quantity. Fields: `sub_activity_id`, `boq_item_id`, `quantity`. This defines the default materials needed for a sub-activity.

#### 6.6 BOQ Items

**Route:** `/settings/boq_items` (GET/POST)
**Page Title:** "BOQ Items"
**Controller:** `SettingsController@boq_items`

Master catalog of all material and labour items that can be used in BOQs. Each item has a unit, rate, item type, and category.

**Add Button:** "New BOQ Item"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Item name (e.g., "Portland Cement 50kg", "Mason - Daily Rate") |
| Category | BOQ item category |
| Item Type | Material or Labour |
| Unit | Unit of measurement (e.g., bag, kg, m², day) |
| Rate | Unit rate in TZS |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Sample Data (20 items):**

| Name | Category | Type | Unit | Rate (TZS) |
|------|----------|------|------|-------------|
| Aggregate (Coarse) | Concrete Works | Material | m³ | 45,000 |
| Carpenter - Daily Rate | Woodwork | Labour | day | 25,000 |
| Cement Bags (50kg) | Concrete Works | Material | bag | 18,000 |
| Ceramic Tiles (30x30) | Finishing Works | Material | m² | 15,000 |
| Concrete Blocks (6 inch) | Masonry Works | Material | piece | 1,200 |
| Electrical Cable (2.5mm) | Electrical Works | Material | m | 3,500 |
| Electrician - Daily Rate | Electrical Works | Labour | day | 30,000 |
| Mason - Daily Rate | Masonry Works | Labour | day | 25,000 |
| Mild Steel Bars (12mm) | Steel Works | Material | kg | 2,800 |
| PVC Pipes (4 inch) | Plumbing Works | Material | m | 8,500 |
| Painter - Daily Rate | Finishing Works | Labour | day | 20,000 |
| Plumber - Daily Rate | Plumbing Works | Labour | day | 28,000 |
| Plywood Sheets (18mm) | Woodwork | Material | sheet | 85,000 |
| Porcelain Tiles (60x60) | Finishing Works | Material | m² | 35,000 |
| Ready Mix Concrete | Concrete Works | Material | m³ | 250,000 |
| River Sand | Concrete Works | Material | m³ | 35,000 |
| Roofing Nails | Roofing Works | Material | kg | 5,000 |
| Roofing Sheets (Gauge 28) | Roofing Works | Material | sheet | 25,000 |
| Wall Paint (20L) | Finishing Works | Material | bucket | 120,000 |
| Waterproofing Membrane | Foundation Works | Material | m² | 18,000 |

**Modal Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Name | Text | Yes | Item name |
| Category | Dropdown | Yes | BOQ item category |
| Item Type | Dropdown | Yes | Material / Labour |
| Unit | Text | Yes | Unit of measurement |
| Rate | Number | Yes | Unit rate (TZS) |
| Description | Textarea | No | Additional details |

**Model:** `BoqItem` — Belongs to `BoqItemCategory`. Relationships: `category()`, `subActivityMaterials()`. Fields: `name`, `boq_item_category_id`, `item_type` (material/labour), `unit`, `rate`, `description`.

#### 6.7 BOQ Templates Design

**Route:** `/settings/boq_templates` (GET/POST)
**Page Title:** "BOQ Templates"
**Controller:** `SettingsController@boq_templates`

Visual template builder that assembles construction stages, activities, and sub-activities into reusable BOQ template structures. Each template is associated with a building type.

**Add Button:** "Create New Template"

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Template Name | Name of the template |
| Building Type | Associated building type |
| Stages | Number of stages in the template |
| Activities | Total activity count |
| Sub-Activities | Total sub-activity count |
| Actions | Edit / Delete |

**Export:** Print, Excel, PDF

**Sample Data (3 templates):**

| Template Name | Building Type | Stages | Activities | Sub-Activities |
|---------------|--------------|--------|------------|----------------|
| Hospital Building Template | Hospital | 3 | 4 | 6 |
| Office Building Standard | Office Building | 4 | 6 | 10 |
| Residential House Standard | Residential House | 5 | 8 | 14 |

**Create/Edit Template Modal Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Template Name | Text | Yes | Descriptive name |
| Building Type | Dropdown | Yes | Links to building type |
| Description | Textarea | No | Template description |

**Template Builder Interface:**

After creating a template, the user enters a **visual builder page** where they assemble the template structure:

1. **Add Stage** — Dropdown to select from `ConstructionStage` list. Creates a `BoqTemplateStage` record.
2. **Add Activity** — Under each stage, dropdown to select from `Activity` records filtered by the selected stage. Creates a `BoqTemplateActivity` record.
3. **Add Sub-Activity** — Under each activity, dropdown to select from `SubActivity` records filtered by the selected activity. Creates a `BoqTemplateSubActivity` record.

**Builder Actions:**
- **Add**: Select stage/activity/sub-activity from dropdown and click add button
- **Remove**: Delete a stage (cascades to remove its activities and sub-activities), activity (cascades sub-activities), or individual sub-activity

**Template Structure Display:**

```
Template: "Residential House Standard"
├── Foundation (stage)
│   ├── Excavation (activity)
│   │   ├── Trench Digging (sub-activity)
│   │   └── Backfilling (sub-activity)
│   └── Concrete Pouring (activity)
│       ├── Rebar Placement (sub-activity)
│       ├── Foundation Concrete (sub-activity)
│       └── Slab Concrete (sub-activity)
├── Superstructure (stage)
│   ├── Column Construction (activity)
│   │   ├── Column Formwork (sub-activity)
│   │   └── Beam Formwork (sub-activity)
│   └── Brickwork (activity)
│       ├── Block Laying (sub-activity)
│       └── Mortar Mixing (sub-activity)
├── Roofing (stage)
│   └── Roofing Installation (activity)
├── MEP (stage)
│   ├── Electrical Wiring (activity)
│   │   ├── Conduit Installation (sub-activity)
│   │   └── Wire Pulling (sub-activity)
│   └── Plumbing Installation (activity)
│       └── Pipe Fitting (sub-activity)
└── Finishing (stage)
    ├── Plastering (activity)
    │   └── Internal Plastering (sub-activity)
    ├── Floor Tiling (activity)
    │   ├── Ceramic Tile Laying (sub-activity)
    │   └── Tile Grouting (sub-activity)
    └── Painting (activity)
        ├── Ceiling Painting (sub-activity)
        └── External Painting (sub-activity)
```

**Models:**
- `BoqTemplate` — Main template record. Fields: `name`, `building_type_id`, `description`. Relationships: `buildingType()`, `stages()`.
- `BoqTemplateStage` — Links template to stage. Fields: `boq_template_id`, `construction_stage_id`. Relationships: `template()`, `constructionStage()`, `activities()`.
- `BoqTemplateActivity` — Links template stage to activity. Fields: `boq_template_stage_id`, `activity_id`. Relationships: `templateStage()`, `activity()`, `subActivities()`.
- `BoqTemplateSubActivity` — Links template activity to sub-activity. Fields: `boq_template_activity_id`, `sub_activity_id`. Relationships: `templateActivity()`, `subActivity()`.

**Project-Level Template System:**

Separate from the settings-level template builder, `ProjectBoqTemplate` records are created from actual project BOQs:

- **Save as Template**: On a project's BOQ page, user can save the current BOQ as a reusable template
- **Apply Template**: When creating a new project BOQ, user can select a saved project template to pre-populate items
- Model: `ProjectBoqTemplate` has `project_id`, `name`, and related `ProjectBoqTemplateItem` records with `ProjectBoqTemplateSection` for hierarchy

**Permissions:**

| Permission | Description |
|------------|-------------|
| Building Types | View/manage building types page |
| Boq Item Categories | View/manage BOQ item categories page |
| Construction Stages | View/manage construction stages page |
| Activities | View/manage activities page |
| Sub Activities | View/manage sub-activities page |
| Boq Items | View/manage BOQ items page |
| Boq Templates | View/manage BOQ templates + builder |

---

### 7. Employee Management — HR Operations

Comprehensive HR operations module covering payroll processing, leave management, attendance tracking, salary/allowance/deduction configuration, advance salary, loans, and bank file generation. This section covers the **operational HR features** under the Employee Management sidebar (the employee profile/staff list is covered in Section 2).

**Sidebar Menu Items (15):**
| # | Menu Item | Route | Description |
|---|-----------|-------|-------------|
| 1 | Staff Bank Details | `/staff_bank_details` | Employee bank account information for payroll |
| 2 | Payroll Administration | `/payroll_administration` | Monthly payroll runs with approval workflow |
| 3 | Deductions | `/deductions` | Statutory deduction types (PAYE, NSSF, etc.) |
| 4 | Deduction Subscriptions | `/deduction_subscriptions` | Staff-level deduction enrollment |
| 5 | Salary Slips | `/salary_slips` | Individual payslip generation and viewing |
| 6 | Employee Allowances | `/employee_allowances` | Allowance type configuration and assignment |
| 7 | Advance Salary | `/advance_salary` | Salary advance requests with approval |
| 8 | Salaries & Salary Arrears | `/salaries` | Base salary records and arrears management |
| 9 | Loan Details | `/loan_details` | Employee loan tracking with installment schedules |
| 10 | Leave Dashboard | `/leave_dashboard` | Leave balance overview with type-based cards |
| 11 | Leave Request | `/leave_request` | Personal leave application form |
| 12 | Leave Managements | `/leave_managements` | Admin view of all leave requests |
| 13 | Leave Types | `/leave_types` | Leave category configuration |
| 14 | CRDB Bank File | `/crdb_bank_file` | Bank payment file generation for CRDB |
| 15 | Attendance Types | `/attendance_types` | Attendance category configuration |

#### 7.1 Staff Bank Details

Employee bank account management for payroll disbursement.

**Page:** DataTable listing all staff with bank details.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Staff Name | Employee full name |
| Bank Name | Financial institution |
| Account Number | Bank account number |
| Branch | Bank branch |
| Actions | Edit/Delete |

**Form Fields:**
| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Staff | Dropdown | Yes | Select from employees |
| Bank | Dropdown | Yes | Bank selection |
| Account Number | Text | Yes | Bank account number |
| Branch | Text | No | Branch name |

**Known Issue:** Page throws "Attempt to read property 'name' on null" error — likely a staff record with a deleted/null bank relationship.

#### 7.2 Payroll Administration

Central payroll processing hub. Manages monthly payroll creation, calculation, preview, and approval.

**Page:** DataTable showing payroll runs with status tracking.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Month | Payroll month (e.g., "January 2026") |
| Year | Payroll year |
| Total Gross | Sum of all gross pay |
| Total Net | Sum of all net pay |
| Status | PENDING / APPROVED |
| Approval | RingleSoft approval status badge |
| Actions | View/Edit/Delete |

**Payroll Creation Process:**
1. Admin clicks "Create Payroll" → `PayrollController@create_payroll`
2. System generates payroll for ALL active staff for the selected month/year
3. For each staff member, the system creates 10+ component records:
   - `PayrollSalary` — base salary amount
   - `PayrollAllowance` — per allowance type (DAILY or MONTHLY)
   - `PayrollDeduction` — per deduction subscription (PAYE, NSSF, WCF, HESLB, SDL, NHIF)
   - `PayrollTaxable` — taxable income calculation
   - `PayrollGrossPay` — total gross pay
   - `PayrollNetSalary` — final net pay
   - `PayrollAdvanceSalary` — advance salary deductions
   - `PayrollLoan` — active loan references
   - `PayrollLoanDeduction` — loan installment deductions
   - `PayrollLoanBalance` — remaining loan balance after deduction
   - `PayrollAdjustment` — manual adjustments

**Payroll Formula:**
```
Gross Pay = Base Salary + Sum(Allowances)
Taxable Income = Gross Pay - NSSF Employee Contribution
PAYE = TRA tax bracket calculation on Taxable Income
Net Pay = Gross Pay - PAYE - NSSF - WCF - HESLB - SDL - NHIF - Advance Salary - Loan Installments + Adjustments
```

**Statutory Deduction Types (Hardcoded IDs):**
| ID | Deduction | Description |
|----|-----------|-------------|
| 1 | PAYE | Pay As You Earn (income tax) |
| 2 | NSSF | National Social Security Fund |
| 3 | WCF | Workers Compensation Fund |
| 4 | HESLB | Higher Education Student Loans Board |
| 5 | SDL | Skills Development Levy |
| 6 | NHIF | National Health Insurance Fund |

**Monthly Workdays Calculation:** Uses `ProjectHoliday` model to exclude holidays and weekends. Formula: total days in month - weekends - holidays = working days. Used for daily allowance proration.

**Approval:** RingleSoft `Approvable` trait on `Payroll` model. Once approved, payroll is finalized and cannot be edited.

#### 7.3 Deductions

Configuration page for statutory and custom deduction types.

**Page:** DataTable listing all deduction types.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Deduction name (e.g., PAYE, NSSF) |
| Description | Details about the deduction |
| Type | Statutory or Custom |
| Actions | Edit/Delete |

**Form Fields:**
| Field | Type | Required |
|-------|------|----------|
| Name | Text | Yes |
| Description | Textarea | No |
| Type | Dropdown | Yes |

#### 7.4 Deduction Subscriptions

Staff-level enrollment in deduction types. Links individual employees to specific deductions with custom amounts or percentages.

**Page:** DataTable showing staff-deduction mappings.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Staff Name | Employee name |
| Deduction | Deduction type name |
| Amount/Rate | Fixed amount or percentage |
| Actions | Edit/Delete |

#### 7.5 Salary Slips

Individual payslip viewing and generation for processed payroll months.

**Page:** Payslip list with filters for month/year and staff selection.

**Known Issue:** Page throws "Trying to access array offset on null" error — likely when no payroll has been processed for the selected period.

**Payslip Content (when working):**
- Employee details (name, position, department)
- Earnings breakdown (base salary + each allowance)
- Deductions breakdown (each statutory deduction + advance + loan)
- Gross pay, total deductions, net pay
- PDF download capability

#### 7.6 Employee Allowances

Allowance type configuration and staff-level assignment.

**Page:** DataTable listing allowance assignments per staff.

**Allowance Types:**
| Type | Calculation | Description |
|------|-------------|-------------|
| DAILY | Amount × Working Days | Prorated based on monthly workdays |
| MONTHLY | Fixed Amount | Same amount every month |

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Staff Name | Employee name |
| Allowance Type | Name of the allowance |
| Amount | Monthly or daily rate |
| Frequency | DAILY or MONTHLY |
| Actions | Edit/Delete |

#### 7.7 Advance Salary

Salary advance request management with approval workflow.

**Page:** DataTable listing advance salary requests.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Staff Name | Employee requesting advance |
| Amount | Requested advance amount |
| Month | Month for deduction |
| Reason | Justification text |
| Status | PENDING / APPROVED / REJECTED |
| Approval | RingleSoft approval badge |
| Actions | View/Edit/Delete |

**Form Fields:**
| Field | Type | Required |
|-------|------|----------|
| Staff | Dropdown | Yes |
| Amount | Number | Yes |
| Month | Date picker | Yes |
| Reason | Textarea | Yes |

**Approval:** RingleSoft `Approvable` trait on `AdvanceSalary` model. Approved advances are automatically deducted in the next payroll run via `PayrollAdvanceSalary` component.

#### 7.8 Salaries & Salary Arrears

Base salary records and arrears management for employees.

**Page:** DataTable listing staff salary records.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Staff Name | Employee name |
| Basic Salary | Monthly base salary (TZS) |
| Effective Date | Date salary takes effect |
| Actions | Edit/Delete |

**Salary Arrears:** When a salary is updated retroactively, the system calculates the difference for previous months and creates arrears entries that are included in the next payroll.

#### 7.9 Loan Details

Employee loan tracking with installment schedules and payroll integration.

**Page:** DataTable listing all employee loans.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Staff Name | Employee name |
| Loan Amount | Total loan principal |
| Monthly Installment | Deduction per month |
| Balance | Remaining loan balance |
| Start Date | Loan start date |
| Status | Active / Completed |
| Approval | RingleSoft approval badge |
| Actions | View/Edit/Delete |

**Form Fields:**
| Field | Type | Required |
|-------|------|----------|
| Staff | Dropdown | Yes |
| Loan Amount | Number | Yes |
| Monthly Installment | Number | Yes |
| Start Date | Date | Yes |
| Notes | Textarea | No |

**Approval:** RingleSoft `Approvable` trait on `Loan` model. Once approved, loan deductions are automatically applied each payroll cycle via `PayrollLoanDeduction`. The `PayrollLoanBalance` tracks the decreasing balance month over month.

#### 7.10 Leave Dashboard

Visual overview of leave balances for the currently logged-in user.

**Page:** Card-based layout showing leave balance per leave type.

**Dashboard Cards (per leave type):**
| Element | Description |
|---------|-------------|
| Leave Type Name | e.g., Annual Leave, Sick Leave |
| Total Entitlement | Days allocated per year |
| Used | Days taken this year |
| Remaining | Days still available |
| Color indicator | Green (>50%), Yellow (25-50%), Red (<25%) |

**Leave Types (configurable):** Annual Leave, Sick Leave, Maternity Leave, Paternity Leave, Compassionate Leave, Study Leave (defaults — can be customized in Leave Types settings).

#### 7.11 Leave Request

Personal leave application form for the logged-in employee.

**Page:** DataTable of user's own leave requests + "New Request" button.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Leave Type | Category of leave |
| Start Date | Leave start date |
| End Date | Leave end date |
| Days | Total leave days |
| Reason | Justification |
| Status | PENDING / APPROVED / REJECTED |
| Approval | RingleSoft approval badge |
| Actions | View/Edit/Delete (only for PENDING) |

**Form Fields:**
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| Leave Type | Dropdown | Yes | Must have sufficient balance |
| Start Date | Date | Yes | Cannot be in the past |
| End Date | Date | Yes | Must be after start date |
| Reason | Textarea | Yes | Min length enforced |

**Validation Rules:**
- Cannot exceed remaining leave balance for the selected type
- Overlap prevention — cannot request leave for dates already covered by another request
- Notice days enforcement — some leave types require advance notice (configurable per type)
- Weekend/holiday exclusion from day count calculation

**Approval:** RingleSoft `Approvable` trait on `LeaveRequest` model. `onApprovalCompleted()` updates leave balance for the staff member.

#### 7.12 Leave Managements

Admin view for managing all employee leave requests across the organization.

**Page:** DataTable showing all leave requests with filtering capabilities.

**Filters:**
| Filter | Type | Description |
|--------|------|-------------|
| Staff | Dropdown | Filter by employee |
| Leave Type | Dropdown | Filter by leave category |
| Status | Dropdown | PENDING / APPROVED / REJECTED |
| Date Range | Date picker | Filter by request date |

**Table Columns:** Same as Leave Request (7.11) plus Staff Name column.

**Admin Actions:**
- View request details
- Process approval (via RingleSoft workflow)
- Override/cancel approved leave (with reason)

#### 7.13 Leave Types

Configuration page for leave categories.

**Page:** DataTable listing all leave types.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Leave type name |
| Days Per Year | Annual entitlement |
| Notice Days | Advance notice required |
| Is Active | Enabled/disabled toggle |
| Actions | Edit/Delete |

**Form Fields:**
| Field | Type | Required |
|-------|------|----------|
| Name | Text | Yes |
| Days Per Year | Number | Yes |
| Notice Days | Number | No |
| Is Active | Checkbox | No |

#### 7.14 CRDB Bank File

Generates bank payment files for CRDB (CRDB Bank Tanzania) for bulk salary disbursement.

**Page:** Filter form for month/year selection + "Generate" button.

**Process:**
1. Admin selects payroll month/year
2. System queries all approved payroll records for that period
3. Generates CRDB-formatted payment file with columns: staff name, account number, bank branch, net pay amount
4. File downloads as CSV/text for upload to CRDB business banking portal

**Controller Method:** `PayrollController@crdb_bank_file` — queries `PayrollNetSalary` records joined with `StaffBankDetail` for the selected period.

#### 7.15 Attendance Types

Configuration page for attendance tracking categories.

**Page:** DataTable listing attendance type definitions.

**Table Columns:**
| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Attendance type name |
| Description | Type description |
| Actions | Edit/Delete |

#### 7.16 Attendance System (Backend)

While the attendance UI is primarily accessed via the mobile API (Section 2), the backend system supports both web and mobile check-in/out.

**Core Configuration (SystemSetting keys):**
| Setting Key | Default | Description |
|-------------|---------|-------------|
| ATTENDANCE_LATE_THRESHOLD | 15 | Minutes after shift start to mark as "Late" |
| ATTENDANCE_EARLY_THRESHOLD | 30 | Minutes before shift end to flag early departure |

**Business Rules:**
- **3-hour duplicate prevention:** Cannot check in again within 3 hours of last check-in
- **Timezone adjustment:** +3 hours added to server time (East Africa Time)
- **Biometric integration:** `Attendance` model supports device-based check-in via `device_id` field
- **GPS tracking:** Latitude/longitude captured on mobile check-in for location verification

**Attendance Model Fields:**
| Field | Type | Description |
|-------|------|-------------|
| staff_id | FK | Employee reference |
| check_in | DateTime | Check-in timestamp |
| check_out | DateTime | Check-out timestamp (nullable) |
| check_in_latitude | Decimal | GPS lat on check-in |
| check_in_longitude | Decimal | GPS lng on check-in |
| check_out_latitude | Decimal | GPS lat on check-out |
| check_out_longitude | Decimal | GPS lng on check-out |
| device_id | String | Biometric device identifier |
| status | String | Present / Late / Absent |
| project_id | FK | Project site (nullable) |

#### 7.17 Data Model

**Primary Tables:**
| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `payrolls` | Monthly payroll runs | id, month, year, status, total_gross, total_net |
| `payroll_salaries` | Per-staff base salary in payroll | payroll_id, staff_id, amount |
| `payroll_allowances` | Per-staff allowances | payroll_id, staff_id, allowance_type_id, amount |
| `payroll_deductions` | Per-staff deductions | payroll_id, staff_id, deduction_id, amount |
| `payroll_taxables` | Taxable income calculation | payroll_id, staff_id, amount |
| `payroll_gross_pays` | Gross pay totals | payroll_id, staff_id, amount |
| `payroll_net_salaries` | Net pay totals | payroll_id, staff_id, amount |
| `payroll_advance_salaries` | Advance deductions | payroll_id, staff_id, advance_salary_id, amount |
| `payroll_loans` | Loan references in payroll | payroll_id, staff_id, loan_id |
| `payroll_loan_deductions` | Loan installment deductions | payroll_id, staff_id, loan_id, amount |
| `payroll_loan_balances` | Loan balance tracking | payroll_id, staff_id, loan_id, balance |
| `payroll_adjustments` | Manual adjustments | payroll_id, staff_id, amount, description |
| `advance_salaries` | Advance salary requests | staff_id, amount, month, reason, status |
| `loans` | Employee loans | staff_id, amount, monthly_installment, balance, status |
| `leave_requests` | Leave applications | staff_id, leave_type_id, start_date, end_date, days, reason, status |
| `leave_types` | Leave categories | name, days_per_year, notice_days, is_active |
| `attendances` | Check-in/out records | staff_id, check_in, check_out, lat/lng, device_id, status |
| `attendance_types` | Attendance categories | name, description |
| `staff_bank_details` | Bank account info | staff_id, bank_name, account_number, branch |
| `salaries` | Base salary records | staff_id, basic_salary, effective_date |
| `deductions` | Deduction type definitions | name, description, type |
| `deduction_subscriptions` | Staff-deduction mappings | staff_id, deduction_id, amount |
| `employee_allowances` | Allowance assignments | staff_id, allowance_type_id, amount, frequency |
| `project_holidays` | Holiday calendar | name, date, is_recurring |

#### 7.18 Approval Workflows

| Model | Trait | Approval Flow | Post-Approval Action |
|-------|-------|---------------|---------------------|
| `Payroll` | RingleSoft `Approvable` | Multi-step (configurable) | Finalizes payroll, locks editing |
| `LeaveRequest` | RingleSoft `Approvable` | Multi-step (configurable) | Deducts leave balance |
| `AdvanceSalary` | RingleSoft `Approvable` | Multi-step (configurable) | Queues for next payroll deduction |
| `Loan` | RingleSoft `Approvable` | Multi-step (configurable) | Activates loan, starts installments |

#### 7.19 Permissions

| Permission | Description |
|------------|-------------|
| Staff Bank Details | View staff bank details page |
| Payroll Administration | View/manage payroll runs |
| Deductions | View/manage deduction types |
| Deduction Subscriptions | View/manage staff deduction enrollment |
| Salary Slips | View/generate salary slips |
| Employee Allowances | View/manage allowance assignments |
| Advance Salary | View/manage advance salary requests |
| Salaries & Salary Arrears | View/manage salary records |
| Loan Details | View/manage employee loans |
| Leave Dashboard | View leave balance dashboard |
| Leave Request | Submit/view personal leave requests |
| Leave Managements | Admin management of all leave requests |
| Leave Types | Configure leave categories |
| CRDB Bank File | Generate bank payment files |
| Attendance Types | Configure attendance categories |
| Add [Entity] | Create permission per entity |
| Edit [Entity] | Edit permission per entity |
| Delete [Entity] | Delete permission per entity |
| Approve [Entity] | Approval permission for Payroll, LeaveRequest, AdvanceSalary, Loan |

---

### 8. Billing

Professional invoicing and billing system built on a **polymorphic `BillingDocument` model** — all document types (quotations, proformas, invoices, credit notes) share a single table distinguished by `document_type`. Supports the full document lifecycle from quote creation through payment collection, with PDF generation, email delivery, WhatsApp sharing, public invoice links, automated reminders, late fee management, and comprehensive reporting.

**Sidebar Menu Items:**

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Dashboard | `/billing` | Billing Dashboard |
| 2 | Quotations | `/billing/quotations` | Quotations |
| 3 | Proformas | `/billing/proformas` | Proformas |
| 4 | Invoices | `/billing/invoices` | Invoices |
| 5 | Payments | `/billing/payments` | Payments |
| 6 | Email Management | `/billing/emails` | Email Management |
| 7 | Products | `/billing/products` | Products |

**Document Conversion Flow:**

```
Lead / Direct
    │
    ▼
Quotation (QT-YYYY-NNNNN)
    │
    ├── convertToProforma() ──▶ Proforma (PRO-YYYY-NNNNN)
    │                                │
    │                                └── convertToInvoice() ──▶ Invoice (INV-YYYY-NNNNN)
    │                                                                │
    └── convertToInvoice() (direct) ──▶ Invoice                     ▼
                                                                Payment (PAY-YYYY-NNNNN)
```

On each conversion: source document marked `accepted`, new document sets `parent_document_id`, all line items replicated via Eloquent `replicate()`, `calculateTotals()` called on new document.

**Document Numbering Format:**

| Document Type | Default Prefix | Example |
|---|---|---|
| Invoice | `INV-` | `INV-2026-00001` |
| Quotation | `QT-` | `QT-2026-00001` |
| Proforma | `PRO-` | `PRO-2026-00001` |
| Credit Note | `CN-` | `CN-2026-00001` |
| Payment | `PAY-` | `PAY-2026-00001` |
| Receipt | `RCP-` | `RCP-2026-00001` |

All prefixes configurable via `BillingDocumentSetting` key-value store. Sequence resets per year. Format: `{PREFIX}{YEAR}-{00001}`.

**Tax/VAT Calculation (per line item):**

```
Line Item:
  subtotal       = quantity × unit_price
  discount_amount = subtotal × discount_pct/100  OR  fixed discount_value
  taxable_amount  = subtotal − discount_amount
  tax_amount      = taxable_amount × tax_percentage / 100
  line_total      = taxable_amount  (excludes tax; tax aggregated at document level)

Document:
  subtotal_amount = SUM(items.line_total)
  discount_amount = document-level discount (percentage or fixed on subtotal)
  tax_amount      = SUM(items.tax_amount)
  total_amount    = subtotal_amount − discount_amount + tax_amount + shipping_amount
  balance_amount  = total_amount − paid_amount
```

Default VAT rate: **18%** (Tanzania standard). Tax rates configurable via `BillingTaxRate` model.

**Core Data Models:**

```
BillingDocument (billing_documents)
├── document_type: quote | proforma | invoice | credit_note
├── BillingDocumentItem (billing_document_items) — line items
│   └── BillingProduct (billing_products_services) — product/service catalog
├── BillingPayment (billing_payments) — payment records
├── BillingDocumentEmail (billing_document_emails) — email audit log
├── BillingReminderLog (billing_reminder_logs) — reminder audit trail
├── ProjectClient — client (from Projects module)
├── Lead — optional CRM lead link
└── BillingDocumentSetting (billing_document_settings) — key-value config
```

**Status Workflows:**

Invoice: `draft → pending → sent → viewed → partial_paid → paid` (also: `overdue`, `void`, `cancelled`, `refunded`)
Quotation/Proforma: `draft → pending → sent → viewed → accepted → (converted)` (also: `rejected`, `cancelled`)
Payment: `completed → voided` or `pending → completed`

**Payment Terms Options:** `immediate`, `net_7`, `net_15`, `net_30`, `net_45`, `net_60`, `net_90`, `custom` (custom uses `custom_payment_days`)

**Currency:** `currency_code` defaults to `TZS`, `exchange_rate` (4 decimal) defaults to 1. Supports TZS, USD, EUR, GBP.

**Key Integrations:**
- **Lead Integration**: Documents can link to a `Lead` via `lead_id`. Create form accepts `?lead_id=ID` for contextual linking.
- **Client Approval**: `ClientApprovalService::autoApproveOnFirstPayment()` called on payment recording — ties billing to CRM/lead approval workflow.
- **WhatsApp Sharing**: Invoice show page has WhatsApp button with pre-filled phone, amounts, and public PDF link. Uses Web Share API on mobile, `wa.me` fallback on desktop.
- **Public PDF Link**: Unauthenticated route `/i/{token}` where token = `substr(md5($id . '|' . app.key), 0, 12)`.
- **Google Calendar**: Invoice show page generates a Google Calendar event URL for the due date.
- **Auto-sign**: Documents and payments automatically capture the authenticated user's signature file path on creation.

**Controllers (under `App\Http\Controllers\Billing\`):**

| Controller | Purpose |
|------------|---------|
| `DashboardController` | KPI cards, recent invoices/payments, revenue chart, status breakdown |
| `InvoiceController` | Full CRUD + PDF + receipt + email + remind + late fee + void + duplicate + public link |
| `QuotationController` | CRUD + PDF + email + convert to proforma/invoice + duplicate |
| `ProformaController` | CRUD + PDF + email + convert to invoice + duplicate |
| `PaymentController` | CRUD + receipt + receipt PDF + void + outstanding document lookup |
| `EmailController` | Email audit list + show + resend |
| `ProductController` | CRUD + activate/deactivate + adjust stock + low stock + search JSON |
| `TaxRateController` | Tax rate CRUD with default management |
| `SettingsController` | Document numbering, company info, email templates, defaults |
| `ReportController` | Sales, tax, aging, payments, outstanding, client statement, product sales |

**Billing Reports (5 + client statement + product sales):**

| Report | Route | Description |
|--------|-------|-------------|
| Sales Report | `/billing/reports/sales` | Revenue by day/week/month/quarter/year, top 10 clients |
| Tax Report | `/billing/reports/tax` | Tax collected and breakdown |
| Aging Report | `/billing/reports/aging` | Receivables buckets: Current, 1–30, 31–60, 61–90, 90+ days |
| Payments Report | `/billing/reports/payments` | Payment breakdown by method, daily totals |
| Outstanding Report | `/billing/reports/outstanding` | Unpaid invoice listing |

#### 8.1 Dashboard

**Route:** `/billing` (GET)
**Page Title:** "Billing Dashboard"
**Controller:** `Billing\DashboardController@index`

Overview of billing activity with KPI metrics, recent transactions, status breakdown, and revenue trend.

**KPI Summary Cards (4):**

| Card | Icon | Example Value | Description |
|------|------|---------------|-------------|
| Total Invoices | Document | 12 | Count of all invoices |
| Active Clients | People | 0 | Clients with recent activity |
| Total Revenue | Chart | TZS 853,089,160.97 | Sum of all invoice totals |
| Emails Sent | Mail | 1 | Count of sent billing emails |

**Recent Invoices Table (left panel):**

| Column | Description |
|--------|-------------|
| Invoice # | Document number (link to invoice) |
| Client | Client name |
| Amount | Total amount (formatted TZS) |
| Status | Color-coded badge |

Shows 5 most recent invoices. "View All" link to invoices list.

**Overdue Invoices Table (right panel):**

| Column | Description |
|--------|-------------|
| Invoice # | Document number (link) |
| Client | Client name |
| Due Date | Due date with `diffForHumans()` relative time |
| Amount | Balance amount |

Shows 5 most overdue invoices, or "No overdue invoices" message.

**Recent Payments Table (left panel):**

| Column | Description |
|--------|-------------|
| Payment # | Payment number |
| Invoice | Linked invoice number |
| Amount | Payment amount |
| Method | Payment method badge (Cash=dark, Bank Transfer=teal) |
| Date | Payment date |

Shows 5 most recent payments. "View All" link to payments list.

**Invoice Status Breakdown Table (right panel):**

| Column | Description |
|--------|-------------|
| Status | Color-coded badge (Pending=orange, Viewed=blue, Partial Paid=yellow, Paid=green) |
| Count | Number of invoices in status |
| Amount | Total amount for status |

**Monthly Revenue Trend:** Chart.js line chart showing last 12 months of revenue. Y-axis formatted as `TZS {amount}`.

**Quick Actions (5 buttons):**

| Button | Color | Target |
|--------|-------|--------|
| Create Invoice | Green | `/billing/invoices/create` |
| Create Quote | Blue | `/billing/quotations/create` |
| Add Client | Teal | `/billing/clients/create` |
| Email Management | Orange | `/billing/emails` |
| View Reports | — | `/billing/reports/sales` |

#### 8.2 Quotations

**Route:** `/billing/quotations` (GET)
**Page Title:** "Quotations"
**Controller:** `Billing\QuotationController@index`

Create, manage, and convert quotations into proformas or invoices. Quotations have a validity period (`valid_until_date`) and show expired warnings.

**Add Button:** "+ New Quotation" → `/billing/quotations/create` (full-page form)

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Status | Dropdown | All Statuses, Draft, Pending, Sent, Viewed, Accepted, Rejected, Cancelled |
| Client | Dropdown | All Clients + client list (17 clients) |
| From | Date picker | Start date |
| To | Date picker | End date |

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| Quote # | Document number |
| Client | Client name + contact person |
| Issue Date | Date issued |
| Valid Until | Validity date + "Expired" red badge if past |
| Amount | Total amount (TZS formatted) |
| Status | Color-coded badge |
| Actions | View, Edit, PDF, Duplicate, Convert to Invoice (if accepted), Cancel |

**Create/Edit Form (full page at `/billing/quotations/create`):**

**Line Items Section:**
- "+ Add Item" button
- Per-item fields: Product/Service dropdown, Item Name, Description textarea, Qty (default 1), Unit, Unit Price, Tax % (default 18), Amount (calculated), Delete button

**Quotation Details Section:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Client | Dropdown | Yes | ProjectClient list |
| Reference Number | Text | No | External reference |
| Issue Date | Date picker | Yes | Default today |
| Payment Terms | Dropdown | No | Due Immediately, Net 7/15/30/45/60/90 days, Custom |
| Valid Until | Date picker | No | Quote expiry date |
| PO Number | Text | No | Purchase order reference |
| Sales Person | Text | No | Auto-filled with current user name |

**Summary Section:** Subtotal, Tax, Total (auto-calculated from line items)

**Additional Settings:**

| Field | Type | Default |
|-------|------|---------|
| Currency | Dropdown | TZS (also USD, EUR, GBP) |
| Shipping Amount | Number | 0 |
| Discount Type | Dropdown | No Discount, Percentage, Fixed Amount |
| Discount Value | Number | 0 |

**Notes & Terms:**

| Field | Type | Notes |
|-------|------|-------|
| Service Description | Rich text editor (Bold/Italic/Underline/Lists/Link) | Shown as "Service Includes" on PDF; pre-populated with 10 site visit items |
| Internal Notes | Textarea | Not shown on document |
| Terms & Conditions | Textarea | Default: "Payment is due within the specified payment terms..." |
| Footer Text | Textarea | Default: "Thank you for your business!" |

**Action Buttons:** Save as Draft (gray), Create Quotation (green), Cancel (link)

**Conversion Actions:**
- `convertToProforma()` — Replicates quote as proforma, new `PRO-` number, marks quote `accepted`
- `convertToInvoice()` — Replicates quote as invoice, new `INV-` number, sets `due_date` to +30 days, marks quote `accepted`

#### 8.3 Proforma Invoices

**Route:** `/billing/proformas` (GET)
**Page Title:** "Proforma Invoices"
**Controller:** `Billing\ProformaController@index`

Same structure as quotations. Proformas serve as preliminary invoices before issuing a final tax invoice.

**Add Button:** "+ New Proforma Invoice" → `/billing/proformas/create`

**Filter Bar:** Same as Quotations (Status, Client, From/To dates)

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| Proforma # | Document number (e.g., PRO-2026-00001) |
| Client | Client name + contact person |
| Issue Date | Date issued |
| Valid Until | Validity date + "Expired" red badge if past |
| Amount | Total amount (TZS formatted) |
| Status | Color-coded badge |
| Actions | View, Edit, PDF, Duplicate, Convert to Invoice (if accepted) |

**Create/Edit Form:** Identical structure to Quotation form (line items, details, summary, additional settings, notes & terms).

**Conversion:** `convertToInvoice()` — Replicates proforma as invoice with new `INV-` number.

**Sample Data:**

| Proforma # | Client | Valid Until | Amount | Status |
|------------|--------|-------------|--------|--------|
| PRO-2026-00001 | Mr. Inoocent Sakaya | 06/03/2026 | TZS 5,000,000.00 | Pending |
| PRO-2025-00002 | Mustapha Ndee | 23/01/2026 (Expired) | TZS 50,000.00 | Pending |
| PRO-2025-00001 | JOSEPH MSEMBE | 19/01/2026 (Expired) | TZS 50,000.00 | Pending |

#### 8.4 Invoices

**Route:** `/billing/invoices` (GET)
**Page Title:** "Invoices"
**Controller:** `Billing\InvoiceController@index`

Full invoice lifecycle management with payments, PDF generation, email delivery, reminders, late fees, and status tracking.

**Add Button:** "+ New Invoice" → `/billing/invoices/create`

**Status Filter Dropdown:** Quick-filter buttons for All, Paid, Unpaid, Overdue, Draft, Cancelled, Refunded (each links to `/billing/invoices/status/{status}`)

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Status | Dropdown | All Statuses, Draft, Pending, Sent, Viewed, Partial Paid, Paid, Overdue |
| Client | Dropdown | All Clients + client list |
| From | Date picker | Start date |
| To | Date picker | End date |

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| Invoice # | Number + reference number (smaller text) |
| Client | Client name + email (smaller text) |
| Issue Date | Date issued |
| Due Date | Due date + relative time in red if overdue (e.g., "1 week ago") |
| Amount | Total amount |
| Paid | Paid amount |
| Balance | Remaining balance |
| Status | Badge: Paid (green), Pending (orange), Partial Paid (yellow), Viewed (blue) |
| Actions | View, Edit (if editable), PDF, Record Payment (if unpaid), dropdown (Duplicate, Void, Email, Reminder, Late Fee) |

**Create/Edit Form:** Same structure as Quotation form, but with `due_date` instead of `valid_until_date`. Supports `?from_document=ID` for conversion pre-population and `?lead_id=ID` for lead linking.

**Invoice Show Page (full-page detail view):**

**Header Actions:** Edit, PDF, Receipt (if paid), Email, WhatsApp, Reminder (if unpaid), Record Payment (if unpaid), dropdown (Duplicate, Apply Late Fee, Void, Back to List)

**Left Panel (8 cols):**
- Company letterhead via `@include('components.headed_paper')`
- Invoice metadata: Invoice #, Reference, Issue Date, Due Date, PO Number
- Bill To card: client name, address, phone, email
- Invoice title + service description (HTML rendered)
- Line items table: Item/Description, Qty, Unit, Unit Price, Tax%, Amount
- Totals: Subtotal, Discount, Tax, Shipping, **Total** (yellow), **Paid** (green), **Balance** (teal)
- Terms & Conditions
- Payment Information (bank details: CRDB Bank, Account: 0150884401500)

**Right Sidebar (4 cols):**
- Invoice Information: Creator, Created, Sent, Viewed, Paid timestamps
- Payment History table: Date, Amount, Method
- Linked Lead card (if `lead_id` set)
- Related Documents: parent/child document links

**Inline Modals on Show Page:**
- **Email Modal**: To, CC, Subject, Message → sends `InvoiceEmail` mailable with PDF attachment
- **Payment Modal**: Amount (max validated against balance), Date, Method dropdown, Reference, Notes
- **Reminder Modal**: To, CC, Reminder Type dropdown (before_due/overdue/late_fee/manual), Subject, Message → sends `InvoiceReminderEmail`
- **Late Fee Modal**: Percentage input with live amount calculation (one-time, guarded by `late_fee_applied_at`)
- **WhatsApp Modal**: Phone (pre-filled from client), message with invoice details + public PDF link

**Invoice PDF (2 pages via DomPDF):**

*Page 1 — Invoice:*
- Company logo, name, address, TIN
- Document type/number, status badge, reference, issue/due dates, PO#, sales person
- Bill To box (client details)
- "Service Includes" section (from `service_description` with HTML support)
- Items table: Item/Description, Qty, Unit, Unit Price, Tax%, Amount
- Totals table: Subtotal, Discount, Tax, Shipping, TOTAL (yellow), PAID (green), BALANCE (teal)
- Payment instructions with bank details (CRDB Bank, Account: 0150884401500, Account Name: WAJENZI PROFESSIONAL COMPANY LTD)
- Footer: contact info, Instagram handle + QR code

*Page 2 — Terms & Conditions:*
Custom terms from `$invoice->terms_conditions` OR 8 default sections: Payment Terms (60% deposit, 40% on completion, 2% late penalty/month), Project Deliverables/Revisions, Validity (7 days), Taxes (VAT 18%), Ownership, Cancellation Policy (tiered refunds), Dispute Resolution, Agreement.

**Special Features:**
- `show()` auto-transitions `sent` → `viewed` on first view (updates `viewed_at`)
- `destroy()` does NOT delete — sets status to `cancelled`. Blocked if payments exist.
- `void()` blocked if invoice is paid
- `publicPDF()` — unauthenticated endpoint at `/i/{token}` for client access
- `duplicate()` — creates exact copy as `draft` with fresh number and today's date

#### 8.5 Payments

**Route:** `/billing/payments` (GET)
**Page Title:** "Payments"
**Controller:** `Billing\PaymentController@index`

Record and track payments against invoices. Payments auto-update invoice `paid_amount`, `balance_amount`, and status.

**Add Button:** "+ Record Payment" → `/billing/payments/create`

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Status | Dropdown | All Statuses, Completed, Pending, Voided |
| Client | Dropdown | All Clients + client list |
| From | Date picker | Start date |
| To | Date picker | End date |

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| Payment # | Auto-numbered (e.g., PAY-2026-00007) |
| Document | Linked invoice number (link to invoice) |
| Client | Client name + email (smaller text) |
| Date | Payment date |
| Amount | Payment amount (TZS formatted) |
| Method | Badge: Cash (dark), Bank Transfer (teal) |
| Status | Badge: Completed (green), Pending, Voided |
| Received By | User who recorded the payment |
| Actions | View, Edit (if not voided), dropdown (View Receipt, Download PDF, Void) |

**Payment Methods:** `cash`, `bank_transfer`, `cheque`, `credit_card`, `mobile_money`, `online`, `other`

**Create/Edit Form:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Invoice | Dropdown | Yes | Outstanding invoices (balance > 0). Pre-selects via `?document_id=ID` |
| Amount | Number | Yes | Validated: cannot exceed `balance_amount` |
| Payment Date | Date picker | Yes | Default today |
| Payment Method | Dropdown | Yes | Cash, Bank Transfer, Cheque, Credit Card, Mobile Money, Online, Other |
| Reference Number | Text | No | Transaction reference |
| Bank Name | Text | No | For cheque/bank transfer |
| Cheque Number | Text | No | For cheque payments |
| Transaction ID | Text | No | External transaction ID |
| Notes | Textarea | No | Internal notes |

**Auto-cascade on create/update/delete:**
1. Recalculates `document.paid_amount` = sum of all completed payments
2. Updates `document.balance_amount` = total − paid
3. Calls `document.updatePaymentStatus()` → transitions to `paid` / `partial_paid` / `overdue`
4. Calls `client.updateBalance()`
5. Triggers `ClientApprovalService::autoApproveOnFirstPayment()` for CRM integration

**Void:** Sets `voided` status + `voided_at` + `voided_by`. Reverses: subtracts amount from document paid, recalculates balance and status.

**Receipt:** HTML receipt view + DomPDF receipt download.

#### 8.6 Email Management

**Route:** `/billing/emails` (GET)
**Page Title:** "Sent Emails"
**Controller:** `Billing\EmailController@index`

Audit log of every billing email sent (success or failure). Supports resending failed emails.

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Type | Dropdown | All Types, Invoice, Proforma, Quote |
| Status | Dropdown | All Status, Sent, Failed |
| Email | Text input | Search recipient email |
| From | Date picker | Start date |
| To | Date picker | End date |

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| Document | Type + number + "PDF Attached" yellow badge |
| Client | Company name + contact person |
| Recipient | Email address + CC addresses |
| Subject | Email subject (truncated) |
| Status | Badge: Sent (green), Failed (red) + error preview if failed |
| Sent By | User who triggered the send |
| Sent At | Timestamp |
| Actions | View details, Resend, View Document |

**Email Show Page:** Full email details — Document Type, To, Subject, Attachment status, Sent At, Sent By. Document Info panel (number, client, amount, issue date, status). Full message body rendered. Actions: Resend (if failed), View Document, Download PDF.

**Resend Form:** Pre-fills with original email data (To, CC, Subject, Message). Creates a new `BillingDocumentEmail` record on send attempt.

**Mail Classes:**
- `InvoiceEmail` — Used for all document types. Determines correct PDF view per type. Attaches PDF inline via `Attachment::fromData()`. Body rendered from `billing.emails.document`.
- `InvoiceReminderEmail` — Reminder-specific. Supports `before_due`, `overdue`, `late_fee`, `manual` types. Auto-generates subject line (e.g., "Overdue Payment Notice - Invoice INV-2026-00001 (5 days overdue)"). Attaches invoice PDF. Body from `billing.emails.reminder`.

#### 8.7 Products & Services

**Route:** `/billing/products` (GET)
**Page Title:** "Products & Services"
**Controller:** `Billing\ProductController@index`

Catalog of products and services used as line items in billing documents. Products support inventory tracking; services do not.

**Add Button:** "+ Add Product/Service" → `/billing/products/create`

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Search | Text input | Search by name/code/description |
| Type | Dropdown | All Types, Products, Services |
| Category | Dropdown | All Categories + distinct categories from DB (e.g., Building Materials, SERVICES) |
| Status | Dropdown | All Status, Active, Inactive, Low Stock |

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| Code | Auto-generated code in dark badge (e.g., `PRO-001`, `SER-001`) |
| Name | Product/service name + description preview (smaller text) |
| Type | Badge: Product (blue), Service (green) |
| Category | Category name |
| Unit Price | Price + "per {unit}" |
| Stock | Numeric badge (green) for products, "N/A" for services. Red if low stock |
| Status | Badge: Active (green), Inactive |
| Actions | View, Edit, Delete (if no document items use it) |

**Product Types:**
- `product` — Physical item, can track inventory (`track_inventory`, `current_stock`, `minimum_stock`, `reorder_level`)
- `service` — No inventory (forced off on save)

**Auto-code:** `{PRO|SER}-{001}`. First 3 uppercase letters of type as prefix. Example: `PRO-001`, `SER-001`.

**Create/Edit Form:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Type | Dropdown | Yes | Product / Service |
| Name | Text | Yes | Product/service name |
| Code | Text | Auto | Auto-generated, editable |
| Description | Textarea | No | Details |
| Category | Text | No | Free-text category |
| Unit of Measure | Text | No | e.g., PCS, KM, HR |
| Unit Price | Number | Yes | Selling price |
| Purchase Price | Number | No | Cost price |
| Tax Rate | Dropdown | No | From `BillingTaxRate` active records |
| SKU | Text | No | Stock keeping unit |
| Barcode | Text | No | Barcode value |
| Track Inventory | Toggle | No | Product only; forced off for services |
| Current Stock | Number | No | If tracking inventory |
| Minimum Stock | Number | No | Low stock threshold |
| Reorder Level | Number | No | Reorder trigger |

**Stock Adjustment:** Three modes — `increase`, `decrease`, `set`. Services cannot have stock adjusted.

**Product Show Page:** Loads tax rate, shows last 10 document items using this product, computes stats: `total_sold`, `total_revenue`, `average_price`, `times_used`.

**Low Stock:** `/billing/products/low-stock` — Products where `track_inventory = true AND current_stock <= minimum_stock`.

**Search Endpoint:** `/billing/products/search` (JSON) — Returns up to 20 matching products by name/code/SKU for autocomplete in line item forms.

**Soft Delete Guard:** Cannot delete if `documentItems()->count() > 0`.

#### 8.8 Billing Settings (Admin)

**Route:** `/billing/settings` (GET)
**Controller:** `Billing\SettingsController`

Configurable settings stored in `BillingDocumentSetting` key-value table.

**Settings Groups:**

**Document Numbering:**
- Configurable prefixes: `INV-`, `QT-`, `PRO-`, `CN-`, `RCP-`, `PAY-`
- Number format: `YYYY-00000`

**Company Information:**
- Company name, address, phone, email, website, TIN
- Company logo upload

**Default Values:**
- Default currency: `TZS`
- Default tax rate: `18%`
- Default payment terms
- Invoice terms and conditions
- Invoice footer text

**Email Templates:**
- From name, from address
- Per-document-type email subjects and templates (invoice, quote, proforma)

**Reminder Settings (via `BillingReminderSetting`):**
- Auto reminders enabled/disabled
- Reminder intervals: `[28, 21, 14, 7, 3, 1]` days before due
- Late fees enabled/disabled
- Late fee percentage: default 10%
- Late fee reminder interval (days)

**Reset:** Restores all settings to defaults.

#### 8.9 Tax Rates

**Route:** `/billing/tax-rates` (resource routes)
**Controller:** `Billing\TaxRateController`

Manage tax rates applied to line items.

**Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Name | Text | Yes | e.g., "VAT 18%" |
| Code | Text | Yes | e.g., "VAT" |
| Rate | Number | Yes | Percentage or fixed amount |
| Type | Dropdown | Yes | Percentage / Fixed |
| Description | Textarea | No | |
| Is Default | Toggle | No | Only one default allowed (others cleared on set) |
| Is Active | Toggle | No | Inactive rates hidden from dropdowns |

**Guard:** Cannot delete if in use by document items or products.

#### 8.10 Billing Reports

**Route prefix:** `/billing/reports/`
**Controller:** `Billing\ReportController`

| Report | Route | Description |
|--------|-------|-------------|
| Sales Summary | `/billing/reports/sales` | Revenue grouped by day/week/month/quarter/year. Total sales, total paid, outstanding, invoice count, top 10 clients by revenue |
| Tax Report | `/billing/reports/tax` | Tax collected breakdown |
| Aging Report | `/billing/reports/aging` | Receivables aging buckets: Current (not overdue), 1–30 days, 31–60 days, 61–90 days, 90+ days |
| Payments Report | `/billing/reports/payments` | Payments by date range, breakdown by payment method, daily payment totals |
| Outstanding Report | `/billing/reports/outstanding` | Unpaid invoice listing |
| Client Statement | per-client | All documents and payments for one client in date range |
| Product Sales | per-product | Line item aggregation — which products generated most revenue |

---

### 9. Statutory

Statutory payment compliance management for recurring obligations such as rent, internet, communication, and government stamp duties. Tracks payment schedules by billing cycle (monthly, quarterly, semi-annually, annually, one-time), links payments to asset properties, and supports approval workflows. Three report views provide monthly/yearly breakdowns by category, sub-category, and payment schedule.

**Note:** Tanzania statutory deductions (NSSF, NHIF, WCF, SDL, PAYE, HESLB) are managed in the **HR & Payroll** section under Settings (Deductions, Deduction Subscriptions, Deduction Settings), not in this Statutory module. This module handles general statutory business obligations.

#### Sidebar Menu

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Statutory Payments | `/statutory_payments` | Statutory Payments |
| 2 | Statutory Category Report | `/reports/statutory_category_report` | Statutory Category Report |
| 3 | Statutory Sub Category Report | `/reports/statutory_payment_report` | Statutory Sub Category Report |
| 4 | Statutory Schedules Report | `/reports/statutory_schedules_report` | Statutory Schedules Report |

---

#### 9.1 Statutory Payments Page

**Route:** `GET/POST /statutory_payments`
**Controller:** `StatutoryPaymentController@statutory_payments`
**Route Name:** `hr_settings_statutory_payments`

**Page Layout:**
- Company header block (logo, name, address, phone, TIN)
- "Back to Settings" link
- "New Statutory Payment" button (permission: `Add Statutory Payment`)
- DataTable with export buttons (Print, Excel, PDF)

**DataTable Columns:**

| # | Column | Source | Notes |
|---|--------|--------|-------|
| 1 | # | Row number | |
| 2 | Date | `$value->updated_at` | Last updated date |
| 3 | Control Number | `$value->control_number` | Bank control number |
| 4 | Statutory Payments | Sub-category name | Via `SubCategory::getSubCategoryName()` |
| 5 | Description | `$value->description` | Truncated to 100 chars |
| 6 | Amount | `number_format($value->amount)` | Formatted with commas |
| 7 | Due Date | `$value->due_date` | Payment due date |
| 8 | Status | `$value->status` | Badge: warning=PENDING, primary=APPROVED, danger=REJECTED, success=COMPLETED |
| 9 | Actions | View, Edit, Delete | Permission-gated |

**Action Buttons per Row:**

| Button | Icon | Permission | Action |
|--------|------|-----------|--------|
| View | `fa fa-eye` | — | Links to `/statutory_payments/{id}/1` (approval/detail page) |
| Edit | `fa fa-pencil` | `Edit Statutory Payment` | `loadFormModal('settings_statutory_payment_form', {className: 'StatutoryPayment', id: ID}, ...)` |
| Delete | `fa fa-times` | `Delete Statutory Payment` | `deleteModelItem('StatutoryPayment', ID, ...)` |

---

#### 9.2 Create / Edit Form Modal

**Form:** `settings_statutory_payment_form`
**Modal Size:** `modal-md`
**Encoding:** `multipart/form-data`

**Form Fields:**

| # | Field | Name | Type | Required | Notes |
|---|-------|------|------|----------|-------|
| 1 | Issues Date | `issue_date` | datepicker | Yes | Default: today. Read-only for non-admins |
| 2 | Sub Category | `sub_category_id` | select dropdown | Yes | Options from `SubCategory::all()`. Triggers AJAX on change |
| 3 | Billing Cycle | `billing_cycle` | select dropdown | Yes | Auto-populated via AJAX from sub-category selection |
| 4 | Amount | `amount` | select dropdown | Yes | Auto-populated via AJAX from sub-category price |
| 5 | Asset | `asset_id` | select dropdown | Yes | From `Asset::all()`. Triggers AJAX to load properties |
| 6 | Asset Property | `asset_property_id` | select dropdown | Yes | Auto-populated via AJAX from asset selection |
| 7 | Description | `description` | text | Yes | Free text |
| 8 | Control Number | `control_number` | number | No | Bank control/reference number |
| 9 | Due Date | `due_date` | datepicker | Yes | Auto-calculated from billing cycle. Read-only for non-admins |
| 10 | File | `file` | file upload | No | Supporting document |

**AJAX Endpoints:**
- `POST /sub_category_list` — On sub-category change: returns billing_cycle + price for the selected sub-category
- `POST /list_asset_properties` — On asset change: returns properties for the selected asset

**Billing Cycle Values:**

| Value | Label | Calculation |
|-------|-------|-------------|
| 0 | One Time | Single payment |
| 1 | Monthly | Every month |
| 3 | Quarterly | Every 3 months |
| 6 | Semi-Annually | Every 6 months |
| 12 | Annually | Once per year |

**Hidden Fields (Create Mode):**
- `document_number` — Format: `STPT/{next_id}/{year}` (e.g., STPT/1/2026)
- `document_id` — Next auto-increment ID
- `link` — `settings/statutory_payments/{document_id}/1`
- `document_type_id` — Value: `1`

**Submit:** `addItem` with `value="StatutoryPayment"` (create) / `updateItem` (edit)

---

#### 9.3 Statutory Payment Detail / Approval Page

**Route:** `GET /statutory_payments/{id}/{document_type_id}`
**Controller:** `StatutoryPaymentController@statutory_payment`
**Route Name:** `hr_settings_statutory_payment`

**Detail Table:**

| Field | Source |
|-------|--------|
| Description | `$value->description` |
| Amount | Formatted amount |
| Control Number | Control number value |
| Issue Date | `$value->issue_date` |
| Due Date | `$value->due_date` |
| Uploaded File | Link to file if exists |
| Status | Badge with status |

**Approval Workflow Section:**
- Shows approval stages via `Approval::getApprovalStages($id, $document_type_id)`
- Displays next pending approval step via `Approval::getNextApproval()`
- If user is in the next approval group:
  - Comments textarea (required)
  - Hidden fields: status, approval_document_types_id, user_id, approval_level_id, user_group_id, document_id, approval_date
  - "Approve now" button (green)
  - "Reject" button (red)
- Shows "Statutory Payment Approved" message if fully approved
- Shows rejection message with comments if rejected

---

#### 9.4 Statutory Category Report

**Route:** `GET/POST /reports/statutory_category_report`
**Controller:** `ReportsController@statutory_category_report`
**Data Source:** `Category::all()`

**Filter:** Year dropdown (2019–2024) + "Show" button

**Table Layout:**
- **Rows:** 12 months (January–December of selected year)
- **Columns:** One per Category (e.g., RENT, Internet, Communication, Stamping) + Total
- **Cell Values:** `StatutoryPayment::getTotalPaymentByCategory($category_id, $start_date, $end_date)` — sum of APPROVED payments
- **Cell Links:** Each cell is clickable, opens `loadFormModal('statutory_payment_per_sub_category_form', ...)` for detail drill-down
- **Footer:** Yearly totals per category

**Export:** Print, Excel, PDF

---

#### 9.5 Statutory Sub Category Report

**Route:** `GET/POST /reports/statutory_payment_report`
**Controller:** `ReportsController@statutory_payment_report`
**Data Source:** `SubCategory::all()`

**Filter:** Year dropdown (2019–2024) + "Show" button

**Table Layout:**
- **Rows:** 12 months (January–December)
- **Columns:** One per Sub-Category (e.g., rent, Internet, Communication, Stamping) + Total
- **Cell Values:** `StatutoryPayment::getTotalPaymentBySubCategory($sub_category_id, $start_date, $end_date)`
- **Cell Links:** Clickable for drill-down
- **Footer:** Yearly totals per sub-category

**Export:** Print, Excel, PDF

---

#### 9.6 Statutory Schedules Report

**Route:** `GET/POST /reports/statutory_schedules_report`
**Controller:** `ReportsController@statutory_schedules_report`
**Data Source:** `Product::all()`

**Filter:** Year dropdown (2019–2025) + "Show" button

**Table Columns:**

| # | Column | Source | Description |
|---|--------|--------|-------------|
| 1 | No | Row number | |
| 2 | Statutory | `$product->name` | Product/obligation name |
| 3 | Sub Category | `$product->subCategory->name` | Parent sub-category |
| 4 | Per Annually | Calculated `total_cost` | Annual total based on billing cycle |
| 5 | Per Monthly | `total_cost / 12` | Monthly equivalent |
| 6 | Per Bill | `$product->amount` | Amount per billing cycle |
| 7 | Billing Cycle | Human-readable label | Monthly/Quarterly/Semi-Annually/etc. |
| 8–19 | January–December | Paid amount per month | Color-coded: green checkmark (paid) / red X (unpaid) |
| 20 | Total | Sum of monthly amounts | Annual total |

**Annual Cost Calculations by Billing Cycle:**

| Billing Cycle | Per Bill | Total Cost (Annual) | Per Monthly |
|---------------|----------|-------------------|-------------|
| 0 (One Time) | amount | amount × 1 | amount |
| 1 (Monthly) | amount | amount × 12 | amount |
| 3 (Quarterly) | amount | amount × 3 | (amount × 3) / 12 |
| 6 (Semi-Annually) | amount | amount × 2 | (amount × 2) / 12 |
| 12 (Annually) | amount | amount × 1 | amount / 12 |

**Monthly Cell Calculation:** Uses `StatutoryInvoicePayment::getPaidAmountByDate()` with `Utility::check_in_range()` to determine if the date falls within an invoice payment range.

---

#### 9.7 Approval Workflow

**Document Type ID:** 1

**Status Lifecycle:**

```
CREATED → PENDING → APPROVED / REJECTED → PAID → COMPLETED
```

**Approval Flow:**
1. User creates StatutoryPayment → status = `CREATED`
2. RingleSoft approval workflow initiated
3. Approvers access via `/statutory_payments/{id}/1`
4. System shows approval stages and next pending step
5. Approver adds comments and clicks "Approve now" or "Reject"
6. On all approvals complete → `StatutoryPayment::onApprovalCompleted()` sets status to `APPROVED`
7. Notifications sent to relevant users

**Approval Model Methods:**
- `Approval::getApprovalStages($document_id, $document_type_id)` — retrieves all stages
- `Approval::getNextApproval($document_id, $document_type_id)` — next pending stage
- `Approval::isApprovalCompleted($document_id, $document_type_id)` — check if fully approved
- `Approval::isRejected($document_id, $document_type_id)` — check for rejection

**Note:** This module uses the **legacy approval system** (ApprovalDocumentType id=1, ApprovalLevel, UserGroup) rather than the newer RingleSoft ProcessApprovalFlow. The `Approvable` trait on the model integrates with the legacy system.

---

#### 9.8 Data Model

**`statutory_payments` table:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | No | auto-increment | Primary key |
| sub_category_id | integer | No | — | FK → sub_categories.id |
| statutory_payment_id | integer | Yes | — | Legacy reference field |
| description | text | Yes | NULL | Payment description |
| file | varchar(255) | Yes | NULL | Uploaded file path |
| issue_date | date | No | — | Issue date |
| due_date | date | No | — | Payment due date |
| control_number | bigint unsigned | Yes | NULL | Bank control number |
| amount | integer | No | — | Payment amount (TZS) |
| status | enum | No | CREATED | CREATED, PENDING, APPROVED, REJECTED, PAID, COMPLETED |
| document_number | varchar(255) | Yes | NULL | Format: STPT/{id}/{year} |
| created_at | timestamp | Yes | — | |
| updated_at | timestamp | Yes | — | |

**Migrations:**
1. `2021_05_13_111908_create_statutory_payments_table` — base table
2. `2025_03_26_113558_add_document_number_to_the_table_statutory_payments` — added `document_number`

**Model:** `App\Models\StatutoryPayment`
- **Traits:** `HasFactory`, `Approvable`
- **Implements:** `ApprovableModel`
- **Fillable:** `sub_category_id`, `description`, `status`, `issue_date`, `due_date`, `amount`, `control_number`, `file`, `document_number`

**Relationships:**

| Relationship | Type | Related Model | Foreign Key |
|--------------|------|---------------|-------------|
| `subCategory()` | belongsTo | SubCategory | `sub_category_id` |
| `category()` | hasOneThrough | Category | via SubCategory.category_id |

**Static Report Methods:**

| Method | Purpose |
|--------|---------|
| `getTotalPaymentBySubCategory($sub_category_id, $start_date, $end_date)` | Sum of APPROVED payments by sub-category |
| `getTotalPaymentByCategory($category_id, $start_date, $end_date)` | Sum by category (through sub-categories) |
| `getTotalPaymentByCategoryByDate($start_date, $end_date)` | Sum across all categories |
| `getTotalPayment($start_date, $end_date)` | Grand total of all APPROVED payments |
| `countUnapproved()` | Count of non-approved/non-rejected records |

---

#### 9.9 Related Models

**`sub_categories` table:**

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| category_id | integer | FK → categories.id |
| name | varchar | Sub-category name |
| description | varchar | Description |
| billing_cycle | integer | 0=One Time, 1=Monthly, 3=Quarterly, 6=Semi-Annually, 12=Annually |
| price | decimal | Amount per billing cycle |

**`categories` table:**

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | Category name (e.g., Rent, Internet) |
| description | varchar | Description |

**`statutory_invoice_payments` table:**

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| date | date | Payment date |
| amount | integer | Payment amount |
| invoice_id | integer | FK → invoices |
| payment_mode | varchar | Payment method |
| description | text | Description |
| status | varchar | APPROVED / REJECTED |
| file | varchar | Supporting document |
| created_by_id | integer | FK → users.id |

**`products` table (statutory schedules):**

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | varchar | Product/obligation name |
| billing_cycle | integer | Billing frequency |
| amount | integer | Amount per cycle |
| description | text | Description |
| sub_category_id | integer | FK → sub_categories.id |
| issue_date | date | Start date |
| due_date | date | End date |
| status | varchar | Status |
| days | integer | Days in cycle |

---

#### 9.10 Routes Summary

| Method | URI | Controller | Route Name |
|--------|-----|-----------|------------|
| GET, POST | `/statutory_payments` | `StatutoryPaymentController@statutory_payments` | `hr_settings_statutory_payments` |
| GET | `/statutory_payments/{id}/{document_type_id}` | `StatutoryPaymentController@statutory_payment` | `hr_settings_statutory_payment` |
| POST | `/sub_category_list` | `SubCategoryController@getSubCategories` | `sub_category_list` |
| GET, POST | `/reports/statutory_payment_report` | `ReportsController@statutory_payment_report` | `reports_statutory_payment_report` |
| GET, POST | `/reports/statutory_category_report` | `ReportsController@statutory_category_report` | `reports_statutory_category_report` |
| GET, POST | `/reports/statutory_schedules_report` | `ReportsController@statutory_schedules_report` | `reports_statutory_schedules_report` |

---

#### 9.11 Permissions

| Permission | Purpose |
|------------|---------|
| Statutory Payments | Access the Statutory Payments page |
| Add Statutory Payment | Create new statutory payments |
| Edit Statutory Payment | Modify existing payments |
| Delete Statutory Payment | Remove payments |
| Statutory Category Report | Access category report |
| Statutory Sub Category Report | Access sub-category report |
| Statutory Schedules Report | Access schedules report |

---

#### 9.12 Configuration (Managed in Settings)

Statutory reference data is configured in the Settings module:

| Settings Page | Route | Model | Purpose |
|---------------|-------|-------|---------|
| Statutory Payment Categories | `/settings/categories` | Category | Top-level categories (Rent, Internet, etc.) |
| Statutory Payment Sub Categories | `/settings/sub_categories` | SubCategory | Child items with billing cycle + price |
| Assets | `/settings/assets` | Asset | Assets linked to statutory payments |
| Asset Properties | `/settings/asset_properties` | AssetProperty | Properties of assets |

---

### 10. Procurement

End-to-end material procurement pipeline built on the BOQ system. Manages the full lifecycle from site-level material requests through supplier quotation, competitive comparison, purchase ordering, goods receiving, quality inspection, and site stock management. The BOQ item serves as the central spine — five quantity fields (`quantity`, `quantity_requested`, `quantity_ordered`, `quantity_received`, `quantity_used`) accumulate through the pipeline and drive `procurement_status` (not_started / in_progress / complete).

**Sidebar Menu Items:**

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Procurement Dashboard | `/procurement_dashboard` | Procurement Dashboard |
| 2 | Material Requests | `/project_material_requests` | Material Requests |
| 3 | Supplier Quotations | `/supplier_quotations` | Supplier Quotations |
| 4 | Quotation Comparisons | `/quotation_comparisons` | Quotation Comparisons |
| 5 | Purchase Orders | `/purchase_orders` | Purchase Orders |
| 6 | Record Deliveries | `/record_deliveries` | Record Deliveries |
| 7 | Supplier Receivings | `/supplier_receivings_procurement` | Supplier Receivings |
| 8 | Material Inspections | `/material_inspections` | Material Inspections |
| 9 | Site Stock Register | `/stock_register` | Site Stock Register |

**Procurement Pipeline (End-to-End Flow):**

```
BOQ Item (project_boq_items)
        │
        │ Site engineer selects BOQ items + quantities
        ▼
[1] Material Request (MR-YYYY-NNNN)
        │
        │ RingleSoft approval → status = APPROVED
        │ onApprovalCompleted → BOQ item quantity_requested incremented
        ▼
[2] Supplier Quotations (SQ-YYYY-NNNN)
        │
        │ Minimum 3 quotations required (hardcoded)
        ▼
[3] Quotation Comparison (QC-YYYY-NNNN)
        │
        │ RingleSoft approval → status = APPROVED
        │ onApprovalCompleted → selected quotation marked "selected", others "rejected"
        ▼
[4] Purchase Order (from purchases table)
        │
        │ Auto-created via Purchase::createFromComparison()
        │ RingleSoft approval → status = APPROVED
        │ onApprovalCompleted → BOQ item quantity_ordered updated
        ▼
[5] Record Delivery → Supplier Receiving (SR-YYYY-NNNN)
        │
        │ PurchaseItem quantity_received incremented per item
        ▼
[6] Material Inspection (MI-YYYY-NNNN)
        │
        │ RingleSoft approval → status = APPROVED
        │ onApprovalCompleted → updateStock() called:
        │   → creates ProjectMaterialInventory record
        │   → creates ProjectMaterialMovement (type=received)
        │   → BOQ item quantity_received incremented
        ▼
[7] Site Stock Register (project_material_inventory / project_material_movements)
        │
        │ Issue materials → movement_type = issued
        │ Adjust stock → movement_type = adjustment
        ▼
    BOQ Item fully received → procurement_status = complete
```

**Document Numbering Formats:**

| Document | Prefix | Format | Example |
|----------|--------|--------|---------|
| Material Request | `MR-` | `MR-YYYY-NNNN` | `MR-2026-0001` |
| Supplier Quotation | `SQ-` | `SQ-YYYY-NNNN` | `SQ-2026-0001` |
| Quotation Comparison | `QC-` | `QC-YYYY-NNNN` | `QC-2026-0001` |
| Supplier Receiving | `SR-` | `SR-YYYY-NNNN` | `SR-2026-0001` |
| Material Inspection | `MI-` | `MI-YYYY-NNNN` | `MI-2026-0001` |
| Material Movement | `MM-` | `MM-YYYY-NNNN` | `MM-2026-0001` |

**Approval Workflows (RingleSoft `Approvable`):**

| Model | Status After Approval | Key Side Effects |
|-------|----------------------|------------------|
| `ProjectMaterialRequest` | `APPROVED` | BOQ `quantity_requested` incremented per item |
| `QuotationComparison` | `APPROVED` | Selected quotation → `selected`; others → `rejected` |
| `Purchase` | `APPROVED` | BOQ `quantity_ordered` recalculated from all approved PO items |
| `MaterialInspection` | `APPROVED` | `updateStock()` → inventory created, movement recorded, BOQ `quantity_received` incremented |

Approval chain: Managing Director, System Administrator (configurable via RingleSoft).

**Core Data Models:**

```
ProjectMaterialRequest (project_material_requests)
├── ProjectMaterialRequestItem (project_material_request_items)
│   └── ProjectBoqItem (reference)
├── SupplierQuotation (supplier_quotations)
│   └── SupplierQuotationItem (supplier_quotation_items)
│       └── links to MaterialRequestItem + BoqItem
├── QuotationComparison (quotation_comparisons)
│   └── selectedQuotation → SupplierQuotation
└── Purchase (purchases)
    ├── PurchaseItem (purchase_items)
    │   └── links to BoqItem
    └── SupplierReceiving (supplier_receivings)
        └── MaterialInspection (material_inspections)
            └── triggers → ProjectMaterialInventory + ProjectMaterialMovement
```

**Three-Quotation Rule:** Hardcoded at `QuotationComparison::MINIMUM_QUOTATIONS = 3`. Enforced in controller validation, model `hasMinimumQuotations()`, and dashboard's `getRequestsReadyForComparison()`. At least 3 non-rejected quotations required before a comparison can be created.

#### 10.1 Procurement Dashboard

**Route:** `/procurement_dashboard` (GET)
**Page Title:** "Procurement Dashboard"
**Controller:** `ProcurementDashboardController@index`

Read-only overview of the entire procurement pipeline with KPI metrics, pending actions, and recent activity.

**KPI Summary Cards (4):**

| Card | Example Value | Description |
|------|---------------|-------------|
| Total Requests | 17 | All material requests |
| Total Quotations | 43 | All supplier quotations |
| Pending Deliveries | 1 | Approved POs with unfulfilled items |
| Completed Inspections | 9 | Approved inspections |

**Actions Required Section (6 categories):**
- Pending material request approvals
- Requests ready for quotation (approved but no quotations)
- Requests ready for comparison (3+ quotations, no comparison yet)
- Pending comparison approvals
- Pending PO approvals
- Deliveries pending inspection

**Active Projects:** Top 10 projects by material request count.

**Low Stock Alerts:** Top 10 items where `quantity_available <= minimum_stock_level`.

**Recent Activity (3 panels):**
- Recent Requests (5 most recent) — with priority badges (High=orange, Urgent=red)
- Recent Comparisons (5 most recent) — with approval status badges
- Recent Inspections (5 most recent) — with result badges (Pass=green)

**Quick Navigation Links:** Quotations, Comparisons, Inspections

**Project-Level Dashboard:** `/procurement_dashboard/project/{id}` — Per-project view with:
- Progress bars (complete/in_progress/not_started percentages)
- Budget utilization
- Construction phases with BOQ item counts
- 4 pending action counts
- 10 recent activity items
- 5 low stock items

**BOQ Item Trace:** `/procurement_dashboard/boq_item/{id}` — Complete audit trail for one BOQ item showing all material requests, quotations, purchases, receivings, and stock movements.

#### 10.2 Material Requests

**Route:** `/project_material_requests` (GET/POST)
**Page Title:** "Material Requests"
**Controller:** `ProjectMaterialRequestController@index`

Create and manage requests for materials linked to project BOQ items. Shared with the Projects module (same controller/view).

**Add Button:** "New Request" (opens modal form)

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Project | Dropdown | All Projects + project list |
| Status | Dropdown | All Statuses, Pending, Approved, Rejected, Completed |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Request No. | Auto-numbered (e.g., MR-2026-0001) |
| Project | Project name |
| Items | Count of line items |
| Priority | Badge: Low (teal), Medium (blue), High (orange), Urgent (red) |
| Required Date | Date materials needed |
| Approvals | RingleSoft approval chain icons (MD status) |
| Status | Badge: Created (blue), Approved (green), Rejected (red) |
| Requester | User who created the request |
| Actions | View, Create Quotation (if approved), Delete (if created/rejected) |

**Create Form (Modal):**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Project | Searchable dropdown | Yes | Project list |
| BOQ Item | Searchable dropdown | Yes | Filtered by project BOQ items |
| Quantity Requested | Number | Yes | Validated against available (BOQ `quantity - quantity_requested`) |
| Unit | Text | Yes | e.g., pcs, kg, m |
| Required Date | Date picker | Yes | Default: 1 week from now |
| Priority | Dropdown | Yes | Low, Medium (default), High, Urgent |
| Purpose / Justification | Text | No | Brief reason for request |

**Bulk Creation (from BOQ page):** `POST /project_material_request/bulk/{project_id}` — Select multiple BOQ items via checkboxes on the project BOQ page, specify quantities, creates one `ProjectMaterialRequest` header with N `ProjectMaterialRequestItem` records.

**Approval Detail Page:** `/project_material_request/{id}/{document_type_id}` — Shows Request Number, Project, Items count, Priority, Required Date, Requested By. RingleSoft approval UI for approve/reject.

**Model:** `ProjectMaterialRequest` — Auto-generates `request_number` (MR-YYYY-NNNN). Uses `$incrementing = false` (manual ID). On approval: sets `status = APPROVED`, loops items to increment BOQ `quantity_requested`, calls `updateProcurementStatus()`.

**Priority Badge Colors:** `low` = secondary, `medium` = info, `high` = warning, `urgent` = danger

#### 10.3 Supplier Quotations

**Route:** `/supplier_quotations` (GET/POST)
**Page Title:** "Supplier Quotations"
**Controller:** `SupplierQuotationController@index`

Collect quotations from suppliers for approved material requests. Requires minimum 3 quotations before comparison can proceed.

**Add Button:** "New Quotation" → navigates to Material Requests page (quotations are created per-request, not standalone)

**Filter Bar:**

| Filter | Type | Default |
|--------|------|---------|
| Start Date | Text/date | First of current month |
| End Date | Text/date | Today |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Quotation # | Auto-numbered (e.g., SQ-2026-0001) |
| Material Request | Linked MR number |
| Supplier | Supplier name |
| Date | Quotation date |
| Subtotal | Amount before VAT |
| VAT | VAT amount |
| Grand Total | Total including VAT |
| Status | Badge: Received (blue), Selected (green), Rejected (red) |
| Actions | View, Edit, Delete |

**Per-Request Quotation Page:** `/supplier_quotations/request/{id}` — Shows all quotations for one material request:
- Progress bar: X/3 quotations (minimum 3 required)
- Quotations sorted by `grand_total` ascending (cheapest first)
- Inline modal to add new quotation
- "Create Comparison" button (visible when 3+ quotations and no existing comparison)
- `canCreateComparison` flag: no approved comparison, 3+ quotations, no pending comparison

**Quotation Form (Modal or inline):**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Material Request | Dropdown | Yes | APPROVED requests only |
| Supplier | Dropdown | Yes | Filtered to exclude suppliers already quoted for this request |
| Quotation Date | Date | Yes | |
| Valid Until | Date | No | Expiry date |
| Delivery Time (days) | Number | No | Used for PO expected delivery date calculation |
| Per-item rows | — | — | See below |
| Payment Terms | Text | No | |
| File | File upload | No | Stored to `storage/quotations/` |
| Notes | Textarea | No | |

**Per-Item Fields (matching MR items):**

| Field | Description |
|--------|-------------|
| Description | BOQ item name (pre-filled) |
| Quantity | From MR item (pre-filled) |
| Unit | From MR item (pre-filled) |
| Unit Price | Supplier's quoted unit price |
| Total Price | Calculated: qty × unit price |

**Computed Totals:** `total_amount` (sum of item totals), `vat_amount` (entered separately), `grand_total = total_amount + vat_amount`.

**Compare View:** `/supplier_quotations/compare/{material_request_id}` — Read-only side-by-side comparison. Computes: lowest, highest, average, variance.

**Available Suppliers Endpoint:** `/supplier_quotations/available_suppliers/{material_request_id}` — JSON response: suppliers not yet having a quotation for this request.

**Model:** `SupplierQuotation` — Auto-generates `quotation_number` (SQ-YYYY-NNNN). Status: `received` (default), `selected`, `rejected`. `markAsSelected()` sets status=selected AND rejects all other quotations for same request. `isValid()` / `isExpired()` checks `valid_until` date.

#### 10.4 Quotation Comparisons

**Route:** `/quotation_comparisons` (GET/POST)
**Page Title:** "Quotation Comparisons"
**Controller:** `QuotationComparisonController@index`

Side-by-side comparison of supplier quotations with per-item price analysis. Requires selection of winning quotation with written justification.

**Filter Bar:**

| Filter | Type | Default |
|--------|------|---------|
| Start Date | Text/date | First of current month |
| End Date | Text/date | Today |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Comparison # | Auto-numbered (e.g., QC-2026-0001) |
| Material Request | Linked MR number |
| Selected Supplier | Winning supplier name |
| Selected Amount | Winning quotation grand total |
| Quotations | Number of quotations compared |
| Prepared By | User who created comparison |
| Approvals | RingleSoft approval chain icons (MD, Sys Admin) |
| Status | Badge: Approved (green), Pending, Rejected |
| Actions | View, Create Purchase Order (if approved) |

**Create Comparison Form:** `/quotation_comparison/create/{material_request_id}`

- Guards: Redirects if existing pending/approved comparison exists. Requires 3+ non-rejected quotations.
- **Per-item price matrix table:** Rows = MR items, Columns = suppliers. Color-coded: lowest price = green, highest = red.
- **Supplier selection cards:** Radio buttons to select winning supplier.
- **Recommendation reason:** Textarea (minimum 10 characters) — justification for selection.

**Computed statistics (from quotations):**
- `lowest_quotation`, `highest_quotation`
- `average_quotation_price`, `price_variance`
- `savings` = highest.grand_total − selected.grand_total

**Create Purchase Order:** `/quotation_comparison/{id}/create_purchase` — Converts approved comparison to PO via `Purchase::createFromComparison()`. Guards: must be APPROVED, no existing purchase.

**Approval Detail Page:** `/quotation_comparison/{id}/{document_type_id}` — Shows Comparison Number, Material Request, Project, Date, Selected Supplier, Selected Amount, Quotations count, Prepared By. RingleSoft approval UI.

**Model:** `QuotationComparison` — `MINIMUM_QUOTATIONS = 3` (constant). Auto-generates `comparison_number` (QC-YYYY-NNNN). On approval: calls `selectedQuotation->markAsSelected()` (rejects others), updates BOQ `updateProcurementStatus()`.

#### 10.5 Purchase Orders

**Route:** `/purchase_orders` (GET/POST)
**Page Title:** "Purchase Orders"
**Controller:** `PurchaseController@purchaseOrders`

Purchase orders generated from approved quotation comparisons. Uses the shared `purchases` table (filtered by `material_request_id NOT NULL`).

**Filter Bar:**

| Filter | Type | Default |
|--------|------|---------|
| Start Date | Text/date | First of current month |
| End Date | Text/date | Today |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| PO Number | Document number |
| Project | Project name |
| Supplier | Supplier name |
| Material Request | Linked MR number |
| Items | Count of purchase items |
| Subtotal | Amount excluding VAT |
| VAT | VAT amount (18%) |
| Total | Grand total |
| Approvals | RingleSoft approval chain icons |
| Status | Badge: APPROVED, CREATED, PENDING, SUBMITTED |
| Actions | View, Record Delivery (if approved with unfulfilled items) |

**Auto-creation via `Purchase::createFromComparison()`:**
1. Guards: comparison must be APPROVED, have selected quotation
2. Creates `Purchase` with: `total_amount = quotation.grand_total`, `amount_vat_exc = quotation.total_amount`, `expected_delivery_date = now() + delivery_time_days`
3. Creates `PurchaseItem` for each MR item using quotation item's unit_price
4. Status starts as `pending`

**Approval Detail Page:** `/purchase_order/{id}/{document_type_id}` — Shows PO Number, Project, Supplier, Material Request ref, Comparison ref, Date, Item count, Subtotal, VAT (18%), Total, Payment Terms, Expected Delivery, Created By.

**Model:** `Purchase` — Dual-purpose (legacy purchases + procurement POs). On approval: calls `purchaseItem->updateBoqItemQuantities()` for each item, which recalculates BOQ `quantity_ordered`.

**PurchaseItem status logic:** `pending` (qty_received=0), `partial` (0 < received < ordered), `complete` (received >= ordered). Auto-calculated on save.

#### 10.6 Record Deliveries

**Route:** `/record_deliveries` (GET/POST)
**Page Title:** "Record Deliveries"
**Controller:** `PurchaseController@pendingDeliveries`

Lists approved purchase orders with unfulfilled items. Entry point for recording goods delivery.

**Content:** Shows "Approved Purchase Orders Awaiting Delivery" section. Lists POs where at least one `PurchaseItem` has `quantity_received < quantity`. Shows empty state message when all deliveries are complete: "All approved purchase orders have been fully delivered."

**Record Delivery Form:** `/purchase_order/{id}/record_delivery` (GET)

Available only for APPROVED POs with unfulfilled items.

**Items Table:**

| Column | Description |
|--------|-------------|
| BOQ Item | Item name from BOQ |
| Ordered | Quantity ordered |
| Already Received | Previously received quantity |
| Pending | Remaining quantity |
| Delivering | Input field for this delivery's quantity |

**Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Delivery Note Number | Text | Yes | Supplier's delivery note reference |
| Date | Date | Yes | Delivery date |
| Condition | Dropdown | Yes | Good, Partial Damage, Damaged |
| Description | Textarea | No | Delivery notes |
| File | File upload | No | Delivery note scan, stored to `storage/delivery_notes/` |
| Items[].quantity | Number | Yes | Per-item delivered quantity |

**On submission:** Creates `SupplierReceiving` record, calls `PurchaseItem::recordReceiving(qty)` for each item (increments `quantity_received`, updates item status).

#### 10.7 Supplier Receivings

**Route:** `/supplier_receivings_procurement` (GET/POST)
**Page Title:** "Supplier Receivings"
**Controller:** `PurchaseController@receivings`

Formal receipt records for goods delivered against procurement purchase orders.

**Filter Bar:**

| Filter | Type | Default |
|--------|------|---------|
| Start Date | Text/date | First of current month |
| End Date | Text/date | Today |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Receiving # | Auto-numbered (e.g., SR-2026-0001) |
| PO Number | Linked purchase order |
| Project | Project name |
| Supplier | Supplier name |
| Delivery Date | Date of delivery |
| Delivery Note | Delivery note reference |
| Qty Delivered | Total quantity delivered |
| Condition | Badge: Good (green), Partial Damage (yellow), Damaged (red) |
| Status | Badge: Pending, Received, Inspected |
| Actions | View detail, Create Inspection (if needs inspection) |

**Detail Page:** `/supplier_receiving_detail/{id}` — Shows receiving details with purchase items and inspection history.

**Model:** `SupplierReceiving` — Auto-generates `receiving_number` (SR-YYYY-NNNN). Uses `$incrementing = false` (manual ID). Status flow: `pending → received → inspected`. `needsInspection()`: status in pending/received AND no inspections exist.

#### 10.8 Material Inspections

**Route:** `/material_inspections` (GET/POST)
**Page Title:** "Material Inspections"
**Controller:** `MaterialInspectionController@index`

Quality inspection of received materials. Two-section page: pending inspections and completed inspections.

**Section 1: "Deliveries Pending Inspection"**
Simple table (not DataTable) showing receivings needing inspection (top 20).

| Column | Description |
|--------|-------------|
| Receiving # | SR number |
| Supplier | Supplier name |
| Delivery Date | Date delivered |
| Qty Delivered | Quantity |
| Condition | Condition badge |
| Action | "Inspect" button (blue) |

**Section 2: "All Inspections"**

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Start Date | Text/date | Date range |
| End Date | Text/date | |
| Project | Dropdown | All Projects + project list |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Inspection # | Auto-numbered (e.g., MI-2026-0001) |
| Project | Project name |
| BOQ Item | Material being inspected |
| Supplier | Supplier name (via receiving → purchase) |
| Delivered | Quantity delivered |
| Accepted | Quantity accepted + acceptance rate % |
| Condition | Badge: Excellent, Good, Acceptable, Poor, Rejected |
| Result | Badge: Pass (green), Conditional (yellow), Fail (red) — auto-computed |
| Status | Badge: Approved (green), Pending (yellow) + "Stock Updated" text |
| Actions | View, Approval page |

**Create Inspection Form:** `/material_inspection/create/{receiving_id}`

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| BOQ Item | Dropdown | Yes | From purchase items or project BOQ items |
| Inspection Date | Date | Yes | Default today |
| Quantity Delivered | Number | Pre-filled | From receiving record |
| Quantity Accepted | Number | Yes | Must be ≤ delivered |
| Overall Condition | Dropdown | Yes | Excellent, Good, Acceptable, Poor, Rejected |
| Rejection Reason | Textarea | No | If any items rejected |
| Inspection Notes | Textarea | No | |

**6-Criteria Checklist (checkboxes):**

| Criteria | Description |
|----------|-------------|
| Packaging Intact | Package not damaged during transport |
| Quantity Correct | Delivered quantity matches delivery note |
| Specification Match | Materials match ordered specifications |
| No Visible Defects | No visible damage or defects |
| Proper Labeling | Items properly labeled/identified |
| Storage Suitable | Materials suitable for site storage conditions |

**Auto-computed on save:**
- `quantity_rejected = quantity_delivered - quantity_accepted`
- `overall_result`: `pass` (no rejections), `conditional` (some accepted, some rejected), `fail` (0 accepted)

**Approval callback:** On approval → calls `updateStock()` (DB transaction):
1. Gets/creates `ProjectMaterialInventory` for (project_id, boq_item_id)
2. Increments inventory quantity by `quantity_accepted`
3. Creates `ProjectMaterialMovement` (type=received)
4. Increments BOQ item `quantity_received`
5. Calls `boqItem->updateProcurementStatus()`
6. Sets `stock_updated = true`, `stock_updated_at = now()`
7. Sets `supplierReceiving.status = inspected`

#### 10.9 Site Stock Register

**Route:** `/stock_register` (GET) → project selection; `/stock_register/{project_id}` → inventory
**Page Title:** "Site Stock Register"
**Controller:** `ProjectMaterialInventoryController`

Per-project material inventory tracking with issue/adjustment capabilities and full movement history.

**Two-Level Navigation:**

1. **Project Selection Page** (`/stock_register`): Cards for each project that has BOQ items. Click to enter project stock register.
2. **Project Inventory Page** (`/stock_register/{project_id}`): Full inventory for selected project.

**KPI Summary Cards (4):**

| Card | Example | Description |
|------|---------|-------------|
| Total Items | 1 | Distinct inventory items |
| In Stock | 1 | Items with available quantity > 0 |
| Low Stock | 0 | Items at or below minimum stock level |
| Out of Stock | 0 | Items with 0 available |

**Action Buttons:** "Issue Materials", "Movements" (history)

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Item Code | BOQ item code |
| Description | BOQ item name/description |
| Unit | Unit of measurement |
| Qty Received | Total received through inspections |
| Qty Used | Total issued/consumed |
| Qty Available | `max(0, received - used)` |
| Min Stock | Minimum stock level threshold |
| Status | Badge: In Stock (green), Low Stock (yellow), Out of Stock (red) |
| Actions | Adjust (pencil), View Movements (history icon) |

**Issue Materials Form:** `/stock_register/{project_id}/issue`
- Shows only in-stock items
- Multi-item issue in one form
- Per item: quantity to issue (validated ≤ available), notes
- Creates `ProjectMaterialMovement` (type=issued) for each item
- Updates inventory `quantity_used`

**Adjust Stock Form:** `/stock_register/{project_id}/adjust/{inventory_id}`
- Single item adjustment
- Fields: New Quantity (number, ≥ 0), Reason (required text)
- Creates `ProjectMaterialMovement` (type=adjustment)
- Sets absolute inventory quantity

**Movements Page:** `/stock_register/{project_id}/movements`
- Paginated (25/page) movement history
- Filterable by date range and movement_type

| Column | Description |
|--------|-------------|
| Movement # | Auto-numbered (MM-YYYY-NNNN) |
| BOQ Item | Item description |
| Type | Badge: Received (green), Issued (blue), Adjustment (yellow), Returned, Transfer |
| Quantity | Signed quantity (negative for issued/transfer) |
| Balance After | Inventory balance after movement |
| Reference | Polymorphic link (inspection, issue, adjustment) |
| Date | Movement date |
| Performed By | User |
| Verified | Verified By + Verified At (or "Verify" button if unverified) |

**Verify Movement:** `POST /stock_register/{project_id}/movements/{movement_id}/verify` — Sets `verified_by` and `verified_at`.

**Model:** `ProjectMaterialInventory` — Stock status computed: `out_of_stock` (available ≤ 0), `low_stock` (available ≤ minimum_stock_level), `in_stock`. Methods: `receive()`, `issue()`, `returnMaterial()`, `adjust()`.

**Model:** `ProjectMaterialMovement` — Auto-generates `movement_number` (MM-YYYY-NNNN). Types: `received`, `issued`, `adjustment`, `returned`, `transfer`. Polymorphic `reference()` via MorphTo. Factory methods: `createFromInspection()`, `createIssue()`.

---

### 11. Expenses

Company-wide operational expense tracking with category/sub-category classification, file attachments, and RingleSoft approval workflow. Separate from project-level costs (`ProjectExpense`) and finance adjustment entries (`AdjustmentExpense`). Expense totals feed into financial reports via static aggregation methods with hardcoded category IDs.

**Sidebar:** Single menu item "Expenses" (not nested under a parent).

**Route:** `/expenses` (GET/POST)
**Permission:** `Expenses` (view), `Add Expense`, `Edit Expense`, `Delete Expense`

**Related Settings Pages:**

| Page | Route | Permission |
|------|-------|------------|
| Expense Categories | `/settings/expenses_categories` | Expenses Categories |
| Expense Sub Categories | `/settings/expenses_sub_categories` | Expenses Sub Categories |

**Data Models:**

```
Expense (expenses)
├── expensesSubCategory → ExpensesSubCategory
│   ├── expensesCategory → ExpensesCategory
│   └── is_financial: YES/NO (controls inclusion in financial reports)
└── file (attachment path)

Related but separate:
├── ProjectExpense (project_expenses) — project-level costs, no approval
└── AdjustmentExpense (adjustment_expenses) — finance-level manual adjustments
```

**Approval:** `Expense` implements `ApprovableModel`, uses `Approvable` trait (RingleSoft). `document_type_id = 5`. On approval completed: sets `status = 'APPROVED'`.

**Status Workflow:**

```
CREATED → PENDING → APPROVED
                  ↘ REJECTED
APPROVED → PAID → COMPLETED
```

| Status | Badge Color | How Set |
|--------|-------------|---------|
| CREATED | Gray/dark | Default on insert |
| PENDING | Yellow/warning | Via approval submission |
| APPROVED | Blue/primary | `onApprovalCompleted()` |
| REJECTED | Red/danger | Via rejection in approval |
| PAID | Blue/primary | Manual update |
| COMPLETED | Green/success | Manual update |

**Document Numbering:** `EXPS/{id}/{year}` (e.g., `EXPS/1/2026`). Generated in the form via `Utility::getLastId('Expense')`.

#### 11.1 Expenses Index

**Route:** `/expenses` (GET/POST)
**Page Title:** "Expenses"
**Controller:** `ExpenseController@index`

Lists all expenses with filtering, export, and inline CRUD via `handleCrud()`.

**Add Button:** "New Expenses" (requires `Add Expense` permission)

**Filter Bar:**

| Filter | Type | Default | Options |
|--------|------|---------|---------|
| Start | Date picker | Today | Date |
| End | Date picker | Today | Date |
| Category | Dropdown | All | All + expense categories (ADMINISTRATION EXPENSES, FINANCIAL EXPENSES, DEPRECIATION EXPENSES) |
| Sub Category | Dropdown | All | All + expense sub-categories (11 options) |

Filter action: "Show" button → POSTs to `/expenses_search` for filtered results.

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Expense date |
| Expenses Sub Category | Sub-category name (e.g., "Current Expense", "Utilities") |
| Expenses Category | Parent category (e.g., "ADMINISTRATION EXPENSES") |
| Description | Free-text description |
| Amount | Formatted with commas (e.g., 358,900.00) |
| Attachment | "View" link if file attached, "No File" otherwise |
| Approvals | RingleSoft approval chain icons (System Admin, Managing Director) with status tooltips |
| Status | Color-coded badge (Created, Pending, Approved, etc.) |
| Actions | View (→ approval page), Edit (permission-gated), Delete (permission-gated) |

**Footer:** Running total of all displayed amounts.

**Sample Data:**

| Date | Sub Category | Category | Description | Amount |
|------|-------------|----------|-------------|--------|
| 2026-05-01 | Current Expense | ADMINISTRATION EXPENSES | January staff Groceries | 358,900.00 |
| 2026-02-01 | Fixed Expenses | ADMINISTRATION EXPENSES | Simoni Salary Advance | 150,000.00 |
| 2026-02-01 | Utilities | ADMINISTRATION EXPENSES | January Electricity Bill | 500,000.00 |
| 2026-02-01 | Miscellaneous | ADMINISTRATION EXPENSES | Site Supervisor Bus fare To Dodoma | 35,000.00 |

#### 11.2 Expense Create/Edit Form (Modal)

**Modal Title:** "Create New Expenses"
**Triggered by:** `loadFormModal('expense_form', {className: 'Expense'}, 'Create New Expenses', 'modal-md')`

**Form Fields:**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Expense Sub Category | Dropdown | Yes | All `ExpensesSubCategory` records. Category derived from selection. |
| Description | Text | Yes | Free-text description |
| Amount | Number (with JS formatting) | Yes | Step 0.01, comma-formatted display |
| Date | Date picker | Yes | |
| File | File upload | No | Server-side: png/jpg/jpeg/csv/txt/xls/xlsx/doc/docx/pdf, max 4MB |

**Hidden fields (create):** `document_number`, `document_id`, `document_type_id = 5`, `link`
**Submit:** `name="addItem" value="Expense"` (create) / `name="updateItem"` (edit)

Note: Only the sub-category is selected — the parent category is automatically derived via the `expensesSubCategory.expensesCategory` relationship.

#### 11.3 Expense Approval Page

**Route:** `/expense/{id}/{document_type_id}` (GET/POST), where `document_type_id = 5`
**Controller:** `ExpenseController@expense`

Detail view with company letterhead and approval workflow.

**Header:** Company logo, name, address, document label "Expenses", document number (e.g., "No. EXPS/1/2026"), created time.

**Detail Fields:**

| Field | Value |
|-------|-------|
| Expense Category | Parent category name |
| Expense Sub Category | Sub-category name |
| Description | Full description text |
| Amount | Formatted amount |
| Date | Expense date |
| Status | Color-coded badge (PENDING=yellow, APPROVED=blue, REJECTED=red) |
| Uploaded File | Link to attachment |

**Approval Flow Section:** Shows current approval status ("IN PROGRESS"), approval chain, and approve/reject buttons for authorized users.

#### 11.4 Expense Categories (Settings)

**Route:** `/settings/expenses_categories` (GET/POST)
**Page Title:** "EXPENSES CATEGORIES"
**Controller:** `SettingsController@expenses_categories`

Manage top-level expense categories. CRUD via `handleCrud()`.

**Add Button:** "New Expense Category"

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Name | Category name |
| Actions | Edit, Delete |

**Pre-configured Categories (3):**

| # | Name |
|---|------|
| 1 | ADMINISTRATION EXPENSES |
| 2 | FINANCIAL EXPENSES |
| 3 | DEPRECIATION EXPENSES |

**Form (Modal):** Single field: Name (text, required).

**Finance Integration (hardcoded category IDs):**
- Category ID 1 (ADMINISTRATION) → `Expense::getTotalAdministrativeExpenses()`
- Category ID 2 (FINANCIAL) → `Expense::getTotalFinancialCharges()`
- Category ID 3 (DEPRECIATION) → `Expense::getTotalDepreciation()`

#### 11.5 Expense Sub Categories (Settings)

**Route:** `/settings/expenses_sub_categories` (GET/POST)
**Page Title:** "EXPENSES SUB CATEGORIES"
**Controller:** `SettingsController@expenses_sub_categories`

Manage sub-categories under parent categories, with the `is_financial` flag controlling financial report inclusion.

**Add Button:** "New Expense Category" (button text reuses category label)

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Expenses Sub Category | Sub-category name |
| Expenses Category | Parent category name |
| IS Deducted | YES/NO flag (`is_financial` field) |
| Actions | Edit, Delete |

**Pre-configured Sub Categories (11):**

| # | Sub Category | Parent Category | IS Deducted |
|---|-------------|----------------|-------------|
| 1 | SITE EXPENSES | ADMINISTRATION EXPENSES | NO |
| 2 | Current Expense | ADMINISTRATION EXPENSES | YES |
| 3 | Stationaries | ADMINISTRATION EXPENSES | NO |
| 4 | Miscellaneous | ADMINISTRATION EXPENSES | NO |
| 5 | Utilities | ADMINISTRATION EXPENSES | NO |
| 6 | Communication | ADMINISTRATION EXPENSES | NO |
| 7 | Transfer Charges | FINANCIAL EXPENSES | NO |
| 8 | Maintenance Fee | FINANCIAL EXPENSES | NO |
| 9 | Fixed Expenses | ADMINISTRATION EXPENSES | NO |
| 10 | Current Expense | ADMINISTRATION EXPENSES | NO |
| 11 | interim statement charge | FINANCIAL EXPENSES | NO |

**Form (Modal):**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Expenses Sub Category | Text | Yes | Sub-category name |
| Expense Category | Dropdown | Yes | Parent category (3 options) |
| is Deducted | Dropdown | Yes | YES / NO (maps to `is_financial` field) |

**The `is_financial` flag:** When set to `YES`, expenses in this sub-category are included in `Expense::getTotalExpensesGroupBySubExpensesCategoryOnlyFinancial()` — used in financial statement calculations. Labeled "IS Deducted" in the UI.

#### 11.6 Expense Reports

**Routes (under `/reports/`):**

| Report | Route | Description |
|--------|-------|-------------|
| Expenses Report | `/reports/expenses_report` | All expenses by date range |
| Expenses Per System Report | `/reports/expenses_per_system_report` | Expenses grouped by system |
| Expenses Categories Report | `/reports/expenses_categories_report` | Totals by category |
| Expenses Sub Categories Report | `/reports/expenses_sub_categories_report` | Totals by sub-category |
| Annual Expenses Summary | `/reports/annually_expenses_summary_report` | Year-over-year summary |
| Annual Sub Categories Summary | `/reports/annually_expense_sub_categories_summary_report` | Sub-category annual summary |

**Static Aggregation Methods on `Expense` model (used by finance reports):**

| Method | Description | Status Filter |
|--------|-------------|---------------|
| `getTotalAdministrativeExpenses($start, $end)` | Sum where category_id = 1 | APPROVED |
| `getTotalFinancialCharges($start, $end)` | Sum where category_id = 2 | None (potential issue) |
| `getTotalDepreciation($start, $end)` | Sum where category_id = 3 | APPROVED |
| `getTotalExpense($start, $end)` | Sum of all expenses | APPROVED |
| `getTotalExpensesGroupByExpensesCategory(...)` | Grouped by category | APPROVED |
| `getTotalExpensesGroupBySubExpensesCategory(...)` | Grouped by sub-category | APPROVED |
| `getTotalExpensesGroupBySubExpensesCategoryOnlyFinancial(...)` | Only `is_financial = YES` | APPROVED |

#### 11.7 Related Systems

**Project Expenses (`ProjectExpense`):** Separate model at `/project_expenses` for project-level cost tracking. Fields: `project_id`, `cost_category_id`, `amount`, `description`, `expense_date`, `created_by`. No approval workflow. Permissions: `Project Costs`, `Add Project Cost`, `Edit Project Cost`, `Delete Project Cost`.

**Adjustment Expenses (`AdjustmentExpense`):** Minimal model at `/expense_adjustable` for finance-level manual adjustments. Fields: `date`, `amount` only. Used in financial reports for manual total adjustments. No categories, no approval.

**Mobile API:** The API `ExpenseController` (`Api/V1/ExpenseController`) operates on `ProjectExpense` (not the general `Expense` model). Supports: draft → pending → approved/rejected status flow with receipt image upload to `expense-receipts/` on public disk.

---

### 12. Labor Procurement

Complete lifecycle management for engaging skilled artisans (tradespeople) on construction projects — from initial work request through contract signing, daily work logging, quality inspections, and milestone-based payments. Artisans are managed via the `Supplier` model with `is_artisan = true` flag, sharing bank account fields with material suppliers.

**Sidebar Menu Items:**

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Labor Dashboard | `/labor/dashboard` | Labor Dashboard |
| 2 | Labor Requests | `/labor/requests` | Labor Requests |
| 3 | Labor Contracts | `/labor/contracts` | Labor Contracts |
| 4 | Work Logs | `/labor/logs` | Work Logs |
| 5 | Labor Inspections | `/labor/inspections` | Labor Inspections |
| 6 | Labor Payments | `/labor/payments` | Labor Payments |

**End-to-End Workflow:**

```
[1] Labor Request (LR-YYYY-NNNN) — draft → pending → approved
        │
        │ RingleSoft approval → approved_amount set
        ▼
[2] Labor Contract (LC-YYYY-NNNN) — draft → signed → active
        │
        │ Sign & Activate → creates 4 default payment phases
        │ Phase 1 (Mobilization) marked "due"
        ▼
[3] Work Logs — daily logging of progress, workers, hours, photos
        │
        │ 3-day edit/delete time lock
        ▼
[4] Labor Inspection (LI-YYYY-NNNN) — auto-submitted for approval
        │
        │ RingleSoft approval → if pass + linked phase → markAsDue()
        │ If final inspection pass → contract completed
        ▼
[5] Payment Phases — due → approved (MD) → processed (Finance)
        │
        │ Finance enters payment reference → paid
        │ Contract amount_paid recalculated
        ▼
    Contract completed when final inspection passes
```

**Document Numbering Formats:**

| Document | Prefix | Format | Example |
|----------|--------|--------|---------|
| Labor Request | `LR-` | `LR-YYYY-NNNN` | `LR-2026-0001` |
| Labor Contract | `LC-` | `LC-YYYY-NNNN` | `LC-2026-0001` |
| Labor Inspection | `LI-` | `LI-YYYY-NNNN` | `LI-2026-0001` |

**Approval Workflows (RingleSoft `Approvable`):**

| Model | Status After Approval | Key Side Effects |
|-------|----------------------|------------------|
| `LaborRequest` | `approved` | Sets `approved_amount` (falls back: approved → negotiated → proposed) |
| `LaborInspection` | `approved` | If pass + linked phase → `markAsDue()`; if final + pass → contract `completed` |

**Default Payment Phase Structure (4 phases):**

| Phase | Name | Percentage | Milestone Trigger |
|-------|------|------------|-------------------|
| 1 | Mobilization | 20% | Contract signed and work commences |
| 2 | Progress | 30% | 50% of work completed |
| 3 | Substantial | 30% | 90% of work completed |
| 4 | Final | 20% | Final inspection passed |

Custom phases can be defined during contract creation (percentage-to-amount auto-calculation).

**Core Data Models:**

```
LaborRequest (labor_requests)
├── project → Project
├── constructionPhase → ProjectBoqSection (top-level)
├── artisan → Supplier (is_artisan = true)
└── contract → LaborContract (hasOne)
    ├── paymentPhases → LaborPaymentPhase[] (hasMany, ordered by phase_number)
    ├── workLogs → LaborWorkLog[] (hasMany, ordered by log_date desc)
    └── inspections → LaborInspection[] (hasMany, ordered by inspection_date desc)
        └── paymentPhase → LaborPaymentPhase (nullable link)
```

**Artisan Model (Supplier fields for artisans):**

| Field | Description |
|-------|-------------|
| `is_artisan` | Boolean flag distinguishing artisans from material suppliers |
| `trade_skill` | e.g., Mason, Carpenter, Electrician, Plumber, Tiler, Painter |
| `daily_rate` | Reference rate in TZS (informational — contracts use lump-sum) |
| `id_number` | National ID or passport |
| `previous_work_history` | Free-text work history |
| `rating` | 1–5 star rating |
| `nmb_account`, `crdb_account`, `account_name` | Bank details for payment processing |

**AjaxController endpoints:**
- `GET /ajax/get_construction_phases?project_id=X` — Top-level BOQ sections for project
- `GET /ajax/get_artisans` — All artisans with trade_skill
- `GET /ajax/get_artisan_details?artisan_id=X` — Full artisan record

#### 12.1 Labor Dashboard

**Route:** `/labor/dashboard` (GET)
**Page Title:** "Labor Procurement Dashboard"
**Controller:** `LaborDashboardController@index`

Overview of labor procurement activity with KPI metrics, pending actions, and alerts.

**Project Filter:** Dropdown to filter all dashboard data by project.

**Quick Navigation Links:** Requests, Contracts, Payments

**KPI Summary Cards (4):**

| Card | Example Value | Sub-text |
|------|---------------|----------|
| Active Contracts | 3 | 9,600,000 TZS (total contract value) |
| Pending Requests | 3 | Awaiting approval |
| Payments Due | 2 | 1,300,000 TZS |
| Completed Contracts | 0 | 1,280,000 TZS paid |

**Actions Required Section (3 counts):**
- Requests Pending Approval
- Inspections Pending
- Payments Due

**Contracts Nearing End:** Contracts with `end_date` within 7 days. Shows contract number, artisan, days remaining.

**Overdue Contracts:** Contracts where `end_date < now()` and still active.

**Recent Activity (3 panels):**
- Recent Requests (5) — with priority and status badges
- Recent Contracts (3) — with status and % paid progress
- Recent Inspections (3) — with type, completion %, result, and status badges

**Training Guide:** `/labor/training-guide` — Generates and streams a PDF training guide via DomPDF.

#### 12.2 Labor Requests

**Route:** `/labor/requests` (GET/POST)
**Page Title:** "Labor Requests"
**Controller:** `LaborRequestController@index`

Request artisan labor for specific project work, with negotiation tracking and approval workflow.

**Add Button:** "+ New Request" → `/labor/requests/create` (full-page form)

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| From | Date | Default: first of month |
| To | Date | Default: today |
| Project | Dropdown | All Projects + project list |
| Status | Dropdown | All Statuses, Draft, Pending, Approved, Rejected, Contracted |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Request # | Auto-numbered (e.g., LR-2026-0008) + date |
| Project | Project name |
| Artisan | Artisan name + trade skill (e.g., "John Mfundi / Mason") |
| Work Description | Truncated description |
| Amount | Proposed amount (TZS formatted) |
| Duration | Estimated days |
| Status | Badge: Draft (gray), Pending (yellow), Approved (green) + contract link, Rejected (red) |
| Actions | View, Edit (draft only), Delete (draft only), Approval page |

**Create Form (full page):**

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Project | Select2 dropdown | Yes | AJAX loads construction phases on change |
| Construction Phase | Select2 dropdown | No | Top-level BOQ sections for selected project |
| Artisan | Select2 dropdown | No | Can be assigned later. Helper: "Can be assigned later before approval" |
| Work Location | Text | No | e.g., "Block A, Ground Floor" |
| Work Description | Textarea | Yes | Min 10 chars. Detailed scope |
| Duration (Days) | Number spinner | No | Estimated duration |
| Expected Start Date | Date | No | |
| Expected End Date | Date | No | |
| Currency | Dropdown | No | TZS (default), USD |
| Proposed Amount | Text (comma formatted) | Yes | Amount in selected currency |
| Negotiated Amount | Text | No | Helper: "Fill after negotiation with artisan" |
| Materials Included in Price | Checkbox | No | Whether artisan supplies materials |
| Payment Terms | Textarea | No | e.g., "20% mobilization, 30% at 50%, 30% at 90%, 20% final" |
| Artisan Assessment Notes | Textarea | No | Notes from site visit |

**Negotiation:** At any point before contracted status, `negotiated_amount` and `artisan_assessment` can be updated via dedicated endpoint. The `final_amount` computed attribute prioritizes: `approved_amount → negotiated_amount → proposed_amount`.

**Submit for Approval:** Requires artisan to be assigned. Sets status to `pending`, triggers RingleSoft notification workflow.

**Approval Detail Page:** `/labor/requests/{id}/{document_type_id}` — Shows all request fields in structured layout. RingleSoft approve/reject UI.

#### 12.3 Labor Contracts

**Route:** `/labor/contracts` (GET/POST)
**Page Title:** "Labor Contracts"
**Controller:** `LaborContractController@index`

Formal contracts with artisans, created from approved labor requests. No direct creation — contracts always originate from a request.

**Banner:** "Requests Ready for Contract" — lists approved requests without contracts, with "Create Contract" button for each.

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Project | Dropdown | All Projects + project list |
| Status | Dropdown | All Statuses, Draft, Active, On Hold, Completed, Terminated |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Contract # | Auto-numbered (e.g., LC-2026-0001) + date |
| Project | Project name |
| Artisan | Artisan name + trade skill |
| Duration | Start–End date range + days remaining (green) or overdue (red) |
| Total | Contract total amount (TZS) |
| Payment Progress | Visual progress bar + percentage + "paid / total" text |
| Status | Badge: Draft, Active (green), On Hold (yellow), Completed (blue), Terminated (red) |
| Actions | View, PDF download |

**Create Contract Form:** `/labor/contracts/create/{request_id}`
- Pre-filled from labor request (project, artisan, amount, scope)
- Fields: Supervisor (Site Supervisor users), Start/End dates, Scope of Work (textarea), Terms & Conditions (textarea)
- **Payment Phases Section:** Editable table with JS percentage-to-amount auto-calculation:

| Column | Description |
|--------|-------------|
| Phase # | Auto-numbered |
| Phase Name | Text (e.g., Mobilization, Progress) |
| Description | Milestone description |
| Percentage | Number (must total 100%) |
| Amount | Auto-calculated: `(percentage/100) × total_amount` |

Default 4 phases (20/30/30/20%). Can be customized.

**Contract Detail Page (show):**
- Contract info, payment phases table, recent work logs (10), inspections table
- Artisan details sidebar (trade skill, rating, phone, bank accounts)
- Financial summary sidebar (total, paid, balance, payment progress bar)
- **Actions:** Sign & Activate, Put on Hold, Resume, Terminate, Download PDF

**Sign & Activate:** Sets `artisan_signature` and `supervisor_signature` from user profile file fields. Optional signed contract file upload (`uploads/labor_contracts/`). Changes status to `active`. Marks Phase 1 (Mobilization) as `due`.

**Contract PDF:** DomPDF template with contract terms, scope, payment schedule. Downloaded as `Labor_Contract_{number}.pdf`.

**Contract Lifecycle:**
- `draft → active` (via sign)
- `active → on_hold` (put on hold with reason)
- `on_hold → active` (resume)
- `active → completed` (when final inspection passes)
- `active → terminated` (with reason, min 10 chars; all pending phases put on `held`)

**Model:** `LaborContract` — Auto-calculates `balance_amount = total_amount - amount_paid` on every save. `updatePaymentTotals()` recalculates `amount_paid` from all paid phases. Computed: `payment_progress`, `latest_progress` (from work logs), `days_remaining`, `days_overdue`.

#### 12.4 Work Logs

**Route:** `/labor/logs` (GET/POST)
**Page Title:** "Work Logs"
**Controller:** `LaborWorkLogController@index`

Daily work logging for active contracts. Captures progress, workers, hours, photos, materials, weather. Created from contract detail page (no standalone "New" button on list page).

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Start Date | Date | Default: first of month |
| End Date | Date | Default: today |
| Project | Dropdown | All Projects + project list |
| Contract | Dropdown | All Contracts + active/on-hold contracts |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Date | Log date |
| Contract | Contract number (link) |
| Artisan | Artisan name |
| Work Done | Truncated work description |
| Workers | Number of workers present |
| Hours | Hours worked (decimal) |
| Progress | Cumulative progress % with color badge (green ≥60%, yellow <60%) |
| Logged By | User who created the log |
| Actions | View, Edit (if ≤3 days old), Delete (if ≤3 days old) |

**Create Form:** `/labor/logs/create/{contract_id}` — Only for active or on-hold contracts.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Log Date | Date | Yes | Auto-set to today |
| Work Done | Textarea | Yes | Min 10 chars |
| Workers Present | Number | Yes | Min 1 |
| Hours Worked | Decimal | No | |
| Progress Percentage | Decimal | No | Cumulative 0–100% |
| Challenges | Textarea | No | Issues encountered |
| Materials Used | JSON | No | Structured material entries |
| Photos | Multiple file upload | No | Stored to `uploads/labor_logs/` |
| Weather Conditions | Dropdown | No | Sunny, Cloudy, Rainy, Stormy |
| Notes | Textarea | No | |

**3-Day Time Lock:** Work logs older than 3 days cannot be edited or deleted (enforced in controller).

**Contract Timeline:** `/labor/contracts/{contract_id}/logs` — Paginated (20/page) timeline view of all logs for one contract, ordered by date desc.

#### 12.5 Labor Inspections

**Route:** `/labor/inspections` (GET/POST)
**Page Title:** "Labor Inspections"
**Controller:** `LaborInspectionController@index`

Quality inspections of artisan work, linked to contract payment phases. Approval triggers payment phase progression.

**Two Sections:**

**Section 1: "Contracts Ready for Inspection"**
Shows active contracts with pending payment phases. Each row has a "Create Inspection" button.

| Column | Description |
|--------|-------------|
| Contract # | Contract number |
| Project | Project name |
| Artisan | Artisan name |
| Current Progress | Latest work log progress % |
| Action | "Create Inspection" button |

**Section 2: "All Inspections"**

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Start Date | Date | Date range |
| End Date | Date | |
| Project | Dropdown | All Projects + project list |
| Status | Dropdown | All Statuses, Draft, Pending, Verified, Approved, Rejected |

**Export:** Print, Excel, PDF

**DataTable Columns:**

| Column | Description |
|--------|-------------|
| # | Row number |
| Inspection # | Auto-numbered (e.g., LI-2026-0001) + date |
| Contract | Contract number |
| Artisan | Artisan name |
| Type | Badge: Progress (blue), Milestone (yellow), Final (green) |
| Completion | Completion percentage |
| Quality | Badge: Excellent (teal), Good (green), Acceptable (blue), Poor (orange), Unacceptable (red) |
| Result | Badge: Pass (green), Conditional (yellow), Fail (red) — auto-computed |
| Status | Badge: Draft, Pending (orange), Verified (teal), Approved (green), Rejected (red) |
| Actions | View, Approval page |

**Create Form:** `/labor/inspections/create/{contract_id}` — Only for active contracts.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| Inspection Type | Dropdown | Yes | Progress, Milestone, Final |
| Payment Phase | Dropdown | No | Pending/due phases for this contract |
| Inspection Date | Date | Yes | Auto-set to today |
| Completion Percentage | Number | Yes | 0–100% |
| Work Quality | Dropdown | Yes | Excellent, Good, Acceptable, Poor, Unacceptable |
| Scope Compliance | Checkbox | Yes | Default: true |
| Defects Found | Textarea | No | |
| Rectification Required | Checkbox | No | Default: false |
| Rectification Notes | Textarea | No | |
| Photos | Multiple file upload | No | Stored to `uploads/labor_inspections/` |
| Notes | Textarea | No | |

**Auto-Result Determination (on save):**
- `unacceptable` quality OR `scope_compliance = false` → **fail**
- `poor` quality OR `rectification_required = true` → **conditional**
- Otherwise → stored result used as-is

**Approval cascade:**
1. If `result = pass` AND linked `paymentPhase` → `paymentPhase->markAsDue()` (pending → due)
2. If `inspection_type = final` AND `result = pass` → contract set to `completed`, `actual_end_date = now()`

#### 12.6 Labor Payments

**Route:** `/labor/payments` (GET)
**Page Title:** "Labor Payments"
**Controller:** `LaborPaymentController@index`

Milestone-based payment processing with three-step workflow: Due → Approved (MD) → Processed (Finance). Payment phases are auto-generated from contracts, not created manually.

**KPI Summary Cards (4):**

| Card | Count | Amount |
|------|-------|--------|
| Pending | 8 | — |
| Due for Approval | 2 | 1,300,000 TZS |
| Approved | 0 | 0 TZS |
| Paid | 2 | 1,280,000 TZS |

**Filter Bar:**

| Filter | Type | Options |
|--------|------|---------|
| Project | Dropdown | All Projects + project list |
| Contract | Dropdown | All Contracts + contract list |
| Status | Dropdown | All Statuses, Pending, Due, Approved, Paid, Held |

**Export:** Print, Excel, PDF

**DataTable Columns (ordered by status priority: due → approved → pending → held → paid):**

| Column | Description |
|--------|-------------|
| # | Row number |
| Contract | Contract number |
| Project | Project name |
| Artisan | Artisan name |
| Phase | **Phase Name** (bold) + percentage + milestone description |
| Amount | Phase amount (TZS) |
| Status | Badge: Pending (gray), Due (orange), Approved (green), Paid (green) + date + reference, Held (red) |
| Actions | Context-dependent (see below) |

**Actions by Status:**
- **Due** → "Approve" button (MD action)
- **Approved** → "Process" button (Finance action)
- **Pending** → "Awaiting" (no action available)
- **Paid** → Checkmark + payment reference number
- **Held** → "Release" button

**Payment Approval (MD):** `POST /labor/payments/{phase_id}/approve` — Phase must be `due`. Sets status to `approved`.

**Payment Processing (Finance):** `/labor/payments/{phase_id}/process` (GET=form, POST=process)
- Shows artisan bank details sidebar (NMB account, CRDB account, phone for mobile money)
- Contract summary sidebar
- Fields: Payment Reference (required, max 100 chars — bank transfer ref, cheque number, mobile money ID), Notes

On processing: sets `status = paid`, `paid_at`, `paid_by`, `payment_reference`, then calls `contract->updatePaymentTotals()` to recalculate `amount_paid` and `balance_amount`.

**Bulk Approve:** `POST /labor/payments/bulk-approve` — Accepts array of `phase_ids`, approves all eligible phases in one request.

**Hold/Release:** Any non-paid phase can be put on hold (requires reason, min 10 chars). Held phases can be released back to `due`.

**Payment Report:** `/labor/payments/report` — Paid phases filtered by date range and optional project. Groups by project and artisan with counts and totals.

**Contract Payments View:** `/labor/payments/contract/{contract_id}` — All phases for one contract with payment history.

**Payment Phase Status Lifecycle:**

```
pending → due (triggered by inspection approval or contract signing for Phase 1)
    due → approved (MD approves)
        approved → paid (Finance processes with reference)
    due → held (with reason)
        held → due (release)
pending → held (contract terminated — all pending phases put on held)
```

---

### 13. eSMS

SMS messaging module for sending individual and bulk text messages to staff, clients, and external contacts via an external SMS gateway.

#### Sidebar Menu

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Messages | `/eSMS` | eSMS |

> Single-page module with no sub-pages. All functionality is accessed via modal forms on the Messages page.

---

#### 13.1 Messages Page

**Route:** `GET /eSMS`
**Controller:** `MessageController@index`
**Permission:** `eSMS`

Main page showing all sent messages in a DataTable with two action buttons for composing new messages.

**Page Layout:**
- Heading: "Messages"
- Two action buttons in header:
  - **"New Message"** — opens single-recipient message form modal
  - **"Bulk SMS"** — opens bulk/department-based message form modal
- DataTable listing all sent messages

**DataTable Columns:**

| # | Column | Description |
|---|--------|-------------|
| 1 | # | Row number |
| 2 | Name | Recipient name |
| 3 | Phone Number | Recipient phone number |
| 4 | Message | Message text content |
| 5 | Actions | Edit / Delete buttons |

**Data Source:** `Message::all()` — no filtering, pagination, or search beyond DataTables client-side features. All messages are loaded at once.

---

#### 13.2 New Message Form (Single Recipient)

**Modal:** Opened via "New Message" button
**Form ID:** `message_form`
**Submit Action:** `addItem` (value=`Message`)

**Form Fields:**

| # | Field | Type | Required | Notes |
|---|-------|------|----------|-------|
| 1 | Name | text | Yes | Recipient display name |
| 2 | Phone Number | text | Yes | Recipient phone number |
| 3 | Message | textarea | Yes | SMS body text |

**On Submit:**
1. Message record created in `messages` table via `handleCrud()`
2. SMS sent via gateway using `Utility::sendSingleDestination($phone, $message)`
3. Phone number normalized before sending (see 13.4)

---

#### 13.3 Bulk SMS Form (Department-Based)

**Modal:** Opened via "Bulk SMS" button
**Route:** `POST /bulk_sms`
**Controller:** `MessageController@bulk_sms`

**Form Fields:**

| # | Field | Type | Required | Options |
|---|-------|------|----------|---------|
| 1 | Department | select dropdown | Yes | All Departments, Technical, Finance, Sales, Human Resource, Procurement, Management |
| 2 | Message | textarea | Yes | SMS body text |

**Department Values (hardcoded in form):**

| Value | Label |
|-------|-------|
| `all` | All Departments |
| `technical` | Technical |
| `finance` | Finance |
| `sales` | Sales |
| `human resource` | Human Resource |
| `procurement` | Procurement |
| `management` | Management |

**On Submit:**
1. Controller queries `Employee` records filtered by selected department (or all if "all")
2. Collects phone numbers from employee records
3. Sends SMS to all recipients via `Utility::sendSingleMessageMultipleDestination($phones, $message)`
4. Individual `Message` records are created for each recipient for history tracking

---

#### 13.4 SMS Gateway Integration

**Gateway Provider:** messaging-service.co.tz (Tanzania SMS gateway)
**Integration Location:** `app/Helpers/Utility.php`

**Two Gateway Methods:**

| Method | Purpose | API Endpoint |
|--------|---------|-------------|
| `sendSingleDestination($phone, $message)` | Send to one recipient | `POST /api/sms/v1/text/single` |
| `sendSingleMessageMultipleDestination($phones, $message)` | Send same message to multiple recipients | `POST /api/sms/v1/text/multi` |

**API Configuration (hardcoded in Utility.php):**

| Parameter | Value |
|-----------|-------|
| API Host | `https://messaging-service.co.tz` |
| API Key | Hardcoded in source |
| Secret Key | Hardcoded in source |
| Sender ID | `LERUMA ENT` |
| Source Address | `LERUMA ENT` |

**Phone Number Normalization:**
Before sending, phone numbers are normalized with the following logic:
- If starts with `0` → replace leading `0` with `255` (Tanzania country code)
- If starts with `+` → remove the `+` prefix
- Otherwise → use as-is

**Request Payload (Single):**
```json
{
  "from": "LERUMA ENT",
  "to": "255XXXXXXXXX",
  "text": "Message content"
}
```

**Request Payload (Multi):**
```json
{
  "from": "LERUMA ENT",
  "to": ["255XXXXXXXXX", "255YYYYYYYYY"],
  "text": "Message content"
}
```

**Authentication:** API key and secret key sent as HTTP headers.

**Limitations:**
- No delivery status tracking or callbacks
- No message scheduling
- No character count / SMS segment calculation
- No retry logic on gateway failure
- Credentials hardcoded (not in `.env`)
- Sender ID is company-specific ("LERUMA ENT"), not configurable

---

#### 13.5 Data Model

**`messages` table:**

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Recipient name |
| phone | string | Recipient phone number |
| message | text | SMS body content |
| created_at | timestamp | When message was sent |
| updated_at | timestamp | Last update |

**Model:** `App\Models\Message`
- No relationships to other models
- No status field (all records are assumed "sent")
- No `user_id` or `sent_by` field — no tracking of who sent the message
- No delivery confirmation or read receipt fields

---

#### 13.6 Routes

| Method | URI | Controller Method | Purpose |
|--------|-----|-------------------|---------|
| GET | `/eSMS` | `MessageController@index` | Messages list page |
| POST | `/bulk_sms` | `MessageController@bulk_sms` | Send bulk SMS by department |

> CRUD operations (create/edit/delete single messages) are handled via the standard `handleCrud()` base controller method, not dedicated routes.

---

#### 13.7 Permissions

| Permission | Purpose |
|------------|---------|
| eSMS | Access to the Messages page and all SMS functionality |

> Single permission controls all eSMS features. No granular permissions for send vs. view vs. delete.

---

### 14. Provision Tax

Standalone module for recording provisional (estimated) tax payments made to the Tanzania Revenue Authority (TRA). Each entry records the payment date, amount, bank account used, debit reference number, and optional supporting attachment. Data feeds into the Statement of Comprehensive Income report for calculating net profit after provision.

#### Sidebar Menu

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Provision Tax | `/provision_tax` | Provision Tax |

> Single-page module — standalone sidebar item, not nested under VAT or Finance.

---

#### 14.1 Provision Taxes List Page

**Route:** `GET /provision_tax`
**Controller:** `ProvisionTaxController@index`
**Permission:** `Provision Tax`

**Page Layout:**
- Heading: "Provision Taxes"
- "New ProvisionTax" button (top right, teal gradient, `si si-plus` icon)
- Date filter bar with Start Date / End Date pickers + "Show" button
- DataTable with export buttons (Print, Excel, PDF)
- Footer row with sum total of all amounts

**Date Filter:**

| Field | Type | Default |
|-------|------|---------|
| Start Date | datepicker | Today (`date('Y-m-d')`) |
| End Date | datepicker | Today (`date('Y-m-d')`) |

The filter submits via POST to the same route. Controller queries: `ProvisionTax::where('date', '>=', $start_date)->where('date', '<=', $end_date)->get()`

**DataTable Columns:**

| # | Column | Source | Alignment | Notes |
|---|--------|--------|-----------|-------|
| 1 | # | `$loop->index + 1` | Center | Row number |
| 2 | Date | `$provision_tax->date` | Left | YYYY-MM-DD format |
| 3 | Debit Number | `$provision_tax->debit_number` | Left | Bank debit reference |
| 4 | Description | `$provision_tax->description` | Left | Optional notes |
| 5 | Amount | `number_format($provision_tax->amount, 2)` | Right | TZS, comma-formatted |
| 6 | Bank | `$provision_tax->bank->name` | Left | Related bank name |
| 7 | Attachment | File link or "No File" | Left | Links to uploaded file |
| 8 | Actions | Edit + Delete buttons | Center | 100px width |

**Footer:** Colspan=4 cell showing `number_format($sum, 2)` — running total of all displayed amounts.

**Action Buttons per Row:**

| Button | Icon | Action |
|--------|------|--------|
| Edit | `fa fa-pencil` (blue) | `loadFormModal('provision_tax_form', {className: 'ProvisionTax', id: ID}, 'Edit ProvisionTax', 'modal-md')` |
| Delete | `fa fa-times` (red) | `deleteModelItem('ProvisionTax', ID, 'provision_tax-tr-ID')` |

---

#### 14.2 Create / Edit Form Modal

**Form:** `provision_tax_form`
**Modal Size:** `modal-md`
**Encoding:** `multipart/form-data` (supports file upload)

**Create trigger:** `loadFormModal('provision_tax_form', {className: 'ProvisionTax'}, 'Create New ProvisionTax', 'modal-md')`
**Edit trigger:** `loadFormModal('provision_tax_form', {className: 'ProvisionTax', id: ID}, 'Edit ProvisionTax', 'modal-md')`

**Form Fields:**

| # | Field | Name | Type | Required | Default | Notes |
|---|-------|------|------|----------|---------|-------|
| 1 | Bank | `bank_id` | select dropdown | Yes | — | Options from `$banks` (all Bank records). Placeholder: "Select Bank" |
| 2 | Debit Number | `debit_number` | number | Yes | — | Bank debit reference number |
| 3 | Amount | `amount` | number (step=.01) | Yes | — | JS formats with commas on display, raw number on focus |
| 4 | Description | `description` | text | No | — | Optional notes |
| 5 | Date | `date` | datepicker | Yes | Today (`date('Y-m-d')`) | Format: YYYY-MM-DD |
| 6 | Choose file | `file` | file upload | No | — | Payment proof / attachment |

**Bank Dropdown Options (sample data):**

| Bank Name |
|-----------|
| CRDB BANK |
| NBC BANK |
| NMB BANK |
| AZANIA BANK |
| CASH IN HAND |

**Submit Buttons:**
- **Create mode:** `addItem` with `value="ProvisionTax"` → label "Submit"
- **Edit mode:** `updateItem` with hidden `id` field → label "Update" (with `si si-check` icon)

**JavaScript Behaviors:**
- Amount field has clone-based formatting: displays comma-separated number, switches to raw input on hover/focus
- Date fields initialized with jQuery datepicker (`format: 'yyyy-mm-dd'`)

---

#### 14.3 CRUD Operations

All CRUD operations handled via the standard `handleCrud()` base controller method:

| Operation | Trigger | Handler |
|-----------|---------|---------|
| Create | Form POST with `addItem=ProvisionTax` | `Controller::crudAdd($request, 'ProvisionTax')` |
| Update | Form POST with `updateItem` + hidden `id` | `Controller::crudUpdate($request, 'ProvisionTax', $id)` |
| Delete | JS `deleteModelItem('ProvisionTax', id, rowId)` | AJAX deletion via `handleCrud()` |

**File Upload Handling:**
- On create/update, if file is present: saved to `/storage/uploads/`, path stored in `file` column
- On the list page, file column shows "Attachment" link if file exists, "No File" otherwise

**No Approval Workflow:** Unlike other financial modules (VAT Payments, Expenses), Provision Tax entries are saved immediately without any RingleSoft approval process. No status field exists.

---

#### 14.4 Integration with Financial Reports

The Provision Tax module feeds into the **Statement of Comprehensive Income Report**.

**Static Method Used:**
```
ProvisionTax::Profit_From_Operating_Activities_After_Provision($start_date, $end_date)
```
- Returns: `SUM(amount)` for all records in the date range, or `0` if none
- Query: `ProvisionTax::where('date', '>=', $start_date)->where('date', '<=', $end_date)->select(DB::raw("SUM(amount) as total_amount"))->get()->first()['total_amount']`

**Report Line Items Using This Data:**

| Line Item | Calculation |
|-----------|-------------|
| Profit from Operating Activities After Provision | Sum of provision tax amounts in period |
| Net Profit | Profit After Taxation − Provision Tax Amount |

**Provision Report Page:**
- **Route:** `GET/POST /reports/provision_report`
- **Controller:** `ReportsController@provision_report`
- **Status:** View template exists but is **empty** (0 bytes). The report route is also commented out from the Reports index page. Query logic exists in the controller but renders a blank page.

---

#### 14.5 Data Model

**`provision_taxes` table:**

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint unsigned | No | auto-increment | Primary key |
| date | date | No | — | Payment date |
| amount | integer | No | — | Payment amount in TZS |
| description | text | Yes | NULL | Optional notes |
| file | varchar(255) | Yes | NULL | Path to uploaded attachment |
| bank_id | integer | No | — | FK → `banks.id` |
| debit_number | integer | Yes | NULL | Bank debit reference number |
| created_at | timestamp | Yes | NULL | Record creation time |
| updated_at | timestamp | Yes | NULL | Last update time |

**Model:** `App\Models\ProvisionTax`
- **Traits:** `HasFactory`
- **Fillable:** `date`, `amount`, `description`, `file`, `bank_id`, `debit_number`
- **No Approvable trait** — no approval workflow

**Relationships:**

| Relationship | Type | Related Model | Foreign Key |
|--------------|------|---------------|-------------|
| `bank()` | belongsTo | `Bank` | `bank_id` |

**Migrations (3):**

| Migration | Date | Change |
|-----------|------|--------|
| `2022_05_25_175958_create_provision_taxes_table` | 2022-05-25 | Base table: id, date, amount, description, file |
| `2022_08_08_083937_add_bank_id_to_provision_taxes_table` | 2022-08-08 | Added `bank_id` column |
| `2022_08_08_085617_add_debit_number_to_provision_taxes_table` | 2022-08-08 | Added `debit_number` column |

---

#### 14.6 Routes

| Method | URI | Controller | Method | Route Name |
|--------|-----|-----------|--------|------------|
| GET, POST | `/provision_tax` | `ProvisionTaxController` | `index` | `provision_tax` |
| GET, POST | `/reports/provision_report` | `ReportsController` | `provision_report` | `reports_provision_report` |

---

#### 14.7 Permissions

| Permission | Purpose |
|------------|---------|
| Provision Tax | Access to the Provision Tax page and all CRUD operations |

> Single permission controls all functionality. No granular add/edit/delete permissions.

---

#### 14.8 Key Differences from Other Tax/Finance Modules

| Feature | Provision Tax | VAT Payments | Expenses |
|---------|---------------|-------------|----------|
| Approval Workflow | No | Yes (RingleSoft) | Yes (RingleSoft) |
| Status Field | No | Yes | Yes |
| Document Number | No | Auto-generated | Auto-generated |
| Bank Account Link | Yes (`bank_id`) | Yes | No |
| File Attachment | Yes | Yes | Yes |
| Project Link | No | No | Optional |
| Menu Location | Standalone | Under VAT | Under Expenses |

---

### 15. Reports

Centralized reporting hub with 64+ report routes aggregating data across all modules. The main landing page displays ~32 active report tiles in a searchable grid. Reports are organized into categories: VAT/Tax, Sales & Purchases, Attendance, Payroll/Statutory, Financial Statements, EFD, Profit/Expense Summaries, and 16 Annually Summary reports. Each report typically features date range filters, DataTables with export buttons (Print, Excel, PDF), and summary footer rows.

**Controller:** `ReportsController` (1,309 lines, 60+ methods)
**Models Referenced:** 25+ models across the system

#### Sidebar Menu

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Reports | `/reports` | Reports |

> Single sidebar item leading to the Reports index page. Individual reports are accessed from the index grid, not as separate sidebar items. Three statutory reports are additionally linked from the Statutory sidebar section.

---

#### 15.1 Reports Index Page

**Route:** `GET /reports`
**Controller:** `ReportsController@index`
**View:** `pages.reports.reports_index`

**Page Layout:**
- Heading: "Financial Analysis Related Reports" with gradient header
- Search bar for real-time filtering of report tiles
- Responsive grid: 6 columns (>1400px), 4 columns (1024-1400px), 3 columns (768-1024px), 2 columns (<768px)
- Each report card shows: document icon (SVG) + report name + link

**Permission Control:** Each tile checks `auth()->user()->can($item['name'])` — only reports the user has permission for are displayed.

**Active Report Tiles (32):**

| # | Report Name | Route |
|---|-------------|-------|
| 1 | Bank Statement | `/reports/bank_statement_report` |
| 2 | VAT Analysis | `/reports/vat_analysis_report` |
| 3 | VAT Payments | `/reports/vat_payments_report` |
| 4 | Exempt Analysis | `/reports/exempt_analysis_report` |
| 5 | Sales Report | `/reports/sales_report` |
| 6 | Purchases Report | `/reports/purchases_report` |
| 7 | Attendances Report | `/reports/attendances_report` |
| 8 | Daily Attendances Report | `/reports/daily_attendances_report` |
| 9 | Purchases By Supplier Report | `/reports/purchases_by_supplier_report` |
| 10 | Departments | Links to HR settings |
| 11 | Gross Summary Report | `/reports/gross_summary_report` |
| 12 | Deduction Report | `/reports/deduction_report` |
| 13 | Allowance Subscriptions Report | `/reports/allowance_subscriptions_report` |
| 14 | Statement of Comprehensive Income | `/reports/statement_of_comprehensive_income_report` |
| 15 | Statement of Financial Position | `/reports/statement_of_financial_position_report` |
| 16 | Detailed Expenditure Statement | `/reports/detailed_expenditure_statement_report` |
| 17 | EFD Report | `/reports/efd_report` |
| 18 | Detailed EFD Report | `/reports/detailed_efd_report` |
| 19 | Statutory Payment Report | `/reports/statutory_payment_report` |
| 20 | Annually Sales Summary | `/reports/annually_sales_summary_report` |
| 21 | Annually Purchases Summary | `/reports/annually_purchases_summary_report` |
| 22 | Annually Expenses Summary | `/reports/annually_expenses_summary_report` |
| 23 | Annually Expense Sub Categories Summary | `/reports/annually_expense_sub_categories_summary_report` |
| 24 | Annually Financial Charges Summary | `/reports/annually_financial_charges_summary_report` |
| 25 | Annually Salaries and Wages Summary | `/reports/annually_salaries_summary_report` |
| 26 | Annually SDL Summary | `/reports/annually_sdl_summary_report` |
| 27 | Annually Advance Salary Summary | `/reports/annually_advance_salary_summary_report` |
| 28 | Annually Allowance Summary | `/reports/annually_allowance_summary_report` |
| 29 | Annually HESLB Summary | `/reports/annually_heslb_summary_report` |
| 30 | Annually Net Salary Summary | `/reports/annually_net_salary_summary_report` |
| 31 | Annually NHIF Summary | `/reports/annually_nhif_summary_report` |
| 32 | Annually NSSF Summary | `/reports/annually_nssf_summary_report` |
| 33 | Annually Deduction Report | `/reports/annually_deduction_report` |
| 34 | Annually PAYE Summary | `/reports/annually_paye_summary_report` |
| 35 | Annually WCF Summary | `/reports/annually_wcf_summary_report` |

**Commented-Out Reports (19 additional routes exist but are hidden from the index):**
General Report, Auto Transaction Report, Business Position, Business Position Details, Supplier Credit, Transaction Movement, Expenses Report, Collection Report, Supplier Receiving, Supplier Transaction, Supplier Report, Collection Per System, Expenses Per System, Expenses Categories, Expenses Sub Categories, Bank Reconciliation, Bank Report, Supplier Bank Deposit, Statement Report, Supplier 2 Report, Provision Report, Commission Vs Deposit.

---

#### 15.2 VAT / Tax Reports

**15.2.1 VAT Analysis Report**

**Route:** `GET/POST /reports/vat_analysis_report`
**Controller:** `ReportsController@vat_analysis_report`

| Filter | Type | Default |
|--------|------|---------|
| Start Date | datepicker | First day of current month |
| End Date | datepicker | Last day of current month |

**Table Columns:** #, Attachment, Date, Supplier, VRN, Invoice, Invoice Date, Goods, Total, VAT EXC, VAT

**Inline Calculations (in view):**
- Queries `Sale`, `Purchase`, `AutoPurchase` models directly
- Calculates: TOTAL PURCHASES, TOTAL SALES, CURRENT VAT PAYABLE/(REFUND), CURRENT VAT PAYMENT, ACTUAL VAT PAYABLE/(REFUND), OLD VAT PAYABLE/(REFUND), TOTAL VAT PAYABLE/(REFUND)
- ~200+ lines of inline PHP for VAT calculations
- Company header with WAJENZI PROFESSIONAL COMPANY LIMITED, address, TIN

**15.2.2 VAT Payments Report**

**Route:** `GET/POST /reports/vat_payments_report`
**Table Columns:** #, Attachment, Date, Bank Name, Description, Amount

**15.2.3 Exempt Analysis Report**

**Route:** `GET/POST /reports/exempt_analysis_report`
**Table Columns:** #, Attachment, Date, Supplier, VRN, Invoice, Invoice Date, Goods, Total

---

#### 15.3 Sales & Purchases Reports

**15.3.1 Sales Report**

**Route:** `GET/POST /reports/sales_report`
**Data Source:** `Efd::all()` passed to view; view queries `Sale` model inline

| Filter | Type | Default |
|--------|------|---------|
| Start Date | datepicker | Today |
| End Date | datepicker | Today |
| EFD | dropdown | All EFDs |

**Table Columns:** #, Attachment, Date, EFD Name, Turnover, NET (A+B+C), Tax, Turnover (EX + SR)

**15.3.2 Purchases Report**

**Route:** `GET/POST /reports/purchases_report`
**Data Source:** `Supplier::all()`, Purchase types (VAT id=1, EXEMPT id=2)

| Filter | Type |
|--------|------|
| Start Date | datepicker |
| End Date | datepicker |
| Supplier | dropdown |
| Purchase Type | dropdown (VAT/EXEMPT) |

**Table Columns:** #, Attachment, Date, Supplier, VRN, Invoice, Invoice Date, Goods, Total, VAT EXC, VAT, Exempt

**15.3.3 Purchases By Supplier Report**

**Route:** `GET/POST /reports/purchases_by_supplier_report`
**Table:** Pivot table — one column per supplier, rows are dates, cells contain purchase amounts. Summary footer with totals per supplier.

---

#### 15.4 Attendance Reports

**15.4.1 Attendances Report (Date Range)**

**Route:** `GET/POST /reports/attendances_report`
**Controller Method:** `attendances_report()` (175 lines — most complex report)

| Filter | Type | Default |
|--------|------|---------|
| Start Date | date input | First day of current month |
| End Date | date input | Last day of current month |
| Department | dropdown | All departments |
| Search | text input | Name, email, or device ID |
| Per Page | number | 25 |
| Export | button | Export to Excel (CSV) |

**Overall Statistics Cards:**
- TOTAL USERS (count)
- TOTAL DAYS (date range span)
- AVG ATTENDANCE (percentage with badge)
- AVG PUNCTUALITY (percentage with badge)

**Table Columns:** #, Staff Name, Email, Department, Device ID, Present Days, Early Days, Late Days, Absent Days, Attendance Rate, then **one column per day** in the date range (e.g., "01 Sun", "02 Mon", etc.)

**Per-Day Cell Display:**
- Icon: check (on-time), clock (late), times (absent)
- Time: HH:MM format
- Color-coded: green (early/on-time), yellow (late), red (absent)

**Late Threshold:** Configurable via `settings('ATTENDANCE_LATE_THRESHOLD', '09:00:00')`

**Excel Export:** CSV format via `exportAttendanceToExcel()` method:
- `response()->stream()` with `fputcsv()`
- UTF-8 BOM for Excel compatibility
- Filename: `attendance_report_{start_date}_to_{end_date}.csv`
- Sections: report metadata, overall statistics, header row, data rows with daily status

**15.4.2 Daily Attendances Report (Single Day)**

**Route:** `GET/POST /reports/daily_attendances_report`

| Filter | Type | Default |
|--------|------|---------|
| Date | date input | Today |
| Attendance Type | dropdown | All types |
| Search | text input | Name, email, device ID |

**Daily Statistics:** Present count, On-time count, Late count, Absent count

**Table Columns:** #, Staff Name, Email, Department, Device ID, Check-in Time, Status (badge), Comment, Attachment

**Grouping:** Staff grouped by attendance type with separate table sections.

---

#### 15.5 Payroll / Statutory Reports (Monthly)

Ten monthly payroll reports follow the same pattern:

| Report | Route | Full Name |
|--------|-------|-----------|
| Net Salary | `/reports/net_report` | NET SALARY |
| PAYE | `/reports/paye_report` | PAY AS YOU EARN |
| NHIF | `/reports/nhif_report` | NHIF |
| NSSF | `/reports/nssf_report` | NATIONAL SOCIAL SECURITY FUND |
| WCF | `/reports/wcf_report` | WORKERS COMPENSATION FUND |
| SDL | `/reports/sdl_report` | SKILL & DEVELOPMENT LEVY |
| HESLB | `/reports/heslb_report` | HIGHER EDUCATION STUDENTS LOANS BOARD |
| Advance Salary | `/reports/advance_salary_report` | ADVANCE SALARY |
| Loan | `/reports/loan_report` | LOAN |
| Allowance | `/reports/allowance_report` | ALLOWANCE |

**Common Pattern for All 10:**

| Filter | Type | Default |
|--------|------|---------|
| Month | select (1-12) | Current month |
| Year | select | Current year |
| Staff | dropdown | All staff |

**Data Source:** `Payroll::getThisPayroll($month, $year)` + `Staff::onlyStaffs()`
**View Parameters:** `$this_month`, `$this_year`, `$payroll`, `$payroll_id`, `$staffs`

**Additional Payroll-Adjacent Reports:**

| Report | Route | Description |
|--------|-------|-------------|
| Deduction Report | `/reports/deduction_report` | Staff deduction details by month |
| Allowance Subscriptions | `/reports/allowance_subscriptions_report` | Staff allowance enrollment summary |
| Statutory Payment | `/reports/statutory_payment_report` | Sub-category payment breakdown by year |
| Statutory Category | `/reports/statutory_category_report` | Category-level yearly breakdown (12 monthly columns) |
| Statutory Schedules | `/reports/statutory_schedules_report` | Products/schedules with billing cycle, 12 monthly columns |

---

#### 15.6 Financial Statement Reports

**15.6.1 Statement of Comprehensive Income**

**Route:** `GET/POST /reports/statement_of_comprehensive_income_report`
**Controller:** Passes empty data array — all calculations done inline in the view.

**View Inline Calculations (referencing multiple models):**
- Revenue from sales (Sale model)
- Cost of sales / purchases (Purchase model)
- Operating expenses by category (Expense, ExpensesSubCategory)
- Financial charges (FinancialChargeCategory)
- Provision tax: `ProvisionTax::Profit_From_Operating_Activities_After_Provision($start_date, $end_date)`
- Compares current year vs. prior year columns

**Report Line Items Include:**
- Revenue / Turnover
- Cost of Sales
- Gross Profit
- Operating Expenses (by sub-category)
- Profit from Operating Activities
- Financial Charges
- Profit Before Taxation
- Provision for Tax
- Net Profit

**15.6.2 Statement of Financial Position**

**Route:** `GET/POST /reports/statement_of_financial_position_report`
**Heading:** "Statement of Financial Position As At {date}"
**Table Columns:** NOTE, Current Year Amount, Prior Year Amount
**Line Items:** ~28 balance sheet items (assets, liabilities, equity)

**15.6.3 Detailed Expenditure Statement**

**Route:** `GET/POST /reports/detailed_expenditure_statement_report`
**Heading:** "Detailed Expenditure Statement For the Year ended {date}"
**Table Columns:** Current Year Amount, Prior Year Amount
**Line Items:** ~16 expenditure categories

---

#### 15.7 EFD (Electronic Fiscal Device) Reports

**15.7.1 EFD Report**

**Route:** `GET/POST /reports/efd_report`
**Data Source:** `Efd::all()`

| Filter | Type | Default |
|--------|------|---------|
| Start Date | datepicker | Today |
| End Date | datepicker | Today |
| EFD | dropdown | All |

**Table Columns:** #, Date, [EFD Machine Name columns], Total
**Layout:** Dual sections for purchases and sales with separate sub-tables.

**15.7.2 Detailed EFD Report**

**Route:** `GET/POST /reports/detailed_efd_report`
**Table Columns:** #, Date, [EFD Machine Name], Total, Exempt, VAT, Turnover, Total
**Additional detail:** Breaks down each EFD's figures into exempt, VAT, and turnover components.

---

#### 15.8 Profit / Expense Summary Reports

**15.8.1 Gross Profit Report**

**Route:** `GET/POST /reports/gross_summary_report`
**Data Source:** `Gross` model, `Supervisor` model
**Table Columns:** #, Date, Total Gross Profit

**15.8.2 Expenses Report**

**Route:** `GET/POST /reports/expenses_report`
**Data Source:** `Expense` model grouped by supervisor and date
**Table Columns:** #, Date, Total Expense

**15.8.3 Expenses Categories Report**

**Route:** `GET/POST /reports/expenses_categories_report`
**Data Source:** `Expense` + `ExpensesCategory` models
**Table Columns:** #, Date, [Category Name columns: ADMINISTRATION EXPENSES, FINANCIAL EXPENSES, DEPRECIATION EXPENSES, ...], Total Expense

**15.8.4 Expenses Sub Categories Report**

**Route:** `GET/POST /reports/expenses_sub_categories_report`
**Data Source:** `Expense` + `ExpensesSubCategory` models
**Table Columns:** #, Date, [Sub-category columns], Total

---

#### 15.9 Annually Summary Reports (16 Reports)

Sixteen reports follow an identical annual pattern — 12 monthly rows with year totals:

| # | Report | Route | Table Columns |
|---|--------|-------|---------------|
| 1 | Sales Summary | `/reports/annually_sales_summary_report` | Monthly, VAT Exclusive, Exempt, Turnover |
| 2 | Purchases Summary | `/reports/annually_purchases_summary_report` | Monthly, VAT Exclusive, Exempt, Turnover, Adjustment Expenses, Total |
| 3 | Expenses Summary | `/reports/annually_expenses_summary_report` | Monthly, [expense category columns], Total |
| 4 | Expense Sub Categories | `/reports/annually_expense_sub_categories_summary_report` | Monthly, [sub-category columns], Total |
| 5 | Financial Charges | `/reports/annually_financial_charges_summary_report` | Monthly, Total |
| 6 | Salaries and Wages | `/reports/annually_salaries_summary_report` | Monthly, Salary Amount |
| 7 | SDL Summary | `/reports/annually_sdl_summary_report` | Monthly, Amount SDL |
| 8 | Advance Salary | `/reports/annually_advance_salary_summary_report` | Monthly, Amount |
| 9 | Allowance Summary | `/reports/annually_allowance_summary_report` | Monthly, Amount |
| 10 | HESLB Summary | `/reports/annually_heslb_summary_report` | Monthly, Amount |
| 11 | Net Salary Summary | `/reports/annually_net_salary_summary_report` | Monthly, Amount |
| 12 | NHIF Summary | `/reports/annually_nhif_summary_report` | Monthly, Employer, Employee, Total |
| 13 | NSSF Summary | `/reports/annually_nssf_summary_report` | Monthly, Employer, Employee, Total |
| 14 | Deduction Report | `/reports/annually_deduction_report` | Monthly, [deduction type columns], Total |
| 15 | PAYE Summary | `/reports/annually_paye_summary_report` | Monthly, Amount |
| 16 | WCF Summary | `/reports/annually_wcf_summary_report` | Monthly, Amount |

**Common Pattern:**
- **Filter:** Year dropdown + "Show" button
- **Table:** 12 rows (January–December) + summary footer row
- **Data Source:** Various models queried with year filter, aggregated per month
- **Export:** Print, Excel, PDF buttons via DataTables

---

#### 15.10 Supplier / Transaction Reports (Commented Out)

These reports have routes and controller methods defined but are **commented out from the index page**. Some have missing Blade views:

| Report | Route | Status |
|--------|-------|--------|
| Supplier Report | `/reports/supplier_report` | View missing |
| Supplier Report Search | `/reports/supplier_report_search` | View missing |
| Supplier 2 Report | `/reports/supplier_2_report` | View missing |
| Supplier Receiving Report | `/reports/supplier_receiving_report` | View missing |
| Supplier Transaction Report | `/reports/supplier_transaction_report` | View missing |
| Supplier Bank Deposit Report | `/reports/supplier_bank_deposit_report` | View missing |
| Supplier Credit Report | `/reports/supplier_credit_report` | No route on index |
| Transaction Movement Report | `/reports/transaction_movement_report` | View missing |
| Transaction Movement Search | `/reports/transaction_movement_report_search` | View missing |
| Collection Report | `/reports/collection_report` | View missing |
| Collection Per System | `/reports/collection_per_system_report` | View missing |
| Bank Statement Report | `/reports/bank_statement_report` | View missing |
| Bank Report | `/reports/bank_report` | View missing |
| Bank Reconciliation Report | `/reports/bank_reconciliation_report` | View missing |
| Statement Report | `/reports/statement_report` | View missing |
| Commission Vs Deposit | `/reports/commission_vs_deposit_report` | View missing |
| Business Position | `/reports/business_position_report` | View missing |
| Business Position Details | `/reports/business_position_details_report` | View missing |
| General Report | `/reports/general_report` | View missing |
| Supervisor Report | `/reports/supervisor_report` | View missing |
| Auto Transaction Report | `/reports/auto_transaction_report` | Requires mysql2 connection |
| Provision Report | `/reports/provision_report` | View template empty (0 bytes) |

---

#### 15.11 Common Report UI Pattern

Most working reports share a consistent layout:

1. **Company Header:** WAJENZI PROFESSIONAL COMPANY LIMITED with address, phone, TIN
2. **"Back to Reports"** link button
3. **Date Filter Section:** Start Date / End Date (datepicker) or Year dropdown + "Show" button
4. **Export Buttons:** Print, Excel, PDF (via DataTables)
5. **DataTable:** With search, sorting, pagination
6. **Summary Footer:** Total/subtotal rows

**Export Capabilities:**
- **Print:** Browser print via DataTables
- **Excel:** DataTables Excel export (client-side)
- **PDF:** DataTables PDF export (client-side)
- **CSV:** Server-side streaming for attendance report only (`exportAttendanceToExcel()`)

---

#### 15.12 Routes Summary

**All Report Routes (64+):**

| Method | URI | Controller | Route Name |
|--------|-----|-----------|------------|
| GET, POST | `/reports` | `ReportsController@index` | `reports` |
| GET, POST | `/reports/vat_analysis_report` | `ReportsController@vat_analysis_report` | `reports_vat_analysis` |
| GET, POST | `/reports/exempt_analysis_report` | `ReportsController@exempt_analysis_report` | `reports_exempt_analysis` |
| GET, POST | `/reports/vat_payments_report` | `ReportsController@vat_payments_report` | `reports_vat_payment` |
| GET, POST | `/reports/statement_of_comprehensive_income_report` | `ReportsController@statement_of_comprehensive_income_report` | `reports_statement_of_comprehensive_income_report` |
| GET, POST | `/reports/detailed_expenditure_statement_report` | `ReportsController@detailed_expenditure_statement_report` | `reports_detailed_expenditure_statement_report` |
| GET, POST | `/reports/statement_of_financial_position_report` | `ReportsController@statement_of_financial_position_report` | `reports_statement_of_financial_position_report` |
| GET, POST | `/reports/statutory_payment_report` | `ReportsController@statutory_payment_report` | `reports_statutory_payment_report` |
| GET, POST | `/reports/statutory_category_report` | `ReportsController@statutory_category_report` | `reports_statutory_category_report` |
| GET, POST | `/reports/statutory_schedules_report` | `ReportsController@statutory_schedules_report` | `reports_statutory_schedules_report` |
| GET, POST | `/reports/net_report` | `ReportsController@net_report` | `reports_net_report` |
| GET, POST | `/reports/paye_report` | `ReportsController@paye_report` | `reports_paye_report` |
| GET, POST | `/reports/nhif_report` | `ReportsController@nhif_report` | `reports_nhif_report` |
| GET, POST | `/reports/nssf_report` | `ReportsController@nssf_report` | `reports_nssf_report` |
| GET, POST | `/reports/wcf_report` | `ReportsController@wcf_report` | `reports_wcf_report` |
| GET, POST | `/reports/sdl_report` | `ReportsController@sdl_report` | `reports_sdl_report` |
| GET, POST | `/reports/heslb_report` | `ReportsController@heslb_report` | `reports_heslb_report` |
| GET, POST | `/reports/loan_report` | `ReportsController@loan_report` | `reports_loan_report` |
| GET, POST | `/reports/advance_salary_report` | `ReportsController@advance_salary_report` | `reports_advance_salary_report` |
| GET, POST | `/reports/allowance_report` | `ReportsController@allowance_report` | `reports_allowance_report` |
| GET, POST | `/reports/sales_report` | `ReportsController@sales_report` | `reports_sales_report` |
| GET, POST | `/reports/purchases_report` | `ReportsController@purchases_report` | `reports_purchases_report` |
| GET, POST | `/reports/purchases_by_supplier_report` | `ReportsController@purchases_by_supplier_report` | `reports_purchases_by_supplier_report` |
| GET, POST | `/reports/attendances_report` | `ReportsController@attendances_report` | `reports_attendances_report` |
| GET, POST | `/reports/daily_attendances_report` | `ReportsController@daily_attendances_report` | `reports_daily_attendances_report` |
| GET, POST | `/reports/efd_report` | `ReportsController@efd_report` | `reports_efd_report` |
| GET, POST | `/reports/detailed_efd_report` | `ReportsController@detailed_efd_report` | `reports_detailed_efd_report` |
| GET, POST | `/reports/gross_summary_report` | `ReportsController@gross_summary_report` | `reports_gross_summary_report` |
| GET, POST | `/reports/deduction_report` | `ReportsController@deduction_report` | `reports_deduction_report` |
| GET, POST | `/reports/allowance_subscriptions_report` | `ReportsController@allowance_subscriptions_report` | `reports_allowance_subscriptions_report` |
| GET, POST | `/reports/expenses_report` | `ReportsController@expenses_report` | `reports_expenses_report` |
| GET, POST | `/reports/expenses_categories_report` | `ReportsController@expenses_categories_report` | `reports_expenses_categories_report` |
| GET, POST | `/reports/expenses_sub_categories_report` | `ReportsController@expenses_sub_categories_report` | `reports_expenses_sub_categories_report` |
| GET, POST | `/reports/annually_sales_summary_report` | `ReportsController@annually_sales_summary_report` | — |
| GET, POST | `/reports/annually_purchases_summary_report` | `ReportsController@annually_purchases_summary_report` | — |
| GET, POST | `/reports/annually_expenses_summary_report` | `ReportsController@annually_expenses_summary_report` | — |
| GET, POST | `/reports/annually_expense_sub_categories_summary_report` | `ReportsController@annually_expense_sub_categories_summary_report` | — |
| GET, POST | `/reports/annually_financial_charges_summary_report` | `ReportsController@annually_financial_charges_summary_report` | — |
| GET, POST | `/reports/annually_salaries_summary_report` | `ReportsController@annually_salaries_summary_report` | — |
| GET, POST | `/reports/annually_sdl_summary_report` | `ReportsController@annually_sdl_summary_report` | — |
| GET, POST | `/reports/annually_advance_salary_summary_report` | `ReportsController@annually_advance_salary_summary_report` | — |
| GET, POST | `/reports/annually_allowance_summary_report` | `ReportsController@annually_allowance_summary_report` | — |
| GET, POST | `/reports/annually_heslb_summary_report` | `ReportsController@annually_heslb_summary_report` | — |
| GET, POST | `/reports/annually_net_salary_summary_report` | `ReportsController@annually_net_salary_summary_report` | — |
| GET, POST | `/reports/annually_nhif_summary_report` | `ReportsController@annually_nhif_summary_report` | — |
| GET, POST | `/reports/annually_nssf_summary_report` | `ReportsController@annually_nssf_summary_report` | — |
| GET, POST | `/reports/annually_deduction_report` | `ReportsController@annually_deduction_report` | — |
| GET, POST | `/reports/annually_paye_summary_report` | `ReportsController@annually_paye_summary_report` | — |
| GET, POST | `/reports/annually_wcf_summary_report` | `ReportsController@annually_wcf_summary_report` | — |
| GET, POST | `/reports/provision_report` | `ReportsController@provision_report` | `reports_provision_report` |
| GET, POST | `/reports/general_report` | `ReportsController@general_report` | `reports_general_report` |
| GET, POST | `/reports/gross_summary_report` | `ReportsController@gross_summary_report` | `reports_gross_summary_report` |
| GET, POST | `/reports/collection_report` | `ReportsController@collection_report` | `reports_collection_report` |
| GET, POST | `/reports/collection_per_system_report` | `ReportsController@collection_per_system_report` | `reports_collection_per_system_report` |
| GET, POST | `/reports/supplier_report` | `ReportsController@supplier_report` | `reports_supplier_report` |
| GET, POST | `/reports/supplier_report_search` | `ReportsController@supplier_report_search` | `reports_supplier_report_search` |
| GET, POST | `/reports/supplier_receiving_report` | `ReportsController@supplier_receiving_report` | `reports_supplier_receiving_report` |
| GET, POST | `/reports/supplier_transaction_report` | `ReportsController@supplier_transaction_report` | `reports_supplier_transaction_report` |
| GET, POST | `/reports/transaction_movement_report` | `ReportsController@transaction_movement_report` | `reports_transaction_movement_report` |
| GET, POST | `/reports/bank_statement_report` | `ReportsController@bank_statement_report` | `reports_bank_statement_report` |
| GET, POST | `/reports/bank_report` | `ReportsController@bank_report` | `reports_bank_report` |
| GET, POST | `/reports/bank_reconciliation_report` | `ReportsController@bank_reconciliation_report` | `reports_bank_reconciliation_report` |
| GET, POST | `/reports/auto_transaction_report` | `ReportsController@auto_transaction_report` | `reports_auto_transaction_report` |
| GET, POST | `/reports/business_position_report` | `ReportsController@business_position_report` | `reports_business_position_report` |
| GET, POST | `/reports/business_position_details_report` | `ReportsController@business_position_details_report` | `reports_business_position_details_report` |
| GET, POST | `/reports/commission_vs_deposit_report` | `ReportsController@commission_vs_deposit_report` | `reports_commission_vs_deposit_report` |
| GET, POST | `/reports/supplier_bank_deposit_report` | `ReportsController@supplier_bank_deposit_report` | `reports_supplier_bank_deposit_report` |
| GET, POST | `/reports/statement_report` | `ReportsController@statement_report` | `reports_statement_report` |
| GET, POST | `/reports/supplier_2_report` | `ReportsController@supplier_2_report` | `reports_supplier_2_report` |

---

#### 15.13 Models Referenced by Reports

| # | Model | Used By Reports |
|---|-------|-----------------|
| 1 | Allowance | Allowance subscriptions, annually allowance summary |
| 2 | Attendance | Attendance reports (raw DB queries) |
| 3 | AttendanceType | Daily attendance report |
| 4 | AutoPurchase | VAT analysis (inline in view) |
| 5 | BankReconciliation | Bank reconciliation report |
| 6 | Category | Statutory category report |
| 7 | Collection | Collection report, collection per system |
| 8 | Deduction | Annually deduction report |
| 9 | Department | Attendance report filters |
| 10 | Efd | EFD reports, sales report, bank deposit |
| 11 | Expense | Expenses report, expenses categories, expenses sub-categories |
| 12 | ExpensesCategory | Expenses categories report |
| 13 | ExpensesSubCategory | Expenses sub-categories, annually expense sub-categories |
| 14 | FinancialChargeCategory | Annually financial charges summary |
| 15 | Gross | Gross summary report |
| 16 | Payroll | All 10 monthly payroll reports |
| 17 | Product | Statutory schedules report |
| 18 | ProvisionTax | Comprehensive income statement, provision report |
| 19 | Purchase | Purchases report, VAT analysis (inline) |
| 20 | Report | Auto transaction report (static helper methods) |
| 21 | Sale | Sales report, VAT analysis (inline) |
| 22 | Staff | Payroll reports, deduction report |
| 23 | SubCategory | Statutory payment report |
| 24 | Supervisor | Expenses, gross summary, collection reports |
| 25 | Supplier | Purchases, bank, transaction, supplier reports |
| 26 | System | Collection per system, expenses per system, transaction movement |
| 27 | TransactionMovement | Supplier receiving, supplier transaction |
| 28 | User | Attendance reports |
| 29 | VatAnalysis | VAT analysis (inline in view) |

---

#### 15.14 Permissions

| Permission | Purpose |
|------------|---------|
| Reports | Access to the Reports index page |
| Bank Statement | Bank statement report |
| VAT Analysis | VAT analysis report |
| VAT Payments | VAT payments report |
| Exempt Analysis | Exempt analysis report |
| Sales Report | Sales report |
| Purchases Report | Purchases report |
| Attendances Report | Attendance reports (both range and daily) |
| Daily Attendances Report | Daily attendance report |
| Purchases By Supplier Report | Purchases by supplier |
| Gross Summary Report | Gross profit summary |
| Deduction Report | Deduction report |
| Allowance Subscriptions Report | Allowance subscriptions |
| Statement of Comprehensive Income Report | Income statement |
| Statement of Financial Position Report | Balance sheet |
| Detailed Expenditure Statement Report | Expenditure detail |
| EFD Report | EFD report |
| Detailed EFD Report | Detailed EFD |
| Statutory Payment Report | Statutory payment |
| Annually * Summary Report | One permission per annually summary report (16 total) |

> Each report tile on the index page requires its own named permission. Permission names match the report display names exactly.

---

#### 15.15 Report Status Summary

| Status | Count | Description |
|--------|-------|-------------|
| Working with data | 35 | Renders correctly and displays data |
| PHP errors | 4 | Deduction, Comprehensive Income, Annually Expenses, Annually Deduction |
| View not found | 19 | Routes/controller exist but Blade templates missing |
| Empty/blank views | 3 | Provision, Expenses Per System, Expenses Sub Categories |
| Database config error | 1 | Auto Transaction (requires mysql2 connection) |
- Inventory reports
- Tax reports
- Custom date-range filtering

---

### 16. Settings

Centralized configuration hub with 50+ settings pages accessed from a single master grid at `/settings`. Covers user/role/permission management, HR & payroll configuration, financial reference data, project setup, BOQ template definitions, approval workflow configuration, and system-level key-value settings. All pages follow the standard CRUD modal pattern with DataTables.

**Controller:** `SettingsController` (57 methods) + `Billing\SettingsController` (8 methods)
**Total Settings Pages:** 50+ on the main hub + 5 finance settings under Finance sidebar

#### Sidebar Menu

| # | Menu Item | Route | Permission |
|---|-----------|-------|------------|
| 1 | Settings | `/settings` | Settings |

> Single sidebar link to the master settings hub. All 50+ sub-pages are accessed from the grid, not as individual sidebar items. Finance settings (Account Types, Charts of Accounts, etc.) appear under the Finance sidebar section instead.

---

#### 16.1 Settings Hub Page

**Route:** `GET /settings`
**Controller:** `SettingsController@index`
**Route Name:** `hr_settings`

**Page Layout:**
- Heading: "System Settings"
- Subtitle: "Configure Financial Analysis settings and modules"
- Search bar for real-time filtering of settings modules
- Grid of 50 card-links, each leading to a specific settings sub-page
- Each card shows: icon + module name + link

---

#### 16.2 Workflow & Approval Settings (4 Pages)

**16.2.1 Approval Flows**

**Route:** `GET/POST /settings/process_approval_flows`
**Table Columns:** #, Name, Approvable Type, Actions
**Sample Data:** 22 entries — one per approvable model (Project, Purchase, ProjectClient, Sale, VatPayment, Expense, StatutoryPayment, Payroll, AdvanceSalary, Loan, LeaveRequest, SiteVisit, PettyCashRefillRequest, ImprestRequest, SalesDailyReport, SiteDailyReport, ProjectMaterialRequest, QuotationComparison, MaterialInspection, LaborRequest, LaborInspection, ProjectBoq)
**Purpose:** Configures which model types go through RingleSoft approval workflows.
**Form Fields:** Name, Approvable Type (fully qualified model class name)

**16.2.2 Approval Flow Steps**

**Route:** `GET/POST /settings/process_approval_flow_steps`
**Table Columns:** #, Approval Name, Role, Action, Order, Actions
**Sample Data:** 43 entries
**Purpose:** Defines individual steps/roles in each approval flow (which role approves at what order).
**Form Fields:** Approval Flow (dropdown), Role (dropdown), Action (approve/reject), Order (integer)

**16.2.3 Approval Document Types (Legacy)**

**Route:** `GET/POST /settings/approval_document_types`
**Table Columns:** #, Date, Name, Description, Keyword, Actions
**Sample Data:** 14 entries
**Purpose:** Legacy approval system — defines document types that require approval.
**Form Fields:** Name, Description, Keyword

**16.2.4 Approval Levels (Legacy)**

**Route:** `GET/POST /settings/approval_levels`
**Table Columns:** #, Date, Approval Document Type, Order, Description, Status, Actions
**Sample Data:** 35 entries
**Purpose:** Legacy approval hierarchy levels for each document type.
**Form Fields:** Approval Document Type (dropdown), Order, Description, Status

---

#### 16.3 User & Access Management (7 Pages)

**16.3.1 Users**

**Route:** `GET/POST /settings/users`
**Table Columns:** #, (checkbox), Name, Email, Device ID, Address, Designation, Department, Attendance Type, Attendance Status, Type, Gender, Employee No., Date of Birth, Date of Job, National ID, TIN, Employment Type, Marital Status, Signature, Profile, Contract, Status, Actions
**Sample Data:** 18 active users
**Features:** Active/Inactive tabs, inline status toggle, comprehensive profile fields

**User Form Fields:**

| # | Field | Type | Required |
|---|-------|------|----------|
| 1 | Name | text | Yes |
| 2 | Email | email | Yes |
| 3 | Password | password | Yes (create only) |
| 4 | Phone | text | No |
| 5 | Address | text | No |
| 6 | Department | dropdown | No |
| 7 | Designation/Position | dropdown | No |
| 8 | Employee No. | text | No |
| 9 | Gender | dropdown | No |
| 10 | Date of Birth | datepicker | No |
| 11 | Date of Job | datepicker | No |
| 12 | National ID | text | No |
| 13 | TIN | text | No |
| 14 | Employment Type | dropdown | No |
| 15 | Marital Status | dropdown | No |
| 16 | Attendance Type | dropdown | No |
| 17 | Attendance Status | dropdown (Enabled/Disabled) | No |
| 18 | Type | dropdown | No |
| 19 | Device ID | text | No |
| 20 | Signature | file upload | No |
| 21 | Profile Photo | file upload | No |
| 22 | Contract | file upload | No |
| 23 | Role | multi-select | No |

**Status Toggle:** `POST /settings/toggle_user_status/{id}` — toggles user active/inactive without page reload.

**16.3.2 Roles**

**Route:** `GET/POST /settings/roles`
**Table Columns:** #, Name, Created at, Actions
**Sample Data:** 15 roles (Super Admin, Admin, Architect, Accountant, Project Manager, Site Engineer, Quantity Surveyor, etc.)
**Form Fields:** Name
**Special Features:**
- Role permissions assignment: `POST /settings/role_permissions` — multi-select permission checkboxes with cache invalidation
- Role users assignment: `POST /settings/role_users` — bulk assign users to role

**16.3.3 Permissions**

**Route:** `GET/POST /settings/permissions`
**Table Columns:** #, Name, Permission Type, Created at, Actions
**Sample Data:** 581 permissions (paginated, 200 per page)
**Form Fields:** Name, Permission Type (dropdown)
**Note:** Uses Spatie Laravel Permissions package. Permission names match menu/entity names.

**16.3.4 Positions**

**Route:** `GET/POST /settings/positions`
**Layout:** Card-based (not standard table)
**Purpose:** Job positions/titles in the organization
**Form Fields:** Name, Description

**16.3.5 Departments**

**Route:** `GET/POST /settings/departments`
**Table Columns:** #, Name, Actions
**Sample Data:** 7 departments (IT, Administration, HRA, Finance and Accounts, Sales and Marketing, Procurement, Technical)
**Form Fields:** Name

**16.3.6 User Groups**

**Route:** `GET/POST /settings/user_groups`
**Table Columns:** #, Date, Name, Keyword, Actions
**Sample Data:** 9 groups
**Form Fields:** Name, Keyword

**16.3.7 Assign User Groups**

**Route:** `GET/POST /settings/assign_user_groups`
**Table Columns:** #, Date, User, User Group, Actions
**Sample Data:** 5 assignments
**Form Fields:** User (dropdown), User Group (dropdown)

---

#### 16.4 HR & Payroll Settings (10 Pages)

**16.4.1 Staff Salaries**

**Route:** `GET/POST /settings/staff_salaries`
**Table Columns:** #, Name, Amount, Actions
**Sample Data:** 20 entries
**Form Fields:** Staff (dropdown), Amount (number)

**16.4.2 Advance Salary**

**Route:** `GET/POST /settings/advance_salaries`
**Table Columns:** #, Date, Name, Description, Amount, Approvals, Status, Actions
**Sample Data:** 2 entries
**Purpose:** Advance salary requests with RingleSoft approval workflow.
**Form Fields:** Staff (dropdown), Date, Amount, Description

**16.4.3 Staff Loans**

**Route:** `GET/POST /settings/staff_loans`
**Table Columns:** #, Date, Name, Deduction, Amount, Status, Approvals, Actions
**Sample Data:** 8 entries
**Purpose:** Employee loan records with deduction tracking and approval workflow.
**Form Fields:** Staff (dropdown), Date, Amount, Deduction (amount per payroll), Description

**16.4.4 Deductions**

**Route:** `GET/POST /settings/deductions`
**Table Columns:** #, Name, Nature, Abbreviation, Description, Registration Number, Actions
**Sample Data:** 6 entries (NSSF, NHIF, PAYE, SDL, WCF, HESLB)
**Form Fields:** Name, Nature, Abbreviation, Description, Registration Number

**16.4.5 Deduction Subscriptions**

**Route:** `GET/POST /settings/deduction_subscriptions`
**Table Columns:** #, Name, Deduction, Membership Number, Actions
**Sample Data:** 69 entries
**Purpose:** Enrolls employees into specific deduction schemes with membership numbers.
**Form Fields:** Staff (dropdown), Deduction (dropdown), Membership Number

**16.4.6 Deduction Settings**

**Route:** `GET/POST /settings/deduction_settings`
**Table Columns:** #, Deduction, Minimum Amount, Maximum Amount, Employee Percentage %, Employer Percentage %, Additional Amount, Actions
**Sample Data:** 12 entries
**Purpose:** Configures calculation rules for each deduction (percentages, min/max caps).
**Form Fields:** Deduction (dropdown), Minimum Amount, Maximum Amount, Employee %, Employer %, Additional Amount

**16.4.7 Allowances**

**Route:** `GET/POST /settings/allowances`
**Layout:** Multi-tab page combining allowance types + subscriptions + deduction settings
**Table Columns (Allowance Types):** #, Name, Type, Description, Actions
**Sample Data:** 33 rows across tabs
**Form Fields:** Name, Type, Description

**16.4.8 Allowance Subscriptions**

**Route:** `GET/POST /settings/allowance_subscriptions`
**Table Columns:** #, Date, Name, Allowance, Amount, Actions
**Purpose:** Assigns specific allowances to individual employees with amounts.
**Form Fields:** Staff (dropdown), Allowance (dropdown), Amount, Date

**16.4.9 Leave Types**

**Route:** `GET/POST /settings/leave_types`
**Table Columns:** #, Name, Days Allowed, Description, Notice Days, Actions
**Sample Data:** 4 entries
**Form Fields:** Name, Days Allowed (number), Description, Notice Days (number)

**16.4.10 Attendance Types**

**Route:** `GET/POST /settings/attendance_types`
**Table Columns:** #, Name, Description, Actions
**Sample Data:** 1 entry (Biometric)
**Form Fields:** Name, Description

---

#### 16.5 Lead & Sales Configuration (5 Pages)

| # | Page | Route | Table Columns | Sample Data | Form Fields |
|---|------|-------|---------------|-------------|-------------|
| 1 | Lead Statuses | `/settings/lead_statuses` | #, Name, Actions | 7 entries | Name |
| 2 | Lead Sources | `/settings/lead_sources` | #, Name, Actions | 4 entries | Name |
| 3 | Service Interesteds | `/settings/service_interesteds` | #, Name, Actions | 7 entries | Name |
| 4 | Service Types | `/settings/service_types` | #, Name, Actions | 7 entries | Name |
| 5 | Client Sources | `/settings/client_sources` | #, Name, Description, Actions | 3 entries | Name, Description |

---

#### 16.6 Project Configuration (3 Pages)

| # | Page | Route | Table Columns | Sample Data | Form Fields |
|---|------|-------|---------------|-------------|-------------|
| 1 | Project Types | `/settings/project_types_settings` | #, Name, Actions | 4 entries | Name |
| 2 | Project Statuses | `/settings/project_statuses` | #, Name, Actions | 0 entries | Name |
| 3 | Cost Categories | `/settings/cost_categories` | #, Name, Actions | 0 entries | Name |

> Note: A separate Project Types page also exists under the Projects sidebar at `/project_types` with additional columns (#, Name, Description, Total Projects, Actions).

---

#### 16.7 BOQ Template Settings (7 Pages)

**16.7.1 Building Types**

**Route:** `GET/POST /settings/building_types`
**Table Columns:** Number, Building Type Name, Parent Type, Description, Sort Order, Status, Actions
**Sample Data:** 5 entries
**Purpose:** Hierarchical building type classification for BOQ templates (supports parent-child).
**Form Fields:** Name, Parent Type (dropdown, optional), Description, Sort Order, Status (Active/Inactive)

**16.7.2 BOQ Item Categories**

**Route:** `GET/POST /settings/boq_item_categories`
**Table Columns:** Number, Category Name, Parent Category, Description, Sort Order, Status, Actions
**Sample Data:** 12 entries
**Purpose:** Hierarchical categorization of BOQ items/materials.
**Form Fields:** Name, Parent Category (dropdown, optional), Description, Sort Order, Status

**16.7.3 Construction Stages**

**Route:** `GET/POST /settings/construction_stages`
**Table Columns:** Number, Stage Name, Parent Stage, Description, Sort Order, Actions
**Sample Data:** 7 entries (e.g., Foundation, Superstructure, Finishing)
**Purpose:** Hierarchical construction stages.
**Form Fields:** Name, Parent Stage (dropdown, optional), Description, Sort Order

**16.7.4 Activities**

**Route:** `GET/POST /settings/activities`
**Table Columns:** #, Name, Construction Stage, Description, Sort Order, Actions
**Sample Data:** 10 entries
**Purpose:** Construction activities linked to stages.
**Form Fields:** Name, Construction Stage (dropdown), Description, Sort Order

**16.7.5 Sub-Activities**

**Route:** `GET/POST /settings/sub_activities`
**Table Columns:** Number, Name, Type, Stage, Duration, Labor, Skill Level, Parallel, Weather, Actions
**Sample Data:** 17 entries
**Purpose:** Granular sub-activities with labor, duration, and scheduling metadata.
**Form Fields:** Name, Type, Activity (dropdown), Duration, Labor Count, Skill Level, Can Run Parallel (bool), Weather Dependent (bool)

**16.7.6 BOQ Items / Materials**

**Route:** `GET/POST /settings/boq_items`
**Table Columns:** Number, Name, Type, Unit, Base Price, Description, Actions
**Sample Data:** 20 entries
**Purpose:** Master list of BOQ items/materials with type, unit, and base pricing.
**Form Fields:** Name, Type (Material/Labour), Category (dropdown), Unit, Base Price, Description

**16.7.7 BOQ Templates**

**Route:** `GET/POST /settings/boq_templates`
**Table Columns:** #, Name, Building Type, Specifications, Measurements, Created By, Status, Actions
**Sample Data:** 3 entries
**Purpose:** Reusable BOQ template designs for different building types.
**Form Fields:** Name, Building Type (dropdown), Specifications, Measurements
**Special:** Links to BOQ Template Builder page for constructing the template hierarchy.

---

#### 16.8 Finance Configuration (8 Pages)

These pages appear under the **Finance sidebar section**, not the Settings hub:

**16.8.1 Account Types**

**Route:** `GET/POST /finance/financial_settings/account_types`
**Table Columns:** #, Type, Code, Normal Balance, Actions
**Sample Data:** 6 entries (Asset, Liability, Equity, Revenue, Expense, etc.)
**Form Fields:** Type Name, Code, Normal Balance (Debit/Credit)

**16.8.2 Charts of Accounts**

**Route:** `GET/POST /finance/financial_settings/charts_of_accounts`
**Table Columns:** #, Code, Account Name, Currency, Option
**Sample Data:** 33 entries
**Form Fields:** Code, Account Name, Account Type (dropdown), Currency, Parent Account (optional)

**16.8.3 Charts of Account Usage**

**Route:** `GET/POST /finance/financial_settings/charts_of_account_usages`
**Table Columns:** #, Name, Charts Account, Description, Actions
**Sample Data:** 2 entries
**Form Fields:** Name, Charts Account (dropdown), Description

**16.8.4 Exchange Rates**

**Route:** `GET/POST /finance/financial_settings/exchange_rates`
**Table Columns:** #, Foreign Currency, Base Currency, Rate, Month, Year, Actions
**Sample Data:** 0 entries
**Form Fields:** Foreign Currency, Base Currency, Rate, Month (dropdown), Year

**16.8.5 Chart of Account Variables**

**Route:** `GET/POST /finance/financial_settings/chart_of_account_variables`
**Table Columns:** #, Variable, Value, Actions
**Sample Data:** 1 entry
**Form Fields:** Variable Name, Value

**16.8.6 Banks** (also on Settings hub)

**Route:** `GET/POST /settings/banks`
**Table Columns:** #, Name, Description, Actions
**Sample Data:** 5 entries (CRDB BANK, NBC BANK, NMB BANK, AZANIA BANK, CASH IN HAND)
**Form Fields:** Name, Description

**16.8.7 Financial Charge Categories**

**Route:** `GET/POST /settings/financial_charge_categories`
**Table Columns:** #, Name, Charge, Actions
**Sample Data:** 0 entries
**Form Fields:** Name, Charge (amount)

**16.8.8 EFD (Electronic Fiscal Devices)**

**Route:** `GET/POST /settings/efd`
**Table Columns:** #, Name, System, Actions
**Sample Data:** 1 entry
**Purpose:** TRA-compliant EFD device registration.
**Form Fields:** Name, System (dropdown)

---

#### 16.9 Expense Configuration (2 Pages)

**16.9.1 Expenses Categories**

**Route:** `GET/POST /settings/expenses_categories`
**Table Columns:** #, Name, Actions
**Sample Data:** 3 entries (Administration Expenses, Financial Expenses, Depreciation Expenses)
**Form Fields:** Name

**16.9.2 Expenses Sub Categories**

**Route:** `GET/POST /settings/expenses_sub_categories`
**Table Columns:** #, Expenses Sub Category, Expenses Category, IS Deducted, Actions
**Sample Data:** 11 entries
**Form Fields:** Name, Expenses Category (dropdown), Is Deducted (checkbox)

---

#### 16.10 Statutory Configuration (3 Pages)

**16.10.1 Statutory Payment Categories**

**Route:** `GET/POST /settings/categories`
**Table Columns:** #, Date, Name, Description, Actions
**Sample Data:** 4 entries
**Form Fields:** Name, Description

**16.10.2 Statutory Payment Sub Categories**

**Route:** `GET/POST /settings/sub_categories`
**Table Columns:** #, Date, Name, Description, Category, Billing Cycle, Amount, Annually, Actions
**Sample Data:** 4 entries
**Form Fields:** Name, Description, Category (dropdown), Billing Cycle, Amount

**16.10.3 Statutory Payments** (separate CRUD page)

**Route:** `GET/POST /statutory_payments`
**Purpose:** Record actual statutory payment transactions.

---

#### 16.11 Procurement Settings (2 Pages)

**16.11.1 Suppliers**

**Route:** `GET/POST /settings/suppliers`
**Table Columns:** #, Name, Phone, Address, VRN, System, Actions
**Sample Data:** 10 entries
**Form Fields:** Name, Phone, Address, Email, VRN (VAT Registration Number), System (dropdown), Is Artisan (boolean — shared model with Labor Procurement)

**16.11.2 Items**

**Route:** `GET/POST /settings/items`
**Table Columns:** #, Name, Actions
**Sample Data:** 6 entries
**Purpose:** General items for VAT/sales/purchasing.
**Form Fields:** Name

---

#### 16.12 System Configuration (2 Pages)

**16.12.1 Systems**

**Route:** `GET/POST /settings/systems`
**Table Columns:** #, Name, Description, Actions
**Sample Data:** 1 entry
**Purpose:** Business system/company entities for multi-system operations.
**Form Fields:** Name, Description

**16.12.2 System Settings (Key-Value Store)**

**Route:** `GET/POST /settings/system_settings`
**Table Columns:** #, Key, Value, Previous Value, Description, Actions
**Sample Data:** 30 entries

**Model:** `SystemSetting` — key-value configuration with audit trail (stores `previous_value` on update).

**Sample Settings Keys:**

| Key | Description | Example Value |
|-----|-------------|---------------|
| SYSTEM_NAME | Company/system display name | WAJENZI PROFESSIONAL |
| DEFAULT_CURRENCY | Default currency code | TZS |
| DEFAULT_TAX_RATE | Default VAT rate | 18 |
| ATTENDANCE_LATE_THRESHOLD | Late check-in time | 09:00:00 |
| SMS_API_KEY | SMS gateway API key | (encrypted) |
| SMS_SENDER_ID | SMS sender name | LERUMA ENT |
| COMPANY_NAME | Company legal name | WAJENZI PROFESSIONAL COMPANY LIMITED |
| COMPANY_ADDRESS | Company address | Dar es Salaam, Tanzania |
| COMPANY_PHONE | Company phone | +255... |
| COMPANY_TIN | TRA Tax Identification Number | ... |

**Access Helper:** `settings('KEY_NAME', 'default_value')` — used throughout the app to read configuration values.
**Form Fields:** Key, Value, Description

---

#### 16.13 Billing Settings (Separate Controller)

**Route:** `GET /billing/settings`
**Controller:** `Billing\SettingsController`

These settings are accessed from the **Billing sidebar section**, not the Settings hub.

**Configuration Sections:**

**Document Numbering:**

| Setting | Description | Example |
|---------|-------------|---------|
| Invoice Prefix | Prefix for invoice numbers | INV |
| Quote Prefix | Prefix for quotation numbers | QT |
| Proforma Prefix | Prefix for proforma numbers | PF |
| Credit Note Prefix | Prefix for credit note numbers | CN |
| Receipt Prefix | Prefix for receipt numbers | RCT |
| Payment Prefix | Prefix for payment numbers | PAY |
| Number Format | Number format pattern | YYYY-00000 |

**Default Behavior:**

| Setting | Description |
|---------|-------------|
| Default Payment Terms | Days for payment due date |
| Default Currency | Default billing currency |
| Default Tax Rate | Default tax percentage |
| Invoice Terms | Standard terms text |
| Invoice Footer | Footer text on invoices |

**Company Information:**

| Setting | Description |
|---------|-------------|
| Company Name | Legal company name |
| Company Address | Business address |
| Company Phone | Contact phone |
| Company Email | Contact email |
| Company Website | Company website URL |
| Tax ID | TRA TIN number |
| Company Logo | Uploaded logo file |

**Email Templates:**

| Setting | Description |
|---------|-------------|
| Email From Name | Sender display name |
| Email From Address | Sender email address |
| Invoice Email Subject | Subject line template for invoices |
| Invoice Email Body | Body template for invoice emails |
| Quote Email Subject | Subject line for quotation emails |
| Quote Email Body | Body template for quotation emails |
| Proforma Email Subject | Subject line for proforma emails |
| Proforma Email Body | Body template for proforma emails |

**Routes:**

| Method | URI | Purpose |
|--------|-----|---------|
| GET | `/billing/settings` | Settings index |
| POST | `/billing/settings` | Update settings |
| GET | `/billing/settings/numbering` | Document numbering config |
| POST | `/billing/settings/numbering` | Update numbering |

---

#### 16.14 Activity Templates (Linked from Settings Hub)

**Route:** `GET/POST /settings/activity_templates`
**Purpose:** Configurable templates that define which activities are assigned to specific roles.
**Details:** Documented in detail under BOQ Templates module (Section 6).

---

#### 16.15 Routes Summary

**Main Settings Routes (68):**

| Method | URI | Controller | Purpose |
|--------|-----|-----------|---------|
| GET, POST | `/settings` | `SettingsController@index` | Settings hub |
| GET, POST | `/settings/users` | `SettingsController@users` | User management |
| POST | `/settings/toggle_user_status/{id}` | `SettingsController@toggleUserStatus` | Toggle user status |
| GET, POST | `/settings/roles` | `SettingsController@roles` | Role management |
| GET, POST | `/settings/permissions` | `SettingsController@permissions` | Permission management |
| POST | `/settings/role_permissions` | `SettingsController@updateRolePermissions` | Assign permissions to role |
| POST | `/settings/role_users` | `SettingsController@assignUsersToRole` | Assign users to role |
| GET, POST | `/settings/positions` | `SettingsController@positions` | Position management |
| GET, POST | `/settings/departments` | `SettingsController@departments` | Department management |
| GET, POST | `/settings/banks` | `SettingsController@banks` | Bank management |
| GET, POST | `/settings/allowances` | `SettingsController@allowances` | Allowance types |
| GET, POST | `/settings/allowance_subscriptions` | `SettingsController@allowance_subscriptions` | Allowance assignments |
| GET, POST | `/settings/deductions` | `SettingsController@deductions` | Deduction types |
| GET, POST | `/settings/deduction_subscriptions` | `SettingsController@deduction_subscriptions` | Deduction enrollment |
| GET, POST | `/settings/deduction_settings` | `SettingsController@deduction_settings` | Deduction calc rules |
| GET, POST | `/settings/staff_salaries` | `SettingsController@staff_salaries` | Staff salary amounts |
| GET, POST | `/settings/staff_loans` | `SettingsController@staff_loans` | Employee loans |
| GET, POST | `/settings/advance_salaries` | `SettingsController@advance_salaries` | Advance salary requests |
| GET, POST | `/settings/leave_types` | `SettingsController@leave_types` | Leave type config |
| GET, POST | `/settings/attendance_types` | `SettingsController@attendance_types` | Attendance types |
| GET, POST | `/settings/lead_statuses` | `SettingsController@lead_statuses` | Lead pipeline statuses |
| GET, POST | `/settings/lead_sources` | `SettingsController@lead_sources` | Lead sources |
| GET, POST | `/settings/service_interesteds` | `SettingsController@service_interesteds` | Service interests |
| GET, POST | `/settings/service_types` | `SettingsController@service_types` | Service types |
| GET, POST | `/settings/client_sources` | `SettingsController@client_sources` | Client sources |
| GET, POST | `/settings/project_types_settings` | `SettingsController@project_types_settings` | Project types |
| GET, POST | `/settings/project_statuses` | `SettingsController@project_statuses` | Project statuses |
| GET, POST | `/settings/cost_categories` | `SettingsController@cost_categories` | Cost categories |
| GET, POST | `/settings/suppliers` | `SettingsController@suppliers` | Supplier master data |
| GET, POST | `/settings/items` | `SettingsController@items` | Item master data |
| GET, POST | `/settings/expenses_categories` | `SettingsController@expenses_categories` | Expense categories |
| GET, POST | `/settings/expenses_sub_categories` | `SettingsController@expenses_sub_categories` | Expense sub-categories |
| GET, POST | `/settings/financial_charge_categories` | `SettingsController@financial_charge_categories` | Financial charges |
| GET, POST | `/settings/efd` | `SettingsController@efd` | EFD devices |
| GET, POST | `/settings/systems` | `SettingsController@systems` | Business systems |
| GET, POST | `/settings/system_settings` | `SettingsController@system_settings` | Key-value config |
| GET, POST | `/settings/user_groups` | `SettingsController@user_groups` | User groups |
| GET, POST | `/settings/assign_user_groups` | `SettingsController@assign_user_groups` | User group assignments |
| GET, POST | `/settings/categories` | `SettingsController@categories` | Statutory categories |
| GET, POST | `/settings/sub_categories` | `SettingsController@sub_categories` | Statutory sub-categories |
| GET, POST | `/settings/approval_document_types` | `SettingsController@approval_document_types` | Legacy approval types |
| GET, POST | `/settings/approval_levels` | `SettingsController@approval_levels` | Legacy approval levels |
| GET, POST | `/settings/process_approval_flows` | `SettingsController@process_approval_flows` | RingleSoft approval flows |
| GET, POST | `/settings/process_approval_flow_steps` | `SettingsController@process_approval_flow_steps` | Approval flow steps |
| GET, POST | `/settings/building_types` | `SettingsController@building_types` | Building types |
| GET, POST | `/settings/boq_item_categories` | `SettingsController@boq_item_categories` | BOQ item categories |
| GET, POST | `/settings/construction_stages` | `SettingsController@construction_stages` | Construction stages |
| GET, POST | `/settings/activities` | `SettingsController@activities` | Activities |
| GET, POST | `/settings/sub_activities` | `SettingsController@sub_activities` | Sub-activities |
| GET, POST | `/settings/boq_items` | `SettingsController@boq_items` | BOQ items |
| GET, POST | `/settings/boq_templates` | `SettingsController@boq_templates` | BOQ templates |
| GET, POST | `/settings/activity_templates` | `SettingsController@activity_templates` | Activity templates |
| GET | `/billing/settings` | `Billing\SettingsController@index` | Billing settings |
| POST | `/billing/settings` | `Billing\SettingsController@update` | Update billing settings |
| GET | `/billing/settings/numbering` | `Billing\SettingsController@numbering` | Document numbering |
| POST | `/billing/settings/numbering` | `Billing\SettingsController@updateNumbering` | Update numbering |
| GET, POST | `/finance/financial_settings/account_types` | `SettingsController@account_types` | Account types |
| GET, POST | `/finance/financial_settings/charts_of_accounts` | `SettingsController@charts_of_accounts` | Chart of accounts |
| GET, POST | `/finance/financial_settings/charts_of_account_usages` | `SettingsController@charts_of_account_usages` | Account usages |
| GET, POST | `/finance/financial_settings/exchange_rates` | `SettingsController@exchange_rates` | Exchange rates |
| GET, POST | `/finance/financial_settings/chart_of_account_variables` | `SettingsController@chart_of_account_variables` | Account variables |

---

#### 16.16 Permissions

| Permission | Purpose |
|------------|---------|
| Settings | Access to the Settings hub |
| Users | View user management |
| Add User | Create new users |
| Edit User | Modify user details |
| Delete User | Remove users |
| Roles | View/manage roles |
| Permissions | View/manage permissions |
| Departments | Manage departments |
| Banks | Manage banks |
| Allowances | Manage allowances |
| Deductions | Manage deductions |
| Staff Salaries | Manage salary records |
| Staff Loans | Manage employee loans |
| Advance Salary | Manage advance salary requests |
| Leave Types | Manage leave type config |
| Suppliers | Manage supplier master data |
| Approval Flows | Configure approval workflows |
| System Settings | Manage key-value settings |
| Building Types | Manage building types |
| BOQ Templates | Manage BOQ templates |
| Activities | Manage construction activities |

> Pattern: Each settings page has its own view permission (matching the entity name), plus optional Add/Edit/Delete permissions checked via `@can()` directives.

---

#### 16.17 Data Storage

**SystemSetting Model:**
- Key-value store with audit trail
- Columns: `id`, `key`, `value`, `previous_value`, `description`, `timestamps`
- Accessed via `settings('KEY_NAME', 'default')` helper throughout the app

**BillingDocumentSetting Model:**
- Grouped document settings by type (company info, email templates, numbering)
- Managed by `Billing\SettingsController`

**Reference Data Models (60+):**
All other settings pages manage standard Eloquent models with CRUD operations through the `handleCrud()` base controller pattern.

---

## Cross-Cutting Features

### Approval Workflows (20 models)

Multi-step approval system using RingleSoft Process Approval:

| Domain | Approvable Models |
|--------|-------------------|
| Projects | Project, ProjectBoq, ProjectMaterialRequest, ProjectSiteVisit |
| Procurement | Purchase, QuotationComparison, MaterialInspection |
| Reports | SiteDailyReport, SalesDailyReport |
| Finance | Expense, Sale, VatPayment, StatutoryPayment |
| HR | Payroll, AdvanceSalary, LeaveRequest, Loan |
| Labor | LaborRequest, LaborInspection |

### Permission System

Spatie Laravel Permission with role-based access:
- Granular permissions per module and action (Add, Edit, Delete, Approve)
- Menu visibility controlled by permissions
- Permission-based UI elements (`@can` directives)

### Mobile Application (Flutter)

Offline-first mobile app for field operations:

| Module | Mobile Capabilities |
|--------|-------------------|
| Attendance | GPS check-in/out, offline queuing |
| Site Reports | Create/edit with offline support |
| Sales Reports | Daily activity reporting |
| Expenses | Submission with photo attachments |
| Approvals | Unified approve/reject inbox |
| Projects | Browse projects, BOQ, team |
| Billing | Create invoices, quotations |
| VAT | Full CRUD for sales, purchases, payments |
| Leave | Apply, check balance |
| Payroll | View payslips |
| Procurement | Material requests |

### 18. Client Portal

External-facing, **read-only** portal for construction clients to track their project progress, view financials, download documents, and access site reports. Completely separate from the staff ERP — uses its own authentication guard (`client`), layout (`layouts.client` with Mantine-inspired design system), and URL prefix (`/client/*`). Clients can only see projects where `projects.client_id` matches their authenticated ID.

**Access URL:** `/client/login`

#### 18.1 Authentication System

**Separate Auth Guard:** The client portal uses a dedicated Laravel authentication guard (`client`) backed by the `project_clients` table (not the `users` table used by staff).

**Auth Configuration (`config/auth.php`):**
| Guard | Driver | Provider | Model |
|-------|--------|----------|-------|
| `client` | session | clients | `App\Models\ProjectClient` |
| `client-api` | sanctum | clients | `App\Models\ProjectClient` |

**Login Flow:**
1. Client visits `/client/login`
2. Can authenticate with **email OR phone number** + password
3. `ClientAuthController` uses `Auth::guard('client')->attempt()`
4. On success, session regenerated, `last_login_at` updated
5. Redirects to `/client/dashboard`

**Middleware (`ClientAuth`):**
- Checks `Auth::guard('client')->check()` — redirects to login if not authenticated
- Checks `portal_access_enabled` flag — disabled clients are logged out with error message
- Shares `$sidebarProjects` (all projects for this client) with all views

**Controllers:**
| Controller | Location | Purpose |
|------------|----------|---------|
| `ClientAuthController` | `App\Http\Controllers\Client` | Web login/logout |
| `ClientPortalController` | `App\Http\Controllers\Client` | All portal pages (dashboard, billing, project pages) |

#### 18.2 Portal Layout & Design

**Layout:** `resources/views/layouts/client.blade.php` — custom Mantine-inspired design system (NOT the staff Bootstrap backend layout).

**Design Features:**
| Feature | Detail |
|---------|--------|
| CSS Framework | Custom CSS variables system inspired by Mantine design tokens |
| Dark Mode | Toggle via `data-theme="dark"` attribute, persisted in `localStorage` |
| Responsive | Mobile sidebar (hamburger menu), responsive stat grid (4 → 2 → 1 columns) |
| Sidebar | 300px sticky navbar with project list, nested sub-links per active project |
| Typography | System font stack (-apple-system, Segoe UI, Roboto) |
| Components | Paper, Stat cards, Badge, Table, Tabs, Button, Avatar |
| Color Palette | Blue primary, Teal/Green/Violet/Orange/Red accents with dark variants |

**Sidebar Navigation:**
| Item | Route | Icon |
|------|-------|------|
| Dashboard | `/client/dashboard` | gauge-high (blue) |
| Billing | `/client/billing` | file-invoice-dollar (teal) |
| *[Each assigned project]* | `/client/project/{id}` | building (color by status) |

**When a project is active (selected), nested sub-links appear:**
- Overview, BOQ, Schedule, Financials, Documents, Gallery, Reports

#### 18.3 Dashboard

**Route:** `GET /client/dashboard` → `ClientPortalController@dashboard`

**Stats Grid (4 cards):**
| Stat | Source |
|------|--------|
| Total Projects | Count of projects where `client_id` = authenticated client |
| Active Projects | Projects with `status = 'APPROVED'` |
| Contract Value | Sum of `contract_value` across all projects |
| Total Invoiced | Sum of billing invoices (`BillingDocument` where `document_type = 'invoice'`) + legacy project invoices |

**Projects List:** Cards per project showing:
- Project name + document number
- Status badge (APPROVED=teal, PENDING=yellow, REJECTED=red, COMPLETED=blue)
- Date range (start → expected end)
- Contract value (TZS formatted)
- Counts: BOQs, Invoices, Reports
- "View Project" button

#### 18.4 Billing Page

**Route:** `GET /client/billing` → `ClientPortalController@billing`

**Purpose:** Cross-project view of ALL billing documents for the client.

**Document Types Displayed:**
| Type | Variable | Description |
|------|----------|-------------|
| Invoices | `$invoices` | All invoices (excluding draft/cancelled/void) |
| Quotes | `$quotes` | Quotations |
| Proformas | `$proformas` | Proforma invoices |
| Credit Notes | `$creditNotes` | Credit notes |

**Summary Stats:**
| Stat | Calculation |
|------|-------------|
| Total Invoiced | Sum of invoice `total_amount` |
| Total Paid | Sum of invoice `paid_amount` |
| Balance Due | Sum of invoice `balance_amount` |
| Overdue Count | Invoices where `is_overdue = true` |

**PDF Download:** `GET /client/billing/{documentId}/pdf` → generates PDF using existing billing PDF templates (`billing.invoices.pdf`, `billing.quotations.pdf`, `billing.proformas.pdf`).

#### 18.5 Project Overview

**Route:** `GET /client/project/{id}` → `ClientPortalController@projectShow`

**Data Loaded:**
- Project with relationships: `projectType`, `serviceType`, `projectManager`, `constructionPhases`
- Project schedule (via `ProjectSchedule` where `client_id` matches)
- Progress details and progress by phase (computed from schedule activities)

**Page Content:** Project details, construction phase timeline, overall progress percentage, progress breakdown per phase.

#### 18.6 Project BOQ

**Route:** `GET /client/project/{id}/boq` → `ClientPortalController@projectBoq`

**Data:** All BOQs for the project with hierarchical sections and items (`sections.children.items`, `sections.items`, `items`).

**View:** `client.projects.boq` — read-only BOQ display with section hierarchy, item details, quantities, rates, and totals. Uses recursive partial `client.partials.boq_section` for nested sections.

#### 18.7 Project Schedule

**Route:** `GET /client/project/{id}/schedule` → `ClientPortalController@projectSchedule`

**Data:**
- Construction phases ordered by start date
- Schedule activities (from `ProjectSchedule` linked to client) ordered by `sort_order`

**View:** `client.projects.schedule` — phase timeline and activity breakdown showing codes, names, dates, durations, predecessors, and status.

#### 18.8 Project Financials

**Route:** `GET /client/project/{id}/financials` → `ClientPortalController@projectFinancials`

**Data (project-scoped):**
- Legacy project invoices with payments
- Billing module invoices, quotes, and proformas (filtered by `client_id` + `project_id`, excluding draft/cancelled/void)

**Summary:**
| Stat | Calculation |
|------|-------------|
| Contract Value | `project.contract_value` |
| Total Invoiced | Billing invoices + legacy invoices |
| Total Paid | Billing payments + legacy payments |
| Balance Due | Invoiced - Paid |

**PDF Downloads:** `GET /client/project/{id}/billing/{documentId}/pdf` — same PDF templates as staff billing.

#### 18.9 Project Documents

**Route:** `GET /client/project/{id}/documents` → `ClientPortalController@projectDocuments`

**Data:** Project designs (`projectDesigns`) ordered by creation date.

**View:** `client.projects.documents` — list of design documents (architectural, structural, MEP) with version, type, status, and client feedback.

#### 18.10 Project Reports

**Route:** `GET /client/project/{id}/reports` → `ClientPortalController@projectReports`

**Data:**
- Daily reports with supervisor relationship, ordered by report date
- Site visits with inspector relationship, ordered by visit date

**Site Visit PDF:** `GET /client/project/{id}/site-visit/{visitId}/pdf` → dedicated client-facing PDF template (`client.site_visit_pdf`).

#### 18.11 Project Gallery

**Route:** `GET /client/project/{id}/gallery` → `ClientPortalController@projectGallery`

**Data:**
- Progress images with construction phase relationship, ordered by `taken_at` date
- Construction phases for filtering

**View:** `client.projects.gallery` — photo gallery of construction progress with phase filtering, titles, descriptions, and dates.

#### 18.12 Client Portal REST API

In addition to the web portal, a **REST API** exists at `/api/client/*` for mobile app or SPA consumption.

**Authentication:** Sanctum token-based (`auth:client-api` guard). Login returns a Bearer token.

**API Routes (`routes/api/client.php`):**
| Method | Endpoint | Controller | Description |
|--------|----------|------------|-------------|
| POST | `/api/client/auth/login` | `AuthController@login` | Login with email/phone + password + device_name |
| POST | `/api/client/auth/logout` | `AuthController@logout` | Revoke current token |
| GET | `/api/client/auth/me` | `AuthController@me` | Get authenticated client profile |
| PUT | `/api/client/auth/profile` | `AuthController@updateProfile` | Update name/email/phone/address |
| PUT | `/api/client/auth/password` | `AuthController@changePassword` | Change password |
| GET | `/api/client/dashboard` | `DashboardController@index` | Dashboard stats + projects |
| GET | `/api/client/projects` | `DashboardController@projects` | Projects list with counts |
| GET | `/api/client/billing` | `BillingController@index` | All billing documents + summary |
| GET | `/api/client/billing/{id}/pdf` | `BillingController@pdf` | Download billing PDF |
| GET | `/api/client/projects/{id}` | `ProjectController@show` | Project overview + progress |
| GET | `/api/client/projects/{id}/boq` | `ProjectController@boq` | BOQ with sections/items |
| GET | `/api/client/projects/{id}/schedule` | `ProjectController@schedule` | Phases + schedule activities |
| GET | `/api/client/projects/{id}/financials` | `ProjectController@financials` | Invoices, quotes, payments, summary |
| GET | `/api/client/projects/{id}/documents` | `ProjectController@documents` | Design documents |
| GET | `/api/client/projects/{id}/reports` | `ProjectController@reports` | Daily reports + site visits |
| GET | `/api/client/projects/{id}/gallery` | `ProjectController@gallery` | Progress images by phase |
| GET | `/api/client/projects/{id}/billing/{doc}/pdf` | `ProjectController@billingPdf` | Project-scoped billing PDF |
| GET | `/api/client/projects/{id}/site-visits/{visit}/pdf` | `ProjectController@siteVisitPdf` | Site visit PDF |

**API Response Format:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

**API Resources (JSON serializers):**
| Resource | Purpose |
|----------|---------|
| `ClientProjectResource` | Project with counts and metadata |
| `ClientBoqResource` | BOQ with sections and items |
| `ClientConstructionPhaseResource` | Phase with date range and status |
| `ClientDailyReportResource` | Daily report with supervisor |
| `ClientDesignResource` | Design document with version/status |
| `ClientProgressImageResource` | Progress image with phase |
| `ClientScheduleActivityResource` | Schedule activity with dates |
| `ClientSiteVisitResource` | Site visit with inspector |
| `BillingDocumentResource` | Shared billing document resource |

#### 18.13 Data Model

**`project_clients` table (used as Authenticatable):**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Auto-increment |
| first_name | varchar | Client first name |
| last_name | varchar | Client last name |
| email | varchar, nullable | Login credential (email) |
| phone_number | varchar, nullable | Login credential (phone) |
| password | varchar, nullable | Hashed password for portal access |
| address | text, nullable | Client address |
| identification_number | varchar, nullable | National ID / company registration |
| file | varchar, nullable | Attached document |
| client_source_id | FK, nullable | How client was acquired |
| create_by_id | FK | Staff who created the record |
| status | varchar | CREATED / PENDING / APPROVED |
| document_number | varchar, nullable | Client reference number |
| portal_access_enabled | boolean | Gate for portal login |
| last_login_at | timestamp, nullable | Last portal login time |
| remember_token | varchar, nullable | Laravel remember me token |

**Model:** `ProjectClient` extends `Authenticatable` (not `Model`) — uses `HasApiTokens` (Sanctum) and `Approvable` (RingleSoft).

**Relationships:**
| Relationship | Type | Target |
|-------------|------|--------|
| `projects()` | HasMany | `Project` (via `client_id`) |
| `documents()` | HasMany | `ProjectClientDocument` (via `client_id`) |
| `user()` | BelongsTo | `User` (via `create_by_id`) |
| `client_source()` | BelongsTo | `ClientSource` |

**Security Model:**
- Every controller method calls `clientProject($id)` which enforces `WHERE client_id = auth()->id()`
- Clients cannot see other clients' data — ownership check on every query
- `portal_access_enabled` checked on every request by middleware
- Disabled clients are immediately logged out

#### 18.14 Admin Setup (Staff Side)

Clients are managed in the staff ERP under **Projects → Clients** (`/project_clients`).

**Portal Access Configuration (in client edit form):**
| Field | Type | Description |
|-------|------|-------------|
| Portal Password | Password | Set/change client login password |
| Confirm Password | Password | Confirmation field |
| Enable Portal Access | Checkbox | Toggle portal login (default: enabled when password set) |

**Clients Table (staff view):**
| Column | Badge |
|--------|-------|
| Portal | Active (green) / Disabled (red) / No Account (grey) |
| Last Login | Relative time (e.g., "2 hours ago") |

#### 18.15 Web Routes

| Route | Name | Controller Method |
|-------|------|-------------------|
| `GET /client/login` | `client.login` | `ClientAuthController@showLoginForm` |
| `POST /client/login` | — | `ClientAuthController@login` |
| `POST /client/logout` | `client.logout` | `ClientAuthController@logout` |
| `GET /client/dashboard` | `client.dashboard` | `ClientPortalController@dashboard` |
| `GET /client/billing` | `client.billing` | `ClientPortalController@billing` |
| `GET /client/billing/{id}/pdf` | `client.billing.pdf` | `ClientPortalController@billingPdf` |
| `GET /client/project/{id}` | `client.project.show` | `ClientPortalController@projectShow` |
| `GET /client/project/{id}/boq` | `client.project.boq` | `ClientPortalController@projectBoq` |
| `GET /client/project/{id}/schedule` | `client.project.schedule` | `ClientPortalController@projectSchedule` |
| `GET /client/project/{id}/financials` | `client.project.financials` | `ClientPortalController@projectFinancials` |
| `GET /client/project/{id}/documents` | `client.project.documents` | `ClientPortalController@projectDocuments` |
| `GET /client/project/{id}/gallery` | `client.project.gallery` | `ClientPortalController@projectGallery` |
| `GET /client/project/{id}/reports` | `client.project.reports` | `ClientPortalController@projectReports` |
| `GET /client/project/{id}/billing/{docId}/pdf` | `client.project.billing_pdf` | `ClientPortalController@billingDocumentPdf` |
| `GET /client/project/{id}/site-visit/{visitId}/pdf` | `client.project.site_visit_pdf` | `ClientPortalController@siteVisitPdf` |

#### 18.16 View Templates

| Template | Purpose |
|----------|---------|
| `layouts/client.blade.php` | Master layout (Mantine design system, sidebar, header, dark mode) |
| `client/auth/login.blade.php` | Login page |
| `client/dashboard.blade.php` | Dashboard with stats grid and project cards |
| `client/billing.blade.php` | Cross-project billing documents |
| `client/projects/show.blade.php` | Project overview with progress |
| `client/projects/boq.blade.php` | BOQ display with sections |
| `client/projects/schedule.blade.php` | Schedule timeline |
| `client/projects/financials.blade.php` | Invoices, quotes, payments |
| `client/projects/documents.blade.php` | Design documents |
| `client/projects/gallery.blade.php` | Progress photo gallery |
| `client/projects/reports.blade.php` | Daily reports + site visits |
| `client/site_visit_pdf.blade.php` | Site visit PDF template |
| `client/partials/project_tabs.blade.php` | Tab navigation partial |
| `client/partials/boq_section.blade.php` | Recursive BOQ section partial |

---

### 17. Attendance (ZKTeco Integration)

Biometric attendance tracking system that integrates **ZKTeco fingerprint/face recognition devices** with the Wajenzi ERP. A standalone **Node.js cron service** (`zkt-attendance`) runs on a local Windows machine connected to the same network as the ZKTeco devices. It polls each device on a schedule, extracts attendance logs, and pushes them to the Wajenzi Laravel API. The Laravel side receives the data, maps device user IDs to system users, applies timezone correction (+3 hours EAT), deduplicates entries, and stores them for reporting and payroll integration.

**Architecture Overview:**
```
┌─────────────┐    UDP/4370     ┌──────────────────┐     HTTPS POST      ┌─────────────────┐
│  ZKTeco      │◄──────────────►│  Node.js Cron     │───────────────────►│  Wajenzi Laravel  │
│  Device(s)   │  node-zklib    │  (zkt-attendance)  │  /api/attendance   │  API + Database   │
│  192.168.x.x │                │  Windows machine   │  Bearer token auth │  MySQL            │
└─────────────┘                └──────────────────┘                     └─────────────────┘
```

#### 17.1 ZKTeco Cron Service (Separate Project)

**Project:** `~/Development/Moinfotech/attendance` — TypeScript/Node.js application.

**Technology Stack:**
| Component | Technology | Version |
|-----------|-----------|---------|
| Runtime | Node.js | 16+ |
| Language | TypeScript | 4.5 |
| ZKTeco SDK | `node-zklib` | 1.3.0 |
| HTTP Client | `axios` | 0.24.0 |
| Database | `mysql` (driver) | 2.18.1 |
| Auth Token | `md5` | 2.3.0 |
| Alternative SDK | `chronos` (ZKTBroker) | GitHub fork |

**Source Files:**
| File | Purpose |
|------|---------|
| `src/index.ts` | Main entry — iterates devices, fetches attendances, submits to API |
| `src/ZKTBroker.ts` | Alternative ZKTeco connection class using `chronos` library |
| `src/ZKRTEvents.js` | Low-level ZKTeco TCP protocol implementation (real-time events, packet parsing) |
| `src/DB.ts` | MySQL connection pool for optional direct DB writes |
| `src/env.ts` | Environment config — device IPs, DB credentials, API URL |

**How the Cron Works:**
1. **Startup:** `npm start` compiles TypeScript → runs `dist/src/index.js`
2. **Device Scan:** Reads device IP list from `env.ts` (e.g., `192.168.18.201`)
3. **Connection:** Creates UDP socket to each ZKTeco device on port **4370** via `node-zklib`
4. **Data Extraction:** Calls `zkInstance.getAttendances()` to pull all attendance logs from device memory
5. **API Submission:** POSTs attendance array to Wajenzi API with Bearer token authentication
6. **Disconnection:** Closes device socket

**Cron Scheduling:** Configured via Windows Task Scheduler (`cron.bat`) or Linux crontab (`cron.txt`):
```
# Windows batch (cron.bat)
cd /d "C:\Users\leruma\Desktop\attendance"
start npm start

# Linux crontab entry (cron.txt)
Files\nodejs\node.exe C:\Users\leruma\Desktop\attendance\dist\src\index
```

**Authentication:** The cron uses a hardcoded token: `md5('WHITE_STAR')` sent as `Authorization: Bearer <token>`.

**Environment Configuration (`env.ts`):**
| Setting | Value | Description |
|---------|-------|-------------|
| `devices` | `['192.168.18.201']` | Array of ZKTeco device IP addresses on LAN |
| `mysql.hostName` | `localhost` | Optional direct DB connection |
| `mysql.dbName` | `test` | Database name |
| `API_URL` | `https://reportsanalysis.co.tz/api/attendance` | Production API endpoint |

**ZKTeco Protocol Details (`ZKRTEvents.js`):**
| Feature | Detail |
|---------|--------|
| Protocol | TCP with custom binary packet format |
| Packet Header | `5050827d` (4 bytes) |
| Commands | CONNECT (0x03e8), DISCONNECT (0x03e9), ENABLE_DEVICE (0x03ea), DISABLE_DEVICE (0x03eb) |
| Events | TRANSACTION (0x0001) — fired on check-in/out |
| Verify Methods | Password (0x00), Fingerprint (0x01), Face (0x0F) |
| Attendance States | CHECK_IN (0x00), CHECK_OUT (0x01), BREAK_OUT (0x02), BREAK_IN (0x03), OT_IN (0x04), OT_OUT (0x05) |
| Session Management | Session ID assigned by device on connect, included in all subsequent packets |

**Real-Time Event Data (per transaction):**
| Field | Size | Description |
|-------|------|-------------|
| enrollNumber | 16 bytes ASCII | User enrollment ID on device |
| attState | 1 byte | Check-in/out/break state |
| verifyMethod | 1 byte | Password/Fingerprint/Face |
| year | 1 byte + 2000 | Year of event |
| month | 1 byte | Month |
| day | 1 byte | Day |
| hours | 1 byte | Hour |
| minutes | 1 byte | Minute |
| seconds | 1 byte | Second |

#### 17.2 Laravel API Endpoint (Receiver)

**Route:** `POST /api/attendance` — protected by `apiAuth` middleware.

**Middleware (`ApiAuth`):** Validates `Authorization: Bearer <token>` header. Requires token length >= 20 characters. The cron sends `md5('WHITE_STAR')` as the token.

**Controller:** `ApiController@store` — receives attendance data from the cron service.

**Request Validation:**
```
data          → required, array
data.*.deviceUserId → required (ZKTeco enrollment number)
data.*.recordTime   → required (datetime from device)
data.*.ip           → required (device IP address)
```

**Processing Logic (`Attendance::recordFromDevice`):**
1. Iterates each attendance record from the device
2. **Duplicate Prevention:** Looks up the last entry from the same device IP. If the new record's adjusted time is <= the last entry's time, the record is **skipped**
3. **Timezone Correction:** Adds **+3 hours** (EAT) to the device-reported `recordTime`: `strtotime($item['recordTime']) + (60*60*3)`
4. **User Mapping:** Maps `deviceUserId` (ZKTeco enrollment number) → Laravel `user_id` via `User::where('user_device_id', $userSn)`. Each user has a `user_device_id` field set in User Settings that matches their biometric enrollment number
5. **Record Creation:** Creates `Attendance` record with `user_id`, `device_user_id`, `record_time`, `type` (default: 'in'), `ip`
6. Returns count of newly created records

**Response Format:**
```json
{
  "success": true,
  "message": "Attendance records processed successfully",
  "data": [...],
  "count": 5
}
```

#### 17.3 User-Device Mapping

Each system user has a `user_device_id` field that corresponds to their enrollment number on the ZKTeco device. This mapping is configured in **Settings → Users** (user edit form).

**User Table Fields (attendance-related):**
| Column | Type | Description |
|--------|------|-------------|
| `user_device_id` | varchar, nullable | ZKTeco biometric enrollment number |
| `attendance_type_id` | FK, nullable | Links to `attendance_types` table |
| `attendance_status` | enum: ENABLED/DISABLED | Whether user is tracked for attendance |

**Mapping Flow:**
1. Admin registers employee on ZKTeco device (fingerprint/face enrollment) → device assigns enrollment number
2. Admin sets `user_device_id` in Wajenzi User Settings to match the device enrollment number
3. When cron pushes attendance data, `Attendance::mapUserId()` resolves `deviceUserId` → `user_id`
4. If no matching user found, `user_id` defaults to `0`

#### 17.4 Attendance Data Model

**`attendances` table:**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Auto-increment |
| user_id | integer (indexed) | Laravel user ID (mapped from device) |
| device_user_id | integer (indexed) | ZKTeco enrollment number |
| record_time | datetime (indexed) | Timestamp with +3h EAT adjustment |
| type | enum: in/out/break_start/break_end | Attendance event type (default: 'in') |
| ip | varchar | Device IP address |
| comment | text, nullable | Optional comment |
| file | varchar, nullable | Attachment (photo/document) |
| created_at | timestamp | Record creation time |
| updated_at | timestamp | Last update time |

**Indexes:**
- `user_id`
- `device_user_id`
- `record_time`
- Composite: `(user_id, record_time)`
- Composite: `(user_id, type, record_time)`

**`attendance_types` table:**
| Column | Type | Description |
|--------|------|-------------|
| id | bigint PK | Auto-increment |
| name | varchar | Type name (e.g., Office, Field, Remote) |
| description | varchar, nullable | Type description |

#### 17.5 Attendance Calculation Logic

**Configurable Settings (via `SystemSetting`):**
| Setting Key | Default | Description |
|-------------|---------|-------------|
| `ATTENDANCE_LATE_THRESHOLD` | `09:00:00` | Time after which check-in is "late" |
| `ATTENDANCE_EARLY_THRESHOLD` | `06:00:00` | Earliest valid check-in time |
| `ATTENDANCE_BREAK_DURATION` | `01:00:00` | Break time deducted from working hours |

**Status Determination:**
| Status | Condition |
|--------|-----------|
| ON_TIME | First check-in time ≤ `ATTENDANCE_LATE_THRESHOLD` |
| LATE | First check-in time > `ATTENDANCE_LATE_THRESHOLD` |
| ABSENT | No check-in record for the date |

**Working Hours Calculation:**
```
Working Hours = (Last check-out time - First check-in time) - Break Duration
```

**Key Model Methods (`Attendance`):**
| Method | Purpose |
|--------|---------|
| `recordFromDevice($data)` | Batch-process device records with dedup + timezone fix |
| `mapUserId($userSn)` | Resolve device enrollment → system user ID |
| `lastEntry($ip)` | Get most recent record from a device IP (for dedup) |
| `getUserAttendanceStatus($start, $end, $userId)` | Early/late/absent counts for date range |
| `isAttendEarly($staffId, $date)` | Check if staff checked in before late threshold |
| `getStaffInTime($staffId, $date)` | First check-in time for a date |
| `getStaffOutTime($staffId, $date)` | Last check-out time for a date |
| `getTotalDaysAttended($staffId, $start, $end)` | Count of distinct dates with attendance |
| `getAttendanceStatus($userId, $date)` | Full status object (status, time_in, time_out, is_late, working_hours) |
| `isLate($userId, $date)` | Boolean late check against threshold |
| `displayDates($date1, $date2)` | Generate array of dates in range |

#### 17.6 Web Reports

Two attendance report pages available under **Reports** module:

**Attendances Report** (`/reports/attendances_report`):
- **Purpose:** Monthly attendance matrix — rows are staff, columns are dates
- **Filters:** Start date, end date, department, search (name/email/device ID), per-page pagination
- **Columns per staff:** Name, department, device ID, then one column per date in range showing:
  - Green check icon + time = on-time check-in
  - Orange warning icon + time = late check-in
  - Gray minus icon = absent
- **Summary per staff:** Early days count, late days count, absent days count
- **Data source:** Bulk query on `attendances` table grouped by `user_id` and `DATE(record_time)`

**Daily Attendances Report** (`/reports/daily_attendances_report`):
- **Purpose:** Single-day detailed view — all staff attendance for one date
- **Filters:** Date, department, attendance type, search
- **Columns:** Name, department, device ID, attendance type, check-in time, status badge (ON_TIME/LATE/ABSENT)
- **Summary stats:** Total users, present count, on-time count, late count, absent count

#### 17.7 Mobile App API

The mobile app (Section 18 — API V1) provides additional attendance endpoints for on-site check-in with GPS:

**Endpoints (`/api/v1/attendance/*`):**
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/attendance` | List user's attendance records (paginated, date-range filter) |
| POST | `/attendance/check-in` | Manual check-in with GPS + optional comment |
| POST | `/attendance/check-out` | Manual check-out with GPS + optional comment |
| GET | `/attendance/status` | Today's check-in/out status + working hours |
| GET | `/attendance/daily-report` | Daily report for all staff (admin view) |
| GET | `/attendance/summary` | Attendance summary for date range |

**Mobile Check-in Fields:**
| Field | Type | Required | Description |
|-------|------|----------|-------------|
| latitude | numeric | No | GPS latitude |
| longitude | numeric | No | GPS longitude |
| comment | string | No | Check-in note (max 500 chars) |
| device_time | datetime | No | Offline-captured timestamp for sync |

**Mobile Business Rules:**
- One check-in per day per user (duplicate prevention)
- Must check-in before check-out
- One check-out per day per user
- Supports offline sync via `device_time` parameter

#### 17.8 Attendance Types Configuration

**Route:** `/settings/attendance_types` (under Settings sidebar)
**Controller:** `AttendanceTypeController@index` — standard CRUD via `handleCrud()`

**Purpose:** Define categories of attendance (e.g., Office Staff, Field Workers, Remote Workers). Each user is assigned an `attendance_type_id` which can be used to filter daily reports.

**Form Fields:**
| Field | Type | Required |
|-------|------|----------|
| Name | Text | Yes |
| Description | Text | No |

#### 17.9 Integration with Payroll

Attendance data feeds into the payroll system:
- **Daily allowances** are prorated based on actual days attended (from `Attendance::getTotalDaysAttended()`)
- **Attendance reports** are reviewed by HR before payroll processing
- **Late/absent tracking** informs disciplinary and bonus decisions
- The payroll module (Section 7.2) uses attendance records to calculate working days for DAILY-type allowances

#### 17.10 Deployment Architecture

| Component | Location | Description |
|-----------|----------|-------------|
| ZKTeco Device(s) | Office premises | Physical biometric devices on LAN (e.g., 192.168.18.201) |
| Node.js Cron | Windows PC on same LAN | Runs periodically via Task Scheduler, connects to devices via UDP |
| Wajenzi API | Cloud server | `POST /api/attendance` receives data over HTTPS |
| MySQL Database | Cloud server | Stores `attendances` table |

**Network Requirements:**
- Cron machine must be on **same LAN** as ZKTeco devices (UDP port 4370)
- Cron machine needs **internet access** to reach the Wajenzi API endpoint
- ZKTeco devices do NOT need internet access — only LAN connectivity

**Error Handling:**
- Device connection failures logged to `logs.txt` (e.g., "Failed to connect to device 192.168.0.201")
- API submission failures logged as "Axios failure"
- Duplicate records silently skipped (not errors)
- If user mapping fails (no matching `user_device_id`), `user_id` defaults to 0

---

### 19. TRA Receipt Scanner (Separate Project)

Automated **Tanzania Revenue Authority (TRA)** fiscal receipt verification and data extraction system. A **Flutter mobile app** scans QR codes printed on TRA-compliant receipts, sends the verification code to a **Node.js Puppeteer scraper** that navigates the TRA verification website (`verify.tra.go.tz`), extracts all receipt data (company, customer, items, totals, tax), and uploads the structured data to the **Wajenzi Laravel API** for permanent storage.

**Project:** `~/Development/Moinfotech/tra_scanner_app` — multi-component architecture.

**Architecture Overview:**
```
┌────────────────┐   QR Scan    ┌────────────────────┐   HTTP GET         ┌─────────────────────┐
│  Flutter App   │─────────────►│  Node.js Scraper    │─────────────────►│  TRA verify.tra.go.tz│
│  (mobile_scanner)│  code+time  │  (Express+Puppeteer)│   Headless Chrome │  (Government site)   │
│  Android/iOS   │              │  Port 4000           │◄─────────────────│  Receipt verification│
└───────┬────────┘              └────────┬─────────────┘   Scraped data    └─────────────────────┘
        │                                │
        │  HTTP POST (scraped JSON)      │ Returns structured JSON
        │  Bearer token auth             │
        ▼                                │
┌────────────────┐                       │
│  Wajenzi Laravel│◄──────────────────────┘
│  /api/add_receipt│
│  MySQL (receipts,│
│  receipt_items)  │
└────────────────┘
```

**Scan Flow:**
1. User opens Flutter app → navigates to **Scan** tab
2. `MobileScanner` activates camera, reads QR code from TRA receipt
3. QR URL format: `https://verify.tra.go.tz/{verification_code}_{HHMMSS}`
4. App splits URL → extracts `code` and `time` parameters
5. App checks for duplicates (local cache + provider list)
6. **Request 1:** `GET {scraperUrl}/receipt/{code}/{time}` → Node.js scraper (60s timeout, 2 retries)
7. Scraper launches headless Chrome → navigates to TRA site → fills verification form → extracts data
8. **Request 2:** `POST {baseUrl}/add_receipt` → Wajenzi API with Bearer token auth
9. Laravel creates `Receipt` + `ReceiptItem` records in a DB transaction
10. App navigates to `ReceiptDetailPage` showing full receipt details

#### 19.1 Flutter Mobile App

**Project path:** `tra_scanner_app/frontend/flutter_receipt_scanner/`

**Technology Stack:**
| Package | Version | Purpose |
|---------|---------|---------|
| `mobile_scanner` | ^3.4.1 | QR/barcode scanning via device camera |
| `http` | ^0.13.5 | HTTP client for API calls |
| `provider` | ^6.0.4 | State management (`ReceiptProvider`, `AppState`) |
| `shared_preferences` | ^2.2.0 | Persist login token, theme, locale |
| `local_auth` | ^2.1.6 | Biometric (fingerprint/face) login |
| `url_launcher` | 6.3.1 | Open TRA verification URL in browser |
| `intl` | ^0.17.0 | Date/number formatting |

**App Structure:**
| File | Purpose |
|------|---------|
| `main.dart` | Entry point, `MyApp`, models (`Receipt`, `Customer`, `Item`, `InvoiceAdjustment`, `InvoicePayment`), `ReceiptProvider`, `ScanPage`, `ReceiptListPage`, `ReceiptDetailPage` |
| `login_page.dart` | Email/password login + biometric authentication |
| `main_shell.dart` | Bottom navigation shell (Dashboard, Receipts, Scan, Profile), `ProfilePage` |
| `dashboard_page.dart` | Orbital stats layout (total receipts, total amount, today's scans, avg value, total tax) |
| `app_state.dart` | `ChangeNotifier` for theme mode (light/dark) and locale (en/sw) |
| `l10n.dart` | Inline i18n translations — English + Swahili (50+ keys) |
| `utils/api_request_status.dart` | API status enum (loading, loaded, error, networkError) |

**API Configuration (`ApiConfig`):**
```
Production:  https://lemuru.co.tz/api
Local dev:   http://10.0.2.2:8000/api
Scraper:     http://50.116.44.162:4000
```

**Navigation Tabs:**
| Tab | Page | Description |
|-----|------|-------------|
| Dashboard | `DashboardPage` | Stats cards in orbital layout, recent receipts list, date range filter |
| Receipts | `ReceiptListPage` | Paginated receipt list, search by company name, date range filter |
| Scan | `ScanPage` | Opens camera → QR scan → scrape → save (navigates away then back) |
| Profile | `ProfilePage` | User info, dark mode toggle, biometric toggle, language selector, logout |

#### 19.2 QR Code Scanning (ScanPage)

The `ScanPage` uses `MobileScanner` widget to detect QR codes from the device camera.

**QR Code Parsing:**
- TRA receipt QR codes encode a URL: `https://verify.tra.go.tz/{code}_{HHMMSS}`
- App splits the last URL segment on `_` to extract `code` and `time`
- If the split doesn't produce exactly 2 parts, shows "Receipt Incorrect" error

**Duplicate Detection:**
- Checks `ReceiptProvider.checkIfReceiptExists(code)` — compares against `verificationCode` in loaded receipts
- Maintains a `_scannedCodes` set for codes scanned in the current session
- If duplicate found, shows "Receipt already scanned!" with close button

**Camera Controls:**
- Toggle flashlight (torch on/off)
- Switch camera (front/rear)

#### 19.3 Node.js Puppeteer Scraper

**Project path:** `tra_scanner_app/server/node-tra-crawler/`

**Technology:** Express.js 4.18 + Puppeteer 19.2

**Endpoint:** `GET /receipt/:code/:time`

**Scraping Logic:**
1. Parse `time` parameter: `HHMMSS` → `HH:MM:SS`
2. Construct URL: `https://verify.tra.go.tz/{code}_{time}`
3. Launch headless Chrome (`--no-sandbox` flag)
4. Navigate to TRA verification URL (30s timeout, `networkidle0`)
5. Check if already on verified page (`/Verify/Verified?Secret={HH:MM:SS}`)
6. If not verified yet, fill the verification form:
   - Type verification code into text input
   - Click submit button
   - Wait for time dropdowns (`#HH`, `#MM`, `#SS`)
   - Select hour, minute, second values
   - Click verify button
   - Wait for `.invoice-header` selector
7. Execute `page.evaluate()` to extract DOM data

**Extracted Fields (20+):**
| Category | Fields |
|----------|--------|
| Company | `company_name`, `p_o_box`, `mobile`, `tin`, `vrn`, `serial_no`, `uin`, `tax_office` |
| Customer | `customer_name`, `customer_id_type`, `customer_id`, `customer_mobile` |
| Receipt | `receipt_number`, `receipt_z_number`, `receipt_date`, `receipt_time`, `receipt_verification_code` |
| Items | Array of `{item_description, item_qty, item_amount}` |
| Totals | `receipt_total_excl_of_tax`, `receipt_total_discount`, `receipt_total_tax`, `receipt_total_incl_of_tax` |

**DOM Extraction Strategy:**
- Company info: `.invoice-header b` elements for company name, `.invoice-info .invoice-col b` for details
- Customer info: `getValueAfterBold()` helper handles both old format (`<b>LABEL:</b> value` text node) and new format (`<b>LABEL:</b><span>value</span>`)
- Items table: Identifies by excluding rows containing "TOTAL" or "DESCRIPTION" headers
- Totals table: Identifies by row labels containing "TOTAL EXCL", "DISCOUNT", "TOTAL TAX", "TOTAL INCL"
- Verification code: Tries `headers[3].children[1].firstChild.innerText`, falls back to regex on body text

#### 19.4 Wajenzi Laravel API (Receiver)

**Routes** (`routes/api.php`):
| Method | URI | Controller | Purpose |
|--------|-----|------------|---------|
| GET/POST | `/api/add_receipt` | `ReceiptController@store` | Create receipt + items |
| GET/POST | `/api/add_receipt_item` | `ReceiptItemController@store` | Add single item to receipt |
| GET | `/api/receipts/{id?}` | `ApiController@receipts` | List receipts (with items, adjustments, payments) |
| GET | `/api/receipt_items/{id?}` | `ApiController@receipt_items` | List receipt items |

**`ReceiptController@store` — Main Ingest Endpoint:**
- Wraps everything in `DB::beginTransaction()` / `DB::commit()`
- Creates `Receipt` with all 25+ fields from scraped JSON
- Auto-detects **TANESCO** receipts by company name (`str_contains` for "tanzania electric supply" or "tanesco")
- Sets `is_tanesco` flag and TANESCO-specific fields: `kwh_charge`, `kva_charge`, `service_charge`, `interest_amount`, `receipt_rea`, `receipt_ewura`, `receipt_property_tax`
- Creates `ReceiptItem` records from `items` array (handles both `item_description`/`item_qty`/`item_amount` and `description`/`qty`/`amount` field names)
- Creates `InvoiceAdjustment` records (for TANESCO: auto-creates default "Marekebisho/Adjustment" if none provided)
- Creates `InvoicePayment` records (for TANESCO: auto-creates default "Kiasi kilichobaki/Balance B/Fwd" if none provided)
- Returns `{message, receipt_id}` on success, 500 with error details on failure

**`ReceiptItemController@store` — Individual Item Endpoint:**
- Validates: `receipt_id` (required, exists), `description` (required), `qty` (numeric), `amount` (numeric)
- For TANESCO receipts, auto-updates parent `Receipt` fields based on item description keywords (`kwh` → `kwh_charge`, `kva` → `kva_charge`, `service charge` → `service_charge`)

#### 19.5 Data Model

**`receipts` table:**
| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `company_name` | varchar | Business name from TRA |
| `p_o_box` | varchar | P.O. Box address |
| `mobile` | varchar | Business phone |
| `tin` | varchar | Tax Identification Number |
| `vrn` | varchar | VAT Registration Number |
| `serial_no` | varchar | EFD serial number |
| `uin` | varchar | Unique Identification Number |
| `tax_office` | varchar | TRA tax office |
| `customer_name` | varchar | Buyer name |
| `customer_id_type` | varchar | ID type (NIN, Passport, etc.) |
| `customer_id` | varchar | Customer ID value |
| `customer_mobile` | varchar | Customer phone |
| `receipt_number` | varchar | TRA receipt number |
| `receipt_z_number` | varchar | Z-report number |
| `receipt_date` | varchar | Receipt date |
| `receipt_time` | varchar | Receipt time |
| `receipt_verification_code` | varchar | QR verification code (unique identifier) |
| `receipt_total_excl_of_tax` | decimal | Total before tax |
| `receipt_total_discount` | decimal | Discount amount |
| `receipt_total_tax` | decimal | Tax amount |
| `receipt_total_incl_of_tax` | decimal | Total including tax |
| `kwh_charge` | decimal | TANESCO: KWH energy charge |
| `kva_charge` | decimal | TANESCO: KVA demand charge |
| `service_charge` | decimal | TANESCO: Service charge |
| `interest_amount` | decimal | TANESCO: Interest |
| `receipt_rea` | decimal | TANESCO: Rural Energy Agency levy |
| `receipt_ewura` | decimal | TANESCO: EWURA levy |
| `receipt_property_tax` | decimal | TANESCO: Property tax |
| `tax_rate` | decimal | Tax rate percentage |
| `is_tanesco` | boolean | Auto-detected TANESCO flag |
| `date` | date | Copied from receipt_date |
| `is_expense` | enum(YES/NO) | Expense classification flag |

**`receipt_items` table:**
| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `receipt_id` | bigint FK | References `receipts.id` |
| `description` | varchar | Item description |
| `qty` | integer | Quantity |
| `amount` | decimal(10,2) | Amount |

**Relationships:**
- `Receipt` → `hasMany(ReceiptItem)` via `items()`
- `Receipt` → `hasMany(InvoiceAdjustment)` via `adjustments()`
- `Receipt` → `hasMany(InvoicePayment)` via `payments()`
- `Receipt` uses `TANESCOReceiptTrait` for `isTanescoReceipt()`, `getTotalAdjustments()`, `getTotalPayments()`, `getBalanceAmount()`
- `Receipt::isExist($code)` — static duplicate check by verification code

#### 19.6 TANESCO Receipt Handling

Special handling for **Tanzania Electric Supply Company (TANESCO)** electricity receipts:

**Auto-Detection:** `str_contains(strtolower($company_name), 'tanesco')` or `str_contains(strtolower($company_name), 'tanzania electric supply')`

**TANESCO-Specific Fields:**
| Field | Description |
|-------|-------------|
| `kwh_charge` | Energy consumption charge |
| `kva_charge` | Demand/capacity charge |
| `service_charge` | Fixed service charge |
| `interest_amount` | Late payment interest |
| `receipt_rea` | Rural Energy Agency levy |
| `receipt_ewura` | EWURA regulatory levy |
| `receipt_property_tax` | Local government property tax |

**Auto-Created Records (TANESCO only):**
- If no `adjustments` provided → creates default `InvoiceAdjustment` (type: CR, description: "Marekebisho/Adjustment", amount: 0)
- If no `payments` provided → creates default `InvoicePayment` (type: CR, description: "Kiasi kilichobaki/Balance B/Fwd", amount: 0)
- `ReceiptItemController` auto-updates parent receipt TANESCO fields based on item description keywords

**Flutter Display:** The `ReceiptDetailPage` conditionally renders TANESCO-specific totals (KWH, KVA, service charge, interest, REA, EWURA, property tax) only when `receipt.isTanesco` is true.

#### 19.7 Flutter State Management

**`ReceiptProvider` (ChangeNotifier):**
| Method | Description |
|--------|-------------|
| `fetchReceipts({startDate, endDate, searchTerm})` | Paginated receipt list from `/api/receipts?page=N`, supports date range + search filters |
| `loadMoreReceipts()` | Loads next page (infinite scroll), appends to existing list |
| `fetchDashboard({startDate, endDate})` | Stats from `/api/dashboard?start_date=&end_date=` |
| `checkIfReceiptExists(code)` | Local duplicate check by verification code |
| `getReceiptsFromJson(json)` | JSON → `List<Receipt>` parser with error tolerance |

**Pagination:** Server returns `{receipts: {data: [...], meta: {current_page, last_page}}}`. Provider tracks `_currentPage`, `_hasMoreData`. `ReceiptListPage` uses `ScrollController` listener to trigger `loadMoreReceipts()` at 200px from bottom.

**Offline Mode:** If token starts with `offline_`, skips API calls. Reads receipts from `SharedPreferences` key `offline_receipts`. Dashboard computed locally from cached data.

**`AppState` (ChangeNotifier):**
- `themeMode` (light/dark) — persisted to SharedPreferences
- `locale` (en/sw) — persisted to SharedPreferences

#### 19.8 Authentication

**Login Methods:**
1. **Email/Password:** POST to `/api/login` → receives `{token, user}` → stores in SharedPreferences → navigates to MainShell
2. **Biometric (Fingerprint/Face):** Uses `local_auth` package. On login page, validates stored token with `GET /api/validate-token`. If valid, proceeds; if expired, clears token and requires password login.

**Token Management:**
- Stored in `SharedPreferences` as `token`
- Sent as `Authorization: Bearer {token}` header on all API calls
- On logout: if biometric is OFF, invalidates token on server + clears locally; if biometric is ON, keeps token for next biometric login
- 401 responses clear login state and redirect to login

#### 19.9 Internationalization (i18n)

**Supported Languages:** English (`en`) and Swahili (`sw`)

**Implementation:** Inline `Map<String, Map<String, String>>` in `l10n.dart` with 50+ translation keys. `L.tr(context, key)` resolves locale from `AppState` provider. Falls back to English if Swahili translation missing.

**Coverage:**
- Login page (title, subtitle, fields, errors, biometric prompts)
- Receipt list (search, filters, counts)
- Receipt detail (all section headers, field labels, totals)
- Scan page (status messages, error messages)
- Dashboard (stat labels, date range)
- Profile (settings labels, logout confirmation)
- General errors (network, timeout, format)

#### 19.10 Receipt Detail View

The `ReceiptDetailPage` renders a full receipt in a card layout with themed styling (light/dark mode):

**Sections:**
1. **Company Header** — Company name, P.O. Box, phone, TIN, VRN, serial number, UIN, tax office (icon labels)
2. **Customer Info** — Name, ID type, ID number, mobile (label-value rows)
3. **Receipt Details** — Receipt number, Z number, date + time
4. **Items Table** — Description, qty, amount (striped rows with blue header)
5. **Adjustments** (conditional) — Type, description, amount (TANESCO receipts)
6. **Payments** (conditional) — Type, description, amount (TANESCO receipts)
7. **Totals** — Total excl. tax, TANESCO charges (conditional), total tax, REA/EWURA/property tax (conditional)
8. **Grand Total** — Blue gradient banner with total incl. tax in large bold white text

**Browser Verification:** AppBar action button opens TRA verification URL in external browser: `https://verify.tra.go.tz/{code}_{time}`

#### 19.11 Dashboard

The `DashboardPage` displays an **orbital stats layout** — 4 stat cards arranged in a circle around a center card:

| Position | Stat | Icon |
|----------|------|------|
| Top | Total Receipts | `receipt_long` |
| Right | Total Amount | `account_balance_wallet` |
| Bottom | Today's Scans | `today` |
| Left | Avg Value | `analytics_outlined` |
| Center | Total Tax | `price_change_outlined` |

**Design Features:**
- Glassmorphism blur effect (`BackdropFilter`)
- Decorative orbit ring (circular border)
- Compact number formatting (1.2K, 1.2M, 1.2B)
- Pull-to-refresh triggers `fetchDashboard()`
- Date range picker in AppBar filters dashboard data
- Recent activity list below stats (receipt cards with time-ago labels)

#### 19.12 Deployment Architecture

```
┌──────────────────────────────────────────────────────────────┐
│  Production                                                   │
│                                                               │
│  Mobile App (Flutter)                                         │
│  ├── Android (APK/Play Store)                                │
│  └── iOS (App Store)                                          │
│       │                                                       │
│       ▼                                                       │
│  Node.js Scraper Server                                       │
│  ├── IP: 50.116.44.162:4000                                  │
│  ├── Express + Puppeteer (headless Chrome)                   │
│  └── Endpoint: GET /receipt/:code/:time                      │
│       │                                                       │
│       ▼                                                       │
│  Wajenzi Laravel API                                          │
│  ├── Host: lemuru.co.tz                                       │
│  ├── Endpoints: /api/add_receipt, /api/receipts, /api/dashboard│
│  └── MySQL: receipts + receipt_items tables                   │
└──────────────────────────────────────────────────────────────┘
```

**Scraper Timeout Budget:**
- Puppeteer navigation: 30s
- Form fill + submit: ~5s
- DOM extraction: ~1s
- Flutter HTTP timeout: 60s (allows for slow TRA site responses)
- Retry policy: 2 attempts with 2s delay between retries

---

### 20. Wajenzi Mobile App (Flutter)

**Overview**: Cross-platform Flutter mobile application serving both **staff** (employees) and **clients** (project owners) through a single codebase with role-based navigation, dashboards, and feature sets. The app connects to the Wajenzi Laravel backend via REST API and includes offline-first capabilities with local SQLite storage and background sync.

**Technology Stack**:

| Component | Technology |
|-----------|-----------|
| Framework | Flutter 3.10+ / Dart |
| State Management | Riverpod (StateNotifier, FutureProvider, ConsumerWidget) |
| Navigation | GoRouter with auth redirect guards |
| HTTP Client | Dio with interceptors (auth, logging) |
| Local Database | Drift (SQLite) with 7 tables |
| Architecture | Clean Architecture (core → data → domain → presentation) |
| Theming | Material 3 with dark/light mode |
| i18n | English + Swahili (inline translations) |
| Storage | Flutter Secure Storage (tokens), SharedPreferences (settings) |
| Background | WorkManager for sync tasks |
| Push Notifications | Firebase Cloud Messaging (configured) |
| Location | Geolocator + Permission Handler |

**Project Structure** (`wajenzi_mobile/lib/`):

```
lib/
├── main.dart                          # Entry point, ProviderScope, MaterialApp.router
├── core/
│   ├── config/
│   │   ├── app_config.dart            # API URLs, timeouts, feature flags
│   │   └── theme_config.dart          # AppColors, AppTheme (light/dark)
│   ├── network/
│   │   ├── api_client.dart            # Dio wrapper + AuthInterceptor
│   │   └── network_info.dart          # Connectivity monitoring providers
│   ├── router/
│   │   └── app_router.dart            # GoRouter (20+ routes, auth guard)
│   └── services/
│       └── storage_service.dart       # Secure token/credential storage
├── data/
│   ├── datasources/
│   │   ├── local/
│   │   │   └── database.dart          # Drift AppDatabase (7 tables)
│   │   └── remote/
│   │       ├── auth_api.dart          # Staff + client auth endpoints
│   │       ├── staff_dashboard_api.dart  # Staff dashboard, calendar, activities
│   │       └── client_api.dart        # Client dashboard, projects, billing, reports
│   └── models/
│       └── user_model.dart            # Freezed user model with code generation
├── domain/                            # (Reserved for use-cases / repositories)
├── l10n/                              # Localization utilities
└── presentation/
    ├── providers/
    │   ├── auth_provider.dart         # AuthNotifier (dual login, token management)
    │   ├── staff_dashboard_provider.dart
    │   ├── client_dashboard_provider.dart
    │   ├── client_billing_provider.dart
    │   ├── client_project_detail_provider.dart
    │   ├── cart_provider.dart
    │   └── settings_provider.dart     # Dark mode + language toggles
    ├── screens/
    │   ├── landing/                   # Public landing page
    │   ├── dashboard/                 # Staff + client dashboards
    │   ├── projects/                  # Staff projects, client project detail
    │   ├── billing/                   # Staff + client billing
    │   ├── attendance/                # Daily attendance report
    │   ├── procurement/               # Procurement overview
    │   ├── expenses/                  # Expense list
    │   ├── approvals/                 # Pending approvals
    │   ├── vat/                       # VAT sales, purchases, auto-purchases, payments
    │   ├── reports/                   # Site daily reports
    │   ├── employee_profile/          # Staff profile
    │   ├── settings/                  # Profile, password, legal, app settings
    │   ├── about/                     # Company about page
    │   ├── services/                  # Company services page
    │   ├── awards/                    # Company awards page
    │   └── cart/                      # Cart / procurement cart
    └── widgets/
        └── curved_internal_nav.dart   # Custom glassmorphism bottom navigation
```

---

#### 20.1 Dual User-Type Architecture

The app supports two distinct user types from a single codebase, determined at login time:

| Aspect | Staff | Client |
|--------|-------|--------|
| Login endpoint | `POST /api/v1/auth/login` | `POST /api/client/auth/login` |
| API base | `/api/v1/*` | `/api/client/*` |
| Dashboard | `DashboardScreen` (revenue, projects, approvals, calendar) | `ClientDashboardScreen` (projects, contract values) |
| Bottom nav | 5 tabs: Projects, Billing, Home, Procurement, Attendance | 3 tabs: Home, Billing, Settings |
| Drawer menu | Dynamic from `/menus` API | Static (Dashboard, Billing, Settings) |
| Available screens | ~20 screens (projects, billing, procurement, attendance, expenses, approvals, VAT, employee profile) | ~8 screens (dashboard, project detail with 7 tabs, billing, settings) |

**Login Flow**: The `AuthNotifier` first attempts staff login. On 401/422 response, it automatically falls back to client login. The `userType` (`staff` or `client`) is stored alongside the auth token in secure storage, enabling the router and navigation to render the appropriate experience.

```
User enters email + password
  → POST /api/v1/auth/login (staff attempt)
     ├─ 200 → userType = 'staff', store token, navigate to /dashboard
     └─ 401/422 → POST /api/client/auth/login (client fallback)
                    ├─ 200 → userType = 'client', store token, navigate to /dashboard
                    └─ error → Show login error
```

---

#### 20.2 Navigation & Routing

**GoRouter** handles all navigation with an auth redirect guard that sends unauthenticated users to `/login`.

**Public Routes** (no auth required):

| Route | Screen | Description |
|-------|--------|-------------|
| `/` | `LandingScreen` | Project showcase with WhatsApp inquiry |
| `/login` | `LoginScreen` | Email/password login |
| `/about` | `AboutScreen` | Company information |
| `/services` | `ServicesScreen` | Construction services offered |
| `/projects` | `ProjectsScreen` | Public project portfolio |
| `/awards` | `AwardsScreen` | Company awards & recognition |

**Protected Routes** (inside `ShellRoute` with `MainScaffold`):

| Route | Screen | User Type |
|-------|--------|-----------|
| `/dashboard` | `DashboardScreen` / `ClientDashboardScreen` | Both (type-switched) |
| `/staff-projects` | `StaffProjectsScreen` | Staff |
| `/staff-billing` | `StaffBillingScreen` | Staff |
| `/procurement` | `ProcurementScreen` | Staff |
| `/attendance` | `AttendanceScreen` | Staff |
| `/expenses` | `ExpenseListScreen` | Staff |
| `/approvals` | `ApprovalsScreen` | Staff |
| `/billing` | `ClientBillingScreen` | Client |
| `/settings` | `SettingsScreen` | Both |
| `/employee-profile` | `EmployeeProfileScreen` | Staff |
| `/vat-sales` | `VatSalesScreen` | Staff |
| `/vat-purchases` | `VatPurchasesScreen` | Staff |
| `/vat-auto-purchases` | `VatAutoPurchasesScreen` | Staff |
| `/vat-payments` | `VatPaymentsScreen` | Staff |
| `/project/:id` | `ClientProjectDetailScreen` | Client |
| `/dashboard/activities` | `ActivitiesScreen` | Staff |
| `/dashboard/followups` | `FollowupsScreen` | Staff |
| `/dashboard/invoices` | `InvoicesScreen` | Staff |
| `/profile` | `ProfileScreen` | Both |
| `/change-password` | `ChangePasswordScreen` | Both |

**Custom Bottom Navigation**: The app uses a custom `CurvedInternalNav` widget with glassmorphism effects (BackdropFilter blur, gradient overlay). The navigation dynamically reorders items so the **active tab is always centered** in the bar, creating a focal notch effect. Staff sees 5 items, client sees 3 items.

**Staff Drawer Menu**: Fetched from `/menus` API at runtime. Each menu item's web route name is mapped to a Flutter route via `_mapWebRoute()`, and FontAwesome icon classes are mapped to Material Icons via `_mapFaIcon()` (50+ icon mappings). This ensures the mobile app's sidebar mirrors the web app's sidebar dynamically.

---

#### 20.3 Staff Dashboard

The staff dashboard (`DashboardScreen`) provides a comprehensive business overview with glassmorphism card design.

**Sections** (top to bottom):

1. **Welcome Header** — Personalized greeting with user's first name
2. **Stat Cards** (2x2 grid):
   - Revenue (MTD) with percentage change badge
   - Active Projects with new-this-month count
   - Team Members (total count)
   - Budget Used (percentage with color warning at >90%)
3. **Pending Approvals** — List of approval types (Material Requests, Invoices, Payments, Site Visits, Reports) with icon, count badge, and "Requires your attention" subtitle. Only shown when `total > 0`.
4. **Activities & Invoices Summary** (side-by-side cards, tappable → detail screens):
   - Activities: Overdue / In Progress / Pending counts
   - Invoices: Overdue / Due Today / Upcoming counts
5. **Follow-ups Summary** — Horizontal pill row: Overdue / Today / Upcoming / Done (completed this month)
6. **Project Progress** — Overall percentage ring chart + per-status counts (Completed, In Progress, Pending, Overdue). Below: per-project cards with multi-segment progress bar and status icon counts.
7. **Calendar Widget** — Monthly calendar grid fetched from `/dashboard/calendar` API. Days with events show colored borders/dots (green=follow-up, blue=activity, amber=invoice, red=overdue). Tapping a day opens a bottom sheet with grouped events. Month navigation with prev/next arrows.

**API Endpoint**: `GET /api/v1/dashboard`

**Data Model** (`StaffDashboardData`):

| Field | Type | Description |
|-------|------|-------------|
| `stats` | `DashboardStats` | Revenue, active projects, team members, budget utilization |
| `pendingApprovals` | `PendingApprovals` | Total count + list of `ApprovalItem` (label, count, icon) |
| `followupSummary` | `StatusSummary` | Overdue, today, upcoming, completed this month |
| `activitiesSummary` | `ActivitiesSummary` | Overdue, in progress, pending |
| `invoicesSummary` | `InvoicesSummary` | Overdue, due today, upcoming |
| `projectProgress` | `ProjectProgressData` | Overall %, status counts, per-project items |

---

#### 20.4 Client Dashboard

The client dashboard (`ClientDashboardScreen`) shows a project-centric overview.

**Sections**:

1. **Welcome Header** — Personalized greeting
2. **Stat Cards** (2x2 grid):
   - Total Projects
   - Active Projects
   - Contract Value (TZS, abbreviated: B/M)
   - Total Invoiced (TZS)
3. **Your Projects** — List of project cards, each showing:
   - Project name + document number
   - Status badge (Active, Completed, On Hold, Pending, Cancelled) with color coding
   - Date range (start → expected end)
   - Contract value
   - Count chips: BOQ items, Invoices, Reports

Tapping a project card navigates to `/project/:id` (ClientProjectDetailScreen).

**API Endpoint**: `GET /api/client/dashboard`

**Data Model** (`ClientDashboardData`):

| Field | Type |
|-------|------|
| `totalProjects` | `int` |
| `activeProjects` | `int` |
| `totalContractValue` | `double` |
| `totalInvoiced` | `double` |
| `projects` | `List<ClientProject>` |

---

#### 20.5 Client Project Detail

The `ClientProjectDetailScreen` displays comprehensive project information across **7 tabs** with lazy loading (tabs only fetch data when first visited):

| Tab | Content | API Endpoint |
|-----|---------|--------------|
| **Overview** | Project progress circle, status, dates, contract value, team, description, client info | `GET /api/client/projects/{id}` |
| **BOQ** | Bill of Quantities grouped by sections with material/labour items, quantities, rates, amounts | `GET /api/client/projects/{id}/boq` |
| **Schedule** | Construction phases with activities, start/end dates, status, progress bars | `GET /api/client/projects/{id}/schedule` |
| **Financials** | Contract value, total invoiced, total paid, balance; payment history list | `GET /api/client/projects/{id}/financials` |
| **Documents** | Project designs/drawings with PDF view/download capability | `GET /api/client/projects/{id}/documents` |
| **Reports** | Daily reports (site conditions, activities, issues) + site visit reports with PDF download | `GET /api/client/projects/{id}/reports` |
| **Gallery** | Progress photos organized by date, with full-screen image viewer (pinch-to-zoom) | `GET /api/client/projects/{id}/gallery` |

**Key Data Models**:

- `ProjectDetail`: name, status, progress, startDate, expectedEndDate, contractValue, description, team, client info
- `ProjectBoq`: sections → items (description, unit, quantity, rate, amount, type: material/labour)
- `ScheduleActivity`: name, phase, startDate, endDate, progress, status, dependencies
- `ProjectFinancials`: contractValue, totalInvoiced, totalPaid, balance, payments list
- `ProgressImage`: imageUrl, date, description, uploadedBy

---

#### 20.6 Staff Features

**Attendance Screen** (`/attendance`):
- Daily attendance report with date picker navigation
- Stats summary: present, absent, late, on-leave counts
- Staff list with individual attendance status
- Pull-to-refresh with `RefreshIndicator`
- API: `GET /api/v1/attendance/daily-report?date=YYYY-MM-DD`

**Staff Billing Screen** (`/staff-billing`):
- List of billing documents (invoices, payments, credit notes)
- Each card shows document number, amount (TZS formatted), date, status badge
- Pull-to-refresh
- API: `GET /api/v1/billing/documents`

**Procurement Screen** (`/procurement`):
- Material requests, purchase orders, supplier quotations overview
- Status-based filtering

**Expenses Screen** (`/expenses`):
- Expense list with categories and amounts

**Approvals Screen** (`/approvals`):
- Pending approval items across document types
- Approve/reject actions

**VAT Screens** (4 screens):
- VAT Sales, VAT Purchases, VAT Auto-Purchases, VAT Payments
- Separate views for each VAT register type

**Employee Profile Screen** (`/employee-profile`):
- Personal details, designation, department
- Employment information

**Drill-Down Screens** (from dashboard):
- Activities list with status filters
- Invoices list with due-date grouping
- Follow-ups list with overdue highlighting

---

#### 20.7 Client Billing

The client billing screen provides:

- **Billing Documents**: List of invoices with status (Paid, Unpaid, Partial, Overdue), amounts, dates
- **Payments**: Payment history with method, reference, date, amount
- **PDF Download**: Direct PDF generation URLs (`/billing/invoices/{id}/pdf`, `/site-visits/{id}/pdf`)
- **Summary Stats**: Total invoiced, total paid, outstanding balance

**API Endpoint**: `GET /api/client/billing`

---

#### 20.8 Networking & API Client

The `ApiClient` wraps Dio with two interceptors:

**AuthInterceptor**:
- Injects `Authorization: Bearer <token>` header on every request
- On 401 response → clears stored credentials → navigates to login
- Reads token from `StorageService` (Flutter Secure Storage)

**LoggingInterceptor** (debug only):
- Logs request method, URL, headers
- Logs response status code and data

**API Configuration** (`AppConfig`):

| Setting | Value |
|---------|-------|
| Production API | `https://wajenziprosystem.co.tz/api/v1` |
| Development API | `http://localhost:8000/api/v1` |
| Client API base | Replaces `/api/v1` with `/api/client` |
| Connect timeout | 30s |
| Receive timeout | 30s |
| Max retries | 3 |

**Methods**: `get()`, `post()`, `put()`, `delete()`, `uploadFile()` — all return Dio `Response`.

---

#### 20.9 Offline-First Architecture

**Drift SQLite Database** (`wajenzi.db`) with 7 tables:

| Table | Purpose |
|-------|---------|
| `SyncQueue` | Pending offline operations (action, endpoint, payload, status, retryCount, createdAt) |
| `Users` | Cached user profiles |
| `Attendances` | Locally recorded attendance entries |
| `SiteDailyReports` | Offline daily report submissions |
| `Expenses` | Locally created expense records |
| `Projects` | Cached project list |
| `Sites` | Cached site information |

**Sync Queue Operations**:
- `addToSyncQueue(action, endpoint, payload)` — Queues an offline operation
- `getPendingSyncItems()` — Retrieves unsynced items (status = 'pending')
- `markSyncItemComplete(id)` — Marks successful sync
- `markSyncItemFailed(id)` — Marks failed sync attempt
- `clearCompletedSyncItems()` — Cleanup

**Background Sync**: WorkManager schedules periodic sync tasks to process the queue when connectivity is restored.

**Feature Flags** (`AppConfig`):
- `enableOfflineMode`: Toggle offline capabilities
- `enablePushNotifications`: Toggle FCM
- `enableBiometricAuth`: Toggle biometric login

---

#### 20.10 State Management (Riverpod)

All state management uses `flutter_riverpod`:

| Provider | Type | Purpose |
|----------|------|---------|
| `authStateProvider` | `StateNotifierProvider<AuthNotifier, AsyncValue<AuthState>>` | Login/logout, token, user, userType |
| `staffDashboardProvider` | `StateNotifierProvider` | Staff dashboard data + loading/error states |
| `clientDashboardProvider` | `StateNotifierProvider` | Client dashboard data |
| `settingsProvider` | `StateNotifierProvider<SettingsNotifier, SettingsState>` | Dark mode + language (persisted to SharedPreferences) |
| `isDarkModeProvider` | `Provider<bool>` | Convenience read from settings |
| `isSwahiliProvider` | `Provider<bool>` | Convenience read from settings |
| `apiClientProvider` | `Provider<ApiClient>` | Singleton Dio-based HTTP client |
| `staffDashboardApiProvider` | `Provider<StaffDashboardApi>` | Staff API wrapper |
| `clientApiProvider` | `Provider<ClientApi>` | Client API wrapper |
| `isOnlineProvider` | `Provider<bool>` | Network connectivity status |
| Per-screen providers | `FutureProvider.autoDispose` | Attendance, billing, etc. (auto-disposed when screen unmount) |

**Pattern**: Screens are `ConsumerStatefulWidget` or `ConsumerWidget`. Data fetching triggers in `initState()` via `Future.microtask()`, and the UI uses `asyncValue.when(loading: ..., error: ..., data: ...)` for tri-state rendering.

---

#### 20.11 Public Landing Page

The `LandingScreen` serves as a public showcase accessible without login:

**Features**:
- **SliverAppBar** with company logo, language toggle, theme toggle, and login button
- **Project Showcase**: 6 featured projects with construction photos, TZS/USD pricing, and project descriptions
- **Glassmorphism Cards**: Each project displayed with backdrop blur overlay, gradient borders
- **WhatsApp Inquiry**: Per-project "Inquire via WhatsApp" button that opens WhatsApp with pre-filled message
- **Image Modal**: Full-screen image viewer with pinch-to-zoom (`InteractiveViewer`)
- **Stats Badges**: Company statistics (projects completed, years of experience, etc.)
- **Bottom CTA**: Call-to-action section with company contact information
- **Public Navigation**: `CurvedBottomNav` with Home, About, Services, Projects tabs

---

#### 20.12 UI Design System

**Glassmorphism Theme**: The entire app uses a consistent glassmorphism design language:

```dart
// Glass container pattern used across all screens
ClipRRect(
  borderRadius: BorderRadius.circular(16),
  child: BackdropFilter(
    filter: ImageFilter.blur(sigmaX: 12, sigmaY: 12),
    child: Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: isDarkMode
              ? [Colors.white.withAlpha(20), Colors.white.withAlpha(10)]
              : [Colors.white.withAlpha(191), Colors.white.withAlpha(140)],
        ),
      ),
    ),
  ),
)
```

**Color System** (`AppColors`):
- Primary, secondary, success, warning, error, info, draft
- Text: primary, secondary, hint
- Surfaces adapt to dark/light mode

**Custom Widgets**:
- `CurvedInternalNav`: ClipPath with `_CurvedNavClipper` for notch shape + `_CurvedNavBorderPainter` for glow border
- `_GlassContainer`: Reusable glass card with blur, gradient, shadow
- `_StatCard`: Metric card with icon, value, optional change badge
- `_ProjectProgressCard`: Multi-segment progress bar with status counts

**Theming**: `AppTheme` provides `lightTheme` and `darkTheme` MaterialThemeData. Toggle persisted via SharedPreferences.

---

#### 20.13 Internationalization

The app supports English and Swahili with inline translation throughout all screens:

```dart
// Pattern used in every screen
final isSwahili = ref.watch(isSwahiliProvider);
Text(isSwahili ? 'Dashibodi' : 'Dashboard')
```

**Language Toggle**: Available in Settings screen and on the public landing page AppBar. Persisted to SharedPreferences via `SettingsNotifier`.

**Translation Coverage**: All user-facing strings are bilingual including:
- Navigation labels
- Dashboard section headers
- Error messages
- Empty states
- Date formatting labels
- Status labels
- Form fields

---

#### 20.14 Authentication & Security

| Feature | Implementation |
|---------|---------------|
| Token storage | Flutter Secure Storage (encrypted) |
| Auth header | Bearer token injected via Dio interceptor |
| Session expiry | 401 response → auto-logout + redirect to login |
| Dual login | Staff first, client fallback (transparent to user) |
| Device registration | `POST /auth/register-device-token` for push notifications |
| Biometric auth | Configurable via feature flag (planned) |
| Password change | Client: `POST /api/client/auth/change-password` |
| Profile update | Staff: `PUT /api/v1/auth/user`, Client: `PUT /api/client/auth/profile` |

---

#### 20.15 Staff Dashboard API Data Classes

The staff dashboard API (`StaffDashboardApi`) defines extensive data models:

| Class | Key Fields |
|-------|-----------|
| `DashboardStats` | totalRevenue, revenueChangePercent, activeProjects, newProjectsThisMonth, teamMembers, budgetUtilization |
| `TeamMembers` | total, present, absent |
| `BudgetUtilization` | totalBudget, utilized, percentage |
| `PendingApprovals` | total, items[] (label, count, icon, route) |
| `ApprovalItem` | label, count, icon |
| `StatusSummary` | overdue, today, upcoming, completedThisMonth |
| `ActivitiesSummary` | overdue, inProgress, pending |
| `InvoicesSummary` | overdue, dueToday, upcoming |
| `ProjectProgressData` | overallPercentage, completed, inProgress, pending, overdue, projects[] |
| `ProjectProgressItem` | id, name, leadName, percentage, completed, inProgress, pending, overdue |
| `CalendarData` | month, year, events (Map<String, CalendarDayEvents>) |
| `CalendarDayEvents` | followups[], activities[], invoices[] |
| `CalendarEvent` | name, type, status |

**Additional List APIs**:
- `GET /api/v1/dashboard/activities` → `List<DashboardActivity>` (title, project, status, dueDate, priority)
- `GET /api/v1/dashboard/invoices` → `List<DashboardInvoice>` (number, client, amount, dueDate, status)
- `GET /api/v1/dashboard/followups` → `List<DashboardFollowup>` (title, contact, dueDate, status, priority)
- `GET /api/v1/dashboard/calendar?month=X&year=Y` → `CalendarData`
- `GET /api/v1/dashboard/recent-activities` → `List<RecentActivity>` (description, user, createdAt, type, icon)

---

#### 20.16 Client API Data Classes

The client API (`ClientApi`) provides project-centric data models:

| Class | Key Fields |
|-------|-----------|
| `ClientProject` | id, projectName, documentNumber, status, startDate, expectedEndDate, contractValue, boqsCount, invoicesCount, dailyReportsCount |
| `ClientBillingData` | totalInvoiced, totalPaid, outstanding, documents[], payments[] |
| `BillingDocument` | id, documentNumber, date, dueDate, amount, paidAmount, balance, status |
| `BillingPayment` | id, reference, date, amount, method, notes |
| `ProjectDetail` | name, status, progress, startDate, expectedEndDate, contractValue, description, team[], client |
| `ProjectOverviewData` | detail, phases[] |
| `ProjectBoq` | sections[] (each with items[]: description, unit, qty, rate, amount, type) |
| `ScheduleActivity` | name, constructionPhase, startDate, endDate, progress, status |
| `ProjectFinancials` | contractValue, totalInvoiced, totalPaid, balance, payments[] |
| `ProjectDesign` | id, title, fileUrl, description |
| `DailyReport` | id, date, conditions, activities, issues |
| `SiteVisit` | id, date, visitor, observations, recommendations |
| `ProgressImage` | id, imageUrl, date, description, uploadedBy |

---

#### 20.17 Deployment & Configuration

**Build Configuration**:

| Platform | Package / Bundle ID |
|----------|-------------------|
| Android | Configured in `android/app/build.gradle` |
| iOS | Configured in `ios/Runner.xcodeproj` |

**Environment Switching** (`AppConfig`):

```dart
static const bool isProduction = true;  // Toggle for builds

static String get baseUrl => isProduction
    ? 'https://wajenziprosystem.co.tz/api/v1'
    : 'http://localhost:8000/api/v1';
```

**Firebase**: `firebase_core` and `firebase_messaging` configured for push notification support. Device token registration endpoint available at `POST /auth/register-device-token`.

**Code Generation**: Uses `build_runner` with `freezed` and `json_serializable` for immutable model classes. Run via:
```bash
dart run build_runner build --delete-conflicting-outputs
```

**Dependencies** (key packages from `pubspec.yaml`):

| Package | Purpose |
|---------|---------|
| `flutter_riverpod` | State management |
| `go_router` | Declarative routing |
| `dio` | HTTP client |
| `drift` + `sqlite3_flutter_libs` | Local SQLite database |
| `flutter_secure_storage` | Encrypted credential storage |
| `shared_preferences` | Settings persistence |
| `workmanager` | Background sync tasks |
| `geolocator` + `permission_handler` | Location services |
| `firebase_core` + `firebase_messaging` | Push notifications |
| `flutter_form_builder` + `form_builder_validators` | Form handling |
| `cached_network_image` | Image caching |
| `image_picker` + `image_cropper` | Photo capture/editing |
| `intl` | Date/number formatting |
| `url_launcher` | External links (WhatsApp, browser) |
| `path_provider` | File system paths |
| `freezed` + `json_serializable` | Code-generated models |

---

## Technical Infrastructure

| Aspect | Detail |
|--------|--------|
| Models | ~170 Eloquent models |
| Controllers | ~130 controllers |
| Database migrations | 366+ |
| Route definitions | 891+ lines |
| View templates | 300+ Blade files |
| Form templates | 220+ AJAX modal forms |
| Database seeders | 36 seeders |
| API endpoints | 60+ (mobile) + 15+ (client portal) |

---

*This document serves as the system overview. Individual module documentation with detailed feature descriptions, data models, workflows, and screenshots to follow.*