<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\StaffBankDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffBankDetailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = StaffBankDetail::with(['staff', 'bank'])
            ->orderBy('created_at', 'desc');

        $staffBankDetails = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $staffBankDetails->getCollection()->map(fn (StaffBankDetail $detail) => $this->transformDetail($detail))->values(),
            'meta' => [
                'current_page' => $staffBankDetails->currentPage(),
                'last_page' => $staffBankDetails->lastPage(),
                'per_page' => $staffBankDetails->perPage(),
                'total' => $staffBankDetails->total(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $staffs = User::onlyStaffs()->sortBy('name')->values();
        $banks = Bank::orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'staffs' => $staffs->map(fn (User $staff) => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                ])->values(),
                'banks' => $banks->map(fn (Bank $bank) => [
                    'id' => $bank->id,
                    'name' => $bank->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $detail = StaffBankDetail::with(['staff', 'bank'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transformDetail($detail),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:255',
            'branch' => 'required|string',
        ]);

        $exists = StaffBankDetail::where('staff_id', $validated['staff_id'])->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A bank detail for this staff member already exists',
            ], 422);
        }

        $detail = StaffBankDetail::create($validated);
        $detail->load(['staff', 'bank']);

        return response()->json([
            'success' => true,
            'message' => 'Staff bank detail created successfully',
            'data' => $this->transformDetail($detail),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $detail = StaffBankDetail::with(['staff', 'bank'])->findOrFail($id);

        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'bank_id' => 'required|exists:banks,id',
            'account_number' => 'required|string|max:255',
            'branch' => 'required|string',
        ]);

        $exists = StaffBankDetail::where('staff_id', $validated['staff_id'])
            ->where('id', '!=', $id)
            ->exists();
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'A bank detail for this staff member already exists',
            ], 422);
        }

        $detail->update($validated);
        $detail->load(['staff', 'bank']);

        return response()->json([
            'success' => true,
            'message' => 'Staff bank detail updated successfully',
            'data' => $this->transformDetail($detail),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $detail = StaffBankDetail::findOrFail($id);
        $detail->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff bank detail deleted successfully',
        ]);
    }

    private function transformDetail(StaffBankDetail $detail): array
    {
        return [
            'id' => $detail->id,
            'staff_id' => $detail->staff_id,
            'staff_name' => $detail->staff?->name,
            'bank_id' => $detail->bank_id,
            'bank_name' => $detail->bank?->name,
            'account_number' => $detail->account_number,
            'branch' => $detail->branch,
            'created_at' => $detail->created_at?->toISOString(),
            'updated_at' => $detail->updated_at?->toISOString(),
        ];
    }
}
