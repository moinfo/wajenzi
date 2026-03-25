<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectMaterialApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectMaterial::with('inventory');

            if ($request->search) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->unit) {
                $query->where('unit', $request->unit);
            }

            $materials = $query->get();

            // Add total inventory to each material
            $materials->each(function ($material) {
                $material->total_inventory = $material->inventory->sum('quantity');
            });

            return response()->json([
                'success' => true,
                'data' => $materials->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                    'description' => $m->description,
                    'unit' => $m->unit,
                    'current_price' => $m->current_price,
                    'total_inventory' => $m->total_inventory,
                    'created_at' => $m->created_at?->toISOString(),
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectMaterial index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch materials: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $material = ProjectMaterial::with(['inventory'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'description' => $material->description,
                    'unit' => $material->unit,
                    'current_price' => $material->current_price,
                    'total_inventory' => $material->inventory->sum('quantity'),
                    'inventory' => $material->inventory->map(fn($i) => [
                        'id' => $i->id,
                        'quantity' => $i->quantity,
                        'project_id' => $i->project_id,
                        'created_at' => $i->created_at?->toISOString(),
                    ]),
                    'created_at' => $material->created_at?->toISOString(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectMaterial show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'unit' => 'required|string|max:50',
                'current_price' => 'nullable|numeric|min:0',
            ]);

            $material = ProjectMaterial::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Material created successfully.',
                'data' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'description' => $material->description,
                    'unit' => $material->unit,
                    'current_price' => $material->current_price,
                    'total_inventory' => 0,
                    'created_at' => $material->created_at?->toISOString(),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectMaterial store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $material = ProjectMaterial::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'unit' => 'sometimes|string|max:50',
                'current_price' => 'sometimes|numeric|min:0',
            ]);

            $material->update($validated);
            $material->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Material updated successfully.',
                'data' => [
                    'id' => $material->id,
                    'name' => $material->name,
                    'description' => $material->description,
                    'unit' => $material->unit,
                    'current_price' => $material->current_price,
                    'total_inventory' => $material->inventory->sum('quantity'),
                    'created_at' => $material->created_at?->toISOString(),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('ProjectMaterial update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $material = ProjectMaterial::findOrFail($id);

            // Check if material has inventory
            if ($material->inventory()->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete material with existing inventory.',
                ], 400);
            }

            $material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectMaterial destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material: ' . $e->getMessage(),
            ], 500);
        }
    }
}
