# Site Visit Workflow — Test Procedure

Manual + scripted test procedure for the 6-stage Site Visit Workflow
(`/project_site_visits`).

The workflow advances a visit through these stages:

```
initiation → billing → assignment → confirmation → reporting → integration → completed
                                                                         └→ (auto-skip) → completed
   any non-terminal stage ──cancel──▶ cancelled
```

---

## 1. Roles & gating reference

Each transition is gated by role (System Administrator overrides everything).

| Stage | Action | Allowed roles |
|------|--------|---------------|
| 1 Initiation | create the request | `Add Visit` permission (Sales/Project coordinator) |
| 2a Billing | enter invoice (number + amount) | Accountant, Finance |
| 2b Billing | confirm payment | Finance, Accountant |
| 3 Assignment | assign Architect / Site Engineer / Supervisor | Project Manager, Sales Manager |
| 4 Confirmation | confirm readiness | the assigned Architect / Site Engineer / Supervisor |
| 5 Reporting | upload report | the assigned team (or coordinator) |
| 6 Integration | attach report to Survey Stage | Project Manager, Sales Manager |
| — | cancel | the creator, or a coordinator |

Role mapping used by the build: **Site Engineer → `Civil Engineer`**, **Project
Coordinator / coordinator → `Project Manager`**, **Sales Coordinator → `Sales Manager`**.
There is no `Billing` role, so billing is handled by **`Accountant`** and payment by **`Finance`**.

---

## 2. Prerequisites

### 2.1 Test users
Ensure at least one **active** user exists for each role below. As of this writing
some roles have NO user — create/assign them before testing:

| Role | Needed for | Have a user? |
|------|-----------|--------------|
| Sales Manager **or** Project Manager | Stage 1, 3, 6, cancel | ⚠️ Project Manager empty — use Sales Manager |
| Accountant | Stage 2a/2b | ✅ |
| Finance | Stage 2b | ⚠️ empty — Accountant also allowed |
| Architect | Stage 3/4/5 | ✅ |
| Civil Engineer (Site Engineer) | Stage 3 | ✅ |
| Site Supervisor | Stage 3 | ✅ |
| Quantity Surveyor (QS) | Stage 5/6 notification audience | ✅ |

To assign a role to a test user (tinker):
```php
\App\Models\User::find(<id>)->assignRole('Finance');
```

### 2.2 Test data
- **A project that has a schedule with a Survey activity (`activity_code A0`)** — needed
  to test Stage 6 (integration). Find one:
  ```php
  foreach (\App\Models\Project::limit(500)->get() as $p) {
      $s = \App\Models\ProjectSchedule::where(fn($q)=>$q
          ->whereHas('lead', fn($l)=>$l->where('project_id',$p->id))
          ->orWhere('client_id',$p->client_id))->first();
      if ($s && $s->activities()->where('activity_code','A0')->exists()) { echo "project #{$p->id}\n"; break; }
  }
  ```
- **An APPROVED client** (for the client-only path): any row in `project_clients` with `status = APPROVED`.

### 2.3 Environment
- Migrations applied: `2026_06_20_000001_add_workflow_to_project_site_visits_table`
  and `2026_06_20_000002_purge_site_visit_ringlesoft_rows` show **Ran** in
  `php artisan migrate:status`.
- The `public` storage symlink exists (`php artisan storage:link`) so uploaded
  reports are downloadable.
- Run artisan as the web user: `sudo -u www-data php artisan ...`.

---

## 3. Test Case 1 — Happy path (project visit, all 6 stages)

Perform each step **logged in as the role shown**. After each step, reload the
visit detail page (`/project_site_visit/{id}`) and check the expected result.

| # | Login as | Action | Expected result |
|---|----------|--------|-----------------|
| 1 | Sales/Project coordinator | On `/project_site_visits`, click **New Visit** → choose **Project**, pick the project from §2.2, fill phone/location/description/visit date → **Submit Request** | Redirects to the detail page. Stage badge = **1/7 Initiation**. A reference `SV-YYYY-####` is shown. List shows the row with the Initiation badge. |
| 2 | Accountant | Detail page → **Record Invoice** (invoice number + amount) | Stage → **2/7 Billing**. "Awaiting Finance to confirm payment." Details panel shows Invoice No. + amount + billed-by. |
| 3 | Finance (or Accountant) | **Confirm Payment** | Stage → **3/7 Assignment**. Details show "Payment Confirmed … by …". |
| 4 | Project Manager / Sales Manager | **Assign Team** — pick Architect, Site Engineer, Supervisor | Stage → **4/7 Confirmation**. Details list the three assignees. |
| 5 | the assigned **Architect** | **Confirm Readiness** | Stage → **5/7 Reporting**. Details show "Readiness Confirmed …". |
| 6 | the assigned Architect | **Upload Report** (attach a PDF + notes) | Stage → **6/7 Integration** (because the project has an A0 activity). Report download link appears. |
| 7 | Project Manager / Sales Manager | **Attach to Survey Stage** | Stage → **7/7 Completed**. Details show "Linked to Survey: A0 — …". |

**Verify the schedule link:** open the project's schedule page and confirm the
uploaded report now appears as an **attachment on the Survey Stage (A0) activity**.

**Data check (tinker):**
```php
$v = \App\Models\ProjectSiteVisit::latest('id')->first();
echo $v->stage;                 // completed
echo $v->schedule_activity_id;  // the A0 activity id
echo $v->schedule_attachment_id;// the attachment row id
```

---

## 4. Test Case 2 — Client-only visit (integration auto-skipped)

1. Create a visit choosing **Client (no project yet)** and an APPROVED client.
2. Walk stages 2–4 as in TC1 (Accountant → Finance → coordinator → team).
3. As the assigned team member, **Upload Report**.

**Expected:** at report upload the stage jumps straight to **Completed** (no
Integration step, because a client-only visit has no project schedule). The
report is still downloadable from the detail page.

---

## 5. Test Case 3 — Project with no schedule (integration auto-skipped)

Same as TC1 but pick a project that has **no** schedule / no A0 activity.

**Expected:** **Upload Report** completes the visit directly (stage = Completed),
and the "Attach to Survey Stage" action never appears.

---

## 6. Test Case 4 — Permission gating (negative tests)

For each row, log in as a user **without** the required role and attempt the action
(via the detail page; the action card should normally be hidden, so also confirm
the server rejects a forged/direct POST).

| Attempt | As | Expected |
|---------|----|----------|
| Record invoice | Architect | Blocked, red error banner, stage unchanged |
| Confirm payment | any non-Finance/Accountant | Blocked, stage unchanged |
| Assign team | non-coordinator | Blocked, stage unchanged |
| Confirm readiness | a user **not** on the assigned team | Blocked ("Only an assigned team member…"), stage unchanged |
| Upload report | a user not on the team / not coordinator | Blocked, stage unchanged |
| Attach to Survey | non-coordinator | Blocked, stage unchanged |

Also confirm **out-of-order** transitions fail: e.g. calling Confirm Payment while
the visit is still in `initiation` returns "An invoice must be prepared first" and
does not advance.

---

## 7. Test Case 5 — Cancel path

1. Create a visit; advance it to any non-terminal stage (e.g. `assignment`).
2. As the **creator** or a **coordinator**, click **Cancel Visit** (optionally enter a reason).

**Expected:** stage → **Cancelled**, a red "cancelled" banner shows on the detail
page, the list shows a red **Cancelled** badge, and no further action card is offered.
A non-owner / non-coordinator must **not** be able to cancel.

---

## 8. Test Case 6 — Edit / delete restricted to Initiation

1. On a visit still in **Initiation**: the list row shows **Edit** (pencil) and
   **Delete** (×) buttons (subject to `Edit Visit` / `Delete Visit` permissions).
   Edit opens the modal and saves; Delete removes the row.
2. Advance a visit past Initiation (e.g. to Billing).

**Expected:** once a visit leaves Initiation, the Edit and Delete buttons are **no
longer shown**, and `update()` server-side returns "already entered the workflow…".

---

## 9. Test Case 7 — Notifications

After each transition, log in as the **next actor** and check the bell/notification
list for a new entry linking to the visit:

| After | Notified |
|-------|----------|
| Create | Accountant / Finance ("Awaiting Billing") |
| Record invoice | Finance / Accountant ("Payment Pending") |
| Confirm payment | Project Manager / Sales Manager ("Ready for Assignment") |
| Assign team | the 3 assignees ("Assigned to a Site Visit") |
| Upload report | Sales, Architect, Engineers, QS ("Report Available"); + coordinator if integration pending |
| Attach to Survey | QS, Architect, Engineers ("Survey Report Linked") |

(Email is sent only if the user has an address **and** SMTP is configured; otherwise
only the in-app notification is created — this is expected.)

---

## 10. Test Case 8 — Backfill of pre-existing visits

Confirm the migration backfilled old rows:
```php
\App\Models\ProjectSiteVisit::orderBy('id')->get(['id','status','stage','reference_number'])
    ->each(fn($v)=>print("{$v->id} | {$v->status} | {$v->stage} | {$v->reference_number}\n"));
```
**Expected:** every row has a non-empty `reference_number` (`SV-YYYY-####`) and a
`stage` mapped from its old `status` (`CREATED`/`PENDING`→`initiation`,
`APPROVED`/`PAID`/`COMPLETED`→`completed`, `REJECTED`→`cancelled`).

---

## 11. Scripted regression (full E2E in one shot)

This drives all 6 stages through the real controller and **rolls everything back**
(no test data persists). Replace the user ids / project id with values from your
environment (§2). Run with a writable HOME so tinker doesn't fail on its config dir.

```bash
sudo -u www-data env HOME=/tmp php artisan tinker --execute='
use Illuminate\Http\Request; use Illuminate\Support\Facades\{Auth,DB};
use App\Models\{User,ProjectSiteVisit};
$ctrl = app(App\Http\Controllers\ProjectSiteVisitController::class);
$coordinator = User::find(1); $accountant = User::find(4); $architect = User::find(10);
$engineer = 29; $supervisor = 1; $projectId = 10;   // project with an A0 activity
$session = app("session.store"); $session->start();
$mk = fn($p) => tap(Request::create("/x","POST",$p), fn($r)=>$r->setLaravelSession($session) ?: app()->instance("request",$r));
$reportPath = null;
DB::beginTransaction();
try {
    Auth::login($coordinator);
    $ctrl->store($mk(["psv_link_type"=>"project","project_id"=>$projectId,"location"=>"L","description"=>"E2E","phone_number"=>"0700","visit_date"=>date("Y-m-d")]));
    $v = ProjectSiteVisit::latest("id")->first();                          echo "store     -> {$v->stage} ({$v->reference_number})\n";
    Auth::login($accountant);
    $ctrl->enterInvoice($mk(["invoice_number"=>"INV-1","invoice_amount"=>150000]), $v->id); echo "invoice   -> {$v->fresh()->stage}\n";
    $ctrl->confirmPayment($mk([]), $v->id);                                 echo "pay       -> {$v->fresh()->stage}\n";
    Auth::login($coordinator);
    $ctrl->assignTeam($mk(["architect_id"=>$architect->id,"site_engineer_id"=>$engineer,"site_supervisor_id"=>$supervisor]), $v->id); echo "assign    -> {$v->fresh()->stage}\n";
    Auth::login($architect);
    $ctrl->confirmReadiness($mk([]), $v->id);                               echo "readiness -> {$v->fresh()->stage}\n";
    $tmp = tempnam(sys_get_temp_dir(),"r"); file_put_contents($tmp,"%PDF-1.4 e2e");
    $f = new \Illuminate\Http\UploadedFile($tmp,"report.pdf","application/pdf",null,true);
    $r = $mk(["report_notes"=>"ok"]); $r->files->set("report",$f);
    $ctrl->uploadReport($r, $v->id); $v = $v->fresh(); $reportPath = $v->report_path; echo "report    -> {$v->stage}\n";
    Auth::login($coordinator);
    $ctrl->integrate($mk([]), $v->id);                                      echo "integrate -> {$v->fresh()->stage} (activity #{$v->fresh()->schedule_activity_id})\n";
} catch (\Throwable $e) { echo "ERROR: {$e->getMessage()} @ {$e->getLine()}\n"; }
finally { DB::rollBack(); if ($reportPath) \Illuminate\Support\Facades\Storage::disk("public")->delete($reportPath);
    echo "(rolled back; count=".ProjectSiteVisit::count().")\n"; }
'
```

**Expected output:**
```
store     -> initiation (SV-YYYY-####)
invoice   -> billing
pay       -> assignment
assign    -> confirmation
readiness -> reporting
report    -> integration
integrate -> completed (activity #…)
(rolled back; count=<unchanged>)
```

---

## 12. Sign-off checklist

- [ ] TC1 happy path reaches **Completed** and report is attached to the Survey (A0) activity
- [ ] TC2 client-only visit completes at report upload (no integration step)
- [ ] TC3 project-without-schedule completes at report upload
- [ ] TC4 every gated action blocks the wrong role and out-of-order calls
- [ ] TC5 cancel works for owner/coordinator only
- [ ] TC6 edit/delete only available in Initiation
- [ ] TC7 the right actor is notified at each transition
- [ ] TC8 pre-existing visits backfilled with stage + reference number
- [ ] §11 scripted regression prints the expected stage sequence
