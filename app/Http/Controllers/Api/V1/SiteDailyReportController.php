<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SiteDailyReportResource;
use App\Models\SiteDailyReport;
use App\Models\SiteLaborNeeded;
use App\Models\SiteMaterialUsed;
use App\Models\SitePayment;
use App\Models\SiteWorkActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SiteDailyReportController extends Controller
{
    /**
     * List site daily reports.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SiteDailyReport::with(['site', 'supervisor', 'preparedBy'])
            ->orderBy('report_date', 'desc');

        // Filter by site
        if ($request->site_id) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->start_date) {
            $query->where('report_date', '>=', Carbon::parse($request->start_date));
        }
        if ($request->end_date) {
            $query->where('report_date', '<=', Carbon::parse($request->end_date));
        }

        // Filter by user's reports or supervised reports
        if ($request->my_reports) {
            $query->where('prepared_by', $user->id);
        }

        $reports = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => SiteDailyReportResource::collection($reports),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * Create a new site daily report.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_date' => 'required|date',
            'site_id' => 'required|exists:sites,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'next_steps' => 'nullable|string',
            'challenges' => 'nullable|string',
            // Nested arrays for related data
            'work_activities' => 'nullable|array',
            'work_activities.*.activity_name' => 'required_with:work_activities|string',
            'work_activities.*.description' => 'nullable|string',
            'work_activities.*.workers_count' => 'nullable|integer',
            'materials_used' => 'nullable|array',
            'materials_used.*.material_name' => 'required_with:materials_used|string',
            'materials_used.*.quantity' => 'required_with:materials_used|numeric',
            'materials_used.*.unit' => 'nullable|string',
            'materials_used.*.unit_price' => 'nullable|numeric',
            'payments' => 'nullable|array',
            'payments.*.recipient_name' => 'required_with:payments|string',
            'payments.*.amount' => 'required_with:payments|numeric',
            'payments.*.payment_type' => 'nullable|string',
            'labor_needed' => 'nullable|array',
            'labor_needed.*.labor_type' => 'required_with:labor_needed|string',
            'labor_needed.*.quantity' => 'required_with:labor_needed|integer',
        ]);

        DB::beginTransaction();
        try {
            $report = SiteDailyReport::create([
                'report_date' => $validated['report_date'],
                'site_id' => $validated['site_id'],
                'supervisor_id' => $validated['supervisor_id'] ?? null,
                'prepared_by' => $request->user()->id,
                'progress_percentage' => $validated['progress_percentage'] ?? null,
                'next_steps' => $validated['next_steps'] ?? null,
                'challenges' => $validated['challenges'] ?? null,
                'status' => SiteDailyReport::STATUS_DRAFT ?? 'draft',
            ]);

            // Create work activities
            if (!empty($validated['work_activities'])) {
                foreach ($validated['work_activities'] as $activity) {
                    SiteWorkActivity::create([
                        'site_daily_report_id' => $report->id,
                        'activity_name' => $activity['activity_name'],
                        'description' => $activity['description'] ?? null,
                        'workers_count' => $activity['workers_count'] ?? null,
                        'status' => $activity['status'] ?? 'completed',
                    ]);
                }
            }

            // Create materials used
            if (!empty($validated['materials_used'])) {
                foreach ($validated['materials_used'] as $material) {
                    SiteMaterialUsed::create([
                        'site_daily_report_id' => $report->id,
                        'material_name' => $material['material_name'],
                        'quantity' => $material['quantity'],
                        'unit' => $material['unit'] ?? null,
                        'unit_price' => $material['unit_price'] ?? 0,
                        'total_cost' => ($material['quantity'] ?? 0) * ($material['unit_price'] ?? 0),
                    ]);
                }
            }

            // Create payments
            if (!empty($validated['payments'])) {
                foreach ($validated['payments'] as $payment) {
                    SitePayment::create([
                        'site_daily_report_id' => $report->id,
                        'recipient_name' => $payment['recipient_name'],
                        'amount' => $payment['amount'],
                        'payment_type' => $payment['payment_type'] ?? null,
                        'payment_method' => $payment['payment_method'] ?? null,
                    ]);
                }
            }

            // Create labor needed
            if (!empty($validated['labor_needed'])) {
                foreach ($validated['labor_needed'] as $labor) {
                    SiteLaborNeeded::create([
                        'site_daily_report_id' => $report->id,
                        'labor_type' => $labor['labor_type'],
                        'quantity' => $labor['quantity'],
                        'rate_per_day' => $labor['rate_per_day'] ?? 0,
                        'total_cost' => ($labor['quantity'] ?? 0) * ($labor['rate_per_day'] ?? 0),
                    ]);
                }
            }

            DB::commit();

            $report->load(['site', 'supervisor', 'preparedBy', 'workActivities', 'materialsUsed', 'payments', 'laborNeeded']);

            return response()->json([
                'success' => true,
                'message' => 'Site daily report created successfully.',
                'data' => new SiteDailyReportResource($report),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific site daily report.
     */
    public function show(int $id): JsonResponse
    {
        $report = SiteDailyReport::with([
            'site',
            'supervisor',
            'preparedBy',
            'workActivities',
            'materialsUsed',
            'payments',
            'laborNeeded',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new SiteDailyReportResource($report),
        ]);
    }

    /**
     * Update a site daily report.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $report = SiteDailyReport::findOrFail($id);

        // Check if can edit
        if (!$report->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'This report cannot be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'report_date' => 'sometimes|date',
            'site_id' => 'sometimes|exists:sites,id',
            'supervisor_id' => 'nullable|exists:users,id',
            'progress_percentage' => 'nullable|integer|min:0|max:100',
            'next_steps' => 'nullable|string',
            'challenges' => 'nullable|string',
            'work_activities' => 'nullable|array',
            'materials_used' => 'nullable|array',
            'payments' => 'nullable|array',
            'labor_needed' => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $report->update([
                'report_date' => $validated['report_date'] ?? $report->report_date,
                'site_id' => $validated['site_id'] ?? $report->site_id,
                'supervisor_id' => $validated['supervisor_id'] ?? $report->supervisor_id,
                'progress_percentage' => $validated['progress_percentage'] ?? $report->progress_percentage,
                'next_steps' => $validated['next_steps'] ?? $report->next_steps,
                'challenges' => $validated['challenges'] ?? $report->challenges,
            ]);

            // Update related records if provided (replace strategy)
            if (isset($validated['work_activities'])) {
                $report->workActivities()->delete();
                foreach ($validated['work_activities'] as $activity) {
                    SiteWorkActivity::create([
                        'site_daily_report_id' => $report->id,
                        'activity_name' => $activity['activity_name'],
                        'description' => $activity['description'] ?? null,
                        'workers_count' => $activity['workers_count'] ?? null,
                    ]);
                }
            }

            if (isset($validated['materials_used'])) {
                $report->materialsUsed()->delete();
                foreach ($validated['materials_used'] as $material) {
                    SiteMaterialUsed::create([
                        'site_daily_report_id' => $report->id,
                        'material_name' => $material['material_name'],
                        'quantity' => $material['quantity'],
                        'unit' => $material['unit'] ?? null,
                        'unit_price' => $material['unit_price'] ?? 0,
                        'total_cost' => ($material['quantity'] ?? 0) * ($material['unit_price'] ?? 0),
                    ]);
                }
            }

            if (isset($validated['payments'])) {
                $report->payments()->delete();
                foreach ($validated['payments'] as $payment) {
                    SitePayment::create([
                        'site_daily_report_id' => $report->id,
                        'recipient_name' => $payment['recipient_name'],
                        'amount' => $payment['amount'],
                        'payment_type' => $payment['payment_type'] ?? null,
                    ]);
                }
            }

            if (isset($validated['labor_needed'])) {
                $report->laborNeeded()->delete();
                foreach ($validated['labor_needed'] as $labor) {
                    SiteLaborNeeded::create([
                        'site_daily_report_id' => $report->id,
                        'labor_type' => $labor['labor_type'],
                        'quantity' => $labor['quantity'],
                        'rate_per_day' => $labor['rate_per_day'] ?? 0,
                        'total_cost' => ($labor['quantity'] ?? 0) * ($labor['rate_per_day'] ?? 0),
                    ]);
                }
            }

            DB::commit();

            $report->load(['site', 'supervisor', 'preparedBy', 'workActivities', 'materialsUsed', 'payments', 'laborNeeded']);

            return response()->json([
                'success' => true,
                'message' => 'Site daily report updated successfully.',
                'data' => new SiteDailyReportResource($report),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a site daily report.
     */
    public function destroy(int $id): JsonResponse
    {
        $report = SiteDailyReport::findOrFail($id);

        if (!$report->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'This report cannot be deleted.',
            ], 403);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Site daily report deleted successfully.',
        ]);
    }

    /**
     * Submit report for approval.
     */
    public function submit(int $id): JsonResponse
    {
        $report = SiteDailyReport::findOrFail($id);

        if (!$report->canSubmit()) {
            return response()->json([
                'success' => false,
                'message' => 'This report cannot be submitted.',
            ], 403);
        }

        $report->update(['status' => SiteDailyReport::STATUS_PENDING ?? 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted for approval.',
            'data' => new SiteDailyReportResource($report->fresh()),
        ]);
    }

    /**
     * Approve a report.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $report = SiteDailyReport::findOrFail($id);

        if (!$report->canApprove()) {
            return response()->json([
                'success' => false,
                'message' => 'This report cannot be approved.',
            ], 403);
        }

        $report->update(['status' => SiteDailyReport::STATUS_APPROVED ?? 'approved']);

        return response()->json([
            'success' => true,
            'message' => 'Report approved successfully.',
            'data' => new SiteDailyReportResource($report->fresh()),
        ]);
    }

    /**
     * Reject a report.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $report = SiteDailyReport::findOrFail($id);

        if (!$report->canApprove()) {
            return response()->json([
                'success' => false,
                'message' => 'This report cannot be rejected.',
            ], 403);
        }

        $report->update([
            'status' => SiteDailyReport::STATUS_REJECTED ?? 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report rejected.',
            'data' => new SiteDailyReportResource($report->fresh()),
        ]);
    }
}
