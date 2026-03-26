<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\ChartAccountVariable;
use App\Models\ImprestRequest;
use App\Models\PettyCashRefillRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class PettyCashRefillRequestApiController extends Controller
{
    public function index(): JsonResponse
    {
        $query = PettyCashRefillRequest::with('user:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($this->hasChartsAccountColumn()) {
            $query->with('chartAccount:id,code,account_name');
        }

        $requests = $query->get();

        return response()->json([
            'success' => true,
            'data' => $requests->map(fn (PettyCashRefillRequest $request) => $this->transformRequest($request))->values(),
            'meta' => [
                'total' => $requests->count(),
                'current_balance' => $this->currentBalance(),
                'petty_cash_limit' => $this->pettyCashLimit(),
                'suggested_refill_amount' => $this->suggestedRefillAmount(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $accounts = ChartAccount::query()
            ->where('id', 12)
            ->orderBy('code')
            ->get(['id', 'code', 'account_name']);

        return response()->json([
            'success' => true,
            'data' => [
                'charts_accounts' => $accounts->map(fn (ChartAccount $account) => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'account_name' => $account->account_name,
                ])->values(),
                'current_balance' => $this->currentBalance(),
                'petty_cash_limit' => $this->pettyCashLimit(),
                'suggested_refill_amount' => $this->suggestedRefillAmount(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $query = PettyCashRefillRequest::with('user:id,name');
        if ($this->hasChartsAccountColumn()) {
            $query->with('chartAccount:id,code,account_name');
        }

        $requestItem = $query->find($id);

        if (!$requestItem) {
            return response()->json([
                'success' => false,
                'message' => 'Petty cash refill request not found',
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
            'charts_account_id' => 'nullable|exists:charts_accounts,id',
            'date' => 'required|date',
            'balance' => 'nullable|numeric|min:0',
            'refill_amount' => 'nullable|numeric|min:0',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx,csv,txt|max:5120',
        ]);

        $balance = array_key_exists('balance', $validated)
            ? (float) $validated['balance']
            : $this->currentBalance();
        $refillAmount = array_key_exists('refill_amount', $validated)
            ? (float) $validated['refill_amount']
            : $this->suggestedRefillAmount();

        if ($refillAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Petty cash does not require a refill right now.',
            ], 422);
        }

        $nextId = ((int) PettyCashRefillRequest::max('id')) + 1;
        $payload = [
            'document_number' => sprintf('PCRF/%s/%d', date('Y'), $nextId),
            'balance' => $balance,
            'refill_amount' => $refillAmount,
            'status' => 'CREATED',
            'create_by_id' => $request->user()->id,
            'date' => $validated['date'],
        ];

        if ($this->hasChartsAccountColumn()) {
            $payload['charts_account_id'] = $validated['charts_account_id'] ?? 12;
        }

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->storeAs(
                'uploads',
                time() . '_' . $request->file('file')->getClientOriginalName(),
                'public'
            );
            $payload['file'] = '/storage/' . $filePath;
        }

        $requestItem = PettyCashRefillRequest::create($payload);
        $requestItem->loadMissing('user:id,name');
        if ($this->hasChartsAccountColumn()) {
            $requestItem->loadMissing('chartAccount:id,code,account_name');
        }

        return response()->json([
            'success' => true,
            'message' => 'Petty cash refill request created successfully',
            'data' => $this->transformRequest($requestItem),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $requestItem = PettyCashRefillRequest::find($id);

        if (!$requestItem) {
            return response()->json([
                'success' => false,
                'message' => 'Petty cash refill request not found',
            ], 404);
        }

        $validated = $request->validate([
            'charts_account_id' => 'nullable|exists:charts_accounts,id',
            'date' => 'sometimes|required|date',
            'balance' => 'nullable|numeric|min:0',
            'refill_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|max:50',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx,csv,txt|max:5120',
        ]);

        $payload = [];
        foreach (['date', 'balance', 'refill_amount', 'status'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if ($this->hasChartsAccountColumn() && array_key_exists('charts_account_id', $validated)) {
            $payload['charts_account_id'] = $validated['charts_account_id'];
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
            $payload['file'] = '/storage/' . $filePath;
        }

        $requestItem->update($payload);
        $requestItem->loadMissing('user:id,name');
        if ($this->hasChartsAccountColumn()) {
            $requestItem->loadMissing('chartAccount:id,code,account_name');
        }

        return response()->json([
            'success' => true,
            'message' => 'Petty cash refill request updated successfully',
            'data' => $this->transformRequest($requestItem),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $requestItem = PettyCashRefillRequest::find($id);

        if (!$requestItem) {
            return response()->json([
                'success' => false,
                'message' => 'Petty cash refill request not found',
            ], 404);
        }

        if (!empty($requestItem->file) && str_starts_with($requestItem->file, '/storage/')) {
            Storage::disk('public')->delete(substr($requestItem->file, strlen('/storage/')));
        }

        $requestItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Petty cash refill request deleted successfully',
        ]);
    }

    private function transformRequest(PettyCashRefillRequest $requestItem): array
    {
        $account = null;
        if ($this->hasChartsAccountColumn()) {
            $account = $requestItem->chartAccount;
        } else {
            $account = ChartAccount::query()->select(['id', 'code', 'account_name'])->find(12);
        }

        return [
            'id' => $requestItem->id,
            'document_number' => $requestItem->document_number,
            'date' => $requestItem->date,
            'balance' => (float) $requestItem->balance,
            'refill_amount' => (float) $requestItem->refill_amount,
            'status' => $requestItem->status,
            'create_by_id' => $requestItem->create_by_id,
            'requested_user_name' => $requestItem->user?->name,
            'charts_account_id' => $this->hasChartsAccountColumn() ? $requestItem->charts_account_id : $account?->id,
            'chart_account_code' => $account?->code,
            'chart_account_name' => $account?->account_name,
            'file' => $requestItem->file,
            'file_url' => $requestItem->file ? url($requestItem->file) : null,
            'created_at' => $requestItem->created_at?->toIso8601String(),
            'updated_at' => $requestItem->updated_at?->toIso8601String(),
        ];
    }

    private function pettyCashLimit(): float
    {
        return (float) (ChartAccountVariable::where('variable', 'PETTY_CASH_LIMIT')->value('value') ?? 0);
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

    private function suggestedRefillAmount(): float
    {
        return max($this->pettyCashLimit() - $this->currentBalance(), 0);
    }

    private function hasChartsAccountColumn(): bool
    {
        return Schema::hasColumn('petty_cash_refill_requests', 'charts_account_id');
    }
}
