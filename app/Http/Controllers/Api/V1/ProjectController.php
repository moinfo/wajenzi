<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use App\Models\ProjectMaterialInventory;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function stats(): JsonResponse
    {
        $projects = Project::all();

        $stats = [
            'total' => $projects->count(),
            'active' => $projects->whereIn('status', ['pending', 'in_progress', 'APPROVED', 'Active'])->count(),
            'completed' => $projects->where('status', 'COMPLETED')->count(),
            'delayed' => $projects->filter(fn($p) => method_exists($p, 'isDelayed') && $p->isDelayed())->count(),
            'total_value' => $projects->sum('contract_value'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager']);

        // Apply filters
        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->project_type_id) {
            $query->where('project_type_id', $request->project_type_id);
        }

        if ($request->service_type_id) {
            $query->where('service_type_id', $request->service_type_id);
        }

        if ($request->salesperson_id) {
            $query->where('salesperson_id', $request->salesperson_id);
        }

        if ($request->project_manager_id) {
            $query->where('project_manager_id', $request->project_manager_id);
        }

        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => [
                'data' => ProjectResource::collection($projects),
                'meta' => [
                    'current_page' => $projects->currentPage(),
                    'last_page' => $projects->lastPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProjectResource($project),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'client_id' => 'nullable|exists:project_clients,id',
                'project_type_id' => 'nullable|exists:project_types,id',
                'service_type_id' => 'nullable|exists:service_types,id',
                'salesperson_id' => 'nullable|exists:users,id',
                'project_manager_id' => 'nullable|exists:users,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'contract_value' => 'nullable|numeric|min:0',
                'status' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:500',
            ]);

            // Map 'name' to 'project_name' for database
            if (isset($validated['name'])) {
                $validated['project_name'] = $validated['name'];
                unset($validated['name']);
            }
            
            $validated['create_by_id'] = $request->user()->id;
            $validated['status'] = $validated['status'] ?? 'PENDING';

            $project = Project::create($validated);
            $project->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager']);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully.',
                'data' => new ProjectResource($project),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'client_id' => 'nullable|exists:project_clients,id',
                'project_type_id' => 'nullable|exists:project_types,id',
                'service_type_id' => 'nullable|exists:service_types,id',
                'salesperson_id' => 'nullable|exists:users,id',
                'project_manager_id' => 'nullable|exists:users,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'contract_value' => 'nullable|numeric|min:0',
                'status' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'location' => 'nullable|string|max:500',
            ]);

            // Map 'name' to 'project_name' for database
            if (isset($validated['name'])) {
                $validated['project_name'] = $validated['name'];
                unset($validated['name']);
            }

            $project->update($validated);
            $project->load(['client', 'projectType', 'serviceType', 'salesperson', 'projectManager']);

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully.',
                'data' => new ProjectResource($project),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function boq(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        // Get the latest BOQ for this project
        $boq = ProjectBoq::where('project_id', $id)
            ->orderBy('version', 'desc')
            ->first();

        if (!$boq) {
            return response()->json([
                'success' => true,
                'data' => [
                    'project_id' => $id,
                    'project_name' => $project->project_name,
                    'boq_id' => null,
                    'version' => null,
                    'items' => [],
                    'total_amount' => 0,
                ],
            ]);
        }

        // Get items for this BOQ
        $boqItems = ProjectBoqItem::where('boq_id', $boq->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'item_code' => $item->item_code,
                'description' => $item->description,
                'specification' => $item->specification,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'item_type' => $item->item_type,
                'procurement_status' => $item->procurement_status,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'project_id' => $id,
                'project_name' => $project->project_name,
                'boq_id' => $boq->id,
                'version' => $boq->version,
                'status' => $boq->status,
                'items' => $boqItems,
                'total_amount' => $boq->total_amount,
            ],
        ]);
    }

    public function materials(int $id): JsonResponse
    {
        $materials = ProjectMaterialInventory::where('project_id', $id)
            ->orderBy('material_name')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'material_name' => $m->material_name,
                'unit' => $m->unit,
                'quantity_available' => $m->quantity_available,
                'quantity_used' => $m->quantity_used,
                'unit_price' => $m->unit_price,
                'total_value' => $m->total_value,
                'supplier' => $m->supplier,
                'last_restocked' => $m->last_restocked_at?->toDateString(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $materials,
        ]);
    }

    public function sites(int $id): JsonResponse
    {
        $sites = Site::where('project_id', $id)
            ->orderBy('name')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'location' => $s->location,
                'address' => $s->address,
                'latitude' => $s->latitude,
                'longitude' => $s->longitude,
                'status' => $s->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => $sites,
        ]);
    }

    public function team(int $id): JsonResponse
    {
        try {
            $project = Project::findOrFail($id);

            $team = [];
            
            // Try to get team from project_manager and salesperson if team_members table doesn't exist
            if ($project->project_manager_id) {
                $manager = User::with(['department'])->find($project->project_manager_id);
                if ($manager) {
                    $team[] = [
                        'id' => $manager->id,
                        'name' => $manager->name,
                        'email' => $manager->email,
                        'designation' => $manager->designation,
                        'department' => $manager->department?->name,
                        'role' => 'Project Manager',
                    ];
                }
            }

            if ($project->salesperson_id) {
                $salesperson = User::with(['department'])->find($project->salesperson_id);
                if ($salesperson) {
                    $team[] = [
                        'id' => $salesperson->id,
                        'name' => $salesperson->name,
                        'email' => $salesperson->email,
                        'designation' => $salesperson->designation,
                        'department' => $salesperson->department?->name,
                        'role' => 'Salesperson',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $team,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team: ' . $e->getMessage(),
            ], 500);
        }
    }
}
