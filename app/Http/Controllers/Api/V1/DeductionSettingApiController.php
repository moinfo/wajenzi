<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\DeductionSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeductionSettingApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = DeductionSetting::with(['deduction:id,name,abbreviation'])
                ->orderBy('deduction_id')
                ->orderBy('minimum_amount')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (DeductionSetting $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DeductionSetting index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deduction settings',
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        try {
            $deductions = Deduction::orderBy('name')->get(['id', 'name', 'abbreviation']);

            return response()->json([
                'success' => true,
                'data' => [
                    'deductions' => $deductions->map(fn ($deduction) => [
                        'id' => $deduction->id,
                        'name' => $deduction->name,
                        'abbreviation' => $deduction->abbreviation,
                        'label' => $deduction->name,
                        'value' => $deduction->id,
                    ])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('DeductionSetting referenceData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch deduction setting references',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validatePayload($request);
            $item = DeductionSetting::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->load(['deduction:id,name,abbreviation'])),
                'message' => 'Deduction setting created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('DeductionSetting store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create deduction setting: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = DeductionSetting::with(['deduction:id,name,abbreviation'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('DeductionSetting show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Deduction setting not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = DeductionSetting::findOrFail($id);
            $validated = $this->validatePayload($request);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->load(['deduction:id,name,abbreviation'])),
                'message' => 'Deduction setting updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('DeductionSetting update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update deduction setting: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = DeductionSetting::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Deduction setting deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('DeductionSetting destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete deduction setting',
            ], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'deduction_id' => 'required|exists:deductions,id',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|min:0',
            'employee_percentage' => 'nullable|numeric|min:0',
            'employer_percentage' => 'nullable|numeric|min:0',
            'additional_amount' => 'nullable|numeric|min:0',
        ]);
    }

    private function formatItem(DeductionSetting $item): array
    {
        return [
            'id' => $item->id,
            'deduction_id' => $item->deduction_id,
            'deduction_name' => $item->deduction?->name,
            'deduction_abbreviation' => $item->deduction?->abbreviation,
            'minimum_amount' => (float) ($item->minimum_amount ?? 0),
            'maximum_amount' => (float) ($item->maximum_amount ?? 0),
            'employee_percentage' => (float) ($item->employee_percentage ?? 0),
            'employer_percentage' => (float) ($item->employer_percentage ?? 0),
            'additional_amount' => (float) ($item->additional_amount ?? 0),
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
