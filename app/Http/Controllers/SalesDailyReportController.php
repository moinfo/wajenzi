<?php

namespace App\Http\Controllers;

use App\Models\SalesDailyReport;
use App\Models\SalesLeadFollowup;
use App\Models\SalesReportActivity;
use App\Models\SalesCustomerAcquisitionCost;
use App\Models\SalesClientConcern;
use App\Models\ClientSource;
use App\Models\User;
use App\Models\Department;
use App\Models\ProjectClient;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesDailyReportController extends Controller
{
    public function index(Request $request)
    {
        // Handle CRUD operations
        if ($this->handleCrud($request, 'SalesDailyReport')) {
            return back();
        }

        $query = $this->filteredReportsQuery($request)
            ->with(['preparedBy.department', 'leadFollowups', 'salesActivities', 'clientConcerns']);

        $reports = $query->orderBy('report_date', 'desc')->paginate(15)->appends($request->query());

        $summary = $this->buildSummary($request);
        $users = User::all();

        $data = [
            'reports' => $reports,
            'users' => $users,
            'object' => new SalesDailyReport(),
            'client_sources' => ClientSource::all(),
            'departments' => Department::all(),
            'clients' => ProjectClient::all(),
            'leads' => Lead::active()->get(),
            'summary' => $summary,
        ];

        return view('pages.sales.sales_daily_reports')->with($data);
    }

    /**
     * Shared filter builder for the index view and the summary exports.
     */
    private function filteredReportsQuery(Request $request)
    {
        $query = SalesDailyReport::query();

        if ($request->start_date) {
            try {
                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
                $query->whereDate('report_date', '>=', $startDate);
            } catch (\Exception $e) {
                // Ignore malformed date
            }
        }

        if ($request->end_date) {
            try {
                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');
                $query->whereDate('report_date', '<=', $endDate);
            } catch (\Exception $e) {
                // Ignore malformed date
            }
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->user_id) {
            $query->where('prepared_by', $request->user_id);
        }

        return $query;
    }

    /**
     * Build summary KPIs + per-user invoice breakdown for the filtered range.
     */
    private function buildSummary(Request $request): array
    {
        $reportIds = $this->filteredReportsQuery($request)->pluck('id');

        $activities = SalesReportActivity::whereIn('sales_daily_report_id', $reportIds)->get();

        $byUser = SalesReportActivity::whereIn('sales_report_activities.sales_daily_report_id', $reportIds)
            ->join('sales_daily_reports', 'sales_daily_reports.id', '=', 'sales_report_activities.sales_daily_report_id')
            ->join('users', 'users.id', '=', 'sales_daily_reports.prepared_by')
            ->selectRaw('users.id as user_id, users.name as user_name,
                COUNT(sales_report_activities.id) as invoice_count,
                COALESCE(SUM(sales_report_activities.invoice_sum), 0) as invoice_total,
                COALESCE(SUM(CASE WHEN sales_report_activities.status = "paid" THEN sales_report_activities.invoice_sum ELSE 0 END), 0) as paid_total,
                COALESCE(SUM(CASE WHEN sales_report_activities.status = "not_paid" THEN sales_report_activities.invoice_sum ELSE 0 END), 0) as unpaid_total')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('invoice_total')
            ->get();

        return [
            'reports_count'   => $reportIds->count(),
            'invoices_count'  => $activities->count(),
            'invoices_total'  => (float) $activities->sum('invoice_sum'),
            'paid_total'      => (float) $activities->where('status', 'paid')->sum('invoice_sum'),
            'unpaid_total'    => (float) $activities->where('status', 'not_paid')->sum('invoice_sum'),
            'partial_total'   => (float) $activities->where('status', 'partial')->sum('invoice_sum'),
            'payments_total'  => (float) $activities->sum('payment_amount'),
            'followups_count' => SalesLeadFollowup::whereIn('sales_daily_report_id', $reportIds)->count(),
            'concerns_count'  => SalesClientConcern::whereIn('sales_daily_report_id', $reportIds)->count(),
            'by_user'         => $byUser,
            'filters'         => [
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'status'     => $request->status,
                'user_id'    => $request->user_id,
            ],
        ];
    }

    /**
     * Download summary as an Excel-compatible file (HTML table with .xls extension).
     */
    public function summaryExcel(Request $request)
    {
        $summary = $this->buildSummary($request);
        $filename = 'sales_summary_' . now()->format('Ymd_His') . '.xls';

        $html = view('pages.sales.exports.sales_summary_excel', compact('summary'))->render();

        return response($html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download summary as a PDF (DomPDF).
     */
    public function summaryPdf(Request $request)
    {
        $summary = $this->buildSummary($request);
        $filename = 'sales_summary_' . now()->format('Ymd_His') . '.pdf';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pages.sales.exports.sales_summary_pdf', compact('summary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function create()
    {
        $data = [
            'object' => new SalesDailyReport(),
            'client_sources' => ClientSource::all(),
            'users' => User::all(),
            'departments' => Department::all(),
            'clients' => ProjectClient::all(),
            'leads' => Lead::active()->get()
        ];

        return view('pages.sales.sales_daily_report_form')->with($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'report_date' => 'required|date_format:Y-m-d',
            'department_id' => 'required|exists:departments,id',
            'daily_summary' => 'required|string',
            'lead_followups' => 'array',
            'sales_activities' => 'array',
            'client_concerns' => 'array',
            'cac_data' => 'array'
        ]);

        DB::beginTransaction();
        try {
            $reportDate = $request->report_date;
            
            // Create the main report
            $report = SalesDailyReport::create([
                'report_date' => $reportDate,
                'prepared_by' => Auth::id(),
                'department_id' => $request->department_id,
                'daily_summary' => $request->daily_summary,
                'notes_recommendations' => $request->notes_recommendations,
                'status' => 'DRAFT'
            ]);

            // Save lead followups
            if ($request->lead_followups) {
                foreach ($request->lead_followups as $followup) {
                    if (!empty($followup['lead_id']) || !empty($followup['details_discussion'])) {
                        // Convert followup date if provided
                        $followupDate = null;
                        if (!empty($followup['followup_date'])) {
                            $followupDate = \Carbon\Carbon::parse($followup['followup_date'])->format('Y-m-d');
                        }

                        SalesLeadFollowup::create([
                            'sales_daily_report_id' => $report->id,
                            'lead_name' => $followup['lead_name'] ?? '',
                            'client_id' => $followup['client_id'] ?? null,
                            'lead_id' => $followup['lead_id'] ?? null,
                            'client_source_id' => $followup['client_source_id'] ?? null,
                            'details_discussion' => $followup['details_discussion'] ?? '',
                            'outcome' => $followup['outcome'] ?? '',
                            'next_step' => $followup['next_step'] ?? '',
                            'followup_date' => $followupDate
                        ]);
                    }
                }
            }

            // Save sales activities
            if ($request->sales_activities) {
                foreach ($request->sales_activities as $activity) {
                    if (!empty($activity['activity'])) {
                        SalesReportActivity::create([
                            'sales_daily_report_id' => $report->id,
                            'invoice_no' => $activity['invoice_no'] ?? null,
                            'invoice_sum' => $activity['invoice_sum'] ?? 0,
                            'activity' => $activity['activity'],
                            'status' => $activity['status'] ?? 'not_paid',
                            'payment_amount' => $activity['payment_amount'] ?? null
                        ]);
                    }
                }
            }

            // Save client concerns
            if ($request->client_concerns) {
                foreach ($request->client_concerns as $concern) {
                    if (!empty($concern['client_name'])) {
                        SalesClientConcern::create([
                            'sales_daily_report_id' => $report->id,
                            'client_name' => $concern['client_name'],
                            'client_id' => $concern['client_id'] ?? null,
                            'issue_concern' => $concern['issue_concern'] ?? '',
                            'action_taken' => $concern['action_taken'] ?? ''
                        ]);
                    }
                }
            }

            // Save CAC data
            if ($request->cac_data) {
                SalesCustomerAcquisitionCost::create([
                    'sales_daily_report_id' => $report->id,
                    'marketing_cost' => $request->cac_data['marketing_cost'] ?? 0,
                    'sales_cost' => $request->cac_data['sales_cost'] ?? 0,
                    'other_cost' => $request->cac_data['other_cost'] ?? 0,
                    'new_customers' => $request->cac_data['new_customers'] ?? 0,
                    'notes' => $request->cac_data['notes'] ?? null
                ]);
            }

            DB::commit();
            return redirect()->route('sales_daily_reports')->with('success', 'Sales Daily Report created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Failed to create report: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $report = SalesDailyReport::with([
            'preparedBy',
            'department',
            'leadFollowups.clientSource',
            'leadFollowups.client',
            'leadFollowups.lead',
            'salesActivities',
            'customerAcquisitionCost',
            'clientConcerns.client'
        ])->findOrFail($id);

        $data = [
            'report' => $report
        ];

        return view('pages.sales.sales_daily_report_view')->with($data);
    }

    public function edit($id)
    {
        $report = SalesDailyReport::with([
            'leadFollowups',
            'salesActivities',
            'customerAcquisitionCost',
            'clientConcerns'
        ])->findOrFail($id);

        if (!$report->canEdit()) {
            return back()->with('error', 'Cannot edit report in current status.');
        }

        $data = [
            'object' => $report,
            'client_sources' => ClientSource::all(),
            'users' => User::all(),
            'departments' => Department::all(),
            'clients' => ProjectClient::all(),
            'leads' => Lead::active()->get()
        ];

        return view('pages.sales.sales_daily_report_form')->with($data);
    }

    public function update(Request $request, $id)
    {
        $report = SalesDailyReport::findOrFail($id);

        if (!$report->canEdit()) {
            return back()->with('error', 'Cannot edit report in current status.');
        }

        $request->validate([
            'report_date' => 'required|date_format:Y-m-d',
            'department_id' => 'required|exists:departments,id',
            'daily_summary' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $reportDate = $request->report_date;
            
            // Update main report
            $report->update([
                'report_date' => $reportDate,
                'department_id' => $request->department_id,
                'daily_summary' => $request->daily_summary,
                'notes_recommendations' => $request->notes_recommendations
            ]);

            // Delete existing related records and recreate
            $report->leadFollowups()->delete();
            $report->salesActivities()->delete();
            $report->customerAcquisitionCost()->delete();
            $report->clientConcerns()->delete();

            // Save lead followups
            if ($request->lead_followups) {
                foreach ($request->lead_followups as $followup) {
                    if (!empty($followup['lead_id']) || !empty($followup['details_discussion'])) {
                        // Convert followup date if provided
                        $followupDate = null;
                        if (!empty($followup['followup_date'])) {
                            try {
                                // Try d/m/Y format first
                                $followupDate = \Carbon\Carbon::createFromFormat('d/m/Y', $followup['followup_date'])->format('Y-m-d');
                            } catch (\Exception $e) {
                                try {
                                    // Try Y-m-d format as fallback
                                    $followupDate = \Carbon\Carbon::createFromFormat('Y-m-d', $followup['followup_date'])->format('Y-m-d');
                                } catch (\Exception $e2) {
                                    // Handle invalid date format - skip this followup date
                                    $followupDate = null;
                                }
                            }
                        }
                        
                        SalesLeadFollowup::create([
                            'sales_daily_report_id' => $report->id,
                            'lead_name' => $followup['lead_name'] ?? '',
                            'client_id' => $followup['client_id'] ?? null,
                            'lead_id' => $followup['lead_id'] ?? null,
                            'client_source_id' => $followup['client_source_id'] ?? null,
                            'details_discussion' => $followup['details_discussion'] ?? '',
                            'outcome' => $followup['outcome'] ?? '',
                            'next_step' => $followup['next_step'] ?? '',
                            'followup_date' => $followupDate
                        ]);
                    }
                }
            }

            // Save sales activities
            if ($request->sales_activities) {
                foreach ($request->sales_activities as $activity) {
                    if (!empty($activity['activity']) || !empty($activity['invoice_sum'])) {
                        SalesReportActivity::create([
                            'sales_daily_report_id' => $report->id,
                            'invoice_no' => $activity['invoice_no'] ?? null,
                            'invoice_sum' => $activity['invoice_sum'] ?? 0,
                            'activity' => $activity['activity'] ?? '',
                            'status' => $activity['status'] ?? 'not_paid',
                            'payment_amount' => $activity['payment_amount'] ?? null
                        ]);
                    }
                }
            }

            // Save client concerns
            if ($request->client_concerns) {
                foreach ($request->client_concerns as $concern) {
                    if (!empty($concern['client_name']) || !empty($concern['issue_concern'])) {
                        SalesClientConcern::create([
                            'sales_daily_report_id' => $report->id,
                            'client_name' => $concern['client_name'] ?? '',
                            'client_id' => $concern['client_id'] ?? null,
                            'issue_concern' => $concern['issue_concern'] ?? '',
                            'action_taken' => $concern['action_taken'] ?? ''
                        ]);
                    }
                }
            }

            // Save CAC data
            if ($request->cac_data && (
                !empty($request->cac_data['marketing_cost']) || 
                !empty($request->cac_data['sales_cost']) || 
                !empty($request->cac_data['other_cost']) || 
                !empty($request->cac_data['new_customers'])
            )) {
                $totalCost = ($request->cac_data['marketing_cost'] ?? 0) + 
                           ($request->cac_data['sales_cost'] ?? 0) + 
                           ($request->cac_data['other_cost'] ?? 0);
                $newCustomers = $request->cac_data['new_customers'] ?? 0;
                $cacValue = $newCustomers > 0 ? $totalCost / $newCustomers : 0;

                SalesCustomerAcquisitionCost::create([
                    'sales_daily_report_id' => $report->id,
                    'marketing_cost' => $request->cac_data['marketing_cost'] ?? 0,
                    'sales_cost' => $request->cac_data['sales_cost'] ?? 0,
                    'other_cost' => $request->cac_data['other_cost'] ?? 0,
                    'total_cost' => $totalCost,
                    'new_customers' => $newCustomers,
                    'cac_value' => $cacValue,
                    'notes' => $request->cac_data['notes'] ?? null
                ]);
            }

            DB::commit();
            return redirect()->route('sales_daily_reports')->with('success', 'Sales Daily Report updated successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withInput()->with('error', 'Failed to update report: ' . $e->getMessage());
        }
    }


    public function exportPDF($id)
    {
        $report = SalesDailyReport::with([
            'preparedBy',
            'leadFollowups.clientSource',
            'salesActivities',
            'customerAcquisitionCost',
            'clientConcerns'
        ])->findOrFail($id);

        // PDF generation logic will be implemented here
        return view('pages.sales.sales_daily_report_pdf', compact('report'));
    }
}