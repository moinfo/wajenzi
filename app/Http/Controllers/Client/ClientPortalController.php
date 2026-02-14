<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\Project;
use App\Models\ProjectSchedule;
use Illuminate\Support\Facades\Auth;
use PDF;

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

        // Summary stats — include billing module invoices
        $billingTotal = BillingDocument::where('client_id', $client->id)
            ->where('document_type', 'invoice')
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->sum('total_amount');

        $stats = [
            'total_projects' => $projects->count(),
            'active_projects' => $projects->where('status', 'APPROVED')->count(),
            'total_contract_value' => $projects->sum('contract_value'),
            'total_invoiced' => $billingTotal + $projects->sum(fn($p) => $p->invoices->sum('amount')),
        ];

        return view('client.dashboard', compact('client', 'projects', 'stats'));
    }

    /**
     * Billing — all billing documents for this client across all projects.
     */
    public function billing()
    {
        $client = $this->client();

        $documents = BillingDocument::where('client_id', $client->id)
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->with(['project', 'payments'])
            ->orderBy('issue_date', 'desc')
            ->get();

        $invoices = $documents->where('document_type', 'invoice');
        $quotes = $documents->where('document_type', 'quote');
        $proformas = $documents->where('document_type', 'proforma');
        $creditNotes = $documents->where('document_type', 'credit_note');

        $summary = [
            'total_invoiced' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('paid_amount'),
            'balance_due' => $invoices->sum('balance_amount'),
            'overdue_count' => $invoices->filter(fn($d) => $d->is_overdue)->count(),
        ];

        return view('client.billing', compact('client', 'invoices', 'quotes', 'proformas', 'creditNotes', 'summary'));
    }

    /**
     * Download a billing document PDF (from top-level billing page, no project context).
     */
    public function billingPdf($documentId)
    {
        $client = $this->client();

        $document = BillingDocument::where('id', $documentId)
            ->where('client_id', $client->id)
            ->with(['client', 'items', 'payments'])
            ->firstOrFail();

        $viewMap = [
            'invoice' => 'billing.invoices.pdf',
            'quote' => 'billing.quotations.pdf',
            'proforma' => 'billing.proformas.pdf',
        ];

        $view = $viewMap[$document->document_type] ?? 'billing.invoices.pdf';
        $varName = $document->document_type === 'quote' ? 'quotation' : ($document->document_type === 'proforma' ? 'proforma' : 'invoice');

        $pdf = PDF::loadView($view, [$varName => $document]);

        return $pdf->download("{$document->document_type}-{$document->document_number}.pdf");
    }

    /**
     * Project overview.
     */
    public function projectShow($id)
    {
        $project = $this->clientProject($id);
        $project->load(['projectType', 'serviceType', 'projectManager', 'constructionPhases']);

        // Load schedule for progress visualization
        $schedule = ProjectSchedule::where('client_id', $this->client()->id)->first();
        $progress = null;
        $progressByPhase = [];
        if ($schedule) {
            $schedule->load('activities');
            $progress = $schedule->progress_details;
            $progressByPhase = $schedule->progress_by_phase;
        }

        return view('client.projects.show', compact('project', 'schedule', 'progress', 'progressByPhase'));
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
        $client = $this->client();

        // Legacy project invoices
        $invoices = $project->invoices()->with('payments')->orderBy('created_at', 'desc')->get();

        // Billing module documents for this client + project
        $billingInvoices = BillingDocument::where('client_id', $client->id)
            ->where('project_id', $project->id)
            ->where('document_type', 'invoice')
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->with('payments', 'items')
            ->orderBy('issue_date', 'desc')
            ->get();

        $billingQuotes = BillingDocument::where('client_id', $client->id)
            ->where('project_id', $project->id)
            ->where('document_type', 'quote')
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->with('items')
            ->orderBy('issue_date', 'desc')
            ->get();

        $billingProformas = BillingDocument::where('client_id', $client->id)
            ->where('project_id', $project->id)
            ->where('document_type', 'proforma')
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->with('items')
            ->orderBy('issue_date', 'desc')
            ->get();

        // Summary from billing invoices (primary) + legacy invoices (fallback)
        $legacyInvoiced = $invoices->sum('amount');
        $legacyPaid = $invoices->sum(fn($inv) => $inv->payments->sum('amount'));

        $billingInvoiced = $billingInvoices->sum('total_amount');
        $billingPaid = $billingInvoices->sum('paid_amount');

        $summary = [
            'contract_value' => $project->contract_value ?? 0,
            'total_invoiced' => $billingInvoiced + $legacyInvoiced,
            'total_paid' => $billingPaid + $legacyPaid,
        ];
        $summary['balance_due'] = $summary['total_invoiced'] - $summary['total_paid'];

        return view('client.projects.financials', compact(
            'project', 'invoices', 'billingInvoices', 'billingQuotes', 'billingProformas', 'summary'
        ));
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

    /**
     * Download a billing document (invoice/quote/proforma) as PDF.
     */
    public function billingDocumentPdf($id, $documentId)
    {
        $project = $this->clientProject($id);
        $client = $this->client();

        $document = BillingDocument::where('id', $documentId)
            ->where('client_id', $client->id)
            ->where('project_id', $project->id)
            ->with(['client', 'items', 'payments'])
            ->firstOrFail();

        // Reuse the existing billing PDF templates
        $viewMap = [
            'invoice' => 'billing.invoices.pdf',
            'quote' => 'billing.quotations.pdf',
            'proforma' => 'billing.proformas.pdf',
        ];

        $view = $viewMap[$document->document_type] ?? 'billing.invoices.pdf';
        $varName = $document->document_type === 'quote' ? 'quotation' : ($document->document_type === 'proforma' ? 'proforma' : 'invoice');

        $pdf = PDF::loadView($view, [$varName => $document]);

        return $pdf->download("{$document->document_type}-{$document->document_number}.pdf");
    }

    /**
     * Download site visit report as PDF.
     */
    public function siteVisitPdf($id, $visitId)
    {
        $project = $this->clientProject($id);
        $visit = $project->siteVisits()->with('inspector')->findOrFail($visitId);

        $pdf = PDF::loadView('client.site_visit_pdf', compact('project', 'visit'));

        return $pdf->download("site-visit-{$visit->document_number}.pdf");
    }

    /**
     * Gallery — progress images for a project.
     */
    public function projectGallery($id)
    {
        $project = $this->clientProject($id);
        $images = $project->progressImages()
            ->with('constructionPhase')
            ->orderBy('taken_at', 'desc')
            ->get();
        $phases = $project->constructionPhases()->orderBy('start_date')->get();

        return view('client.projects.gallery', compact('project', 'images', 'phases'));
    }
}
