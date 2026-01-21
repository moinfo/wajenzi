<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\SiteDailyReportResource;
use App\Models\ProjectExpense;
use App\Models\ProjectMaterialRequest;
use App\Models\ProjectSiteVisit;
use App\Models\SiteDailyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * Get all pending approvals for the user.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->pending($request);
    }

    /**
     * Get pending items awaiting approval.
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get pending site daily reports
        $siteReports = SiteDailyReport::where('status', 'pending')
            ->with(['site', 'preparedBy'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'type' => 'site_daily_report',
                'title' => 'Site Report - ' . $r->site?->name,
                'description' => $r->report_date?->format('M d, Y'),
                'submitted_by' => $r->preparedBy?->name,
                'submitted_at' => $r->updated_at?->toISOString(),
                'data' => new SiteDailyReportResource($r),
            ]);

        // Get pending expenses
        $expenses = ProjectExpense::where('status', 'pending')
            ->with(['project', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($e) => [
                'id' => $e->id,
                'type' => 'expense',
                'title' => 'Expense - ' . number_format($e->amount, 2),
                'description' => $e->description,
                'submitted_by' => $e->creator?->name,
                'submitted_at' => $e->updated_at?->toISOString(),
                'data' => new ExpenseResource($e),
            ]);

        // Get pending material requests
        $materialRequests = ProjectMaterialRequest::where('status', 'pending')
            ->with(['project', 'requester'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'type' => 'material_request',
                'title' => 'Material Request - ' . $m->project?->project_name,
                'description' => $m->description ?? 'Material requisition',
                'submitted_by' => $m->requester?->name,
                'submitted_at' => $m->updated_at?->toISOString(),
            ]);

        // Get pending site visits
        $siteVisits = ProjectSiteVisit::where('status', 'pending')
            ->with(['project', 'inspector'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($v) => [
                'id' => $v->id,
                'type' => 'site_visit',
                'title' => 'Site Visit - ' . $v->project?->project_name,
                'description' => $v->visit_date?->format('M d, Y'),
                'submitted_by' => $v->inspector?->name,
                'submitted_at' => $v->updated_at?->toISOString(),
            ]);

        // Merge and sort by date
        $allPending = collect()
            ->merge($siteReports)
            ->merge($expenses)
            ->merge($materialRequests)
            ->merge($siteVisits)
            ->sortByDesc('submitted_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $allPending,
            'meta' => [
                'total' => $allPending->count(),
                'by_type' => [
                    'site_daily_reports' => $siteReports->count(),
                    'expenses' => $expenses->count(),
                    'material_requests' => $materialRequests->count(),
                    'site_visits' => $siteVisits->count(),
                ],
            ],
        ]);
    }

    /**
     * Approve an item.
     */
    public function approve(Request $request, string $type, int $id): JsonResponse
    {
        $model = $this->getModel($type, $id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        if ($model->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending items can be approved.',
            ], 422);
        }

        $model->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' approved successfully.',
        ]);
    }

    /**
     * Reject an item.
     */
    public function reject(Request $request, string $type, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $model = $this->getModel($type, $id);

        if (!$model) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found.',
            ], 404);
        }

        if ($model->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending items can be rejected.',
            ], 422);
        }

        $model->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => ucfirst(str_replace('_', ' ', $type)) . ' rejected.',
        ]);
    }

    /**
     * Get the model instance based on type.
     */
    private function getModel(string $type, int $id)
    {
        return match ($type) {
            'site_daily_report', 'site-daily-report' => SiteDailyReport::find($id),
            'expense' => ProjectExpense::find($id),
            'material_request', 'material-request' => ProjectMaterialRequest::find($id),
            'site_visit', 'site-visit' => ProjectSiteVisit::find($id),
            default => null,
        };
    }
}
