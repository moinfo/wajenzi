<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ExpenseResource;
use App\Models\ExpenseCategory;
use App\Models\ProjectExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ProjectExpense::with(['project', 'category', 'creator'])
            ->where('created_by', $user->id)
            ->orderBy('expense_date', 'desc');

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->start_date) {
            $query->where('expense_date', '>=', Carbon::parse($request->start_date));
        }

        if ($request->end_date) {
            $query->where('expense_date', '<=', Carbon::parse($request->end_date));
        }

        $expenses = $query->paginate($request->per_page ?? 20);

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
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'expense_category_id' => 'required|exists:expense_categories,id',
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
    }

    public function show(int $id): JsonResponse
    {
        $expense = ProjectExpense::with(['project', 'category', 'creator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new ExpenseResource($expense),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $expense = ProjectExpense::findOrFail($id);

        if ($expense->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft expenses can be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'project_id' => 'sometimes|exists:projects,id',
            'expense_category_id' => 'sometimes|exists:expense_categories,id',
            'description' => 'sometimes|string|max:500',
            'amount' => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'receipt' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('receipt')) {
            if ($expense->receipt) {
                Storage::disk('public')->delete($expense->receipt);
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
    }

    public function destroy(int $id): JsonResponse
    {
        $expense = ProjectExpense::findOrFail($id);

        if ($expense->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft expenses can be deleted.',
            ], 403);
        }

        if ($expense->receipt) {
            Storage::disk('public')->delete($expense->receipt);
        }

        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully.',
        ]);
    }

    public function submit(int $id): JsonResponse
    {
        $expense = ProjectExpense::findOrFail($id);

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
    }

    public function approve(Request $request, int $id): JsonResponse
    {
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
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

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
    }

    public function categories(): JsonResponse
    {
        $categories = ExpenseCategory::all()->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'description' => $c->description ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
