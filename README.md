# Wajenzi - Construction Project Management System

A comprehensive enterprise resource planning (ERP) system designed for construction companies in East Africa. Wajenzi ("builders" in Swahili) streamlines project management, procurement, financial operations, HR, and client relations — from bidding through project completion.

## Table of Contents

- [Tech Stack](#tech-stack)
- [System Architecture](#system-architecture)
- [Core Modules](#core-modules)
  - [Project Management](#1-project-management)
  - [Bill of Quantities (BOQ)](#2-bill-of-quantities-boq)
  - [Procurement & Inventory](#3-procurement--inventory)
  - [Site Management](#4-site-management)
  - [Labor Management](#5-labor-management)
  - [Financial Management](#6-financial-management)
  - [Billing & Invoicing](#7-billing--invoicing)
  - [Human Resources & Payroll](#8-human-resources--payroll)
  - [Sales & CRM](#9-sales--crm)
  - [Accounting](#10-accounting)
  - [Client Portal](#11-client-portal)
- [Mobile Application](#mobile-application)
- [API Reference](#api-reference)
- [Approval Workflows](#approval-workflows)
- [Permission System](#permission-system)
- [Requirements](#requirements)
- [Installation](#installation)
- [Development](#development)
- [Project Structure](#project-structure)

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | Laravel 11 (PHP 8.2+) |
| **Frontend** | Blade templates, Bootstrap 5, jQuery, DataTables, Select2 |
| **JavaScript** | Vue.js 2 (selective), Laravel Mix (Webpack) |
| **Database** | MySQL |
| **Authentication** | Laravel Sanctum (API tokens + SPA) |
| **Permissions** | Spatie laravel-permission (roles & permissions) |
| **Approval Workflows** | RingleSoft laravel-process-approval |
| **PDF Generation** | barryvdh/laravel-dompdf |
| **Real-time** | Pusher + Laravel Echo |
| **Mobile** | Flutter (Dart), offline-first architecture |
| **Build** | Laravel Mix 6, Webpack |

---

## System Architecture

### Web Application

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (Frontend)                     │
│  Bootstrap 5 · jQuery · DataTables · Select2 · Vue.js    │
├──────────────────────┬──────────────────────────────────┤
│    Blade Templates   │         Inertia.js (selective)    │
├──────────────────────┴──────────────────────────────────┤
│                   Laravel 11 Backend                     │
│  ┌──────────┐  ┌───────────┐  ┌──────────────────────┐  │
│  │ Routes   │→ │Controllers│→ │ Models (Eloquent)    │  │
│  │ web.php  │  │ + Ajax    │  │ + Spatie Permissions │  │
│  │ api/*.php│  │ Controller│  │ + RingleSoft Approval│  │
│  └──────────┘  └───────────┘  └──────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐    │
│  │ Services: PDF (DomPDF) · Pusher · Sanctum Auth  │    │
│  └──────────────────────────────────────────────────┘    │
├─────────────────────────────────────────────────────────┤
│                    MySQL Database                        │
│                   (366+ migrations)                      │
└─────────────────────────────────────────────────────────┘
```

### Key Architectural Patterns

- **CRUD via AJAX Modal Forms**: Forms are loaded via `loadFormModal()` → `AjaxController` (switch-case dispatcher) → Blade form templates. Create/update actions use `addItem`/`updateItem` buttons handled by `Controller::handleCrud()`.
- **Menu-driven Navigation**: Menus stored in the database (`menus` table), dynamically rendered based on user permissions.
- **Multi-layout System**: Four Blade layouts — `backend` (main admin), `client` (portal), `app` (Inertia SPA), `simple` (minimal/auth).

---

## Core Modules

### 1. Project Management

The central module around which all other functionality revolves.

| Feature | Description |
|---|---|
| **Projects** | Create, track, and manage construction projects with types, statuses, and assignments |
| **Team Members** | Assign staff to projects with role-based access |
| **Daily Reports** | Track daily progress at the project level |
| **Site Visits** | Schedule, assign, and document site visits with approval workflows |
| **Documents** | Upload and manage project-related documents |
| **Progress Images** | Photo gallery for construction progress tracking |
| **Expenses** | Track project-specific expenses |
| **Payments** | Record project-related payments (incoming and outgoing) |
| **Schedules** | Gantt-style scheduling with activities and role-based templates |
| **Construction Phases** | Divide projects into construction phases for tracking |

**Key views:** `pages/projects/` (22 Blade templates)

### 2. Bill of Quantities (BOQ)

Hierarchical cost estimation system that feeds into procurement and invoicing.

| Feature | Description |
|---|---|
| **Hierarchical Sections** | Self-referential `project_boq_sections` table with unlimited nesting via `parent_id` |
| **Line Items** | Material and labour items within sections, with `sort_order` for manual arrangement |
| **Quantities & Pricing** | Unit, quantity, rate, and computed amounts per item |
| **Templates** | Save BOQ structures as reusable templates, apply to new projects |
| **PDF Export** | Full BOQ rendered as PDF via DomPDF (recursive section rendering) |
| **CSV Export** | UTF-8 BOM export for Excel compatibility |

**Key models:** `ProjectBoq`, `ProjectBoqSection`, `ProjectBoqItem`, `BoqTemplate`, `BoqTemplateItem`

### 3. Procurement & Inventory

End-to-end procurement pipeline from material request to delivery.

```
Material Request → Supplier Quotations → Quotation Comparison → Purchase Order → Delivery/Receiving → Stock
```

| Feature | Description |
|---|---|
| **Material Requests** | Bulk request from BOQ (checkbox selection), item-level quantities and approvals |
| **Supplier Quotations** | Request and receive quotations from multiple suppliers per item |
| **Quotation Comparisons** | Side-by-side comparison with approval workflow |
| **Purchase Orders** | Generated from approved comparisons, tracked through delivery |
| **Supplier Receiving** | Record deliveries against purchase orders |
| **Material Inspections** | Quality inspection on received materials |
| **Stock Register** | Per-project inventory tracking with movements (issue, adjust, receive) |
| **Auto-Purchase** | Automated purchase generation based on BOQ thresholds |

**Key views:** `pages/procurement/` (19 Blade templates)

### 4. Site Management

Operations management for construction sites.

| Feature | Description |
|---|---|
| **Sites** | Register physical construction sites linked to projects |
| **Site Daily Reports** | Daily activity logging — work done, materials used, labor deployed, weather |
| **Supervisor Assignments** | Assign supervisors to sites |
| **Site Payments** | Track site-level payments |

**Key views:** `pages/sites/`

### 5. Labor Management

Workforce management for construction laborers (separate from salaried staff).

| Feature | Description |
|---|---|
| **Labor Requests** | Request labor for specific construction phases with approvals |
| **Labor Contracts** | Formal contracts for hired laborers |
| **Work Logs** | Track daily labor hours and output |
| **Labor Inspections** | Quality/safety inspections of labor work |
| **Payment Phases** | Milestone-based payment tracking for labor contracts |

**Key views:** `labor/` (dashboard, contracts, requests, inspections, logs, payments)

### 6. Financial Management

Core financial operations and banking.

| Feature | Description |
|---|---|
| **Bank Reconciliation** | Match bank statements to system records (deposits, withdrawals, transfers) |
| **Expenses** | Track company-wide expenses by category/subcategory with approval workflows |
| **Petty Cash** | Petty cash refill requests with approval workflow |
| **Imprest Requests** | Advance fund requests for field operations |
| **Collections** | Record and track collections from various sources |
| **Financial Charges** | Track bank fees, interest, and other financial charges |

### 7. Billing & Invoicing

Standalone billing system for generating professional invoices.

| Feature | Description |
|---|---|
| **Billing Documents** | Invoices, Quotations, and Proforma invoices |
| **Products/Services** | Product catalog for line items |
| **Clients** | Client database separate from project clients |
| **Payments** | Record payments against billing documents |
| **Tax Rates** | Configurable tax rates (VAT, WHT, etc.) |
| **Email Sending** | Send invoices directly from the system |
| **Reminder System** | Automated payment reminders with configurable schedules |
| **Public PDF Links** | Shareable invoice links via `/i/{token}` (no auth required) |
| **Reports** | Revenue, aging, and payment reports |

**Key views:** `billing/` (clients, dashboard, invoices, payments, products, proformas, quotations, reports)

### 8. Human Resources & Payroll

Complete HR lifecycle management.

| Feature | Description |
|---|---|
| **Staff Profiles** | Employee information, bank details, positions, departments |
| **Attendance** | Check-in/check-out tracking (web + mobile) |
| **Leave Management** | Leave types, request/approval workflow, balance tracking |
| **Payroll Processing** | Full payroll — gross pay, allowances, deductions, loans, taxes, net salary |
| **Loans** | Employee loan management with payroll-integrated deductions |
| **Advance Salary** | Advance salary requests with approval |
| **Recruitment** | Recruitment tracking and management |
| **Timesheets** | Detailed timesheet management |

**Payroll calculation chain:** Gross Pay → Allowances → Taxable Income → Tax (PAYE) → Deductions → Loan Deductions → Net Salary

### 9. Sales & CRM

Lead management and sales operations.

| Feature | Description |
|---|---|
| **Leads** | Lead tracking with statuses, sources, and assignments |
| **Follow-ups** | Scheduled follow-up activities with calendar integration |
| **Sales Daily Reports** | Daily sales activity logging with approval workflows |
| **Customer Acquisition** | Track cost-per-acquisition metrics |
| **Dashboard Calendar** | Follow-up and invoice due date calendar exports (iCal) |

### 10. Accounting

Chart of accounts and tax management.

| Feature | Description |
|---|---|
| **Chart of Accounts** | Hierarchical account structure with variables |
| **VAT Management** | VAT sales, purchases, auto-purchases, and payments |
| **Withholding Tax** | WHT tracking and payments |
| **Provision Tax** | Tax provision management |
| **Statutory Payments** | NSSF, WCF, SDL, and other statutory obligations with approvals |
| **Transaction Movements** | Double-entry transaction recording |

### 11. Client Portal

External-facing portal for construction clients to monitor their projects.

| Feature | Description |
|---|---|
| **Separate Authentication** | Clients authenticate via `ProjectClient` model with portal credentials |
| **Project Dashboard** | View project progress, financials, and gallery |
| **BOQ Access** | Read-only access to project bill of quantities |
| **Schedule View** | View project timeline and milestones |
| **Document Access** | Download project documents |
| **Billing** | View invoices and download PDFs |
| **Site Visit Reports** | Access site visit reports as PDFs |

**API routes:** `routes/api/client.php` — separate auth guard (`client-api`)

---

## Mobile Application

A **Flutter** mobile app (`wajenzi_mobile/`) with offline-first architecture.

### Architecture

```
lib/
├── core/              # Config, networking, routing, storage
│   ├── config/        # App & theme configuration
│   ├── network/       # API client, network info
│   ├── router/        # App routing
│   └── services/      # Local storage service
├── data/              # Data layer
│   ├── datasources/   # Local (SQLite/Drift) + Remote (API)
│   ├── models/        # Data transfer objects
│   └── repositories/  # Repository implementations
├── domain/            # Business logic layer
├── l10n/              # Localization
├── presentation/      # UI layer
│   ├── providers/     # State management
│   ├── screens/       # Screen widgets
│   └── widgets/       # Reusable components
└── main.dart
```

### Mobile Features

| Module | Capabilities |
|---|---|
| **Attendance** | GPS check-in/check-out, offline queuing |
| **Site Daily Reports** | Create/edit reports with offline support |
| **Sales Reports** | Daily sales activity reporting |
| **Expenses** | Expense submission with approval |
| **Approvals** | Unified approval inbox (approve/reject) |
| **Projects** | Browse projects, BOQ, team, materials |
| **Billing** | Create/view invoices, quotations, payments |
| **VAT** | Sales, purchases, payments management |
| **Leave Requests** | Apply for leave, check balances |
| **Payroll** | View payslips, loan balances |
| **Procurement** | Material requests from mobile |
| **Employee Profile** | View profile and staff directory |

### Offline Support

The mobile app uses a **sync architecture** with local SQLite storage:
- **Push**: Queue local changes and batch-upload when online (`POST /api/v1/sync/push`)
- **Pull**: Download server changes since last sync (`GET /api/v1/sync/pull`)
- **Reference Data**: Sync lookup tables for offline form filling (`GET /api/v1/sync/reference-data`)

Offline-critical modules: Attendance, Site Daily Reports, Sales Reports, Expenses.

---

## API Reference

### Mobile API (`/api/v1/`)

All endpoints require `Authorization: Bearer {sanctum_token}` except login.

| Prefix | Resource | Methods |
|---|---|---|
| `auth/` | Authentication | login, logout, user, profile, device-token |
| `dashboard/` | Dashboard | stats, activities, invoices, followups, calendar |
| `attendance/` | Attendance | index, check-in, check-out, status, daily-report, summary |
| `site-daily-reports/` | Site Reports | CRUD + submit/approve/reject |
| `sales-daily-reports/` | Sales Reports | CRUD + submit/approve/reject |
| `expenses/` | Expenses | CRUD + categories + submit/approve/reject |
| `approvals/` | Approvals | index, pending, approve, reject |
| `projects/` | Projects | index, show, boq, materials, sites, team |
| `site-visits/` | Site Visits | CRUD + submit |
| `material-requests/` | Material Requests | CRUD + submit/approve/reject |
| `billing/documents/` | Billing | CRUD + send + pdf |
| `billing/payments/` | Payments | CRUD |
| `leave-requests/` | Leave | CRUD + balance + types |
| `payroll/` | Payroll | payslips, payslip detail, loan balance |
| `notifications/` | Notifications | index, mark read, mark all read, unread count |
| `vat/` | VAT | sales, purchases, auto-purchases, payments (full CRUD) |
| `employee-profile/` | Employee | profile, staff list |
| `sync/` | Offline Sync | push, pull, reference-data |

### Client Portal API (`/api/client/`)

Separate authentication for construction clients.

| Prefix | Resource | Methods |
|---|---|---|
| `auth/` | Authentication | login, logout, me, profile, password |
| `dashboard` | Dashboard | summary stats |
| `projects` | Projects | list all client's projects |
| `billing` | Billing | list documents, download PDF |
| `projects/{id}/` | Project Detail | show, boq, schedule, financials, documents, reports, gallery, PDFs |

---

## Approval Workflows

The system uses [RingleSoft Laravel Process Approval](https://github.com/raboragit/laravel-process-approval) for multi-step approval workflows.

### Models with Approval Workflows

| Model | Use Case |
|---|---|
| `Project` | New project approval |
| `ProjectBoq` | BOQ approval before procurement |
| `ProjectMaterialRequest` | Material request approval |
| `Purchase` | Purchase order approval |
| `QuotationComparison` | Quotation comparison approval |
| `MaterialInspection` | Material quality approval |
| `Expense` | Expense approval |
| `Sale` | Sales transaction approval |
| `SalesDailyReport` | Daily sales report approval |
| `SiteDailyReport` | Site daily report approval |
| `ProjectSiteVisit` | Site visit report approval |
| `Payroll` | Payroll approval |
| `AdvanceSalary` | Advance salary approval |
| `LeaveRequest` | Leave request approval |
| `Loan` | Loan approval |
| `LaborRequest` | Labor request approval |
| `LaborInspection` | Labor inspection approval |
| `StatutoryPayment` | Statutory payment approval |
| `VatPayment` | VAT payment approval |

### Approval Flow

```
Draft → Submitted → Level 1 Approval → Level 2 Approval → ... → Approved
                 ↘ Rejected (at any level)
```

Approval levels and steps are configured per document type via `ProcessApprovalFlow` and `ProcessApprovalFlowStep`.

---

## Permission System

Built on [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission).

- **Roles**: Configured in the database, assignable to users
- **Permissions**: Granular permissions per module and action
- **Menu Visibility**: Sidebar menus filtered by user permissions
- **Route Protection**: Middleware-based permission checks on routes
- **Mobile Menus**: API returns permission-filtered menu items

---

## Requirements

- PHP >= 8.2
- MySQL >= 5.7
- Node.js >= 16.x
- Composer >= 2.x
- Flutter >= 3.x (for mobile development)

---

## Installation

### Web Application

1. **Clone the repository**
```bash
git clone https://github.com/moinfo/wajenzi.git
cd wajenzi
```

2. **Install PHP dependencies**
```bash
composer install
```

3. **Install Node.js dependencies**
```bash
npm install
```

4. **Configure environment**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configure database** in `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wajenzi
DB_USERNAME=root
DB_PASSWORD=
```

6. **Configure Pusher** (for real-time features) in `.env`
```env
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=mt1
```

7. **Run database migrations and seeders**
```bash
php artisan migrate
php artisan db:seed
```

8. **Build frontend assets**
```bash
npm run dev
```

9. **Start the development server**
```bash
php artisan serve
```

### Mobile Application

1. **Navigate to the mobile directory**
```bash
cd wajenzi_mobile
```

2. **Install Flutter dependencies**
```bash
flutter pub get
```

3. **Configure API endpoint** in `lib/core/config/app_config.dart`

4. **Run the app**
```bash
flutter run
```

---

## Development

### Frontend Assets

```bash
npm run dev          # Compile for development
npm run watch        # Watch for changes and recompile
npm run prod         # Compile and minify for production
```

### Database

```bash
php artisan migrate              # Run pending migrations
php artisan migrate:rollback     # Rollback last migration batch
php artisan db:seed              # Run all seeders
php artisan db:seed --class=MenusSeeder  # Run specific seeder
```

### Key Development Conventions

- **Forms**: Create Blade templates in `resources/views/forms/`, load via `AjaxController`
- **CRUD buttons**: Use `name="addItem" value="ModelClassName"` for create, `name="updateItem"` for update
- **PDF exports**: Use `barryvdh/laravel-dompdf` with inline CSS only (no external stylesheets)
- **CSV exports**: Use `response()->stream()` with `fputcsv()`, include UTF-8 BOM for Excel
- **Migrations**: Use anonymous class format `return new class extends Migration`
- **Permissions**: Seed new permissions via dedicated seeders, check with `@can` / `$user->hasPermissionTo()`
- **Menus**: Add sidebar entries via migration seeders (e.g., `ProcurementMenuSeeder`)

---

## Project Structure

```
wajenzi/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── V1/              # Mobile API controllers (19)
│   │   │   │   └── Client/          # Client portal API controllers (4)
│   │   │   ├── Billing/             # Billing module controllers (11)
│   │   │   ├── Client/              # Client portal web controllers (2)
│   │   │   ├── AjaxController.php   # Central AJAX dispatcher
│   │   │   ├── Controller.php       # Base controller with handleCrud()
│   │   │   └── ...                  # ~120 feature controllers
│   │   └── Middleware/
│   │       ├── ApiAuth.php          # API authentication
│   │       ├── ClientAuth.php       # Client portal authentication
│   │       └── ...
│   └── Models/                      # ~170 Eloquent models
├── database/
│   ├── migrations/                  # 366+ migrations
│   └── seeders/                     # 36 seeders (menus, permissions, test data)
├── resources/
│   └── views/
│       ├── billing/                 # Billing module views
│       ├── client/                  # Client portal views
│       ├── forms/                   # 30+ AJAX modal form templates
│       ├── labor/                   # Labor management views
│       ├── layouts/                 # 4 layout templates
│       ├── pages/                   # 48 page view directories
│       ├── partials/                # Shared partial views
│       └── project-schedules/       # Schedule management views
├── routes/
│   ├── web.php                      # 891 lines of web routes
│   └── api/
│       ├── v1.php                   # Mobile app API routes
│       └── client.php               # Client portal API routes
├── public/                          # Public assets
├── wajenzi_mobile/                  # Flutter mobile app
│   └── lib/
│       ├── core/                    # Config, networking, routing
│       ├── data/                    # Data sources, models, repositories
│       ├── domain/                  # Business logic
│       ├── l10n/                    # Localization
│       └── presentation/            # UI (screens, providers, widgets)
└── config/                          # Laravel configuration
```

---

## License

This project is proprietary software. All rights reserved.

Built by [Moinfotech](https://moinfotech.com).