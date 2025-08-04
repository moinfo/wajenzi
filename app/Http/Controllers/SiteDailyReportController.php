<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\SiteDailyReport;
use App\Models\SiteWorkActivity;
use App\Models\SiteMaterialUsed;
use App\Models\SitePayment;
use App\Models\SiteLaborNeeded;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SiteDailyReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View All Site Reports|View Own Site Reports')->only(['index', 'show']);
        $this->middleware('permission:Add Site Reports')->only(['create', 'store']);
        $this->middleware('permission:Edit All Site Reports|Edit Own Site Reports')->only(['edit', 'update']);
        $this->middleware('permission:Delete All Site Reports|Delete Own Site Reports')->only('destroy');
        $this->middleware('permission:Submit Site Reports')->only('submit');
        $this->middleware('permission:Approve Site Reports')->only('approve');
        $this->middleware('permission:Reject Site Reports')->only('reject');
        $this->middleware('permission:Export Site Reports')->only('export');
        $this->middleware('permission:Share Site Reports')->only('share');
    }

    public function index(Request $request)
    {
        $query = SiteDailyReport::with(['site', 'supervisor', 'preparedBy']);

        // Check permissions
        if (!Auth::user()->can('View All Site Reports')) {
            // Show only reports for sites where user is supervisor
            $query->whereHas('site.currentSupervisorAssignment', function ($q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Apply filters
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('start_date')) {
            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
            $query->whereDate('report_date', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');
            $query->whereDate('report_date', '<=', $endDate);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supervisor_id')) {
            $query->where('supervisor_id', $request->supervisor_id);
        }

        $reports = $query->orderBy('report_date', 'desc')->paginate(15);
        
        // Get data for filters
        $sites = Auth::user()->can('View All Site Reports') 
            ? Site::all() 
            : Site::whereHas('currentSupervisorAssignment', function ($q) {
                $q->where('user_id', Auth::id());
            })->get();

        $supervisors = User::whereHas('siteSupervisorAssignments')->get();

        return view('pages.sites.reports.index', compact('reports', 'sites', 'supervisors'));
    }

    public function create()
    {
        // Get sites where user can create reports
        if (Auth::user()->can('View All Site Reports')) {
            $sites = Site::active()->get();
        } else {
            $sites = Site::active()
                ->whereHas('currentSupervisorAssignment', function ($q) {
                    $q->where('user_id', Auth::id());
                })->get();
        }

        if ($sites->isEmpty()) {
            return redirect()->route('site-daily-reports.index')
                ->with('error', 'No sites available for reporting.');
        }

        return view('pages.sites.reports.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_date' => 'required|date',
            'site_id' => 'required|exists:sites,id',
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'next_steps' => 'nullable|string',
            'challenges' => 'nullable|string',
            
            // Work activities
            'work_activities' => 'nullable|array',
            'work_activities.*' => 'required|string',
            
            // Materials
            'materials' => 'nullable|array',
            'materials.*.name' => 'required|string',
            'materials.*.quantity' => 'nullable|string',
            'materials.*.unit' => 'nullable|string',
            
            // Payments
            'payments' => 'nullable|array',
            'payments.*.description' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.payment_to' => 'nullable|string',
            
            // Labor
            'labor' => 'nullable|array',
            'labor.*.type' => 'required|string',
            'labor.*.description' => 'nullable|string',
        ]);

        // Convert report date format
        $reportDate = \Carbon\Carbon::parse($validated['report_date'])->format('Y-m-d');

        // Get supervisor from site assignment
        $site = Site::with('currentSupervisorAssignment')->find($validated['site_id']);
        $supervisorId = $site->currentSupervisorAssignment->user_id ?? Auth::id();

        DB::beginTransaction();
        try {
            // Create report
            $report = SiteDailyReport::create([
                'report_date' => $reportDate,
                'site_id' => $validated['site_id'],
                'supervisor_id' => $supervisorId,
                'prepared_by' => Auth::id(),
                'progress_percentage' => $validated['progress_percentage'],
                'next_steps' => $validated['next_steps'],
                'challenges' => $validated['challenges'],
                'status' => 'DRAFT'
            ]);

            // Save work activities
            if (!empty($validated['work_activities'])) {
                foreach ($validated['work_activities'] as $index => $activity) {
                    SiteWorkActivity::create([
                        'site_daily_report_id' => $report->id,
                        'work_description' => $activity,
                        'order_number' => $index + 1
                    ]);
                }
            }

            // Save materials
            if (!empty($validated['materials'])) {
                foreach ($validated['materials'] as $material) {
                    if (!empty($material['name'])) {
                        SiteMaterialUsed::create([
                            'site_daily_report_id' => $report->id,
                            'material_name' => $material['name'],
                            'quantity' => $material['quantity'] ?? null,
                            'unit' => $material['unit'] ?? null
                        ]);
                    }
                }
            }

            // Save payments
            if (!empty($validated['payments'])) {
                foreach ($validated['payments'] as $payment) {
                    if (!empty($payment['description'])) {
                        SitePayment::create([
                            'site_daily_report_id' => $report->id,
                            'payment_description' => $payment['description'],
                            'amount' => $payment['amount'],
                            'payment_to' => $payment['payment_to'] ?? null
                        ]);
                    }
                }
            }

            // Save labor
            if (!empty($validated['labor'])) {
                foreach ($validated['labor'] as $labor) {
                    if (!empty($labor['type'])) {
                        SiteLaborNeeded::create([
                            'site_daily_report_id' => $report->id,
                            'labor_type' => $labor['type'],
                            'description' => $labor['description'] ?? null
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('site-daily-reports.show', $report)
                ->with('success', 'Report created successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating report: ' . $e->getMessage());
        }
    }

    public function show($siteDailyReport, $document_type_id = null)
    {
        // Handle both route patterns - with model binding and with ID
        if (!$siteDailyReport instanceof SiteDailyReport) {
            $siteDailyReport = SiteDailyReport::findOrFail($siteDailyReport);
        }
        
        // Check permissions
        if (!Auth::user()->can('View All Site Reports')) {
            if ($siteDailyReport->supervisor_id !== Auth::id() && 
                $siteDailyReport->prepared_by !== Auth::id()) {
                abort(403);
            }
        }

        $siteDailyReport->load([
            'site',
            'supervisor',
            'preparedBy',
            'workActivities',
            'materialsUsed',
            'payments',
            'laborNeeded'
        ]);

        return view('pages.sites.reports.show', ['report' => $siteDailyReport]);
    }

    public function edit(SiteDailyReport $siteDailyReport)
    {
        // Check if report can be edited
        if (!$siteDailyReport->canEdit()) {
            return redirect()->back()
                ->with('error', 'This report cannot be edited.');
        }

        // Check permissions
        if (!Auth::user()->can('Edit All Site Reports')) {
            if ($siteDailyReport->prepared_by !== Auth::id()) {
                abort(403);
            }
        }

        $siteDailyReport->load([
            'workActivities',
            'materialsUsed',
            'payments',
            'laborNeeded'
        ]);

        // Get sites for dropdown
        if (Auth::user()->can('View All Site Reports')) {
            $sites = Site::active()->get();
        } else {
            $sites = Site::active()
                ->whereHas('currentSupervisorAssignment', function ($q) {
                    $q->where('user_id', Auth::id());
                })->get();
        }

        return view('pages.sites.reports.edit', [
            'report' => $siteDailyReport,
            'sites' => $sites
        ]);
    }

    public function update(Request $request, SiteDailyReport $siteDailyReport)
    {
        // Check if report can be edited
        if (!$siteDailyReport->canEdit()) {
            return redirect()->back()
                ->with('error', 'This report cannot be edited.');
        }

        // Check permissions
        if (!Auth::user()->can('Edit All Site Reports')) {
            if ($siteDailyReport->prepared_by !== Auth::id()) {
                abort(403);
            }
        }

        $validated = $request->validate([
            'progress_percentage' => 'required|numeric|min:0|max:100',
            'next_steps' => 'nullable|string',
            'challenges' => 'nullable|string',
            
            // Work activities
            'work_activities' => 'nullable|array',
            'work_activities.*' => 'required|string',
            
            // Materials
            'materials' => 'nullable|array',
            'materials.*.name' => 'required|string',
            'materials.*.quantity' => 'nullable|string',
            'materials.*.unit' => 'nullable|string',
            
            // Payments
            'payments' => 'nullable|array',
            'payments.*.description' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.payment_to' => 'nullable|string',
            
            // Labor
            'labor' => 'nullable|array',
            'labor.*.type' => 'required|string',
            'labor.*.description' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update report
            $siteDailyReport->update([
                'progress_percentage' => $validated['progress_percentage'],
                'next_steps' => $validated['next_steps'],
                'challenges' => $validated['challenges']
            ]);

            // Delete existing relations
            $siteDailyReport->workActivities()->delete();
            $siteDailyReport->materialsUsed()->delete();
            $siteDailyReport->payments()->delete();
            $siteDailyReport->laborNeeded()->delete();

            // Save work activities
            if (!empty($validated['work_activities'])) {
                foreach ($validated['work_activities'] as $index => $activity) {
                    SiteWorkActivity::create([
                        'site_daily_report_id' => $siteDailyReport->id,
                        'work_description' => $activity,
                        'order_number' => $index + 1
                    ]);
                }
            }

            // Save materials
            if (!empty($validated['materials'])) {
                foreach ($validated['materials'] as $material) {
                    if (!empty($material['name'])) {
                        SiteMaterialUsed::create([
                            'site_daily_report_id' => $siteDailyReport->id,
                            'material_name' => $material['name'],
                            'quantity' => $material['quantity'] ?? null,
                            'unit' => $material['unit'] ?? null
                        ]);
                    }
                }
            }

            // Save payments
            if (!empty($validated['payments'])) {
                foreach ($validated['payments'] as $payment) {
                    if (!empty($payment['description'])) {
                        SitePayment::create([
                            'site_daily_report_id' => $siteDailyReport->id,
                            'payment_description' => $payment['description'],
                            'amount' => $payment['amount'],
                            'payment_to' => $payment['payment_to'] ?? null
                        ]);
                    }
                }
            }

            // Save labor
            if (!empty($validated['labor'])) {
                foreach ($validated['labor'] as $labor) {
                    if (!empty($labor['type'])) {
                        SiteLaborNeeded::create([
                            'site_daily_report_id' => $siteDailyReport->id,
                            'labor_type' => $labor['type'],
                            'description' => $labor['description'] ?? null
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('site-daily-reports.show', $siteDailyReport)
                ->with('success', 'Report updated successfully.');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating report: ' . $e->getMessage());
        }
    }

    public function destroy(SiteDailyReport $siteDailyReport)
    {
        // Check if report can be deleted
        if (!$siteDailyReport->canDelete()) {
            return redirect()->back()
                ->with('error', 'This report cannot be deleted.');
        }

        // Check permissions
        if (!Auth::user()->can('Delete All Site Reports')) {
            if ($siteDailyReport->prepared_by !== Auth::id()) {
                abort(403);
            }
        }

        $siteDailyReport->delete();

        return redirect()->route('site-daily-reports.index')
            ->with('success', 'Report deleted successfully.');
    }

    public function myReports(Request $request)
    {
        $query = SiteDailyReport::with(['site', 'supervisor', 'preparedBy'])
            ->where(function ($q) {
                $q->where('supervisor_id', Auth::id())
                  ->orWhere('prepared_by', Auth::id());
            });

        // Apply filters
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('start_date')) {
            $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->start_date)->format('Y-m-d');
            $query->whereDate('report_date', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', $request->end_date)->format('Y-m-d');
            $query->whereDate('report_date', '<=', $endDate);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reports = $query->orderBy('report_date', 'desc')->paginate(15);
        
        // Get sites where user is supervisor
        $sites = Site::whereHas('currentSupervisorAssignment', function ($q) {
            $q->where('user_id', Auth::id());
        })->get();

        return view('pages.sites.reports.my_reports', compact('reports', 'sites'));
    }

    public function submit(SiteDailyReport $siteDailyReport)
    {
        if (!$siteDailyReport->canSubmit()) {
            return redirect()->back()
                ->with('error', 'This report cannot be submitted.');
        }

        if ($siteDailyReport->prepared_by !== Auth::id()) {
            abort(403);
        }

        $siteDailyReport->update(['status' => 'PENDING']);

        // TODO: Trigger approval workflow

        return redirect()->route('site-daily-reports.show', $siteDailyReport)
            ->with('success', 'Report submitted for approval.');
    }

    public function approve(SiteDailyReport $siteDailyReport)
    {
        if (!$siteDailyReport->canApprove()) {
            return redirect()->back()
                ->with('error', 'This report cannot be approved.');
        }

        $siteDailyReport->update(['status' => 'APPROVED']);

        return redirect()->route('site-daily-reports.show', $siteDailyReport)
            ->with('success', 'Report approved successfully.');
    }

    public function reject(SiteDailyReport $siteDailyReport)
    {
        if (!$siteDailyReport->canApprove()) {
            return redirect()->back()
                ->with('error', 'This report cannot be rejected.');
        }

        $siteDailyReport->update(['status' => 'REJECTED']);

        return redirect()->route('site-daily-reports.show', $siteDailyReport)
            ->with('success', 'Report rejected.');
    }

    public function export(SiteDailyReport $siteDailyReport)
    {
        $siteDailyReport->load([
            'site',
            'supervisor',
            'workActivities',
            'materialsUsed',
            'payments',
            'laborNeeded'
        ]);

        $content = $siteDailyReport->getFormattedReport();

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="site-report-' . $siteDailyReport->report_date->format('Y-m-d') . '.txt"');
    }

    public function share(SiteDailyReport $report)
    {
        // Debug the incoming parameter
        \Log::info('Share method called', [
            'report_id' => $report->id,
            'exists' => $report->exists,
            'site_id' => $report->site_id
        ]);

        // Ensure we have a valid report
        if (!$report->exists) {
            abort(404, 'Report not found');
        }

        // Load all necessary relationships - same as show method
        $report->load([
            'site.currentSupervisor',
            'supervisor',
            'preparedBy',
            'workActivities',
            'materialsUsed',
            'payments',
            'laborNeeded'
        ]);

        // Generate the formatted report content
        $content = $report->getFormattedReport();
        
        // Create WhatsApp URL with proper encoding
        $whatsappUrl = 'https://api.whatsapp.com/send/?text=' . urlencode($content) . '&type=custom_url&app_absent=0';

        return redirect()->away($whatsappUrl);
    }
}