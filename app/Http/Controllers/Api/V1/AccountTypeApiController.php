<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $types = AccountType::withCount('chartAccounts')
            ->orderBy('code')
            ->orderBy('type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $types->map(fn (AccountType $type) => [
                'id' => $type->id,
                'type' => $type->type,
                'code' => $type->code,
                'normal_balance' => $type->normal_balance,
                'charts_count' => $type->chart_accounts_count,
                'created_at' => $type->created_at?->toIso8601String(),
                'updated_at' => $type->updated_at?->toIso8601String(),
            ])->values(),
            'meta' => [
                'total' => $types->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $type = AccountType::withCount('chartAccounts')->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Account type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformType($type),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:account_types,code',
            'normal_balance' => 'required|string|max:10',
        ]);

        $type = AccountType::create($validated);
        $type->loadCount('chartAccounts');

        return response()->json([
            'success' => true,
            'message' => 'Account type created successfully',
            'data' => $this->transformType($type),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $type = AccountType::find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Account type not found',
            ], 404);
        }

        $validated = $request->validate([
            'type' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:account_types,code,' . $type->id,
            'normal_balance' => 'sometimes|required|string|max:10',
        ]);

        $type->update($validated);
        $type->loadCount('chartAccounts');

        return response()->json([
            'success' => true,
            'message' => 'Account type updated successfully',
            'data' => $this->transformType($type),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $type = AccountType::withCount('chartAccounts')->find($id);

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Account type not found',
            ], 404);
        }

        if ($type->chart_accounts_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete account type that has chart accounts assigned',
            ], 422);
        }

        $type->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account type deleted successfully',
        ]);
    }

    private function transformType(AccountType $type): array
    {
        return [
            'id' => $type->id,
            'type' => $type->type,
            'code' => $type->code,
            'normal_balance' => $type->normal_balance,
            'charts_count' => $type->chart_accounts_count,
            'created_at' => $type->created_at?->toIso8601String(),
            'updated_at' => $type->updated_at?->toIso8601String(),
        ];
    }
}
