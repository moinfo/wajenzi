<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteSupervisorAssignment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteSupervisorAssignmentApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SiteSupervisorAssignment::with(['site', 'supervisor', 'assignedBy'])
            ->where('is_active', true);

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('supervisor_id')) {
            $query->where('user_id', $request->supervisor_id);
        }

        $assignments = $query->orderBy('assigned_from', 'desc')->get();

        // Get sites without active supervisors using subquery
        $assignedSiteIds = SiteSupervisorAssignment::where('is_active', true)->pluck('site_id');
        $unassignedSites = Site::active()
            ->whereNotIn('id', $assignedSiteIds)
            ->get(['id', 'name', 'location']);

        $sites = Site::active()->get(['id', 'name', 'location']);
        $supervisors = User::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'data' => [
                'assignments' => $assignments->map(fn($a) => $this->formatAssignment($a)),
                'unassigned_sites' => $unassignedSites,
                'sites' => $sites,
                'supervisors' => $supervisors,
                'stats' => [
                    'total' => $assignments->count(),
                    'unassigned_count' => $unassignedSites->count(),
                ],
            ],
        ]);
    }

    public function history(Request $request, Site $site): JsonResponse
    {
        $assignments = $site->supervisorAssignments()
            ->with(['supervisor', 'assignedBy'])
            ->orderBy('assigned_from', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'site' => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'location' => $site->location,
                ],
                'assignments' => $assignments->map(fn($a) => $this->formatAssignment($a, true)),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'user_id' => 'required|exists:users,id',
            'assigned_from' => 'required|date',
            'assigned_to' => 'nullable|date|after:assigned_from',
            'notes' => 'nullable|string',
        ]);

        $existingAssignment = SiteSupervisorAssignment::where('site_id', $validated['site_id'])
            ->where('is_active', true)
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'success' => false,
                'message' => 'This site already has an active supervisor. Please end the current assignment first.',
            ], 422);
        }

        $assignment = SiteSupervisorAssignment::create([
            'site_id' => $validated['site_id'],
            'user_id' => $validated['user_id'],
            'assigned_from' => $validated['assigned_from'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'is_active' => true,
            'assigned_by' => Auth::id(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $assignment->load(['site', 'supervisor', 'assignedBy']);

        return response()->json([
            'success' => true,
            'data' => $this->formatAssignment($assignment),
            'message' => 'Supervisor assigned successfully.',
        ]);
    }

    public function show($id): JsonResponse
    {
        $assignment = SiteSupervisorAssignment::with(['site', 'supervisor', 'assignedBy'])->find($id);
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatAssignment($assignment, true),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $assignment = SiteSupervisorAssignment::find($id);
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
            ], 404);
        }

        if (!$assignment->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update inactive assignments.',
            ], 422);
        }

        try {
            $validated = $request->validate([
                'assigned_to' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            $data = ['notes' => $validated['notes'] ?? $assignment->notes];

            if (!empty($validated['assigned_to'])) {
                $data['assigned_to'] = $validated['assigned_to'];
                $data['is_active'] = false;
            }

            $assignment->update($data);
            $assignment->load(['site', 'supervisor', 'assignedBy']);

            return response()->json([
                'success' => true,
                'data' => $this->formatAssignment($assignment),
                'message' => 'Assignment updated successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->errors()[$e->errors()[array_key_first($e->errors())][0]] ?? ['Unknown error']),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update assignment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $assignment = SiteSupervisorAssignment::find($id);
        
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found.',
            ], 404);
        }

        try {
            if ($assignment->is_active) {
                $assignment->deactivate();
                return response()->json([
                    'success' => true,
                    'message' => 'Assignment ended successfully.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Cannot end inactive assignments.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to end assignment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function availableSites(): JsonResponse
    {
        $assignedSiteIds = SiteSupervisorAssignment::where('is_active', true)->pluck('site_id');
        $sites = Site::active()
            ->whereNotIn('id', $assignedSiteIds)
            ->get(['id', 'name', 'location']);

        return response()->json([
            'success' => true,
            'data' => $sites,
        ]);
    }

    public function getSupervisors(): JsonResponse
    {
        $supervisors = User::where('status', 'ACTIVE')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'data' => $supervisors,
        ]);
    }

    private function formatAssignment(SiteSupervisorAssignment $assignment, bool $detailed = false): array
    {
        $data = [
            'id' => $assignment->id,
            'site_id' => $assignment->site_id,
            'site_name' => $assignment->site?->name,
            'site_location' => $assignment->site?->location,
            'user_id' => $assignment->user_id,
            'supervisor_name' => $assignment->supervisor?->name,
            'supervisor_email' => $assignment->supervisor?->email,
            'assigned_from' => $assignment->assigned_from?->format('Y-m-d'),
            'assigned_to' => $assignment->assigned_to?->format('Y-m-d'),
            'is_active' => $assignment->is_active,
            'assigned_by_name' => $assignment->assignedBy?->name,
            'assigned_at' => $assignment->assigned_from?->toIso8601String(),
            'duration_days' => $assignment->getDurationInDays(),
            'notes' => $assignment->notes,
        ];

        if ($detailed) {
            $data['assigned_by'] = $assignment->assignedBy ? [
                'id' => $assignment->assignedBy->id,
                'name' => $assignment->assignedBy->name,
            ] : null;
        }

        return $data;
    }
}
