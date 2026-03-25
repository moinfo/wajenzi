<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectMaterialInventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaterialInventoryApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectMaterialInventory::with(['project', 'material']);

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->material_id) {
                $query->where('material_id', $request->material_id);
            }

            $inventories = $query->get();

            return response()->json([
                'success' => true,
                'data' => $inventories->map(fn($i) => [
                    'id' => $i->id,
                    'project_id' => $i->project_id,
                    'project_name' => $i->project?->project_name,
                    'material_id' => $i->material_id,
                    'material_name' => $i->material?->name,
                    'quantity' => $i->quantity,
                    'unit' => $i->material?->unit,
                    'last_updated' => $i->last_updated_at ? $i->last_updated_at->format('Y-m-d H:i:s') : ($i->updated_at ? $i->updated_at->format('Y-m-d H:i:s') : null),
                    'created_at' => $i->created_at?->toISOString(),
                    'updated_at' => $i->updated_at?->toISOString(),
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $inventory = ProjectMaterialInventory::with(['project', 'material'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $inventory->id,
                    'project_id' => $inventory->project_id,
                    'project_name' => $inventory->project?->project_name,
                    'material_id' => $inventory->material_id,
                    'material_name' => $inventory->material?->name,
                    'quantity' => $inventory->quantity,
                    'unit' => $inventory->material?->unit,
                    'created_at' => $inventory->created_at?->toISOString(),
                    'updated_at' => $inventory->updated_at?->toISOString(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch inventory: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'material_id' => 'required|exists:project_materials,id',
                'quantity' => 'required|numeric|min:0',
            ]);

            $inventory = ProjectMaterialInventory::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Inventory created successfully.',
                'data' => [
                    'id' => $inventory->id,
                    'project_id' => $inventory->project_id,
                    'material_id' => $inventory->material_id,
                    'quantity' => $inventory->quantity,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create inventory: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $inventory = ProjectMaterialInventory::findOrFail($id);

            $validated = $request->validate([
                'project_id' => 'sometimes|exists:projects,id',
                'material_id' => 'sometimes|exists:project_materials,id',
                'quantity' => 'sometimes|numeric|min:0',
            ]);

            $inventory->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Inventory updated successfully.',
                'data' => [
                    'id' => $inventory->id,
                    'project_id' => $inventory->project_id,
                    'material_id' => $inventory->material_id,
                    'quantity' => $inventory->quantity,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update inventory: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $inventory = ProjectMaterialInventory::findOrFail($id);
            $inventory->delete();

            return response()->json([
                'success' => true,
                'message' => 'Inventory deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete inventory: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function projects(): JsonResponse
    {
        try {
            $projects = Project::orderBy('project_name')->get()->map(fn($p) => [
                'id' => $p->id,
                'project_name' => $p->project_name,
            ]);

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory projects error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function materials(): JsonResponse
    {
        try {
            $materials = ProjectMaterial::orderBy('name')->get()->map(fn($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'unit' => $m->unit,
            ]);

            return response()->json([
                'success' => true,
                'data' => $materials,
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory materials error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch materials: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
