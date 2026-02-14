<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingDocumentResource;
use App\Http\Resources\Client\ClientBoqResource;
use App\Http\Resources\Client\ClientConstructionPhaseResource;
use App\Http\Resources\Client\ClientDailyReportResource;
use App\Http\Resources\Client\ClientDesignResource;
use App\Http\Resources\Client\ClientProgressImageResource;
use App\Http\Resources\Client\ClientProjectResource;
use App\Http\Resources\Client\ClientScheduleActivityResource;
use App\Http\Resources\Client\ClientSiteVisitResource;
use App\Models\BillingDocument;
use App\Models\Project;
use App\Models\ProjectSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDF;

class ProjectController extends Controller
{
    /**
     * Resolve a project owned by the authenticated client.
     */
    private function clientProject(Request $request, $id): Project
    {
        return Project::where('client_id', $request->user()->id)
            ->findOrFail($id);
    }

    /**
     * Project overview.
     */
    public function show(Request $request, $project): JsonResponse
    {
        $project = $this->clientProject($request, $project);
        $project->load(['projectType', 'serviceType', 'projectManager', 'constructionPhases']);

        $schedule = ProjectSchedule::where('client_id', $request->user()->id)->first();
        $progress = null;
        $progressByPhase = [];
        if ($schedule) {
            $schedule->load('activities');
            $progress = $schedule->progress_details;
            $progressByPhase = $schedule->progress_by_phase;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'project' => new ClientProjectResource($project),
                'progress' => $progress,
                'progress_by_phase' => $progressByPhase,
            ],
        ]);
    }

    /**
     * BOQ with sections and items.
     */
    public function boq(Request $request, $project): JsonResponse
    {
        $project = $this->clientProject($request, $project);

        $boqs = $project->boqs()
            ->with(['sections.children.items', 'sections.items', 'items'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => ClientBoqResource::collection($boqs),
        ]);
    }

    /**
     * Schedule — phases and activities.
     */
    public function schedule(Request $request, $project): JsonResponse
    {
        $project = $this->clientProject($request, $project);
        $phases = $project->constructionPhases()->orderBy('start_date')->get();

        $activities = collect();
        $schedule = ProjectSchedule::where('client_id', $request->user()->id)->first();
        if ($schedule) {
            $activities = $schedule->activities()->orderBy('sort_order')->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'phases' => ClientConstructionPhaseResource::collection($phases),
                'activities' => ClientScheduleActivityResource::collection($activities),
            ],
        ]);
    }

    /**
     * Financials — invoices, payments, and summary.
     */
    public function financials(Request $request, $project): JsonResponse
    {
        $project = $this->clientProject($request, $project);
        $client = $request->user();

        $invoices = $project->invoices()->with('payments')->orderBy('created_at', 'desc')->get();

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

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'billing_invoices' => BillingDocumentResource::collection($billingInvoices),
                'billing_quotes' => BillingDocumentResource::collection($billingQuotes),
                'billing_proformas' => BillingDocumentResource::collection($billingProformas),
            ],
        ]);
    }

    /**
     * Documents — designs and project documents.
     */
    public function documents(Request $request, $project): JsonResponse
    {
        $project = $this->clientProject($request, $project);
        $designs = $project->projectDesigns()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => ClientDesignResource::collection($designs),
        ]);
    }

    /**
     * Reports — daily reports and site visits.
     */
    public function reports(Request $request, $project): JsonResponse
    {
        $project = $this->clientProject($request, $project);
        $dailyReports = $project->dailyReports()->with('supervisor')->orderBy('report_date', 'desc')->get();
        $siteVisits = $project->siteVisits()->with('inspector')->orderBy('visit_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_reports' => ClientDailyReportResource::collection($dailyReports),
                'site_visits' => ClientSiteVisitResource::collection($siteVisits),
            ],
        ]);
    }

    /**
     * Gallery — progress images.
     */
    public function gallery(Request $request, $project): JsonResponse
    {
        $project = $this->clientProject($request, $project);

        $images = $project->progressImages()
            ->with('constructionPhase')
            ->orderBy('taken_at', 'desc')
            ->get();

        $phases = $project->constructionPhases()->orderBy('start_date')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'images' => ClientProgressImageResource::collection($images),
                'phases' => ClientConstructionPhaseResource::collection($phases),
            ],
        ]);
    }

    /**
     * Download a billing document PDF (project-scoped).
     */
    public function billingPdf(Request $request, $project, $document)
    {
        $project = $this->clientProject($request, $project);
        $client = $request->user();

        $document = BillingDocument::where('id', $document)
            ->where('client_id', $client->id)
            ->where('project_id', $project->id)
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
     * Download site visit report as PDF.
     */
    public function siteVisitPdf(Request $request, $project, $visit)
    {
        $project = $this->clientProject($request, $project);
        $visit = $project->siteVisits()->with('inspector')->findOrFail($visit);

        $pdf = PDF::loadView('client.site_visit_pdf', compact('project', 'visit'));

        return $pdf->download("site-visit-{$visit->document_number}.pdf");
    }
}
