<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectMaterialMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MaterialInventoryApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectMaterialInventory::with(['project', 'material', 'boqItem']);

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->material_id) {
                $query->where('material_id', $request->material_id);
            }

            $inventories = $query->get();
            $stats = [
                'total' => $inventories->count(),
                'in_stock' => $inventories->filter(fn($inventory) => $inventory->stock_status === 'in_stock')->count(),
                'low_stock' => $inventories->filter(fn($inventory) => $inventory->stock_status === 'low_stock')->count(),
                'out_of_stock' => $inventories->filter(fn($inventory) => $inventory->stock_status === 'out_of_stock')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $inventories->map(fn($i) => $this->formatInventory($i))->values(),
                    'stats' => $stats,
                ],
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
            $inventory = ProjectMaterialInventory::with(['project', 'material', 'boqItem'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatInventory($inventory),
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
            $projects = Project::whereHas('boqs')
                ->orderBy('project_name')
                ->get()
                ->map(fn($p) => [
                'id' => $p->id,
                'project_name' => $p->project_name ?? $p->name,
                'name' => $p->project_name ?? $p->name,
                'code' => $p->code,
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

    public function movements(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'movement_type' => 'nullable|string|in:received,issued,adjustment,returned,transfer',
                'boq_item_id' => 'nullable|integer',
            ]);

            $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', now()->toDateString());

            if ($startDate > $endDate) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            $query = ProjectMaterialMovement::forProject($request->integer('project_id'))
                ->with(['boqItem', 'inventory', 'performedBy', 'verifiedBy'])
                ->orderBy('movement_date', 'desc')
                ->orderBy('id', 'desc')
                ->inDateRange($startDate, $endDate);

            if ($request->filled('movement_type')) {
                $query->ofType($request->input('movement_type'));
            }

            if ($request->filled('boq_item_id')) {
                $query->forBoqItem($request->integer('boq_item_id'));
            }

            $movements = $query->limit((int) $request->input('limit', 100))->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $movements->map(fn($movement) => $this->formatMovement($movement))->values(),
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'movement_type' => $request->input('movement_type'),
                    ],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory movements error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch movements: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function issue(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'items' => 'required|array|min:1',
                'items.*.inventory_id' => 'required|exists:project_material_inventory,id',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.location' => 'nullable|string|max:255',
                'items.*.notes' => 'nullable|string|max:500',
            ]);

            DB::transaction(function () use ($validated) {
                foreach ($validated['items'] as $item) {
                    $inventory = ProjectMaterialInventory::with('boqItem')
                        ->where('project_id', $validated['project_id'])
                        ->findOrFail($item['inventory_id']);

                    if ((float) $item['quantity'] > $inventory->quantity_available) {
                        abort(422, "Cannot issue {$item['quantity']} - only {$inventory->quantity_available} available.");
                    }

                    ProjectMaterialMovement::createIssue(
                        (int) $validated['project_id'],
                        $inventory->boq_item_id,
                        (float) $item['quantity'],
                        $inventory->boqItem?->unit,
                        $item['notes'] ?? null,
                        $item['location'] ?? null,
                    );
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Materials issued successfully.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode() ?: 422);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory issue error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to issue materials: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function adjust(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'inventory_id' => 'required|exists:project_material_inventory,id',
                'new_quantity' => 'required|numeric|min:0',
                'reason' => 'required|string|max:500',
            ]);

            $inventory = ProjectMaterialInventory::with(['project', 'material', 'boqItem'])
                ->findOrFail($validated['inventory_id']);

            $inventory->adjust((float) $validated['new_quantity'], $validated['reason']);
            $inventory->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'data' => $this->formatInventory($inventory->load(['project', 'material', 'boqItem'])),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory adjust error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function verifyMovement(int $id): JsonResponse
    {
        try {
            $movement = ProjectMaterialMovement::with(['boqItem', 'inventory', 'performedBy', 'verifiedBy'])
                ->findOrFail($id);

            if (!$movement->isVerified()) {
                $movement->verify();
                $movement->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Movement verified successfully.',
                'data' => $this->formatMovement($movement->load(['boqItem', 'inventory', 'performedBy', 'verifiedBy'])),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialInventory verify movement error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify movement: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatInventory(ProjectMaterialInventory $inventory): array
    {
        return [
            'id' => $inventory->id,
            'project_id' => $inventory->project_id,
            'project_name' => $inventory->project?->project_name ?? $inventory->project?->name,
            'material_id' => $inventory->material_id,
            'material_name' => $inventory->material?->name,
            'boq_item_id' => $inventory->boq_item_id,
            'boq_item' => $inventory->boqItem ? [
                'id' => $inventory->boqItem->id,
                'item_code' => $inventory->boqItem->item_code,
                'description' => $inventory->boqItem->description,
                'unit' => $inventory->boqItem->unit,
            ] : null,
            'item_code' => $inventory->boqItem?->item_code,
            'description' => $inventory->boqItem?->description ?? $inventory->material?->name,
            'quantity' => $inventory->quantity,
            'quantity_used' => $inventory->quantity_used,
            'quantity_available' => $inventory->quantity_available,
            'minimum_stock_level' => $inventory->minimum_stock_level,
            'stock_status' => $inventory->stock_status,
            'stock_status_label' => $inventory->stock_status_label,
            'stock_status_badge_class' => $inventory->stock_status_badge_class,
            'unit' => $inventory->boqItem?->unit ?? $inventory->material?->unit,
            'last_updated' => $inventory->last_updated_at
                ? $inventory->last_updated_at->format('Y-m-d H:i:s')
                : ($inventory->updated_at ? $inventory->updated_at->format('Y-m-d H:i:s') : null),
            'created_at' => $inventory->created_at?->toISOString(),
            'updated_at' => $inventory->updated_at?->toISOString(),
        ];
    }

    private function formatMovement(ProjectMaterialMovement $movement): array
    {
        return [
            'id' => $movement->id,
            'movement_number' => $movement->movement_number,
            'project_id' => $movement->project_id,
            'boq_item_id' => $movement->boq_item_id,
            'inventory_id' => $movement->inventory_id,
            'movement_type' => $movement->movement_type,
            'movement_type_label' => $movement->movement_type_label,
            'movement_type_badge_class' => $movement->movement_type_badge_class,
            'quantity' => $movement->quantity,
            'signed_quantity' => $movement->signed_quantity,
            'unit' => $movement->unit,
            'movement_date' => $movement->movement_date?->toDateString(),
            'notes' => $movement->notes,
            'location' => $movement->location,
            'balance_after' => $movement->balance_after,
            'is_verified' => $movement->isVerified(),
            'verified_at' => $movement->verified_at?->toISOString(),
            'performed_by' => $movement->performedBy?->name,
            'verified_by' => $movement->verifiedBy?->name,
            'boq_item' => $movement->boqItem ? [
                'id' => $movement->boqItem->id,
                'item_code' => $movement->boqItem->item_code,
                'description' => $movement->boqItem->description,
            ] : null,
            'inventory' => $movement->inventory ? [
                'id' => $movement->inventory->id,
                'quantity' => $movement->inventory->quantity,
                'quantity_used' => $movement->inventory->quantity_used,
                'quantity_available' => $movement->inventory->quantity_available,
            ] : null,
        ];
    }
}
