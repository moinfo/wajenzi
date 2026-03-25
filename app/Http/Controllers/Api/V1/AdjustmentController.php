<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Adjustment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdjustmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Adjustment::with(['staff', 'payroll'])
            ->orderBy('created_at', 'desc');

        if ($request->staff_id) {
            $query->where('staff_id', $request->staff_id);
        }

        $adjustments = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $adjustments->map(fn($a) => [
                'id' => $a->id,
                'staff_id' => $a->staff_id,
                'staff_name' => $a->staff?->name ?? null,
                'payroll_id' => $a->payroll_id,
                'adjustment_type' => $a->adjustment_type,
                'amount' => $a->amount,
                'description' => $a->description,
                'created_at' => $a->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $adjustments->currentPage(),
                'last_page' => $adjustments->lastPage(),
                'per_page' => $adjustments->perPage(),
                'total' => $adjustments->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $adjustment = Adjustment::with(['staff', 'payroll'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $adjustment->id,
                'staff_id' => $adjustment->staff_id,
                'staff_name' => $adjustment->staff?->name ?? null,
                'payroll_id' => $adjustment->payroll_id,
                'adjustment_type' => $adjustment->adjustment_type,
                'amount' => $adjustment->amount,
                'description' => $adjustment->description,
                'created_at' => $adjustment->created_at?->toISOString(),
            ],
        ]);
    }
}
