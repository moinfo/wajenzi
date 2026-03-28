<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApprovalDocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApprovalDocumentTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = ApprovalDocumentType::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (ApprovalDocumentType $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalDocumentType index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch approval document types',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'keyword' => 'required|string|max:255',
            ]);

            $item = ApprovalDocumentType::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Approval document type created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ApprovalDocumentType store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create approval document type: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ApprovalDocumentType::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalDocumentType show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Approval document type not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = ApprovalDocumentType::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'keyword' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Approval document type updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalDocumentType update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update approval document type: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = ApprovalDocumentType::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Approval document type deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ApprovalDocumentType destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete approval document type',
            ], 500);
        }
    }

    private function formatItem(ApprovalDocumentType $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'keyword' => $item->keyword,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
