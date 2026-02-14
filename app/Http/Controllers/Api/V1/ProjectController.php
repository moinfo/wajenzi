<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectMaterialInventory;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Project::with(['client', 'projectType']);

        // If user is not admin, show only projects they manage or are assigned to
        if (!$user->hasRole('admin') && !$user->hasRole('super-admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('project_manager_id', $user->id)
                  ->orWhere('salesperson_id', $user->id)
                  ->orWhere('create_by_id', $user->id);
            });
        }

        $projects = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => ProjectResource::collection($projects),
            'meta' => [
                'current_page' => $projects->currentPage(),
                'last_page' => $projects->lastPage(),
                'per_page' => $projects->perPage(),
                'total' => $projects->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $project = Project::with(['client', 'projectType', 'sites'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ProjectResource($project),
        ]);
    }

    public function boq(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $boqItems = ProjectBoq::where('project_id', $id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total_amount' => $item->total_amount,
                'category' => $item->category,
                'status' => $item->status,
            ]);

        $totalAmount = $boqItems->sum('total_amount');

        return response()->json([
            'success' => true,
            'data' => [
                'project_id' => $id,
                'project_name' => $project->project_name,
                'items' => $boqItems,
                'total_amount' => $totalAmount,
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
        $project = Project::findOrFail($id);

        $team = User::whereHas('projects', fn($q) => $q->where('projects.id', $id))
            ->with(['department'])
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'designation' => $u->designation,
                'department' => $u->department?->name,
                'role' => $u->projects->find($id)?->pivot->role,
            ]);

        return response()->json([
            'success' => true,
            'data' => $team,
        ]);
    }
}
