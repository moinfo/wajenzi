<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Site::with(['createdBy', 'currentSupervisor']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sites = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sites' => $sites->map(fn($site) => $this->formatSite($site)),
                'stats' => [
                    'total' => $sites->count(),
                    'active' => $sites->where('status', 'ACTIVE')->count(),
                    'inactive' => $sites->where('status', 'INACTIVE')->count(),
                    'completed' => $sites->where('status', 'COMPLETED')->count(),
                ],
            ],
        ]);
    }

    public function show(Site $site): JsonResponse
    {
        $site->load([
            'createdBy',
            'supervisorAssignments.supervisor',
            'supervisorAssignments.assignedBy',
            'dailyReports.preparedBy',
            'currentSupervisor'
        ]);

        $recentReports = $site->dailyReports()
            ->with(['supervisor', 'preparedBy'])
            ->orderBy('report_date', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'site' => $this->formatSite($site, true),
                'recent_reports' => $recentReports->map(fn($r) => [
                    'id' => $r->id,
                    'report_date' => $r->report_date,
                    'progress_percentage' => $r->progress_percentage,
                    'status' => $r->status,
                    'prepared_by' => $r->preparedBy?->name,
                    'supervisor' => $r->supervisor?->name,
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sites,name',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ACTIVE,INACTIVE,COMPLETED',
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $validated['created_by'] = Auth::id();
        $site = Site::create($validated);
        $site->load(['createdBy', 'currentSupervisor']);

        return response()->json([
            'success' => true,
            'data' => $this->formatSite($site),
            'message' => 'Site created successfully.',
        ]);
    }

    public function update(Request $request, Site $site): JsonResponse
    {
        $routeId = $request->route('id'); // Get 'id' parameter from route
        \Illuminate\Support\Facades\Log::info("UPDATE: route id param=$routeId, site object id=" . ($site?->id ?? 'null'));
        
        if (!$site || $site->id === null) {
            // Try manual lookup
            if ($routeId) {
                $site = Site::find($routeId);
                \Illuminate\Support\Facades\Log::info("UPDATE: Manual lookup for id=$routeId, found: " . ($site ? $site->id : 'null'));
            }
            if (!$site || $site->id === null) {
                \Illuminate\Support\Facades\Log->error("UPDATE: Site not found for route id=$routeId");
                return response()->json(['success' => false, 'message' => 'Site not found'], 404);
            }
        }
        
        $name = $request->input('name');
        $existingSite = Site::where('name', $name)
            ->where('id', '!=', $site->id)
            ->first();
        
        if ($existingSite) {
            \Illuminate\Support\Facades\Log::error("UPDATE: Duplicate name found. Request name=$name, existing id=" . $existingSite->id);
            return response()->json([
                'success' => false,
                'message' => 'Site name already exists',
                'errors' => ['name' => ['The name has already been taken.']]
            ], 422);
        }
        
        $site->update($request->all());
        $site->load(['createdBy', 'currentSupervisor']);

        return response()->json([
            'success' => true,
            'data' => $this->formatSite($site),
            'message' => 'Site updated successfully.',
        ]);
    }

    public function destroy(Site $site): JsonResponse
    {
        if (!$site->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete site with existing reports.',
            ], 422);
        }

        $site->delete();

        return response()->json([
            'success' => true,
            'message' => 'Site deleted successfully.',
        ]);
    }

    public function supervisors(): JsonResponse
    {
        $supervisors = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json([
            'success' => true,
            'data' => $supervisors,
        ]);
    }

    private function formatSite(Site $site, bool $detailed = false): array
    {
        $data = [
            'id' => $site->id,
            'name' => $site->name,
            'location' => $site->location,
            'description' => $site->description,
            'status' => $site->status,
            'start_date' => $site->start_date?->format('Y-m-d'),
            'expected_end_date' => $site->expected_end_date?->format('Y-m-d'),
            'actual_end_date' => $site->actual_end_date?->format('Y-m-d'),
            'progress_percentage' => $site->getProgressPercentage(),
            'created_by' => $site->createdBy?->name,
            'created_at' => $site->created_at?->toIso8601String(),
            'current_supervisor' => $site->currentSupervisor ? [
                'id' => $site->currentSupervisor->id,
                'name' => $site->currentSupervisor->name,
            ] : null,
        ];

        if ($detailed) {
            $data['supervisor_assignments'] = $site->supervisorAssignments->map(fn($a) => [
                'id' => $a->id,
                'supervisor' => $a->supervisor?->name,
                'is_active' => $a->is_active,
                'assigned_by' => $a->assignedBy?->name,
                'assigned_at' => $a->assigned_at?->toIso8601String(),
            ]);
            $data['daily_reports_count'] = $site->dailyReports->count();
        }

        return $data;
    }
}
