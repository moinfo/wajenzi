<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StaffSalary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffSalaryApiController extends Controller
{
    public function index(): JsonResponse
    {
        $items = StaffSalary::with('staff:id,name')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn (StaffSalary $item) => $this->transform($item))->values(),
            'meta' => [
                'total' => $items->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $staffs = User::onlyStaffs()->sortBy('name')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'staffs' => $staffs->map(fn (User $staff) => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = StaffSalary::with('staff:id,name')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Staff salary not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transform($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $item = StaffSalary::create($validated);
        $item->load('staff:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Staff salary created successfully',
            'data' => $this->transform($item),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = StaffSalary::with('staff:id,name')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Staff salary not found',
            ], 404);
        }

        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $item->update($validated);
        $item->load('staff:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Staff salary updated successfully',
            'data' => $this->transform($item),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = StaffSalary::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Staff salary not found',
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff salary deleted successfully',
        ]);
    }

    private function transform(StaffSalary $item): array
    {
        return [
            'id' => $item->id,
            'staff_id' => $item->staff_id,
            'staff_name' => $item->staff?->name,
            'amount' => (float) $item->amount,
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }
}
