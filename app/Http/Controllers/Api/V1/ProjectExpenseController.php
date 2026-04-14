<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CostCategory;
use App\Models\ProjectExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProjectExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProjectExpense::with(['project', 'costCategory', 'creator'])
                ->orderByDesc('expense_date')
                ->orderByDesc('id');

            if ($request->filled('project_id')) {
                $query->where('project_id', (int) $request->input('project_id'));
            }

            if ($request->filled('cost_category_id')) {
                $query->where('cost_category_id', (int) $request->input('cost_category_id'));
            }

            if ($request->filled('start_date')) {
                $query->whereDate('expense_date', '>=', Carbon::parse($request->input('start_date'))->toDateString());
            }

            if ($request->filled('end_date')) {
                $query->whereDate('expense_date', '<=', Carbon::parse($request->input('end_date'))->toDateString());
            }

            $items = $query->paginate((int) $request->input('per_page', 100));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items->getCollection()->map(fn (ProjectExpense $expense) => $this->transform($expense))->values(),
                    'meta' => [
                        'current_page' => $items->currentPage(),
                        'last_page' => $items->lastPage(),
                        'per_page' => $items->perPage(),
                        'total' => $items->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectExpense index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => CostCategory::orderBy('name')->get(['id', 'name'])->values(),
                    'sub_categories' => [],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectExpense categories error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch project expense categories: ' . $e->getMessage(),
                'data' => [
                    'categories' => [],
                    'sub_categories' => [],
                ],
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'required|exists:projects,id',
                'cost_category_id' => 'required|exists:cost_categories,id',
                'description' => 'required|string',
                'amount' => 'required|numeric',
                'date' => 'required|date',
                'remarks' => 'nullable|string',
            ]);

            $expense = ProjectExpense::create([
                'project_id' => $validated['project_id'],
                'cost_category_id' => $validated['cost_category_id'],
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'expense_date' => $validated['date'],
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->id() ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->transform($expense->fresh(['project', 'costCategory', 'creator'])),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ProjectExpense store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to store project expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $expense = ProjectExpense::with(['project', 'costCategory', 'creator'])->findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $this->transform($expense),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectExpense show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load project expense: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $expense = ProjectExpense::findOrFail($id);

            $validated = $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'cost_category_id' => 'required|exists:cost_categories,id',
                'description' => 'required|string',
                'amount' => 'required|numeric',
                'date' => 'required|date',
                'remarks' => 'nullable|string',
            ]);

            $expense->update([
                'project_id' => $validated['project_id'] ?? $expense->project_id,
                'cost_category_id' => $validated['cost_category_id'],
                'description' => $validated['description'],
                'amount' => $validated['amount'],
                'expense_date' => $validated['date'],
                'remarks' => $validated['remarks'] ?? $expense->remarks,
            ]);

            return response()->json([
                'success' => true,
                'data' => $this->transform($expense->fresh(['project', 'costCategory', 'creator'])),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectExpense update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update project expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $expense = ProjectExpense::findOrFail($id);
            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project expense deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectExpense destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete project expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function transform(ProjectExpense $expense): array
    {
        return [
            'id' => $expense->id,
            'document_number' => $expense->document_number ?? null,
            'description' => $expense->description,
            'amount' => $expense->amount,
            'expense_date' => $expense->expense_date?->toDateString(),
            'date' => $expense->expense_date?->toDateString(),
            'status' => strtoupper((string) ($expense->status ?? 'PENDING')),
            'receipt_path' => $expense->receipt,
            'file' => $expense->receipt,
            'project_id' => $expense->project_id,
            'project_name' => $expense->project?->project_name,
            'cost_category_id' => $expense->cost_category_id,
            'expenses_category' => [
                'id' => $expense->costCategory?->id,
                'name' => $expense->costCategory?->name,
                'expenses_category_name' => $expense->costCategory?->name,
            ],
            'expenses_sub_category' => null,
            'approval_status' => null,
            'remarks' => $expense->remarks,
            'created_at' => $expense->created_at?->toISOString(),
            'updated_at' => $expense->updated_at?->toISOString(),
        ];
    }
}
