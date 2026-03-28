<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpensesSubCategoryApiController extends Controller
{
    public function referenceData(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'expenses_categories' => ExpensesCategory::orderBy('name')
                        ->get(['id', 'name'])
                        ->values(),
                    'financial_options' => [
                        ['value' => 'NO', 'label' => 'NO'],
                        ['value' => 'YES', 'label' => 'YES'],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesSubCategory reference data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expense sub category reference data',
                'data' => [
                    'expenses_categories' => [],
                    'financial_options' => [],
                ],
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $items = ExpensesSubCategory::with('expensesCategory:id,name')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (ExpensesSubCategory $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesSubCategory index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expense sub categories',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'expenses_category_id' => 'required|exists:expenses_categories,id',
                'is_financial' => 'required|string|in:YES,NO',
            ]);

            $item = ExpensesSubCategory::create($validated)->load('expensesCategory:id,name');

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
                'message' => 'Expense sub category created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ExpensesSubCategory store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense sub category: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = ExpensesSubCategory::with('expensesCategory:id,name')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesSubCategory show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Expense sub category not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = ExpensesSubCategory::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'expenses_category_id' => 'required|exists:expenses_categories,id',
                'is_financial' => 'required|string|in:YES,NO',
            ]);
            $item->update($validated);
            $item->load('expensesCategory:id,name');

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh('expensesCategory:id,name')),
                'message' => 'Expense sub category updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesSubCategory update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense sub category: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = ExpensesSubCategory::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense sub category deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ExpensesSubCategory destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense sub category',
            ], 500);
        }
    }

    private function formatItem(ExpensesSubCategory $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'expenses_category_id' => $item->expenses_category_id,
            'expenses_category_name' => $item->expensesCategory?->name,
            'is_financial' => $item->is_financial,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
