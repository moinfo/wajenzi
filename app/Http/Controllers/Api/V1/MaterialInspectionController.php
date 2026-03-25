<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MaterialInspection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialInspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = MaterialInspection::with(['supplierReceiving.supplier', 'project', 'inspector'])
            ->orderBy('created_at', 'desc');

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $inspections = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $inspections->map(fn($i) => $this->formatInspection($i)),
            'meta' => [
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'per_page' => $inspections->perPage(),
                'total' => $inspections->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $inspection = MaterialInspection::with(['supplierReceiving.supplier', 'project', 'inspector'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatInspection($inspection, true),
        ]);
    }

    private function formatInspection($inspection, bool $full = false): array
    {
        return [
            'id' => $inspection->id,
            'inspection_number' => $inspection->inspection_number,
            'supplier_receiving_id' => $inspection->supplier_receiving_id,
            'supplier_receiving' => $inspection->supplierReceiving ? [
                'id' => $inspection->supplierReceiving->id,
                'supplier_name' => $inspection->supplierReceiving->supplier?->name ?? null,
            ] : null,
            'project_id' => $inspection->project_id,
            'project_name' => $inspection->project?->project_name ?? null,
            'inspector_id' => $inspection->inspector_id,
            'inspector_name' => $inspection->inspector?->name ?? null,
            'inspection_date' => $inspection->inspection_date?->format('Y-m-d'),
            'quantity_delivered' => $inspection->quantity_delivered,
            'quantity_accepted' => $inspection->quantity_accepted,
            'quantity_rejected' => $inspection->quantity_rejected,
            'status' => $inspection->status,
            'overall_condition' => $inspection->overall_condition,
            'overall_result' => $inspection->overall_result,
            'notes' => $inspection->inspection_notes,
            'created_at' => $inspection->created_at?->toISOString(),
        ];
    }
}
