<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StaffBankDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffBankDetailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = StaffBankDetail::with(['staff', 'bank'])
            ->orderBy('created_at', 'desc');

        $staffBankDetails = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $staffBankDetails->map(fn($d) => [
                'id' => $d->id,
                'staff_id' => $d->staff_id,
                'staff_name' => $d->staff?->name ?? null,
                'bank_id' => $d->bank_id,
                'bank_name' => $d->bank?->name ?? null,
                'account_number' => $d->account_number,
                'branch' => $d->branch,
                'created_at' => $d->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $staffBankDetails->currentPage(),
                'last_page' => $staffBankDetails->lastPage(),
                'per_page' => $staffBankDetails->perPage(),
                'total' => $staffBankDetails->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $detail = StaffBankDetail::with(['staff', 'bank'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $detail->id,
                'staff_id' => $detail->staff_id,
                'staff_name' => $detail->staff?->name ?? null,
                'bank_id' => $detail->bank_id,
                'bank_name' => $detail->bank?->name ?? null,
                'account_number' => $detail->account_number,
                'branch' => $detail->branch,
                'created_at' => $detail->created_at?->toISOString(),
            ],
        ]);
    }
}
