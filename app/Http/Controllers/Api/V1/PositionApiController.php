<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PositionApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = Position::with('reportsTo')->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (Position $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Position index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch positions',
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        try {
            $positions = Position::orderBy('name')->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'positions' => $positions->map(fn (Position $item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                    ])->values(),
                    'statuses' => ['ACTIVE', 'INACTIVE'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Position reference data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validatePayload($request);
            $item = Position::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh('reportsTo')),
                'message' => 'Position created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Position store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create position: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = Position::with('reportsTo')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('Position show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Position not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = Position::findOrFail($id);
            $validated = $this->validatePayload($request, $item->id);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh('reportsTo')),
                'message' => 'Position updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Position update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update position: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = Position::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Position deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Position destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete position',
            ], 500);
        }
    }

    private function validatePayload(Request $request, ?int $positionId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'required|string|max:20',
            'description' => 'nullable|string',
            'report_to_id' => 'nullable|integer|exists:positions,id|not_in:' . ($positionId ?? 0),
            'status' => 'required|in:ACTIVE,INACTIVE',
        ]);
    }

    private function formatItem(Position $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'abbreviation' => $item->abbreviation,
            'description' => $item->description,
            'report_to_id' => $item->report_to_id,
            'report_to_name' => optional($item->reportsTo)->name,
            'status' => $item->status,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
