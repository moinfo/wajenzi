<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deduction;
use App\Models\DeductionSubscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeductionSubscriptionApiController extends Controller
{
    public function index(): JsonResponse
    {
        $subscriptions = DeductionSubscription::with(['staff:id,name', 'deduction:id,name,abbreviation'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $subscriptions->map(fn (DeductionSubscription $subscription) => $this->transformSubscription($subscription))->values(),
            'meta' => [
                'total' => $subscriptions->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $staffs = User::onlyStaffs()->sortBy('name')->values();
        $deductions = Deduction::orderBy('name')->get(['id', 'name', 'abbreviation']);

        return response()->json([
            'success' => true,
            'data' => [
                'staffs' => $staffs->map(fn (User $staff) => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                ])->values(),
                'deductions' => $deductions->map(fn (Deduction $deduction) => [
                    'id' => $deduction->id,
                    'name' => $deduction->name,
                    'abbreviation' => $deduction->abbreviation,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $subscription = DeductionSubscription::with(['staff:id,name', 'deduction:id,name,abbreviation'])->find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction subscription not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSubscription($subscription),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'deduction_id' => 'required|exists:deductions,id',
            'membership_number' => 'nullable|string|max:255',
        ]);

        $exists = DeductionSubscription::where('staff_id', $validated['staff_id'])
            ->where('deduction_id', $validated['deduction_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This staff member is already subscribed to that deduction',
            ], 422);
        }

        $subscription = DeductionSubscription::create($validated);
        $subscription->load(['staff:id,name', 'deduction:id,name,abbreviation']);

        return response()->json([
            'success' => true,
            'message' => 'Deduction subscription created successfully',
            'data' => $this->transformSubscription($subscription),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $subscription = DeductionSubscription::with(['staff:id,name', 'deduction:id,name,abbreviation'])->find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction subscription not found',
            ], 404);
        }

        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'deduction_id' => 'required|exists:deductions,id',
            'membership_number' => 'nullable|string|max:255',
        ]);

        $exists = DeductionSubscription::where('staff_id', $validated['staff_id'])
            ->where('deduction_id', $validated['deduction_id'])
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This staff member is already subscribed to that deduction',
            ], 422);
        }

        $subscription->update($validated);
        $subscription->load(['staff:id,name', 'deduction:id,name,abbreviation']);

        return response()->json([
            'success' => true,
            'message' => 'Deduction subscription updated successfully',
            'data' => $this->transformSubscription($subscription),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $subscription = DeductionSubscription::find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Deduction subscription not found',
            ], 404);
        }

        $subscription->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deduction subscription deleted successfully',
        ]);
    }

    private function transformSubscription(DeductionSubscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'staff_id' => $subscription->staff_id,
            'staff_name' => $subscription->staff?->name,
            'deduction_id' => $subscription->deduction_id,
            'deduction_name' => $subscription->deduction?->name,
            'deduction_abbreviation' => $subscription->deduction?->abbreviation,
            'membership_number' => $subscription->membership_number,
            'created_at' => $subscription->created_at?->toISOString(),
            'updated_at' => $subscription->updated_at?->toISOString(),
        ];
    }
}
