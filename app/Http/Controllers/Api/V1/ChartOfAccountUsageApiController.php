<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChartAccount;
use App\Models\ChartAccountUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartOfAccountUsageApiController extends Controller
{
    public function index(): JsonResponse
    {
        $usages = ChartAccountUsage::with('chartAccount:id,account_name,code')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $usages->map(fn (ChartAccountUsage $usage) => $this->transformUsage($usage))->values(),
            'meta' => [
                'total' => $usages->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $accounts = ChartAccount::orderBy('code')->orderBy('account_name')->get(['id', 'code', 'account_name']);

        return response()->json([
            'success' => true,
            'data' => [
                'charts_accounts' => $accounts->map(fn (ChartAccount $account) => [
                    'id' => $account->id,
                    'code' => $account->code,
                    'account_name' => $account->account_name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $usage = ChartAccountUsage::with('chartAccount:id,account_name,code')->find($id);

        if (!$usage) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account usage not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformUsage($usage),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'charts_account_id' => 'required|exists:charts_accounts,id',
            'description' => 'nullable|string',
        ]);

        $usage = ChartAccountUsage::create($validated);
        $usage->load('chartAccount:id,account_name,code');

        return response()->json([
            'success' => true,
            'message' => 'Chart account usage created successfully',
            'data' => $this->transformUsage($usage),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $usage = ChartAccountUsage::find($id);

        if (!$usage) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account usage not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'charts_account_id' => 'sometimes|required|exists:charts_accounts,id',
            'description' => 'nullable|string',
        ]);

        $usage->update($validated);
        $usage->load('chartAccount:id,account_name,code');

        return response()->json([
            'success' => true,
            'message' => 'Chart account usage updated successfully',
            'data' => $this->transformUsage($usage),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $usage = ChartAccountUsage::find($id);

        if (!$usage) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account usage not found',
            ], 404);
        }

        $usage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chart account usage deleted successfully',
        ]);
    }

    private function transformUsage(ChartAccountUsage $usage): array
    {
        return [
            'id' => $usage->id,
            'name' => $usage->name,
            'charts_account_id' => $usage->charts_account_id,
            'chart_account_name' => $usage->chartAccount->account_name ?? null,
            'chart_account_code' => $usage->chartAccount->code ?? null,
            'description' => $usage->description,
            'created_at' => $usage->created_at?->toIso8601String(),
            'updated_at' => $usage->updated_at?->toIso8601String(),
        ];
    }
}
