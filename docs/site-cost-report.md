# Site Cost Report

**Module:** Procurement (Purchase Orders → Site Cost Report)
**Roles responsible:** System Administrator / Finance / Procurement (anyone with the `Site Cost Report` permission)
**Type:** Read-only report (aggregator) — stores no data of its own

---

## What This Report Does

Gives a **per-site (per-project) cost breakdown** assembled live from the procurement data already in the system. For every site it shows four cost columns plus a total, and can be downloaded as an Excel-compatible CSV.

```
Material  +  Labour  +  Overhead  +  Drawing Charges  =  Total   (per site)
```

It is a **mirror**, not a ledger: it never writes cost rows. It re-reads the source tables every time you open it, so it is always current.

---

## Where To Find It

- **Page:** `/purchase_orders/site-report`
- **How to get there:** a green **"Site Cost Report"** button in the header of the Purchase Orders page (`/purchase_orders`).
- **Permission:** the button and report require the `Site Cost Report` permission (MENU), granted to **System Administrator** by the setup migration. Grant it to other roles via the normal roles/permissions screen.

---

## The Four Cost Columns — where each number comes from

| Column | Source table | Filter | How a value gets there |
|---|---|---|---|
| **Material** | `purchases` (Purchase Orders) | `status = APPROVED`, `project_id` set | A Purchase Order is approved for the project |
| **Labour** | `labor_contracts` | `status ∈ {active, on_hold, completed}`, `project_id` set | A Labour Charge contract becomes committed |
| **Overhead** | `project_expenses` | category = `Overhead Expense` | Loading/offloading/transport on a supplier receiving, or a manual project expense |
| **Drawing Charges** | `project_expenses` | category = `Drawing Charge` | A project expense recorded under the new "Drawing Charge" category |

> **Material = approved POs only.** Drafts and pending POs are excluded — the report reflects committed cost, not speculative.
> **Labour excludes** `draft` (not finalised) and `terminated` (cancelled) contracts.
> **Scope is all-time per site** — there is no date filter, because PO dates, contract dates, and expense dates are not directly comparable.

---

## How It Works — Step by Step

### Step 1 — User opens the report
Clicking **Site Cost Report** on `/purchase_orders` issues `GET /purchase_orders/site-report`. The route maps to `SiteReportController@index`.

### Step 2 — Controller asks for the data
`index()` calls one private method, `buildReport($projectId)`, which returns `[$rows, $totals]`. (`$projectId` is `null` for "all sites", or a single project id when the site filter is used.)

### Step 3 — Four grouped SUM queries (one per column)
`buildReport()` runs **four independent queries**, each returning a small `project_id ⇒ total` map:

```php
// Material
Purchase::where('status','APPROVED')->whereNotNull('project_id')
    ->groupBy('project_id')->selectRaw('project_id, SUM(total_amount) as total')
    ->pluck('total','project_id');
// Labour   → labor_contracts, status in {active,on_hold,completed}, SUM(total_amount)
// Overhead → project_expenses, cost_category_id = "Overhead Expense", SUM(amount)
// Drawing  → project_expenses, cost_category_id = "Drawing Charge",  SUM(amount)
```

Summing each source **separately** avoids the SQL "fan-out" bug — a single join across POs, contracts and expenses would multiply rows and produce wrong totals.

### Step 4 — Union the sites
The project ids from all four maps are merged and de-duplicated, so a site appears if it has cost in **any** column:

```php
$projectIds = collect()
    ->merge($material->keys())->merge($labour->keys())
    ->merge($overhead->keys())->merge($drawing->keys())
    ->unique();
```

### Step 5 — Build the per-site rows
For each project id it reads the four maps (defaulting to `0` when a site is absent from a map), looks up the project name + document number, computes `total = material + labour + overhead + drawing`, and sorts rows by total descending. Grand totals are summed across the rows.

### Step 6 — Render
- **HTML:** `index()` passes `$rows` + `$totals` to `pages/procurement/site_report.blade.php` — a table with a grand-total footer, plus a site filter dropdown.
- **CSV:** the **Download Excel** button hits `GET /purchase_orders/site-report/export` → `SiteReportController@export`, which calls the *same* `buildReport()` and streams a CSV (so on-screen and downloaded numbers can never disagree).

### Step 7 — CSV export specifics
`export()` uses `response()->stream()` with `fputcsv()`:
- Starts with a UTF-8 BOM (`\xEF\xBB\xBF`) so Excel renders Swahili/accented site names correctly.
- Numbers are written as `18674680.00` (no thousands separator) so Excel treats them as real, summable numbers. The on-screen table uses comma grouping (`18,674,680.00`) for human reading.
- Filename: `site_cost_report_<YYYY-MM-DD>.csv`.

---

## How To Feed It Data

| To populate… | Do this |
|---|---|
| **Material** | Approve Purchase Orders against the project (normal procurement flow). |
| **Labour** | Create/activate Labour Charge contracts on the project (`/labor/...`). |
| **Overhead** | Record delivery overheads on a supplier receiving, or add a project expense in the `Overhead Expense` category. |
| **Drawing Charges** | Go to `/project_expense/create`, choose the project, pick the **"Drawing Charge"** category, enter the amount. |

The "Drawing Charge" category appears automatically in the project-expense form because every category dropdown lists `CostCategory::orderBy('name')->get()` — adding the category row was enough; no form code changed.

---

## HTTP Routes

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/purchase_orders/site-report` | `purchase_orders.site_report` | `SiteReportController@index` |
| GET | `/purchase_orders/site-report/export` | `purchase_orders.site_report.export` | `SiteReportController@export` |

Both carry the optional `?project_id=` filter.

---

## Setup / Migration

`database/migrations/2026_05_27_120000_add_drawing_charge_and_site_report.php`:
1. Inserts the `Drawing Charge` cost category (idempotent).
2. Creates the `Site Cost Report` permission (MENU) and grants it to System Administrator (role id 1).

`down()` removes the permission/grant but **leaves** the `Drawing Charge` category in place (it may already be in use).

---

## Key Files

| Layer | Path |
|---|---|
| Controller | `app/Http/Controllers/SiteReportController.php` |
| View | `resources/views/pages/procurement/site_report.blade.php` |
| Entry button | `resources/views/pages/procurement/purchase_orders.blade.php` |
| Routes | `routes/web.php` (Procurement — Purchase Orders section) |
| Migration | `database/migrations/2026_05_27_120000_add_drawing_charge_and_site_report.php` |
| Sources read | `app/Models/Purchase.php`, `app/Models/LaborContract.php`, `app/Models/ProjectExpense.php`, `app/Models/CostCategory.php`, `app/Models/Project.php` |

---

## Business Rules & Assumptions

- **Material** counts only `APPROVED` purchases; **Labour** only committed contracts (active/on-hold/completed).
- A site with **zero in a column** simply has no data of that type yet — not an error.
- All amounts are **TZS**.
- The report is **all-time**; there is currently no date range.

## Possible Enhancements

- Optional date-range filter (per-source date columns).
- Capture drawing charges automatically at a defined stage (like overheads on supplier receiving) instead of manual entry.
- Add `Drawing Charge` to the Expenditure Dashboard's category summary cards.
