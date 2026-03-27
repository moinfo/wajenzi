<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Allowance;
use App\Models\AllowanceSubscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AllowanceSubscriptionApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $items = AllowanceSubscription::with(['staff:id,name', 'allowance:id,name'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (AllowanceSubscription $item) => $this->formatItem($item))->values(),
                'summary' => [
                    'total_amount' => (float) $items->sum('amount'),
                    'count' => $items->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AllowanceSubscription index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch allowance subscriptions',
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        try {
            $staffs = User::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']);
            $allowances = Allowance::orderBy('name')->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'staffs' => $staffs->map(fn ($staff) => [
                        'id' => $staff->id,
                        'name' => $staff->name,
                        'label' => $staff->name,
                        'value' => $staff->id,
                    ])->values(),
                    'allowances' => $allowances->map(fn ($allowance) => [
                        'id' => $allowance->id,
                        'name' => $allowance->name,
                        'label' => $allowance->name,
                        'value' => $allowance->id,
                    ])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AllowanceSubscription referenceData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch allowance subscription references',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validatePayload($request);
            $item = AllowanceSubscription::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->load(['staff:id,name', 'allowance:id,name'])),
                'message' => 'Allowance subscription created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('AllowanceSubscription store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create allowance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = AllowanceSubscription::with(['staff:id,name', 'allowance:id,name'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('AllowanceSubscription show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Allowance subscription not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = AllowanceSubscription::findOrFail($id);
            $validated = $this->validatePayload($request);
            $item->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->load(['staff:id,name', 'allowance:id,name'])),
                'message' => 'Allowance subscription updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('AllowanceSubscription update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update allowance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = AllowanceSubscription::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Allowance subscription deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('AllowanceSubscription destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete allowance subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'staff_id' => 'required|exists:users,id',
            'allowance_id' => 'required|exists:allowances,id',
            'amount' => 'required|numeric|min:0',
            'date' => 'required|date',
        ]);
    }

    private function formatItem(AllowanceSubscription $item): array
    {
        return [
            'id' => $item->id,
            'staff_id' => $item->staff_id,
            'staff_name' => $item->staff?->name,
            'allowance_id' => $item->allowance_id,
            'allowance_name' => $item->allowance?->name,
            'amount' => (float) ($item->amount ?? 0),
            'date' => $item->date,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
