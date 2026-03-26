<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Allowance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllowanceApiController extends Controller
{
    public function index(): JsonResponse
    {
        $allowances = Allowance::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $allowances->map(fn (Allowance $allowance) => $this->transform($allowance))->values(),
            'meta' => [
                'total' => $allowances->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'allowance_types' => [
                    ['name' => 'DAILY'],
                    ['name' => 'MONTHLY'],
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $allowance = Allowance::find($id);

        if (!$allowance) {
            return response()->json([
                'success' => false,
                'message' => 'Allowance not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transform($allowance),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:allowances,name',
            'allowance_type' => 'required|in:DAILY,MONTHLY',
            'description' => 'required|string',
        ]);

        $allowance = Allowance::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Allowance created successfully',
            'data' => $this->transform($allowance),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $allowance = Allowance::find($id);

        if (!$allowance) {
            return response()->json([
                'success' => false,
                'message' => 'Allowance not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:allowances,name,' . $id,
            'allowance_type' => 'required|in:DAILY,MONTHLY',
            'description' => 'required|string',
        ]);

        $allowance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Allowance updated successfully',
            'data' => $this->transform($allowance),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $allowance = Allowance::withCount('allowanceSubscriptions')->find($id);

        if (!$allowance) {
            return response()->json([
                'success' => false,
                'message' => 'Allowance not found',
            ], 404);
        }

        if (($allowance->allowance_subscriptions_count ?? 0) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete allowance that has staff subscriptions',
            ], 422);
        }

        $allowance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Allowance deleted successfully',
        ]);
    }

    private function transform(Allowance $allowance): array
    {
        return [
            'id' => $allowance->id,
            'name' => $allowance->name,
            'allowance_type' => $allowance->allowance_type,
            'description' => $allowance->description,
            'created_at' => $allowance->created_at?->toISOString(),
            'updated_at' => $allowance->updated_at?->toISOString(),
        ];
    }
}
