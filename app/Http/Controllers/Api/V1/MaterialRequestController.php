<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectMaterialRequest;
use App\Models\ProjectMaterialRequestItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaterialRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectMaterialRequest::with(['project', 'requester', 'items'])
                ->orderBy('created_at', 'desc');

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->my_requests) {
                $query->where('requester_id', $request->user()->id);
            }

            $requests = $query->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $requests->items(),
                    'meta' => [
                        'current_page' => $requests->currentPage(),
                        'last_page' => $requests->lastPage(),
                        'per_page' => $requests->perPage(),
                        'total' => $requests->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest index error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'purpose' => 'required|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.material_name' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit' => 'nullable|string',
                'items.*.estimated_cost' => 'nullable|numeric|min:0',
                'required_date' => 'nullable|date',
                'priority' => 'nullable|in:low,medium,high,urgent',
            ]);

            $materialRequest = ProjectMaterialRequest::create([
                'project_id' => $validated['project_id'],
                'requester_id' => $request->user()->id,
                'purpose' => $validated['purpose'],
                'required_date' => $validated['required_date'] ?? null,
                'priority' => $validated['priority'] ?? 'medium',
                'status' => 'draft',
            ]);

            // Create items
            foreach ($validated['items'] as $item) {
                ProjectMaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'description' => $item['material_name'],
                    'quantity_requested' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                ]);
            }

            $materialRequest->load(['project', 'requester', 'items']);

            return response()->json([
                'success' => true,
                'message' => 'Material request created successfully.',
                'data' => $materialRequest,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest store error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $materialRequest = ProjectMaterialRequest::with(['project', 'requester', 'items'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $materialRequest,
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest show error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $materialRequest = ProjectMaterialRequest::findOrFail($id);

            if (!in_array($materialRequest->status, ['draft', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be edited.',
                ], 403);
            }

            $validated = $request->validate([
                'purpose' => 'sometimes|string|max:500',
                'items' => 'sometimes|array|min:1',
                'items.*.material_name' => 'required_with:items|string',
                'items.*.quantity' => 'required_with:items|numeric|min:0.01',
                'items.*.unit' => 'nullable|string',
                'items.*.estimated_cost' => 'nullable|numeric|min:0',
                'required_date' => 'nullable|date',
                'priority' => 'nullable|in:low,medium,high,urgent',
            ]);

            $materialRequest->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Material request updated successfully.',
                'data' => $materialRequest->fresh(['project', 'requester', 'items']),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest update error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $materialRequest = ProjectMaterialRequest::findOrFail($id);

            if ($materialRequest->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft requests can be deleted.',
                ], 403);
            }

            $materialRequest->items()->delete();
            $materialRequest->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material request deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest destroy error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $materialRequest = ProjectMaterialRequest::findOrFail($id);

            if (!in_array($materialRequest->status, ['draft', 'rejected'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be submitted.',
                ], 403);
            }

            $materialRequest->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Material request submitted for approval.',
                'data' => $materialRequest->fresh(['project', 'requester', 'items']),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest submit error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $materialRequest = ProjectMaterialRequest::findOrFail($id);

            if ($materialRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be approved.',
                ], 403);
            }

            $materialRequest->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Material request approved.',
                'data' => $materialRequest->fresh(['project', 'requester', 'items']),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest approve error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve material request: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['reason' => 'required|string|max:500']);

            $materialRequest = ProjectMaterialRequest::findOrFail($id);

            if ($materialRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be rejected.',
                ], 403);
            }

            $materialRequest->update([
                'status' => 'rejected',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Material request rejected.',
                'data' => $materialRequest->fresh(['project', 'requester', 'items']),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialRequest reject error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject material request: ' . $e->getMessage(),
            ], 500);
        }
    }
}
