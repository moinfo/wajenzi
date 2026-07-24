<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierQuotationResource;
use App\Models\ProjectMaterialRequest;
use App\Models\Supplier;
use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Mobile API for supplier quotations.
 * Mirrors web {@see \App\Http\Controllers\SupplierQuotationController}.
 */
class SupplierQuotationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!$request->user()->can('Supplier Quotations')) {
            return $this->permissionDenied();
        }

        try {
            $query = SupplierQuotation::with(['supplier', 'materialRequest.project', 'items'])
                ->orderByDesc('created_at');

            if ($request->filled('material_request_id')) {
                $query->where('material_request_id', $request->material_request_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('quotation_number', 'like', "%{$search}%")
                      ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                          $supplierQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('materialRequest', function ($mrQuery) use ($search) {
                          $mrQuery->where('request_number', 'like', "%{$search}%");
                      });
                });
            }

            $quotations = $query->paginate((int) ($request->per_page ?? 100));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => SupplierQuotationResource::collection($quotations->getCollection()),
                    'meta' => [
                        'current_page' => $quotations->currentPage(),
                        'last_page' => $quotations->lastPage(),
                        'per_page' => $quotations->perPage(),
                        'total' => $quotations->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch supplier quotations: '.$e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(Request $request): JsonResponse
    {
        if (!$request->user()->can('Supplier Quotations')) {
            return $this->permissionDenied();
        }

        $suppliers = Supplier::orderBy('name')->get(['id', 'name', 'vrn']);
        $materialRequests = ProjectMaterialRequest::with(['project', 'items'])
            ->whereRaw('UPPER(status) = ?', ['APPROVED'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($mr) => [
                'id' => $mr->id,
                'request_number' => $mr->request_number,
                'project_name' => $mr->project?->project_name ?? $mr->project?->name,
                'status' => $mr->status,
                'items' => $mr->items->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity_requested' => (float) $item->quantity_requested,
                    'quantity_approved' => $item->quantity_approved !== null ? (float) $item->quantity_approved : null,
                    'unit' => $item->unit,
                    'boq_item_id' => $item->boq_item_id,
                ])->values(),
            ])->values();

        return response()->json([
            'success' => true,
            'data' => [
                'suppliers' => $suppliers,
                'material_requests' => $materialRequests,
            ],
        ]);
    }

    /**
     * GET /procurement/supplier-quotations/available-suppliers/{materialRequestId}
     * Suppliers that do NOT yet have a quotation for the given request.
     */
    public function availableSuppliers(Request $request, int $materialRequestId): JsonResponse
    {
        if (!$request->user()->can('Supplier Quotations')) {
            return $this->permissionDenied();
        }

        $existingSupplierIds = SupplierQuotation::where('material_request_id', $materialRequestId)
            ->pluck('supplier_id')
            ->all();

        $suppliers = Supplier::when(!empty($existingSupplierIds), fn ($q) => $q->whereNotIn('id', $existingSupplierIds))
            ->orderBy('name')
            ->get(['id', 'name', 'vrn']);

        return response()->json([
            'success' => true,
            'data' => ['suppliers' => $suppliers],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Supplier Quotations')) {
            return $this->permissionDenied();
        }

        $quotation = SupplierQuotation::with(['supplier', 'materialRequest.project', 'items'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new SupplierQuotationResource($quotation),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$request->user()->can('Add Supplier Quotation')) {
            return $this->permissionDenied();
        }

        $validated = $this->validatePayload($request);

        try {
            $quotation = DB::transaction(function () use ($request, $validated) {
                return $this->persistQuotation(new SupplierQuotation(), $request, $validated);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier quotation: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Supplier quotation created successfully.',
            'data' => new SupplierQuotationResource($quotation->load(['supplier', 'materialRequest.project', 'items'])),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Edit Supplier Quotation')) {
            return $this->permissionDenied();
        }

        $quotation = SupplierQuotation::with('items')->findOrFail($id);

        if ($quotation->status !== 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Only quotations with status "received" can be edited.',
            ], 422);
        }

        $validated = $this->validatePayload($request);

        try {
            $quotation = DB::transaction(function () use ($quotation, $request, $validated) {
                return $this->persistQuotation($quotation, $request, $validated);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier quotation: '.$e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Supplier quotation updated successfully.',
            'data' => new SupplierQuotationResource($quotation->load(['supplier', 'materialRequest.project', 'items'])),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        if (!$request->user()->can('Delete Supplier Quotation')) {
            return $this->permissionDenied();
        }

        $quotation = SupplierQuotation::findOrFail($id);

        if ($quotation->status !== 'received') {
            return response()->json([
                'success' => false,
                'message' => 'Only quotations with status "received" can be deleted.',
            ], 422);
        }

        $quotation->items()->delete();
        $quotation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier quotation deleted successfully.',
        ]);
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'material_request_id' => 'required|exists:project_material_requests,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'delivery_time_days' => 'nullable|integer|min:0',
            'payment_terms' => 'nullable|string|max:255',
            'vat_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'file' => 'nullable|file|max:10240',
            'items' => 'required|array|min:1',
            'items.*.material_request_item_id' => 'required|exists:project_material_request_items,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.boq_item_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:100',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);
    }

    private function persistQuotation(SupplierQuotation $quotation, Request $request, array $validated): SupplierQuotation
    {
        $totalAmount = 0;
        $totalQuantity = 0;

        foreach ($validated['items'] as $item) {
            $totalAmount += ((float) $item['quantity']) * ((float) $item['unit_price']);
            $totalQuantity += (float) $item['quantity'];
        }

        $data = [
            'material_request_id' => $validated['material_request_id'],
            'supplier_id' => $validated['supplier_id'],
            'quotation_date' => $validated['quotation_date'],
            'valid_until' => $validated['valid_until'] ?? null,
            'delivery_time_days' => $validated['delivery_time_days'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'quantity' => $totalQuantity,
            'unit_price' => $totalQuantity > 0 ? $totalAmount / $totalQuantity : 0,
            'total_amount' => $totalAmount,
            'vat_amount' => $validated['vat_amount'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'status' => $quotation->exists ? $quotation->status : 'received',
        ];

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('quotations', 'public');
            $data['file'] = '/storage/'.$path;
        }

        if (!$quotation->exists) {
            $data['created_by'] = $request->user()->id;
            $quotation = SupplierQuotation::create($data);
        } else {
            $quotation->update($data);
            $quotation->items()->delete();
        }

        foreach ($validated['items'] as $index => $item) {
            SupplierQuotationItem::create([
                'supplier_quotation_id' => $quotation->id,
                'material_request_item_id' => $item['material_request_item_id'],
                'boq_item_id' => $item['boq_item_id'] ?? null,
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? null,
                'unit_price' => $item['unit_price'],
                'total_price' => ((float) $item['quantity']) * ((float) $item['unit_price']),
                'sort_order' => $index,
            ]);
        }

        return $quotation;
    }

    private function permissionDenied(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}
