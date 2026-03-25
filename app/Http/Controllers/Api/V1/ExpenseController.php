<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\CostCategory;
use App\Models\ProjectExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $hasStatusColumn = Schema::hasColumn('project_expenses', 'status');
            $hasCostCategoryColumn = Schema::hasColumn(
                'project_expenses',
                'cost_category_id'
            );

            $relations = ['project', 'creator'];
            if ($hasCostCategoryColumn && Schema::hasTable('cost_categories')) {
                $relations[] = 'costCategory';
            }

            $query = ProjectExpense::query();
            
            $query->orderBy('expense_date', 'desc');

            if ($request->project_id) {
                $query->where('project_id', $request->project_id);
            }

            if ($hasStatusColumn && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->cost_category_id) {
                $query->where('cost_category_id', $request->cost_category_id);
            }

            if ($request->start_date) {
                $query->where('expense_date', '>=', Carbon::parse($request->start_date));
            }

            if ($request->end_date) {
                $query->where('expense_date', '<=', Carbon::parse($request->end_date));
            }

            // Filter by my_expenses=1 to show only user's expenses
            if ($request->my_expenses == '1') {
                $query->where('created_by', $request->user()->id);
            }

            $expenses = $query->with($relations)->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => ExpenseResource::collection($expenses),
                'meta' => [
                    'current_page' => $expenses->currentPage(),
                    'last_page' => $expenses->lastPage(),
                    'per_page' => $expenses->perPage(),
                    'total' => $expenses->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'cost_category_id' => 'required|exists:cost_categories,id',
                'description' => 'required|string|max:500',
                'amount' => 'required|numeric|min:0',
                'expense_date' => 'required|date',
                'receipt' => 'nullable|image|max:5120',
                'notes' => 'nullable|string',
            ]);

            if ($request->hasFile('receipt')) {
                $validated['receipt'] = $request->file('receipt')->store('expense-receipts', 'public');
            }

            $validated['created_by'] = $request->user()->id;
            $validated['status'] = 'draft';

            $expense = ProjectExpense::create($validated);
            $expense->load(['project', 'category', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully.',
                'data' => new ExpenseResource($expense),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Expense store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $relations = ['project', 'creator'];
            if (
                Schema::hasColumn('project_expenses', 'cost_category_id') &&
                Schema::hasTable('cost_categories')
            ) {
                $relations[] = 'category';
            }

            $expense = ProjectExpense::with($relations)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ExpenseResource($expense),
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $expense = ProjectExpense::findOrFail($id);
            $hasStatusColumn = Schema::hasColumn('project_expenses', 'status');

            if ($hasStatusColumn && $expense->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft expenses can be edited.',
                ], 403);
            }

            $validated = $request->validate([
                'project_id' => 'sometimes|exists:projects,id',
                'cost_category_id' => 'sometimes|exists:cost_categories,id',
                'description' => 'sometimes|string|max:500',
                'amount' => 'sometimes|numeric|min:0',
                'expense_date' => 'sometimes|date',
                'receipt' => 'nullable|image|max:5120',
                'notes' => 'nullable|string',
            ]);

            if ($request->hasFile('receipt')) {
                if ($expense->receipt) {
                    try {
                        Storage::disk('public')->delete($expense->receipt);
                    } catch (\Throwable $e) {
                        // Ignore storage errors
                    }
                }
                $validated['receipt'] = $request->file('receipt')->store('expense-receipts', 'public');
            }

            $expense->update($validated);
            $expense->load(['project', 'category', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'data' => new ExpenseResource($expense),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Expense update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $expense = ProjectExpense::findOrFail($id);
            $hasStatusColumn = Schema::hasColumn('project_expenses', 'status');

            if ($hasStatusColumn && $expense->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft expenses can be deleted.',
                ], 403);
            }

            if ($expense->receipt) {
                try {
                    Storage::disk('public')->delete($expense->receipt);
                } catch (\Throwable $e) {
                    // Ignore storage errors
                }
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $expense = ProjectExpense::findOrFail($id);
            if (!Schema::hasColumn('project_expenses', 'status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense approvals are not available until migrations are up to date.',
                ], 409);
            }

            if ($expense->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft expenses can be submitted.',
                ], 403);
            }

            $expense->update(['status' => 'pending']);

            return response()->json([
                'success' => true,
                'message' => 'Expense submitted for approval.',
                'data' => new ExpenseResource($expense->fresh()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense submit error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            if (!Schema::hasColumn('project_expenses', 'status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense approvals are not available until migrations are up to date.',
                ], 409);
            }

            $expense = ProjectExpense::findOrFail($id);

            if ($expense->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending expenses can be approved.',
                ], 403);
            }

            $expense->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense approved successfully.',
                'data' => new ExpenseResource($expense->fresh()),
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense approve error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            if (!Schema::hasColumn('project_expenses', 'status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense approvals are not available until migrations are up to date.',
                ], 409);
            }

            $expense = ProjectExpense::findOrFail($id);

            if ($expense->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending expenses can be rejected.',
                ], 403);
            }

            $expense->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Expense rejected.',
                'data' => new ExpenseResource($expense->fresh()),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Expense reject error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = [];
            if (Schema::hasTable('cost_categories')) {
                $categories = CostCategory::all()->map(fn($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description ?? null,
                ])->toArray();
            }

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense categories error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
