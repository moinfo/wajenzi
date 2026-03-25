<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BoqController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectBoq::with(['project', 'approvalStatus']);

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->type) {
                $query->where('type', $request->type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            $boqs = $query->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            $items = collect($boqs->items())->map(fn($boq) => [
                'id' => $boq->id,
                'project_id' => $boq->project_id,
                'project_name' => $boq->project?->project_name,
                'version' => $boq->version,
                'type' => $boq->type,
                'total_amount' => $boq->total_amount,
                'status' => $boq->approvalStatus?->status ?? 'Pending',
                'items_count' => $boq->items()->count(),
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $boqs->currentPage(),
                        'last_page' => $boqs->lastPage(),
                        'per_page' => $boqs->perPage(),
                        'total' => $boqs->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch BOQs: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'version' => 'required|integer|min:1',
                'type' => 'required|in:client,internal',
                'status' => 'nullable|string',
            ]);

            $validated['total_amount'] = 0;
            $validated['created_by'] = $request->user()->id;

            $boq = ProjectBoq::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'BOQ created successfully.',
                'data' => [
                    'id' => $boq->id,
                    'project_id' => $boq->project_id,
                    'version' => $boq->version,
                    'type' => $boq->type,
                    'total_amount' => $boq->total_amount,
                    'status' => 'Draft',
                    'items_count' => 0,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Boq store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::with(['project', 'approvalStatus', 'items'])->findOrFail($id);

            $items = $boq->items->map(fn($item) => [
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
                    'id' => $boq->id,
                    'project_id' => $boq->project_id,
                    'project_name' => $boq->project?->project_name,
                    'version' => $boq->version,
                    'type' => $boq->type,
                    'status' => $boq->approvalStatus?->status ?? 'Pending',
                    'total_amount' => $boq->total_amount,
                    'items_count' => $boq->items()->count(),
                    'items' => $items,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);

            $validated = $request->validate([
                'project_id' => 'sometimes|exists:projects,id',
                'version' => 'sometimes|integer|min:1',
                'type' => 'sometimes|in:client,internal',
            ]);

            $boq->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'BOQ updated successfully.',
                'data' => [
                    'id' => $boq->id,
                    'project_id' => $boq->project_id,
                    'project_name' => $boq->project?->project_name,
                    'version' => $boq->version,
                    'type' => $boq->type,
                    'total_amount' => $boq->total_amount,
                    'status' => $boq->approvalStatus?->status ?? 'Draft',
                    'items_count' => $boq->items()->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $boq = ProjectBoq::findOrFail($id);

            $forceDelete = $request->boolean('force', false);
            $itemsCount = $boq->items()->count();

            if ($itemsCount > 0 && !$forceDelete) {
                $boq->items()->delete();
            }

            $boq->delete();

            return response()->json([
                'success' => true,
                'message' => "BOQ deleted successfully. ($itemsCount items also deleted)",
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete BOQ: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function projects(): JsonResponse
    {
        try {
            $projects = Project::orderBy('project_name')
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'project_name' => $p->project_name,
                ]);

            return response()->json([
                'success' => true,
                'data' => $projects,
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq projects error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch projects: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function nextVersion(Request $request): JsonResponse
    {
        try {
            $projectId = $request->project_id;
            $maxVersion = ProjectBoq::where('project_id', $projectId)->max('version') ?? 0;

            return response()->json([
                'success' => true,
                'version' => $maxVersion + 1,
            ]);
        } catch (\Throwable $e) {
            Log::error('Boq nextVersion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get next version: ' . $e->getMessage(),
                'version' => 1,
            ], 500);
        }
    }
}
