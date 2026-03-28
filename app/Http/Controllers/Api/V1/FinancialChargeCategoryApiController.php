<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialChargeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FinancialChargeCategoryApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = FinancialChargeCategory::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (FinancialChargeCategory $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('FinancialChargeCategory index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch financial charge categories',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'charge' => 'required',
            ]);

            $item = FinancialChargeCategory::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Financial charge category created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('FinancialChargeCategory store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create financial charge category: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = FinancialChargeCategory::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('FinancialChargeCategory show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Financial charge category not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = FinancialChargeCategory::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'charge' => 'required',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Financial charge category updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('FinancialChargeCategory update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update financial charge category: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = FinancialChargeCategory::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Financial charge category deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('FinancialChargeCategory destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete financial charge category',
            ], 500);
        }
    }

    private function formatItem(FinancialChargeCategory $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'charge' => $item->charge,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
