<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeadStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LeadStatusApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = LeadStatus::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (LeadStatus $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadStatus index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lead statuses',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = LeadStatus::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Lead status created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('LeadStatus store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lead status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = LeadStatus::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadStatus show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Lead status not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = LeadStatus::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Lead status updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadStatus update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lead status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = LeadStatus::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lead status deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('LeadStatus destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lead status',
            ], 500);
        }
    }

    private function formatItem(LeadStatus $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
