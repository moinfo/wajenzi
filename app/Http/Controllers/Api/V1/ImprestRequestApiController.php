<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpensesSubCategory;
use App\Models\ImprestRequest;
use App\Models\PettyCashRefillRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImprestRequestApiController extends Controller
{
    public function index(): JsonResponse
    {
        $requests = ImprestRequest::with([
            'user:id,name',
            'project:id,project_name',
            'expenseSubCategory:id,name',
        ])->orderByDesc('date')->orderByDesc('id')->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn (ImprestRequest $request) => $this->transformRequest($request))->values(),
            'meta' => [
                'total' => $requests->count(),
                'current_balance' => $this->currentBalance(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'expenses_sub_categories' => ExpensesSubCategory::orderBy('name')->get(['id', 'name']),
                'projects' => Project::orderBy('project_name')->get(['id', 'project_name']),
                'current_balance' => $this->currentBalance(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $requestItem = ImprestRequest::with([
            'user:id,name',
            'project:id,project_name',
            'expenseSubCategory:id,name',
        ])->find($id);

        if (!$requestItem) {
            return response()->json([
                'success' => false,
                'message' => 'Imprest request not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformRequest($requestItem),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'expenses_sub_category_id' => 'required|exists:expenses_sub_categories,id',
            'project_id' => 'required|exists:projects,id',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx,csv,txt|max:5120',
        ]);

        $balance = $this->currentBalance();
        if ((float) $validated['amount'] > $balance) {
            return response()->json([
                'success' => false,
                'message' => 'Imprest amount cannot be greater than the balance.',
            ], 422);
        }

        $nextId = ((int) ImprestRequest::max('id')) + 1;
        $payload = [
            'document_number' => sprintf('IMPT/%s/%d', date('Y'), $nextId),
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'status' => 'CREATED',
            'create_by_id' => $request->user()->id,
            'expenses_sub_category_id' => $validated['expenses_sub_category_id'],
            'date' => $validated['date'],
            'project_id' => $validated['project_id'],
        ];

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->storeAs(
                'uploads',
                time() . '_' . $request->file('file')->getClientOriginalName(),
                'public'
            );
            $payload['file'] = '/storage/' . $filePath;
        }

        $requestItem = ImprestRequest::create($payload);
        $requestItem->load(['user:id,name', 'project:id,project_name', 'expenseSubCategory:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Imprest request created successfully',
            'data' => $this->transformRequest($requestItem),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestItem = ImprestRequest::find($id);

        if (!$requestItem) {
            return response()->json([
                'success' => false,
                'message' => 'Imprest request not found',
            ], 404);
        }

        $validated = $request->validate([
            'expenses_sub_category_id' => 'sometimes|required|exists:expenses_sub_categories,id',
            'project_id' => 'sometimes|required|exists:projects,id',
            'description' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'date' => 'sometimes|required|date',
            'status' => 'nullable|string|max:50',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx,csv,txt|max:5120',
        ]);

        $nextAmount = array_key_exists('amount', $validated)
            ? (float) $validated['amount']
            : (float) $requestItem->amount;
        if ($nextAmount > $this->currentBalance()) {
            return response()->json([
                'success' => false,
                'message' => 'Imprest amount cannot be greater than the balance.',
            ], 422);
        }

        if ($request->hasFile('file')) {
            if (!empty($requestItem->file) && str_starts_with($requestItem->file, '/storage/')) {
                Storage::disk('public')->delete(substr($requestItem->file, strlen('/storage/')));
            }
            $filePath = $request->file('file')->storeAs(
                'uploads',
                time() . '_' . $request->file('file')->getClientOriginalName(),
                'public'
            );
            $validated['file'] = '/storage/' . $filePath;
        }

        $requestItem->update($validated);
        $requestItem->load(['user:id,name', 'project:id,project_name', 'expenseSubCategory:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Imprest request updated successfully',
            'data' => $this->transformRequest($requestItem),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $requestItem = ImprestRequest::find($id);

        if (!$requestItem) {
            return response()->json([
                'success' => false,
                'message' => 'Imprest request not found',
            ], 404);
        }

        if (!empty($requestItem->file) && str_starts_with($requestItem->file, '/storage/')) {
            Storage::disk('public')->delete(substr($requestItem->file, strlen('/storage/')));
        }

        $requestItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Imprest request deleted successfully',
        ]);
    }

    private function transformRequest(ImprestRequest $request): array
    {
        return [
            'id' => $request->id,
            'document_number' => $request->document_number,
            'description' => $request->description,
            'amount' => (float) $request->amount,
            'status' => $request->status,
            'date' => $request->date,
            'create_by_id' => $request->create_by_id,
            'requested_user_name' => $request->user?->name,
            'project_id' => $request->project_id,
            'project_name' => $request->project?->project_name,
            'expenses_sub_category_id' => $request->expenses_sub_category_id,
            'expenses_sub_category_name' => $request->expenseSubCategory?->name,
            'file' => $request->file,
            'file_url' => $request->file ? url($request->file) : null,
            'created_at' => $request->created_at?->toIso8601String(),
            'updated_at' => $request->updated_at?->toIso8601String(),
        ];
    }

    private function currentBalance(): float
    {
        $approvedStatuses = ['approved', 'APPROVED'];

        $refills = (float) PettyCashRefillRequest::query()
            ->whereIn('status', $approvedStatuses)
            ->sum('refill_amount');
        $imprest = (float) ImprestRequest::query()
            ->whereIn('status', $approvedStatuses)
            ->sum('amount');

        return $refills - $imprest;
    }
}
