<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use App\Models\ChartAccount;
use App\Models\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartOfAccountApiController extends Controller
{
    public function index(): JsonResponse
    {
        $accountTypes = AccountType::orderBy('code')->orderBy('type')->get();
        $accounts = ChartAccount::with(['accountType:id,type,code,normal_balance', 'parentAccount:id,account_name,code'])
            ->orderBy('account_type')
            ->orderBy('code')
            ->orderBy('account_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'account_types' => $accountTypes->map(fn (AccountType $type) => [
                    'id' => $type->id,
                    'type' => $type->type,
                    'code' => $type->code,
                    'normal_balance' => $type->normal_balance,
                ])->values(),
                'currencies' => Currency::orderBy('name')->get()->map(fn ($currency) => [
                    'id' => $currency->id,
                    'name' => $currency->name ?? null,
                    'symbol' => $currency->symbol ?? null,
                ])->values(),
                'accounts' => $accounts->map(
                    fn (ChartAccount $account) => $this->transformAccount($account)
                )->values(),
            ],
            'meta' => [
                'total' => $accounts->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $account = ChartAccount::with([
            'accountType:id,type,code,normal_balance',
            'parentAccount:id,account_name,code',
            'childAccounts:id,parent',
        ])->find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformAccount($account),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_type' => 'required|exists:account_types,id',
            'parent' => 'nullable|exists:charts_accounts,id',
            'code' => 'required|string|max:255|unique:charts_accounts,code',
            'currency' => 'nullable|exists:currencies,id',
            'account_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|max:20',
        ]);

        $validated['parent'] = $validated['parent'] ?? null;
        $validated['status'] = $validated['status'] ?? 'ACTIVE';

        $account = ChartAccount::create($validated);
        $account->load(['accountType:id,type,code,normal_balance', 'parentAccount:id,account_name,code', 'childAccounts:id,parent']);

        return response()->json([
            'success' => true,
            'message' => 'Chart account created successfully',
            'data' => $this->transformAccount($account),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $account = ChartAccount::withCount('childAccounts')->find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account not found',
            ], 404);
        }

        $validated = $request->validate([
            'account_type' => 'sometimes|required|exists:account_types,id',
            'parent' => 'nullable|exists:charts_accounts,id',
            'code' => 'sometimes|required|string|max:255|unique:charts_accounts,code,' . $account->id,
            'currency' => 'nullable|exists:currencies,id',
            'account_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string|max:20',
        ]);

        if (array_key_exists('parent', $validated) && (int) ($validated['parent'] ?? 0) === (int) $account->id) {
            return response()->json([
                'success' => false,
                'message' => 'An account cannot be its own parent',
            ], 422);
        }

        $account->update($validated);
        $account->load(['accountType:id,type,code,normal_balance', 'parentAccount:id,account_name,code', 'childAccounts:id,parent']);

        return response()->json([
            'success' => true,
            'message' => 'Chart account updated successfully',
            'data' => $this->transformAccount($account),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $account = ChartAccount::withCount('childAccounts')->find($id);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account not found',
            ], 404);
        }

        if (($account->child_accounts_count ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete chart account that has child accounts',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chart account deleted successfully',
        ]);
    }

    private function transformAccount(ChartAccount $account): array
    {
        return [
            'id' => $account->id,
            'code' => $account->code,
            'account_name' => $account->account_name,
            'account_type' => $account->account_type,
            'account_type_name' => $account->accountType->type ?? null,
            'account_type_code' => $account->accountType->code ?? null,
            'currency' => $account->currency,
            'currency_name' => $account->currency()->value('name'),
            'parent' => $account->parent,
            'parent_name' => $account->parentAccount->account_name ?? null,
            'description' => $account->description,
            'status' => $account->status,
            'children_count' => $account->relationLoaded('childAccounts')
                ? $account->childAccounts->count()
                : ($account->child_accounts_count ?? 0),
            'created_at' => $account->created_at?->toIso8601String(),
            'updated_at' => $account->updated_at?->toIso8601String(),
        ];
    }
}
