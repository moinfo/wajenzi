<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectMaterialRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ProjectMaterialRequest::with(['project', 'requester'])
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
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'description' => 'required|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.material_name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.estimated_cost' => 'nullable|numeric|min:0',
            'needed_by_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);

        $materialRequest = ProjectMaterialRequest::create([
            'project_id' => $validated['project_id'],
            'requester_id' => $request->user()->id,
            'description' => $validated['description'],
            'items' => $validated['items'],
            'needed_by_date' => $validated['needed_by_date'] ?? null,
            'priority' => $validated['priority'] ?? 'medium',
            'notes' => $validated['notes'] ?? null,
            'status' => 'draft',
        ]);

        $materialRequest->load(['project', 'requester']);

        return response()->json([
            'success' => true,
            'message' => 'Material request created successfully.',
            'data' => $materialRequest,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $materialRequest = ProjectMaterialRequest::with(['project', 'requester'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $materialRequest,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $materialRequest = ProjectMaterialRequest::findOrFail($id);

        if (!in_array($materialRequest->status, ['draft', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'This request cannot be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string|max:500',
            'items' => 'sometimes|array|min:1',
            'items.*.material_name' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.estimated_cost' => 'nullable|numeric|min:0',
            'needed_by_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'notes' => 'nullable|string',
        ]);

        $materialRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Material request updated successfully.',
            'data' => $materialRequest->fresh(['project', 'requester']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $materialRequest = ProjectMaterialRequest::findOrFail($id);

        if ($materialRequest->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft requests can be deleted.',
            ], 403);
        }

        $materialRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Material request deleted successfully.',
        ]);
    }

    public function submit(int $id): JsonResponse
    {
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
            'data' => $materialRequest->fresh(),
        ]);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
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
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Material request approved.',
            'data' => $materialRequest->fresh(),
        ]);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
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
            'rejection_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Material request rejected.',
            'data' => $materialRequest->fresh(),
        ]);
    }
}
