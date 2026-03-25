<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSiteVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectSiteVisitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectSiteVisit::with(['project', 'project.client', 'inspector'])
                ->orderBy('visit_date', 'desc');

            // Filter by date range
            if ($request->start_date) {
                $query->whereDate('visit_date', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->whereDate('visit_date', '<=', $request->end_date);
            }

            // Filter by project
            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            // My visits only
            if ($request->my_visits) {
                $query->where('inspector_id', $request->user()->id);
            }

            $visits = $query->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $visits->items(),
                    'meta' => [
                        'current_page' => $visits->currentPage(),
                        'last_page' => $visits->lastPage(),
                        'per_page' => $visits->perPage(),
                        'total' => $visits->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisit index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch site visits: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'visit_date' => 'required|date',
                'description' => 'nullable|string',
                'findings' => 'nullable|string',
                'recommendations' => 'nullable|string',
                'location' => 'nullable|string|max:255',
            ]);

            $validated['inspector_id'] = $request->user()->id;
            $validated['create_by_id'] = $request->user()->id;
            $validated['status'] = 'CREATED';

            $visit = ProjectSiteVisit::create($validated);
            $visit->load(['project', 'project.client', 'inspector']);

            return response()->json([
                'success' => true,
                'message' => 'Site visit created successfully.',
                'data' => $visit,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('SiteVisit store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create site visit: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $visit = ProjectSiteVisit::with(['project', 'project.client', 'inspector'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $visit,
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisit show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch site visit: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $visit = ProjectSiteVisit::findOrFail($id);

            if (!in_array($visit->status, ['CREATED', 'draft', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This visit record cannot be edited.',
                ], 403);
            }

            $validated = $request->validate([
                'project_id' => 'sometimes|exists:projects,id',
                'visit_date' => 'sometimes|date',
                'description' => 'nullable|string',
                'findings' => 'nullable|string',
                'recommendations' => 'nullable|string',
                'location' => 'nullable|string|max:255',
            ]);

            $visit->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Site visit updated successfully.',
                'data' => $visit->fresh(['project', 'project.client', 'inspector']),
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisit update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update site visit: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $visit = ProjectSiteVisit::findOrFail($id);

            if (!in_array($visit->status, ['CREATED', 'draft'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only created visits can be deleted.',
                ], 403);
            }

            $visit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Site visit deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisit destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete site visit: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $visit = ProjectSiteVisit::findOrFail($id);

            if (!in_array($visit->status, ['draft', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This visit cannot be submitted.',
                ], 403);
            }

            $visit->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Site visit submitted for approval.',
                'data' => $visit->fresh(['project', 'project.client', 'inspector']),
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisit submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit site visit: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function projects(): JsonResponse
    {
        try {
            $projects = Project::with('client')
                ->orderBy('project_name')
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'project_name' => $p->project_name,
                    'client_name' => $p->client ? $p->client->first_name . ' ' . $p->client->last_name : null,
                ]);

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);
        } catch (\Throwable $e) {
            Log::error('SiteVisit projects error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
