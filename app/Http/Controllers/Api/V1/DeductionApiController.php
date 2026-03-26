<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeductionApiController extends Controller
{
    public function index(): JsonResponse
    {
        $deductions = Deduction::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $deductions->map(fn (Deduction $deduction) => $this->transformDeduction($deduction))->values(),
            'meta' => [
                'total' => $deductions->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'natures' => [
                    ['name' => 'GROSS'],
                    ['name' => 'NET'],
                    ['name' => 'TAXABLE'],
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $deduction = Deduction::find($id);

        if (!$deduction) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformDeduction($deduction),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nature' => 'required|in:GROSS,NET,TAXABLE',
            'name' => 'required|string|max:255|unique:deductions,name',
            'abbreviation' => 'required|string|max:255|unique:deductions,abbreviation',
            'description' => 'nullable|string',
            'registration_number' => 'nullable|string|max:255',
        ]);

        $deduction = Deduction::create([
            'nature' => $validated['nature'],
            'name' => $validated['name'],
            'abbreviation' => strtoupper($validated['abbreviation']),
            'description' => $validated['description'] ?? null,
            'registration_number' => $validated['registration_number'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deduction created successfully',
            'data' => $this->transformDeduction($deduction),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $deduction = Deduction::find($id);

        if (!$deduction) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction not found',
            ], 404);
        }

        $validated = $request->validate([
            'nature' => 'required|in:GROSS,NET,TAXABLE',
            'name' => 'required|string|max:255|unique:deductions,name,' . $id,
            'abbreviation' => 'required|string|max:255|unique:deductions,abbreviation,' . $id,
            'description' => 'nullable|string',
            'registration_number' => 'nullable|string|max:255',
        ]);

        $deduction->update([
            'nature' => $validated['nature'],
            'name' => $validated['name'],
            'abbreviation' => strtoupper($validated['abbreviation']),
            'description' => $validated['description'] ?? null,
            'registration_number' => $validated['registration_number'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Deduction updated successfully',
            'data' => $this->transformDeduction($deduction),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deduction = Deduction::find($id);

        if (!$deduction) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction not found',
            ], 404);
        }

        $deduction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deduction deleted successfully',
        ]);
    }

    private function transformDeduction(Deduction $deduction): array
    {
        return [
            'id' => $deduction->id,
            'nature' => $deduction->nature,
            'name' => $deduction->name,
            'abbreviation' => $deduction->abbreviation,
            'description' => $deduction->description,
            'registration_number' => $deduction->registration_number,
            'created_at' => $deduction->created_at?->toISOString(),
            'updated_at' => $deduction->updated_at?->toISOString(),
        ];
    }
}
