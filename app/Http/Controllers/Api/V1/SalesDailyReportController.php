<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\SalesDailyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDailyReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SalesDailyReport::with(['preparedBy'])
            ->orderBy('report_date', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date) {
            $query->where('report_date', '>=', Carbon::parse($request->start_date));
        }

        if ($request->end_date) {
            $query->where('report_date', '<=', Carbon::parse($request->end_date));
        }

        if ($request->my_reports) {
            $query->where('prepared_by', $request->user()->id);
        }

        $reports = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'report_date' => 'required|date',
            'total_sales' => 'nullable|numeric|min:0',
            'total_collections' => 'nullable|numeric|min:0',
            'new_customers' => 'nullable|integer|min:0',
            'visits_made' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'challenges' => 'nullable|string',
            'next_steps' => 'nullable|string',
        ]);

        $validated['prepared_by'] = $request->user()->id;
        $validated['status'] = 'draft';

        $report = SalesDailyReport::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Sales daily report created successfully.',
            'data' => $report,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $report = SalesDailyReport::with(['preparedBy'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = SalesDailyReport::findOrFail($id);

        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft reports can be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'report_date' => 'sometimes|date',
            'total_sales' => 'nullable|numeric|min:0',
            'total_collections' => 'nullable|numeric|min:0',
            'new_customers' => 'nullable|integer|min:0',
            'visits_made' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
            'challenges' => 'nullable|string',
            'next_steps' => 'nullable|string',
        ]);

        $report->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully.',
            'data' => $report->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $report = SalesDailyReport::findOrFail($id);

        if ($report->status !== 'draft') {
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
        $report = SalesDailyReport::findOrFail($id);

        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft reports can be submitted.',
            ], 403);
        }

        $report->update(['status' => 'pending']);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted for approval.',
            'data' => $report->fresh(),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $report = SalesDailyReport::findOrFail($id);

        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending reports can be approved.',
            ], 403);
        }

        $report->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report approved successfully.',
            'data' => $report->fresh(),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $report = SalesDailyReport::findOrFail($id);

        if ($report->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending reports can be rejected.',
            ], 403);
        }

        $report->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report rejected.',
            'data' => $report->fresh(),
        ]);
    }
}
