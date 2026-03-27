<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MaterialInspection;
use App\Models\SupplierReceiving;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MaterialInspectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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
            'data' => [
                'data' => collect($inspections->items())
                    ->map(fn($i) => $this->formatInspection($i))
                    ->values(),
                'pending_receivings' => SupplierReceiving::with(['supplier', 'purchase.project'])
                    ->pendingInspection()
                    ->orderByDesc('date')
                    ->limit(20)
                    ->get()
                    ->map(fn($receiving) => [
                        'id' => $receiving->id,
                        'receiving_number' => $receiving->receiving_number,
                        'supplier_name' => $receiving->supplier?->name,
                        'delivery_date' => $receiving->date?->format('Y-m-d'),
                        'quantity_delivered' => $receiving->quantity_delivered,
                        'condition' => $receiving->condition,
                        'purchase_number' => $receiving->purchase?->document_number ?? ('PO-' . $receiving->purchase_id),
                        'project_name' => $receiving->purchase?->project?->project_name
                            ?? $receiving->purchase?->project?->name,
                    ])
                    ->values(),
                'meta' => [
                    'current_page' => $inspections->currentPage(),
                    'last_page' => $inspections->lastPage(),
                    'per_page' => $inspections->perPage(),
                    'total' => $inspections->total(),
                ],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $inspection = MaterialInspection::with([
            'supplierReceiving.supplier',
            'supplierReceiving.purchase',
            'project',
            'boqItem',
            'inspector',
            'verifier',
        ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatInspection($inspection, true),
        ]);
    }

    private function formatInspection($inspection, bool $full = false): array
    {
        $data = [
            'id' => $inspection->id,
            'inspection_number' => $inspection->inspection_number,
            'supplier_receiving_id' => $inspection->supplier_receiving_id,
            'supplier_receiving' => $inspection->supplierReceiving ? [
                'id' => $inspection->supplierReceiving->id,
                'supplier_name' => $inspection->supplierReceiving->supplier?->name ?? null,
                'receiving_number' => $inspection->supplierReceiving->receiving_number,
                'delivery_note_number' => $inspection->supplierReceiving->delivery_note_number,
                'purchase_number' => $inspection->supplierReceiving->purchase?->document_number
                    ?? ($inspection->supplierReceiving->purchase_id
                        ? 'PO-' . $inspection->supplierReceiving->purchase_id
                        : null),
            ] : null,
            'project_id' => $inspection->project_id,
            'project_name' => $inspection->project?->project_name ?? $inspection->project?->name,
            'boq_item' => $inspection->boqItem ? [
                'id' => $inspection->boqItem->id,
                'description' => $inspection->boqItem->description,
                'item_code' => $inspection->boqItem->item_code,
            ] : null,
            'inspector_id' => $inspection->inspector_id,
            'inspector_name' => $inspection->inspector?->name ?? null,
            'inspection_date' => $inspection->inspection_date?->format('Y-m-d'),
            'quantity_delivered' => $inspection->quantity_delivered,
            'quantity_accepted' => $inspection->quantity_accepted,
            'quantity_rejected' => $inspection->quantity_rejected,
            'acceptance_rate' => $inspection->acceptance_rate,
            'status' => $inspection->status,
            'overall_condition' => $inspection->overall_condition,
            'overall_result' => $inspection->overall_result,
            'rejection_reason' => $inspection->rejection_reason,
            'notes' => $inspection->inspection_notes,
            'stock_updated' => $inspection->stock_updated,
            'created_at' => $inspection->created_at?->toISOString(),
        ];

        if ($full) {
            $data['verifier_name'] = $inspection->verifier?->name;
            $data['criteria_checklist'] = $inspection->criteria_checklist;
        }

        return $data;
    }
}
