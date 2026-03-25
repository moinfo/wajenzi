<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectDailyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectDailyReportApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProjectDailyReport::with(['project', 'supervisor']);

        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('report_date', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('report_date', '<=', $request->end_date);
        }

        if ($request->has('project_id') && $request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $reports = $query->orderBy('report_date', 'desc')->get();

        $data = $reports->map(function ($report) {
            return [
                'id' => $report->id,
                'project_id' => $report->project_id,
                'project_name' => $report->project?->project_name,
                'supervisor_id' => $report->supervisor_id,
                'supervisor_name' => $report->supervisor?->name,
                'report_date' => $report->report_date?->format('Y-m-d'),
                'weather_conditions' => $report->weather_conditions,
                'work_completed' => $report->work_completed,
                'materials_used' => $report->materials_used,
                'labor_hours' => $report->labor_hours,
                'issues_faced' => $report->issues_faced,
                'created_at' => $report->created_at?->toIso8601String(),
                'updated_at' => $report->updated_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'total' => $data->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $report = ProjectDailyReport::with(['project', 'supervisor'])->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $report->id,
                'project_id' => $report->project_id,
                'project_name' => $report->project?->project_name,
                'supervisor_id' => $report->supervisor_id,
                'supervisor_name' => $report->supervisor?->name,
                'report_date' => $report->report_date?->format('Y-m-d'),
                'weather_conditions' => $report->weather_conditions,
                'work_completed' => $report->work_completed,
                'materials_used' => $report->materials_used,
                'labor_hours' => $report->labor_hours,
                'issues_faced' => $report->issues_faced,
                'created_at' => $report->created_at?->toIso8601String(),
                'updated_at' => $report->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'supervisor_id' => 'nullable|integer|exists:users,id',
            'report_date' => 'required|date',
            'weather_conditions' => 'nullable|string|max:255',
            'work_completed' => 'nullable|string',
            'materials_used' => 'nullable|string',
            'labor_hours' => 'nullable|integer|min:0',
            'issues_faced' => 'nullable|string',
        ]);

        $report = ProjectDailyReport::create($validated);

        $report->load(['project', 'supervisor']);

        return response()->json([
            'success' => true,
            'message' => 'Report created successfully',
            'data' => [
                'id' => $report->id,
                'project_id' => $report->project_id,
                'project_name' => $report->project?->project_name,
                'supervisor_id' => $report->supervisor_id,
                'supervisor_name' => $report->supervisor?->name,
                'report_date' => $report->report_date?->format('Y-m-d'),
                'weather_conditions' => $report->weather_conditions,
                'work_completed' => $report->work_completed,
                'materials_used' => $report->materials_used,
                'labor_hours' => $report->labor_hours,
                'issues_faced' => $report->issues_faced,
                'created_at' => $report->created_at?->toIso8601String(),
                'updated_at' => $report->updated_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $report = ProjectDailyReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        $validated = $request->validate([
            'project_id' => 'sometimes|integer|exists:projects,id',
            'supervisor_id' => 'nullable|integer|exists:users,id',
            'report_date' => 'sometimes|date',
            'weather_conditions' => 'nullable|string|max:255',
            'work_completed' => 'nullable|string',
            'materials_used' => 'nullable|string',
            'labor_hours' => 'nullable|integer|min:0',
            'issues_faced' => 'nullable|string',
        ]);

        $report->update($validated);
        $report->load(['project', 'supervisor']);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully',
            'data' => [
                'id' => $report->id,
                'project_id' => $report->project_id,
                'project_name' => $report->project?->project_name,
                'supervisor_id' => $report->supervisor_id,
                'supervisor_name' => $report->supervisor?->name,
                'report_date' => $report->report_date?->format('Y-m-d'),
                'weather_conditions' => $report->weather_conditions,
                'work_completed' => $report->work_completed,
                'materials_used' => $report->materials_used,
                'labor_hours' => $report->labor_hours,
                'issues_faced' => $report->issues_faced,
                'created_at' => $report->created_at?->toIso8601String(),
                'updated_at' => $report->updated_at?->toIso8601String(),
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $report = ProjectDailyReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found',
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully',
        ]);
    }

    public function projects(): JsonResponse
    {
        $projects = Project::orderBy('project_name')->get(['id', 'project_name']);

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }
}
