<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectClient;
use App\Models\ProjectSchedule;
use App\Models\ProjectScheduleActivity;
use App\Models\ProjectScheduleActivityAttachment;
use App\Models\ProjectSiteVisit;
use App\Models\User;
use App\Notifications\ApprovalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectSiteVisitController extends Controller
{
    // Role groups gating each workflow transition. 'System Administrator' is a
    // universal override. Stage 2 maps Billing → Accountant, Finance → Finance
    // (there is no dedicated "Billing" role in the system).
    private const ADMIN_ROLES       = ['System Administrator'];
    private const INVOICE_ROLES     = ['Accountant', 'Finance', 'System Administrator'];
    private const PAYMENT_ROLES     = ['Finance', 'Accountant', 'System Administrator'];
    private const COORDINATOR_ROLES = ['Project Manager', 'Sales Manager', 'System Administrator'];
    // Who can see/consume the final report (Stage 5 visibility).
    private const REPORT_AUDIENCE   = [
        'Sales Manager', 'Sales and Marketing', 'Architect',
        'Civil Engineer', 'Service Engineer', 'Quantity Surveyor (QS)',
    ];

    public function index(Request $request)
    {
        $start_date = $request->input('start_date') ?: date('Y-m-d');
        $end_date   = $request->input('end_date') ?: date('Y-m-d');

        if ($start_date && $end_date && $start_date > $end_date) {
            [$start_date, $end_date] = [$end_date, $start_date];
        }

        $visits = ProjectSiteVisit::with(['project.client', 'client'])
            ->whereDate('visit_date', '>=', $start_date)
            ->whereDate('visit_date', '<=', $end_date)
            ->when($request->project_id, fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->stage, fn ($q) => $q->where('stage', $request->stage))
            ->orderByDesc('id')
            ->get();

        return view('pages.projects.project_site_visits')->with([
            'visits'     => $visits,
            'projects'   => Project::orderBy('project_name')->get(),
            'clients'    => ProjectClient::where('status', 'APPROVED')->orderBy('first_name')->get(),
            'stages'     => ProjectSiteVisit::STAGES,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ]);
    }

    /**
     * Stage 1 — Initiation. A Sales/Project coordinator raises the request.
     */
    public function store(Request $request)
    {
        $validated = $this->validateBasics($request);

        $visit = ProjectSiteVisit::create($validated + [
            'reference_number' => ProjectSiteVisit::generateReferenceNumber(),
            'stage'            => 'initiation',
            'status'           => 'CREATED',
            'create_by_id'     => auth()->id(),
        ]);

        // Tell billing a new visit awaits an invoice.
        $this->notifyUsers(
            $this->usersWithRoles(self::INVOICE_ROLES),
            "project_site_visit/{$visit->id}",
            'Site Visit Awaiting Billing',
            "Site visit {$visit->reference_number} ({$this->subjectName($visit)}) was raised and needs an invoice."
        );

        return redirect()
            ->route('project_site_visit.show', $visit->id)
            ->with('success', "Site visit {$visit->reference_number} created. Awaiting billing.");
    }

    /**
     * Edit preliminary details — only while still in initiation.
     */
    public function update(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if ($visit->stage !== 'initiation') {
            return back()->with('error', 'This visit has already entered the workflow and can no longer be edited.');
        }

        $visit->update($this->validateBasics($request));

        return back()->with('success', 'Site visit updated.');
    }

    /**
     * Workflow detail page — progress tracker + the single action available to
     * the current user at the current stage.
     */
    public function show($id)
    {
        $visit = ProjectSiteVisit::with([
            'project.client', 'project.projectType', 'client', 'user',
            'architect', 'siteEngineer', 'siteSupervisor', 'billedBy',
            'paymentConfirmedBy', 'teamConfirmedBy', 'reportUploader', 'scheduleActivity',
        ])->findOrFail($id);

        return view('pages.projects.project_site_visit_workflow')->with([
            'visit'          => $visit,
            'surveyActivity' => $this->surveyActivityFor($visit),
            'architects'     => $this->usersWithRoles(['Architect'])->sortBy('name')->values(),
            'siteEngineers'  => $this->usersWithRoles(['Civil Engineer'])->sortBy('name')->values(),
            'supervisors'    => $this->usersWithRoles(['Site Supervisor'])->sortBy('name')->values(),
        ]);
    }

    /**
     * Stage 2a — Billing prepares the invoice.
     */
    public function enterInvoice(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if (!$this->userHasRole(self::INVOICE_ROLES)) {
            return back()->with('error', 'You are not authorised to prepare site-visit invoices.');
        }
        if ($visit->stage !== 'initiation') {
            return back()->with('error', 'An invoice can only be prepared while the visit is awaiting billing.');
        }

        $data = $request->validate([
            'invoice_number' => 'required|string|max:255',
            'invoice_amount' => 'required|numeric|min:0',
        ]);

        $visit->update($data + [
            'billed_by' => auth()->id(),
            'stage'     => 'billing',
        ]);

        $this->notifyUsers(
            $this->usersWithRoles(self::PAYMENT_ROLES),
            "project_site_visit/{$visit->id}",
            'Site Visit Payment Pending',
            "Invoice {$data['invoice_number']} for site visit {$visit->reference_number} is ready for payment confirmation."
        );

        return back()->with('success', 'Invoice recorded. Awaiting payment confirmation from Finance.');
    }

    /**
     * Stage 2b — Finance confirms payment.
     */
    public function confirmPayment(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if (!$this->userHasRole(self::PAYMENT_ROLES)) {
            return back()->with('error', 'You are not authorised to confirm site-visit payments.');
        }
        if ($visit->stage !== 'billing' || !$visit->invoice_number) {
            return back()->with('error', 'An invoice must be prepared before payment can be confirmed.');
        }

        $visit->update([
            'payment_confirmed_at' => now(),
            'payment_confirmed_by' => auth()->id(),
            'status'               => 'PAID',
            'stage'                => 'assignment',
        ]);

        $this->notifyUsers(
            $this->usersWithRoles(self::COORDINATOR_ROLES),
            "project_site_visit/{$visit->id}",
            'Site Visit Ready for Assignment',
            "Payment for {$visit->reference_number} is confirmed. Please assign the site-visit team."
        );

        return back()->with('success', 'Payment confirmed. Ready for team assignment.');
    }

    /**
     * Stage 3 — Coordinator assigns the field team.
     */
    public function assignTeam(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if (!$this->userHasRole(self::COORDINATOR_ROLES)) {
            return back()->with('error', 'You are not authorised to assign the site-visit team.');
        }
        if ($visit->stage !== 'assignment') {
            return back()->with('error', 'The team can only be assigned after payment is confirmed.');
        }

        $data = $request->validate([
            'architect_id'       => 'required|exists:users,id',
            'site_engineer_id'   => 'required|exists:users,id',
            'site_supervisor_id' => 'required|exists:users,id',
        ]);

        $visit->update($data + [
            'assigned_by' => auth()->id(),
            'assigned_at' => now(),
            'stage'       => 'confirmation',
        ]);

        $team = User::whereIn('id', array_values($data))->get();
        $this->notifyUsers(
            $team,
            "project_site_visit/{$visit->id}",
            'Assigned to a Site Visit',
            "You have been assigned to site visit {$visit->reference_number} ({$this->subjectName($visit)}) on "
                . optional($visit->visit_date)->format('Y-m-d') . '. Please confirm your readiness.'
        );

        return back()->with('success', 'Team assigned. Awaiting their readiness confirmation.');
    }

    /**
     * Stage 4 — An assigned team member confirms readiness.
     */
    public function confirmReadiness(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if (!$visit->isOnTeam(auth()->id()) && !$this->userHasRole(self::ADMIN_ROLES)) {
            return back()->with('error', 'Only an assigned team member can confirm readiness.');
        }
        if ($visit->stage !== 'confirmation') {
            return back()->with('error', 'This visit is not awaiting a readiness confirmation.');
        }

        $visit->update([
            'team_confirmed_at' => now(),
            'team_confirmed_by' => auth()->id(),
            'stage'             => 'reporting',
        ]);

        if ($visit->user) {
            $this->notifyUsers(
                collect([$visit->user]),
                "project_site_visit/{$visit->id}",
                'Site Visit Confirmed',
                "The team confirmed readiness for site visit {$visit->reference_number}."
            );
        }

        return back()->with('success', 'Readiness confirmed. Upload the report after the visit.');
    }

    /**
     * Stage 5 — Upload the site-visit report.
     */
    public function uploadReport(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if (!$visit->isOnTeam(auth()->id()) && !$this->userHasRole(self::COORDINATOR_ROLES)) {
            return back()->with('error', 'Only an assigned team member can upload the report.');
        }
        if ($visit->stage !== 'reporting') {
            return back()->with('error', 'This visit is not awaiting a report upload.');
        }

        $request->validate([
            'report'       => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,zip,dwg|max:51200',
            'report_notes' => 'nullable|string|max:5000',
        ]);

        $file = $request->file('report');
        $path = $file->store('uploads/site-visit-reports', 'public');

        // If the visit belongs to a project whose schedule has a Survey activity,
        // pause at 'integration' so a coordinator can link it; otherwise finish.
        $hasSurvey = $this->surveyActivityFor($visit) !== null;

        $visit->update([
            'report_path'        => $path,
            'report_name'        => $file->getClientOriginalName(),
            'report_notes'       => $request->report_notes,
            'report_uploaded_at' => now(),
            'report_uploaded_by' => auth()->id(),
            'status'             => 'COMPLETED',
            'stage'              => $hasSurvey ? 'integration' : 'completed',
        ]);

        $this->notifyUsers(
            $this->usersWithRoles(self::REPORT_AUDIENCE),
            "project_site_visit/{$visit->id}",
            'Site Visit Report Available',
            "The report for site visit {$visit->reference_number} ({$this->subjectName($visit)}) has been uploaded."
        );

        if ($hasSurvey) {
            $this->notifyUsers(
                $this->usersWithRoles(self::COORDINATOR_ROLES),
                "project_site_visit/{$visit->id}",
                'Site Visit Ready for Schedule Link',
                "The report for {$visit->reference_number} can be attached to the project's Survey Stage."
            );
        }

        return back()->with('success', $hasSurvey
            ? 'Report uploaded. It can now be attached to the Survey Stage.'
            : 'Report uploaded. Site visit completed.');
    }

    /**
     * Stage 6 — Attach the report to the project's Survey Stage activity.
     */
    public function integrate(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if (!$this->userHasRole(self::COORDINATOR_ROLES)) {
            return back()->with('error', 'You are not authorised to link reports to the schedule.');
        }
        if ($visit->stage !== 'integration') {
            return back()->with('error', 'This visit is not awaiting schedule integration.');
        }

        $activity = $this->surveyActivityFor($visit);
        if (!$activity) {
            return back()->with('error', 'No Survey Stage activity was found for this project. Cannot link the report.');
        }
        if (!$visit->report_path || !Storage::disk('public')->exists($visit->report_path)) {
            return back()->with('error', 'The report file is missing and cannot be attached.');
        }

        DB::transaction(function () use ($visit, $activity) {
            $attachment = ProjectScheduleActivityAttachment::create([
                'activity_id' => $activity->id,
                'path'        => $visit->report_path,
                'name'        => $visit->report_name ?: 'Site Visit Report',
                'mime_type'   => Storage::disk('public')->mimeType($visit->report_path),
                'size_bytes'  => Storage::disk('public')->size($visit->report_path),
                'uploaded_by' => auth()->id(),
            ]);

            $visit->update([
                'schedule_activity_id'   => $activity->id,
                'schedule_attachment_id' => $attachment->id,
                'integrated_at'          => now(),
                'stage'                  => 'completed',
            ]);
        });

        $this->notifyUsers(
            $this->usersWithRoles(['Quantity Surveyor (QS)', 'Architect', 'Civil Engineer', 'Service Engineer']),
            "project_site_visit/{$visit->id}",
            'Survey Report Linked',
            "The site-visit report for {$visit->reference_number} is now linked to the Survey Stage. Review before design begins."
        );

        return back()->with('success', "Report linked to the Survey Stage ({$activity->activity_code}). Workflow complete.");
    }

    /**
     * Cancel a visit at any non-terminal stage (creator or coordinator).
     */
    public function cancel(Request $request, $id)
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        $isOwner = $visit->create_by_id === auth()->id();
        if (!$isOwner && !$this->userHasRole(self::COORDINATOR_ROLES)) {
            return back()->with('error', 'You are not authorised to cancel this visit.');
        }
        if ($visit->isTerminal()) {
            return back()->with('error', 'This visit is already closed.');
        }

        $visit->update([
            'cancelled_at'  => now(),
            'cancelled_by'  => auth()->id(),
            'cancel_reason' => $request->input('cancel_reason'),
            'status'        => 'REJECTED',
            'stage'         => 'cancelled',
        ]);

        return back()->with('success', "Site visit {$visit->reference_number} cancelled.");
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    /**
     * Validate + normalise the preliminary fields shared by store()/update().
     */
    private function validateBasics(Request $request): array
    {
        $request->validate([
            'psv_link_type' => 'required|in:project,client',
            'project_id'    => 'required_if:psv_link_type,project|nullable|exists:projects,id',
            'client_id'     => 'required_if:psv_link_type,client|nullable|exists:project_clients,id',
            'location'      => 'required|string|max:255',
            'description'   => 'required|string|max:1000',
            'phone_number'  => 'nullable|string|max:40',
            'visit_date'    => 'required|date',
        ]);

        $isProject = $request->psv_link_type === 'project';

        return [
            'project_id'   => $isProject ? $request->project_id : null,
            'client_id'    => $isProject ? null : $request->client_id,
            'location'     => $request->location,
            'description'  => $request->description,
            'phone_number' => $request->phone_number,
            'visit_date'   => $request->visit_date,
        ];
    }

    /**
     * The Survey Stage (activity_code A0) activity for the visit's project, or null.
     * Mirrors ProjectScheduleController::createForProject's schedule lookup.
     */
    private function surveyActivityFor(ProjectSiteVisit $visit): ?ProjectScheduleActivity
    {
        if (!$visit->project_id || !$visit->project) {
            return null;
        }

        $project  = $visit->project;
        $schedule = ProjectSchedule::where(function ($q) use ($project) {
            $q->whereHas('lead', fn ($l) => $l->where('project_id', $project->id))
                ->orWhere('client_id', $project->client_id);
        })->first();

        return $schedule
            ? $schedule->activities()->where('activity_code', 'A0')->first()
            : null;
    }

    private function subjectName(ProjectSiteVisit $visit): string
    {
        if ($visit->project) {
            return $visit->project->project_name;
        }
        if ($visit->client) {
            return trim($visit->client->first_name . ' ' . $visit->client->last_name);
        }

        return 'client-only visit';
    }

    private function userHasRole(array $roles): bool
    {
        return auth()->user() && auth()->user()->hasAnyRole($roles);
    }

    private function usersWithRoles(array $roles)
    {
        // NOTE: User has a custom role() BelongsTo that shadows Spatie's `role`
        // query scope, so query the Spatie `roles` relation directly by name.
        return User::whereHas('roles', fn ($q) => $q->whereIn('name', $roles))->get();
    }

    /**
     * Send an in-app (and conditionally email) notification to each user, deduped.
     * Failures per-recipient are swallowed so a bad address can't block the flow.
     */
    private function notifyUsers($users, string $link, string $title, string $body): void
    {
        foreach (collect($users)->unique('id') as $user) {
            try {
                $user->notify(new ApprovalNotification([
                    'staff_id'         => $user->id,
                    'link'             => $link,
                    'title'            => $title,
                    'body'             => $body,
                    'document_id'      => '0',
                    'document_type_id' => '0',
                ]));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
