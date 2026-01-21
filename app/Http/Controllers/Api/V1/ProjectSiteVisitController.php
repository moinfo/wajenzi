<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectSiteVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProjectSiteVisitController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProjectSiteVisit::with(['project', 'inspector'])
            ->orderBy('visit_date', 'desc');

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->my_visits) {
            $query->where('inspector_id', $request->user()->id);
        }

        $visits = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $visits->items(),
            'meta' => [
                'current_page' => $visits->currentPage(),
                'last_page' => $visits->lastPage(),
                'per_page' => $visits->perPage(),
                'total' => $visits->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'visit_date' => 'required|date',
            'purpose' => 'required|string|max:500',
            'observations' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'issues_found' => 'nullable|string',
            'follow_up_required' => 'boolean',
            'next_visit_date' => 'nullable|date|after:visit_date',
        ]);

        $validated['inspector_id'] = $request->user()->id;
        $validated['status'] = 'draft';

        $visit = ProjectSiteVisit::create($validated);
        $visit->load(['project', 'inspector']);

        return response()->json([
            'success' => true,
            'message' => 'Site visit created successfully.',
            'data' => $visit,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $visit = ProjectSiteVisit::with(['project', 'inspector'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $visit,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if (!in_array($visit->status, ['draft', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'This visit record cannot be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'visit_date' => 'sometimes|date',
            'purpose' => 'sometimes|string|max:500',
            'observations' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'issues_found' => 'nullable|string',
            'follow_up_required' => 'boolean',
            'next_visit_date' => 'nullable|date',
        ]);

        $visit->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Site visit updated successfully.',
            'data' => $visit->fresh(['project', 'inspector']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $visit = ProjectSiteVisit::findOrFail($id);

        if ($visit->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft visits can be deleted.',
            ], 403);
        }

        $visit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Site visit deleted successfully.',
        ]);
    }

    public function submit(int $id): JsonResponse
    {
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
            'data' => $visit->fresh(),
        ]);
    }
}
