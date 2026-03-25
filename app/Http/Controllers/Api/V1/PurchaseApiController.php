<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PurchaseApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Purchase::with(['supplier', 'item', 'approvalStatus']);

            if ($request->start_date) {
                $query->where('date', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->where('date', '<=', $request->end_date);
            }

            if ($request->supplier_id) {
                $query->where('supplier_id', $request->supplier_id);
            }

            $purchases = $query->orderBy('date', 'desc')
                ->paginate($request->per_page ?? 20);

            $items = collect($purchases->items())->map(fn($p) => $this->formatPurchase($p));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $purchases->currentPage(),
                        'last_page' => $purchases->lastPage(),
                        'per_page' => $purchases->perPage(),
                        'total' => $purchases->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Purchase index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch purchases: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $purchase = Purchase::with(['supplier', 'item', 'project', 'approvalStatus'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatPurchase($purchase, true),
            ]);
        } catch (\Throwable $e) {
            Log::error('Purchase show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch purchase: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function suppliers(): JsonResponse
    {
        try {
            $suppliers = Supplier::orderBy('name')->get()->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'vrn' => $s->vrn ?? null,
            ]);

            return response()->json([
                'success' => true,
                'data' => $suppliers,
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suppliers: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'item_id' => 'nullable|exists:items,id',
                'project_id' => 'nullable|exists:projects,id',
                'date' => 'required|date',
                'tax_invoice' => 'nullable|string|max:255',
                'invoice_date' => 'nullable|date',
                'total_amount' => 'required|numeric|min:0',
                'amount_vat_exc' => 'nullable|numeric|min:0',
                'vat_amount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|max:5120',
            ]);

            if ($request->hasFile('file')) {
                $validated['file'] = $request->file('file')->store('purchases', 'public');
            }

            // Handle nullable fields
            if (empty($validated['invoice_date'])) {
                $validated['invoice_date'] = $validated['date'];
            }
            if (empty($validated['amount_vat_exc'])) {
                $validated['amount_vat_exc'] = $validated['total_amount'];
            }
            if (empty($validated['vat_amount'])) {
                $validated['vat_amount'] = $validated['total_amount'] - $validated['amount_vat_exc'];
            }

            $validated['create_by_id'] = $request->user()->id;
            $validated['status'] = 'PENDING';

            $purchase = Purchase::create($validated);
            $purchase->load(['supplier', 'item']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase created successfully.',
                'data' => $this->formatPurchase($purchase),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Purchase store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create purchase: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $purchase = Purchase::findOrFail($id);

            $validated = $request->validate([
                'supplier_id' => 'sometimes|exists:suppliers,id',
                'item_id' => 'nullable|exists:items,id',
                'project_id' => 'nullable|exists:projects,id',
                'date' => 'sometimes|date',
                'tax_invoice' => 'nullable|string|max:255',
                'invoice_date' => 'nullable|date',
                'total_amount' => 'sometimes|numeric|min:0',
                'amount_vat_exc' => 'nullable|numeric|min:0',
                'vat_amount' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|max:5120',
            ]);

            if ($request->hasFile('file')) {
                $validated['file'] = $request->file('file')->store('purchases', 'public');
            }

            $purchase->update($validated);
            $purchase->load(['supplier', 'item']);

            return response()->json([
                'success' => true,
                'message' => 'Purchase updated successfully.',
                'data' => $this->formatPurchase($purchase),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Purchase update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update purchase: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $purchase = Purchase::findOrFail($id);
            $purchase->delete();

            return response()->json([
                'success' => true,
                'message' => 'Purchase deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Purchase destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete purchase: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatPurchase($purchase, bool $detailed = false)
    {
        $data = [
            'id' => $purchase->id,
            'supplier_id' => $purchase->supplier_id,
            'item_id' => $purchase->item_id,
            'project_id' => $purchase->project_id,
            'date' => $purchase->date,
            'tax_invoice' => $purchase->tax_invoice,
            'invoice_date' => $purchase->invoice_date,
            'total_amount' => $purchase->total_amount,
            'amount_vat_exc' => $purchase->amount_vat_exc,
            'vat_amount' => $purchase->vat_amount,
            'notes' => $purchase->notes,
            'status' => $purchase->approvalStatus?->status ?? 'PENDING',
            'document_number' => $purchase->document_number,
            'created_at' => $purchase->created_at?->toISOString(),
        ];

        if ($purchase->relationLoaded('supplier') && $purchase->supplier) {
            $data['supplier'] = [
                'id' => $purchase->supplier->id,
                'name' => $purchase->supplier->name,
                'vrn' => $purchase->supplier->vrn ?? null,
            ];
        }

        if ($purchase->relationLoaded('item') && $purchase->item) {
            $data['item'] = [
                'id' => $purchase->item->id,
                'name' => $purchase->item->name,
            ];
        }

        if ($purchase->relationLoaded('project') && $purchase->project) {
            $data['project'] = [
                'id' => $purchase->project->id,
                'name' => $purchase->project->project_name ?? $purchase->project->name,
            ];
        }

        if ($detailed) {
            $data['file'] = $purchase->file;
            $data['expected_delivery_date'] = $purchase->expected_delivery_date;
            $data['delivery_address'] = $purchase->delivery_address;
            $data['payment_terms'] = $purchase->payment_terms;
        }

        return $data;
    }
}
