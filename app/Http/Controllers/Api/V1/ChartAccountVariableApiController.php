<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChartAccountVariable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartAccountVariableApiController extends Controller
{
    public function index(): JsonResponse
    {
        $variables = ChartAccountVariable::orderBy('variable')->get();

        return response()->json([
            'success' => true,
            'data' => $variables->map(fn (ChartAccountVariable $variable) => $this->transformVariable($variable))->values(),
            'meta' => [
                'total' => $variables->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $variable = ChartAccountVariable::find($id);

        if (!$variable) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account variable not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformVariable($variable),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variable' => 'required|string|max:255|unique:chart_account_variables,variable',
            'value' => 'required|string|max:255',
        ]);

        $variable = ChartAccountVariable::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Chart account variable created successfully',
            'data' => $this->transformVariable($variable),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $variable = ChartAccountVariable::find($id);

        if (!$variable) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account variable not found',
            ], 404);
        }

        $validated = $request->validate([
            'value' => 'required|string|max:255',
        ]);

        $variable->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Chart account variable updated successfully',
            'data' => $this->transformVariable($variable),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $variable = ChartAccountVariable::find($id);

        if (!$variable) {
            return response()->json([
                'success' => false,
                'message' => 'Chart account variable not found',
            ], 404);
        }

        $variable->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chart account variable deleted successfully',
        ]);
    }

    private function transformVariable(ChartAccountVariable $variable): array
    {
        return [
            'id' => $variable->id,
            'variable' => $variable->variable,
            'value' => $variable->value,
            'created_at' => $variable->created_at?->toIso8601String(),
            'updated_at' => $variable->updated_at?->toIso8601String(),
        ];
    }
}
