<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SalesDailyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SalesDailyReportController extends Controller
{
    /**
     * The web's Sales Daily Reports page as data: the same filters (a user sees
     * only their own reports unless they may view all), the summary cards and
     * "Invoices Written - by Preparer" table, and the report rows with the
     * counts, department and approval steps the web's list shows.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $canViewAll = (bool) $user->can('View All Daily Reports');

        $query = SalesDailyReport::with([
                'preparedBy.department',
                'approvalStatus',
                'leadFollowups:id,sales_daily_report_id',
                'salesActivities:id,sales_daily_report_id,invoice_no,invoice_sum,activity,status',
                'clientConcerns:id,sales_daily_report_id',
            ])
            ->orderBy('report_date', 'desc');

        if (!$canViewAll || $request->boolean('my_reports')) {
            $query->where('prepared_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->status));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('report_date', '>=', Carbon::parse($request->start_date));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('report_date', '<=', Carbon::parse($request->end_date));
        }
        if ($request->filled('user_id')) {
            $query->where('prepared_by', $request->user_id);
        }

        $reports = $query->paginate((int) ($request->per_page ?? 20));

        $flowSteps = [];
        $rows = collect($reports->items())->map(function (SalesDailyReport $report) use (&$flowSteps) {
            $row = $this->transformReport($report, false);

            $approvals = collect($report->approvalStatus?->steps ?? [])->map(function ($step) use (&$flowSteps) {
                $id = $step['id'] ?? null;
                if ($id !== null && !array_key_exists($id, $flowSteps)) {
                    $flowSteps[$id] = \RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep::with('role')->find($id)?->role?->name;
                }

                return [
                    'role_name' => $flowSteps[$id] ?? 'Step',
                    'action' => $step['process_approval_action'] ?? 'Pending',
                ];
            })->values();

            return $row + [
                'department_name' => $report->preparedBy?->department?->name,
                'followups_count' => $report->leadFollowups->count(),
                'activities_count' => $report->salesActivities->count(),
                'activities_total' => (float) $report->salesActivities->sum('invoice_sum'),
                'concerns_count' => $report->clientConcerns->count(),
                'approval_status' => $report->approvalStatus?->status ?? 'PENDING',
                'approvals' => $approvals,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $rows,
            'summary' => $this->summary($request),
            'filters' => [
                'can_view_all' => $canViewAll,
                'users' => $canViewAll
                    ? \App\Models\User::orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values()
                    : [],
            ],
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    /**
     * The summary cards and per-preparer invoice table, computed by the web
     * controller's own method so the numbers are the web's. They are the totals
     * of everything the user may see: the list's date, status and preparer
     * filters narrow the list only, never these figures.
     */
    private function summary(Request $request): array
    {
        $web = app(\App\Http\Controllers\SalesDailyReportController::class);
        $method = new \ReflectionMethod($web, 'buildSummary');
        $method->setAccessible(true);
        $summary = $method->invoke($web, Request::create('/x', 'GET'));

        $summary['by_user'] = collect($summary['by_user'])->map(fn ($r) => [
            'user_id' => (int) $r->user_id,
            'user_name' => $r->user_name,
            'invoice_count' => (int) $r->invoice_count,
            'invoice_total' => (float) $r->invoice_total,
            'paid_total' => (float) $r->paid_total,
            'unpaid_total' => (float) $r->unpaid_total,
        ])->values()->all();
        unset($summary['filters']);

        return $summary;
    }
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_date' => 'required|date',
            'notes' => 'nullable|string',
            'next_steps' => 'nullable|string',
            'challenges' => 'nullable|string',
        ]);

        $report = SalesDailyReport::create([
            'report_date' => $validated['report_date'],
            'prepared_by' => $request->user()->id,
            'daily_summary' => $validated['notes'] ?? '',
            'notes_recommendations' => $validated['next_steps'] ?? null,
            'status' => 'DRAFT',
        ]);

        $report->load([
            'preparedBy:id,name',
            'leadFollowups:id,sales_daily_report_id,lead_name,details_discussion,outcome,next_step,followup_date',
            'salesActivities:id,sales_daily_report_id,invoice_no,invoice_sum,activity,status',
            'clientConcerns:id,sales_daily_report_id,client_name,issue_concern,action_taken',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Sales daily report created successfully.',
            'data' => $this->transformReport($report, true),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $report = SalesDailyReport::with([
            'preparedBy:id,name',
            'leadFollowups:id,sales_daily_report_id,lead_name,details_discussion,outcome,next_step,followup_date',
            'salesActivities:id,sales_daily_report_id,invoice_no,invoice_sum,activity,status',
            'clientConcerns:id,sales_daily_report_id,client_name,issue_concern,action_taken',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformReport($report, true),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = SalesDailyReport::with([
            'preparedBy:id,name',
            'leadFollowups:id,sales_daily_report_id,lead_name,details_discussion,outcome,next_step,followup_date',
            'salesActivities:id,sales_daily_report_id,invoice_no,invoice_sum,activity,status',
            'clientConcerns:id,sales_daily_report_id,client_name,issue_concern,action_taken',
        ])->findOrFail($id);

        if (strtoupper((string) $report->status) !== 'DRAFT') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft reports can be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'report_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'next_steps' => 'nullable|string',
            'challenges' => 'nullable|string',
        ]);

        $report->update([
            'report_date' => $validated['report_date'] ?? $report->report_date,
            'daily_summary' => array_key_exists('notes', $validated)
                ? ($validated['notes'] ?? '')
                : $report->daily_summary,
            'notes_recommendations' => array_key_exists('next_steps', $validated)
                ? $validated['next_steps']
                : $report->notes_recommendations,
        ]);

        $report->refresh()->load([
            'preparedBy:id,name',
            'leadFollowups:id,sales_daily_report_id,lead_name,details_discussion,outcome,next_step,followup_date',
            'salesActivities:id,sales_daily_report_id,invoice_no,invoice_sum,activity,status',
            'clientConcerns:id,sales_daily_report_id,client_name,issue_concern,action_taken',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully.',
            'data' => $this->transformReport($report, true),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $report = SalesDailyReport::findOrFail($id);

        if (strtoupper((string) $report->status) !== 'DRAFT') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft reports can be deleted.',
            ], 403);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully.',
        ]);
    }

    public function submit(int $id): JsonResponse
    {
        $report = SalesDailyReport::with('preparedBy:id,name')->findOrFail($id);

        if (strtoupper((string) $report->status) !== 'DRAFT') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft reports can be submitted.',
            ], 403);
        }

        $report->update(['status' => 'PENDING']);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted for approval.',
            'data' => $this->transformReport($report->fresh(['preparedBy:id,name']), true),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $report = SalesDailyReport::with('preparedBy:id,name')->findOrFail($id);

        if (strtoupper((string) $report->status) !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending reports can be approved.',
            ], 403);
        }

        $report->update([
            'status' => 'APPROVED',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report approved successfully.',
            'data' => $this->transformReport($report->fresh(['preparedBy:id,name']), true),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $report = SalesDailyReport::with('preparedBy:id,name')->findOrFail($id);

        if (strtoupper((string) $report->status) !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending reports can be rejected.',
            ], 403);
        }

        $report->update([
            'status' => 'REJECTED',
            'notes_recommendations' => trim(
                implode("\n\n", array_filter([
                    $report->notes_recommendations,
                    'Rejection reason: ' . $request->reason,
                ]))
            ),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report rejected.',
            'data' => $this->transformReport($report->fresh(['preparedBy:id,name']), true),
        ]);
    }

    private function transformReport(SalesDailyReport $report, bool $includeDetails): array
    {
        $status = strtolower((string) $report->status);

        $data = [
            'id' => $report->id,
            'report_number' => 'SDR-' . $report->id,
            'document_number' => 'SDR-' . $report->id,
            'report_date' => optional($report->report_date)->format('Y-m-d'),
            'prepared_by' => $report->prepared_by,
            'prepared_by_name' => $report->preparedBy?->name ?? '-',
            'prepared_by_user' => $report->preparedBy ? [
                'id' => $report->preparedBy->id,
                'name' => $report->preparedBy->name,
            ] : null,
            'department_id' => $report->department_id,
            'daily_summary' => $report->daily_summary,
            'notes' => $report->daily_summary,
            'notes_recommendations' => $report->notes_recommendations,
            'next_steps' => $report->notes_recommendations,
            'challenges' => null,
            'status' => $status,
            'can_edit' => in_array($status, ['draft', 'rejected'], true),
            'can_submit' => $status === 'draft',
            'can_approve' => $status === 'pending',
            'can_reject' => $status === 'pending',
            'can_return' => false,
            'next_approval_action' => $status === 'pending' ? 'approve' : null,
            'total_sales' => (float) $report->salesActivities->sum('invoice_sum'),
            'total_collections' => (float) $report->salesActivities
                ->where('status', 'paid')
                ->sum('invoice_sum'),
            'new_customers' => 0,
            'visits_made' => $report->leadFollowups->count(),
            'created_at' => $report->created_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
        ];

        if ($includeDetails) {
            $data['lead_followups'] = $report->leadFollowups->map(function ($item) {
                return [
                    'id' => $item->id,
                    'lead_name' => $item->lead_name,
                    'details_discussion' => $item->details_discussion,
                    'outcome' => $item->outcome,
                    'next_step' => $item->next_step,
                    'followup_date' => optional($item->followup_date)->format('Y-m-d'),
                ];
            })->values();

            $data['sales_activities'] = $report->salesActivities->map(function ($item) {
                return [
                    'id' => $item->id,
                    'invoice_no' => $item->invoice_no,
                    'invoice_sum' => (float) $item->invoice_sum,
                    'activity' => $item->activity,
                    'status' => $item->status,
                ];
            })->values();

            $data['client_concerns'] = $report->clientConcerns->map(function ($item) {
                return [
                    'id' => $item->id,
                    'client_name' => $item->client_name,
                    'issue_concern' => $item->issue_concern,
                    'action_taken' => $item->action_taken,
                ];
            })->values();
        }

        return $data;
    }
}
