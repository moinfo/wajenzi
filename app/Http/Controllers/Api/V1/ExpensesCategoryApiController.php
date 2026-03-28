<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpensesCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpensesCategoryApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = ExpensesCategory::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (ExpensesCategory $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesCategory index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expense categories',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $item = ExpensesCategory::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Expense category created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ExpensesCategory store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense category: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ExpensesCategory::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesCategory show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Expense category not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = ExpensesCategory::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh()),
                'message' => 'Expense category updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesCategory update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense category: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = ExpensesCategory::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense category deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesCategory destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense category',
            ], 500);
        }
    }

    private function formatItem(ExpensesCategory $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
