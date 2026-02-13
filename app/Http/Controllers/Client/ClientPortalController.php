<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ClientPortalController extends Controller
{
    /**
     * Get the authenticated client.
     */
    private function client()
    {
        return Auth::guard('client')->user();
    }

    /**
     * Get a project that belongs to this client, or abort 404.
     */
    private function clientProject($id)
    {
        return Project::where('id', $id)
            ->where('client_id', $this->client()->id)
            ->firstOrFail();
    }

    /**
     * Dashboard — list all projects for this client.
     */
    public function dashboard()
    {
        $client = $this->client();
        $projects = Project::where('client_id', $client->id)
            ->withCount(['invoices', 'boqs', 'dailyReports'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Summary stats
        $stats = [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status', 'APPROVED')->count(),
            'total_contract_value' => $projects->sum('contract_value'),
            'total_invoiced' => $projects->sum(function ($p) {
                return $p->invoices->sum('amount');
            }),
        ];

        return view('client.dashboard', compact('client', 'projects', 'stats'));
    }

    /**
     * Project overview.
     */
    public function projectShow($id)
    {
        $project = $this->clientProject($id);
        $project->load(['projectType', 'serviceType', 'projectManager', 'constructionPhases']);

        return view('client.projects.show', compact('project'));
    }

    /**
     * BOQ — bill of quantities with sections and items.
     */
    public function projectBoq($id)
    {
        $project = $this->clientProject($id);
        $boqs = $project->boqs()
            ->with(['sections.children.items', 'sections.items', 'items'])
            ->get();

        return view('client.projects.boq', compact('project', 'boqs'));
    }

    /**
     * Schedule — construction phases and schedule activities.
     */
    public function projectSchedule($id)
    {
        $project = $this->clientProject($id);
        $phases = $project->constructionPhases()->orderBy('start_date')->get();

        // Get schedule activities if a schedule exists for this client
        $activities = collect();
        $schedule = \App\Models\ProjectSchedule::where('client_id', $this->client()->id)->first();
        if ($schedule) {
            $activities = $schedule->activities()->orderBy('sort_order')->get();
        }

        return view('client.projects.schedule', compact('project', 'phases', 'activities'));
    }

    /**
     * Financials — invoices, payments, and summary.
     */
    public function projectFinancials($id)
    {
        $project = $this->clientProject($id);
        $invoices = $project->invoices()->with('payments')->orderBy('created_at', 'desc')->get();

        $summary = [
            'contract_value' => $project->contract_value ?? 0,
            'total_invoiced' => $invoices->sum('amount'),
            'total_paid' => $invoices->sum(fn($inv) => $inv->payments->sum('amount')),
        ];
        $summary['balance_due'] = $summary['total_invoiced'] - $summary['total_paid'];

        return view('client.projects.financials', compact('project', 'invoices', 'summary'));
    }

    /**
     * Documents — designs and project documents.
     */
    public function projectDocuments($id)
    {
        $project = $this->clientProject($id);
        $designs = $project->projectDesigns()->orderBy('created_at', 'desc')->get();

        return view('client.projects.documents', compact('project', 'designs'));
    }

    /**
     * Reports — daily reports and site visits.
     */
    public function projectReports($id)
    {
        $project = $this->clientProject($id);
        $dailyReports = $project->dailyReports()->with('supervisor')->orderBy('report_date', 'desc')->get();
        $siteVisits = $project->siteVisits()->with('inspector')->orderBy('visit_date', 'desc')->get();

        return view('client.projects.reports', compact('project', 'dailyReports', 'siteVisits'));
    }
}
