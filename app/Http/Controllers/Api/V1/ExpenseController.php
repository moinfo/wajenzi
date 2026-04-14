<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpensesCategory;
use App\Models\ExpensesSubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Expense::with([
                'expensesSubCategory.expensesCategory',
                'approvalStatus',
                'project',
            ])->orderByDesc('date')->orderByDesc('id');

            if ($request->filled('expenses_category_id')) {
                $categoryId = (int) $request->input('expenses_category_id');
                $query->whereHas('expensesSubCategory', function ($subQuery) use ($categoryId) {
                    $subQuery->where('expenses_category_id', $categoryId);
                });
            }

            if ($request->filled('project_id')) {
                $query->where('project_id', (int) $request->input('project_id'));
            }

            if ($request->filled('expenses_sub_category_id')) {
                $query->where('expenses_sub_category_id', (int) $request->input('expenses_sub_category_id'));
            }

            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', Carbon::parse($request->input('start_date'))->toDateString());
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', Carbon::parse($request->input('end_date'))->toDateString());
            }

            $items = $query->paginate((int) $request->input('per_page', 100));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items->getCollection()->map(fn (Expense $expense) => $this->transform($expense))->values(),
                    'meta' => [
                        'current_page' => $items->currentPage(),
                        'last_page' => $items->lastPage(),
                        'per_page' => $items->perPage(),
                        'total' => $items->total(),
                    ],
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

    public function show(int $id): JsonResponse
    {
        try {
            $expense = Expense::with([
                'expensesSubCategory.expensesCategory',
                'approvalStatus',
                'project',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->transform($expense),
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'expenses_sub_category_id' => 'required|exists:expenses_sub_categories,id',
                'description' => 'required|string|max:500',
                'amount' => 'required|numeric|min:0',
                'date' => 'required|date',
                'receipt' => 'nullable|file|max:5120',
            ]);

            $nextId = ((int) Expense::max('id')) + 1;
            $expense = new Expense();
            $expense->project_id = array_key_exists('project_id', $validated)
                ? ($validated['project_id'] !== null ? (int) $validated['project_id'] : null)
                : null;
            $expense->expenses_sub_category_id = (int) $validated['expenses_sub_category_id'];
            $expense->description = $validated['description'];
            $expense->amount = $validated['amount'];
            $expense->date = $validated['date'];
            $expense->status = 'PENDING';
            $expense->document_number = sprintf('EXPS/%d/%s', $nextId, date('Y'));

            if ($request->hasFile('receipt')) {
                $path = $request->file('receipt')->store('expense-files', 'public');
                $expense->file = Storage::disk('public')->url($path);
            }

            $expense->save();
            $expense->load(['expensesSubCategory.expensesCategory', 'approvalStatus', 'project']);

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully.',
                'data' => $this->transform($expense),
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

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $expense = Expense::with(['expensesSubCategory.expensesCategory', 'approvalStatus', 'project'])->findOrFail($id);

            $validated = $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'expenses_sub_category_id' => 'required|exists:expenses_sub_categories,id',
                'description' => 'required|string|max:500',
                'amount' => 'required|numeric|min:0',
                'date' => 'required|date',
                'receipt' => 'nullable|file|max:5120',
            ]);

            if ($request->has('project_id')) {
                $expense->project_id = $validated['project_id'] !== null ? (int) $validated['project_id'] : null;
            }
            $expense->expenses_sub_category_id = (int) $validated['expenses_sub_category_id'];
            $expense->description = $validated['description'];
            $expense->amount = $validated['amount'];
            $expense->date = $validated['date'];

            if ($request->hasFile('receipt')) {
                $path = $request->file('receipt')->store('expense-files', 'public');
                $expense->file = Storage::disk('public')->url($path);
            }

            $expense->save();
            $expense->load(['expensesSubCategory.expensesCategory', 'approvalStatus', 'project']);

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully.',
                'data' => $this->transform($expense),
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
            $expense = Expense::findOrFail($id);
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

    public function categories(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => ExpensesCategory::orderBy('name')->get(['id', 'name'])->values(),
                    'sub_categories' => ExpensesSubCategory::with('expensesCategory:id,name')
                        ->orderBy('name')
                        ->get()
                        ->map(fn (ExpensesSubCategory $item) => [
                            'id' => $item->id,
                            'name' => $item->name,
                            'expenses_category_id' => $item->expenses_category_id,
                            'category_name' => $item->expensesCategory?->name,
                        ])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Expense categories error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage(),
                'data' => [
                    'categories' => [],
                    'sub_categories' => [],
                ],
            ], 500);
        }
    }

    public function submit(int $id): JsonResponse
    {
        try {
            $expense = Expense::with('approvalStatus')->findOrFail($id);
            if (method_exists($expense, 'submit')) {
                $expense->submit(auth()->user());
                $expense->refresh();
            }

            return response()->json([
                'success' => true,
                'message' => 'Expense submitted successfully.',
                'data' => $this->transform($expense->load(['expensesSubCategory.expensesCategory', 'approvalStatus', 'project'])),
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
        return response()->json([
            'success' => false,
            'message' => 'Approving expenses from mobile is not available here.',
        ], 409);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Rejecting expenses from mobile is not available here.',
        ], 409);
    }

    private function transform(Expense $expense): array
    {
        $status = strtoupper((string) ($expense->approvalStatus?->status ?? $expense->status ?? 'PENDING'));

        return [
            'id' => $expense->id,
            'document_number' => $expense->document_number,
            'description' => $expense->description,
            'amount' => $expense->amount,
            'expense_date' => $expense->date,
            'date' => $expense->date,
            'status' => $status,
            'receipt_path' => $expense->file,
            'file' => $expense->file,
            'project_id' => $expense->project_id,
            'project_name' => $expense->project?->project_name,
            'expenses_sub_category_id' => $expense->expenses_sub_category_id,
            'expenses_sub_category' => [
                'id' => $expense->expensesSubCategory?->id,
                'name' => $expense->expensesSubCategory?->name,
            ],
            'expenses_category' => [
                'id' => $expense->expensesSubCategory?->expensesCategory?->id,
                'name' => $expense->expensesSubCategory?->expensesCategory?->name,
            ],
            'approval_status' => $expense->approvalStatus?->status,
            'created_at' => $expense->created_at?->toISOString(),
            'updated_at' => $expense->updated_at?->toISOString(),
        ];
    }
}
