<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProcessApprovalFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcessApprovalFlowApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $flows = ProcessApprovalFlow::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $flows->map(fn (ProcessApprovalFlow $flow) => $this->formatFlow($flow))->values(),
                'meta' => [
                    'total' => $flows->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlow index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval flows',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'approvable_type' => 'required|string|max:255',
            ]);

            $flow = ProcessApprovalFlow::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatFlow($flow),
                'message' => 'Approval flow created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlow store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create approval flow: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $flow = ProcessApprovalFlow::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatFlow($flow),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlow show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Approval flow not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $flow = ProcessApprovalFlow::findOrFail($id);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'approvable_type' => 'required|string|max:255',
            ]);

            $flow->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatFlow($flow->fresh()),
                'message' => 'Approval flow updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlow update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update approval flow: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $flow = ProcessApprovalFlow::findOrFail($id);
            $flow->delete();

            return response()->json([
                'success' => true,
                'message' => 'Approval flow deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessApprovalFlow destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete approval flow: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatFlow(ProcessApprovalFlow $flow): array
    {
        return [
            'id' => $flow->id,
            'name' => $flow->name,
            'approvable_type' => $flow->approvable_type,
            'created_at' => optional($flow->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($flow->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
