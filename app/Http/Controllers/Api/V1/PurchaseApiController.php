<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\User;
use App\Models\SupplierReceiving;
use App\Models\Supplier;
use RingleSoft\LaravelProcessApproval\Models\ProcessApprovalFlowStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Purchase::with([
                'supplier',
                'item',
                'project',
                'materialRequest',
                'quotationComparison',
                'purchaseItems.boqItem',
                'user',
                'approvalStatus',
            ]);

            if ($request->start_date) {
                $query->where('date', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->where('date', '<=', $request->end_date);
            }

            if ($request->supplier_id) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->boolean('procurement_only')) {
                $query->whereNotNull('material_request_id');
            }

            $purchases = $query->orderBy('date', 'desc')->get();
            $items = collect($purchases)->map(fn($p) => $this->formatPurchase($p));
            $totals = [
                'total_amount' => (float) $items->sum(fn ($item) => (float) ($item['total_amount'] ?? 0)),
                'amount_vat_exc' => (float) $items->sum(fn ($item) => (float) ($item['amount_vat_exc'] ?? 0)),
                'vat_amount' => (float) $items->sum(fn ($item) => (float) ($item['vat_amount'] ?? 0)),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'purchases' => $items->values(),
                    'totals' => $totals,
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $items->count(),
                        'total' => $items->count(),
                    ],
                    // Legacy compatibility for older mobile parsing paths.
                    'data' => $items->values(),
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
            $purchase = Purchase::with([
                'supplier',
                'item',
                'project',
                'materialRequest',
                'quotationComparison',
                'purchaseItems.boqItem',
                'user',
                'approvalStatus',
                'approvals.user',
            ])->findOrFail($id);

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

    public function pendingDeliveries(Request $request): JsonResponse
    {
        try {
            $perPage = min((int) $request->integer('per_page', 100), 200);

            $purchases = Purchase::with([
                    'supplier',
                    'project',
                    'materialRequest',
                    'purchaseItems',
                    'approvalStatus',
                ])
                ->whereNotNull('material_request_id')
                ->where(function ($query) {
                    $query->whereRaw('UPPER(status) = ?', ['APPROVED'])
                        ->orWhereHas('approvalStatus', function ($approvalQuery) {
                            $approvalQuery->whereRaw('UPPER(status) = ?', ['APPROVED']);
                        });
                })
                ->orderByDesc('created_at')
                ->get()
                ->filter(fn ($purchase) => $purchase->purchaseItems->contains(
                    fn ($item) => !$item->isFullyReceived()
                ))
                ->values();

            $items = $purchases->take($perPage)->map(function ($purchase) {
                $totalItems = $purchase->purchaseItems->count();
                $fullyReceived = $purchase->purchaseItems->filter(
                    fn ($item) => $item->isFullyReceived()
                )->count();
                $partiallyReceived = $purchase->purchaseItems->filter(
                    fn ($item) => $item->isPartiallyReceived()
                )->count();

                return [
                    'id' => $purchase->id,
                    'document_number' => $purchase->document_number ?? 'PO-' . $purchase->id,
                    'project' => $purchase->project ? [
                        'id' => $purchase->project->id,
                        'name' => $purchase->project->project_name ?? $purchase->project->name,
                    ] : null,
                    'supplier' => $purchase->supplier ? [
                        'id' => $purchase->supplier->id,
                        'name' => $purchase->supplier->name,
                    ] : null,
                    'material_request' => $purchase->materialRequest ? [
                        'id' => $purchase->materialRequest->id,
                        'request_number' => $purchase->materialRequest->request_number,
                    ] : null,
                    'purchase_items_count' => $totalItems,
                    'fully_received_count' => $fullyReceived,
                    'partially_received_count' => $partiallyReceived,
                    'pending_count' => max(0, $totalItems - $fullyReceived),
                    'date' => $purchase->date,
                    'status' => strtoupper($purchase->approvalStatus?->status ?? $purchase->status ?? 'PENDING'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items->values(),
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => $items->count(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Pending deliveries error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending deliveries: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeDelivery(Request $request, int $id): JsonResponse
    {
        try {
            $purchase = Purchase::with(['purchaseItems', 'supplier', 'project'])->findOrFail($id);

            $status = strtoupper($purchase->approvalStatus?->status ?? $purchase->status ?? 'PENDING');
            if ($status !== 'APPROVED' || $purchase->material_request_id === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only approved purchase orders can receive deliveries.',
                ], 422);
            }

            $validated = $request->validate([
                'delivery_note_number' => 'required|string|max:100',
                'date' => 'required|date',
                'condition' => 'required|in:good,damaged,partial_damage',
                'description' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.purchase_item_id' => 'required|integer|exists:purchase_items,id',
                'items.*.quantity' => 'required|numeric|min:0',
            ]);

            $quantitiesByItemId = collect($validated['items'])
                ->mapWithKeys(fn ($item) => [(int) $item['purchase_item_id'] => (float) $item['quantity']]);

            $totalDelivered = 0.0;
            $totalOrdered = 0.0;

            foreach ($purchase->purchaseItems as $purchaseItem) {
                $quantity = $quantitiesByItemId[$purchaseItem->id] ?? 0.0;

                if ($quantity > $purchaseItem->quantity_pending) {
                    return response()->json([
                        'success' => false,
                        'message' => "Delivery quantity exceeds pending quantity for item {$purchaseItem->id}.",
                    ], 422);
                }

                $totalDelivered += $quantity;
                $totalOrdered += (float) $purchaseItem->quantity;
            }

            if ($totalDelivered <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one item must have a delivery quantity greater than zero.',
                ], 422);
            }

            DB::transaction(function () use ($request, $purchase, $quantitiesByItemId, $totalDelivered, $totalOrdered) {
                $receiving = SupplierReceiving::create([
                    'purchase_id' => $purchase->id,
                    'project_id' => $purchase->project_id,
                    'supplier_id' => $purchase->supplier_id,
                    'received_by' => $request->user()->id,
                    'delivery_note_number' => $request->string('delivery_note_number')->toString(),
                    'date' => $request->input('date'),
                    'condition' => $request->string('condition')->toString(),
                    'description' => $request->input('description'),
                    'quantity_ordered' => $totalOrdered,
                    'quantity_delivered' => $totalDelivered,
                    'status' => 'pending',
                ]);

                if ($request->hasFile('file')) {
                    $receiving->file = $request->file('file')->store('delivery_notes', 'public');
                    $receiving->save();
                }

                foreach ($purchase->purchaseItems as $purchaseItem) {
                    $quantity = $quantitiesByItemId[$purchaseItem->id] ?? 0.0;
                    if ($quantity > 0) {
                        $purchaseItem->recordReceiving($quantity);
                    }
                }
            });

            $updatedPurchase = Purchase::with([
                'supplier',
                'project',
                'materialRequest',
                'quotationComparison',
                'purchaseItems.boqItem',
                'user',
                'approvalStatus',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Delivery recorded successfully.',
                'data' => $this->formatPurchase($updatedPurchase, true),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Store delivery error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to record delivery: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function receivings(Request $request): JsonResponse
    {
        try {
            $query = SupplierReceiving::with([
                'purchase',
                'supplier',
                'project',
                'receivedBy',
                'inspections',
            ])
                ->whereNotNull('purchase_id')
                ->whereHas('purchase', fn ($purchaseQuery) => $purchaseQuery->whereNotNull('material_request_id'))
                ->orderByDesc('created_at');

            if ($request->start_date) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            $receivings = $query->paginate($request->per_page ?? 100);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => collect($receivings->items())
                        ->map(fn ($receiving) => $this->formatReceiving($receiving))
                        ->values(),
                    'meta' => [
                        'current_page' => $receivings->currentPage(),
                        'last_page' => $receivings->lastPage(),
                        'per_page' => $receivings->perPage(),
                        'total' => $receivings->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Receivings index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch receivings: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showReceiving(int $id): JsonResponse
    {
        try {
            $receiving = SupplierReceiving::with([
                'purchase.purchaseItems.boqItem',
                'supplier',
                'project',
                'receivedBy',
                'inspections',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatReceiving($receiving, true),
            ]);
        } catch (\Throwable $e) {
            Log::error('Receiving show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch receiving: ' . $e->getMessage(),
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
                'purchase_type' => 'nullable|in:1,2',
                'project_id' => 'nullable|exists:projects,id',
                'date' => 'required|date',
                'tax_invoice' => 'nullable|string|max:255',
                'invoice_date' => 'nullable|date',
                'total_amount' => 'required|numeric|min:0',
                'amount_vat_exc' => 'nullable|numeric|min:0',
                'vat_amount' => 'nullable|numeric|min:0',
                'is_expense' => 'nullable|in:YES,NO',
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
            $purchaseType = (int) ($validated['purchase_type'] ?? 1);
            $totalAmount = (float) $validated['total_amount'];
            $validated['amount_vat_exc'] = $purchaseType === 1 ? ($totalAmount * 100 / 118) : 0;
            $validated['vat_amount'] = $purchaseType === 1 ? ($validated['amount_vat_exc'] * 18 / 100) : 0;
            $validated['is_expense'] = $validated['is_expense'] ?? 'NO';

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
                'purchase_type' => 'nullable|in:1,2',
                'project_id' => 'nullable|exists:projects,id',
                'date' => 'sometimes|date',
                'tax_invoice' => 'nullable|string|max:255',
                'invoice_date' => 'nullable|date',
                'total_amount' => 'sometimes|numeric|min:0',
                'amount_vat_exc' => 'nullable|numeric|min:0',
                'vat_amount' => 'nullable|numeric|min:0',
                'is_expense' => 'nullable|in:YES,NO',
                'notes' => 'nullable|string',
                'file' => 'nullable|file|max:5120',
            ]);

            if ($request->hasFile('file')) {
                $validated['file'] = $request->file('file')->store('purchases', 'public');
            }

            $purchaseType = (int) ($validated['purchase_type'] ?? $purchase->purchase_type ?? 1);
            $totalAmount = (float) ($validated['total_amount'] ?? $purchase->total_amount ?? 0);
            $validated['amount_vat_exc'] = $purchaseType === 1 ? ($totalAmount * 100 / 118) : 0;
            $validated['vat_amount'] = $purchaseType === 1 ? ($validated['amount_vat_exc'] * 18 / 100) : 0;

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
        $status = strtoupper($purchase->approvalStatus?->status ?? $purchase->status ?? 'PENDING');
        $fileUrl = Purchase::resolveAttachmentUrl($purchase->file);
        $data = [
            'id' => $purchase->id,
            'supplier_id' => $purchase->supplier_id,
            'item_id' => $purchase->item_id,
            'purchase_type' => $purchase->purchase_type,
            'project_id' => $purchase->project_id,
            'date' => $purchase->date,
            'tax_invoice' => $purchase->tax_invoice,
            'invoice_date' => $purchase->invoice_date,
            'total_amount' => $purchase->total_amount,
            'amount_vat_exc' => $purchase->amount_vat_exc,
            'vat_amount' => $purchase->vat_amount,
            'notes' => $purchase->notes,
            'status' => $status,
            'approval_status' => $status,
            'approval_summary' => $this->approvalSummary($status),
            'document_number' => $purchase->document_number,
            'is_expense' => $purchase->is_expense,
            'has_attachment' => !empty($purchase->file),
            'file_url' => $fileUrl,
            'material_request_id' => $purchase->material_request_id,
            'quotation_comparison_id' => $purchase->quotation_comparison_id,
            'purchase_items_count' => $purchase->relationLoaded('purchaseItems')
                ? $purchase->purchaseItems->count()
                : null,
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

        $data['goods'] = $purchase->item?->name ?? ($purchase->document_number ? 'Project Purchase' : '-');

        if ($purchase->relationLoaded('project') && $purchase->project) {
            $data['project'] = [
                'id' => $purchase->project->id,
                'name' => $purchase->project->project_name ?? $purchase->project->name,
            ];
        }

        if ($purchase->relationLoaded('materialRequest') && $purchase->materialRequest) {
            $data['material_request'] = [
                'id' => $purchase->materialRequest->id,
                'request_number' => $purchase->materialRequest->request_number,
                'status' => $purchase->materialRequest->status,
            ];
        }

        if ($purchase->relationLoaded('quotationComparison') && $purchase->quotationComparison) {
            $data['quotation_comparison'] = [
                'id' => $purchase->quotationComparison->id,
                'comparison_number' => $purchase->quotationComparison->comparison_number,
                'status' => $purchase->quotationComparison->status,
            ];
        }

        if ($purchase->relationLoaded('user') && $purchase->user) {
            $data['user'] = [
                'id' => $purchase->user->id,
                'name' => $purchase->user->name,
            ];
        }

        if ($detailed) {
            $data['file'] = $purchase->file;
            $data['approval_page_url'] = url("/purchase/{$purchase->id}/3");
            $data['expected_delivery_date'] = $purchase->expected_delivery_date;
            $data['delivery_address'] = $purchase->delivery_address;
            $data['payment_terms'] = $purchase->payment_terms;
            $data['approval_flow'] = $this->buildApprovalFlow($purchase);
            $data['purchase_items'] = $purchase->relationLoaded('purchaseItems')
                ? $purchase->purchaseItems->map(fn ($item) => [
                    'id' => $item->id,
                    'description' => $item->description ?? $item->boqItem?->description ?? 'Item',
                    'unit' => $item->unit,
                    'quantity' => $item->quantity,
                    'quantity_received' => $item->quantity_received,
                    'quantity_pending' => $item->quantity_pending,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'material_name' => $item->boqItem?->description,
                    'boq_item' => $item->boqItem ? [
                        'id' => $item->boqItem->id,
                        'description' => $item->boqItem->description,
                    ] : null,
                ])->values()
                : [];
        }

        return $data;
    }

    private function buildApprovalFlow(Purchase $purchase): array
    {
        $steps = collect($purchase->approvalStatus?->steps ?? [])->map(function ($step) {
            $flowStep = ProcessApprovalFlowStep::with('role')->find($step['id']);
            $approval = !empty($step['process_approval_id'])
                ? $purchase->approvals->firstWhere('id', $step['process_approval_id'])
                : null;

            return [
                'step_id' => $step['id'] ?? null,
                'role_name' => $flowStep?->role?->name ?? ('Step ' . ($step['id'] ?? '')),
                'action' => $step['process_approval_action'] ?? 'Pending',
                'approver_name' => $approval?->user?->name ?? $approval?->approver_name,
                'date' => $approval?->created_at?->format('d F, Y'),
                'comment' => $approval?->comment,
            ];
        })->values();

        $nextStep = $purchase->nextApprovalStep();
        $isCompleted = $purchase->isApprovalCompleted();

        return [
            'status_label' => $isCompleted
                ? 'Approval completed!'
                : ($purchase->isSubmitted() ? 'In Progress' : 'Not Submitted'),
            'next_role_name' => $nextStep?->role?->name,
            'is_submitted' => $purchase->isSubmitted(),
            'is_completed' => $isCompleted,
            'can_be_submitted' => auth()->check() ? (bool) $purchase->canBeSubmittedBy(auth()->user()) : false,
            'can_be_approved' => auth()->check() ? (bool) $purchase->canBeApprovedBy(auth()->user()) : false,
            'steps' => $steps,
        ];
    }

    private function approvalSummary(string $status): string
    {
        return match ($status) {
            'PENDING', 'CREATED' => 'Waiting for submission/approval',
            'SUBMITTED' => 'Submitted into approval workflow',
            'APPROVED', 'COMPLETED' => 'Approval completed',
            'REJECTED' => 'Rejected in approval workflow',
            'DISCARDED' => 'Discarded from approval workflow',
            'PAID' => 'Processed and paid',
            default => $status,
        };
    }

    private function formatReceiving(SupplierReceiving $receiving, bool $detailed = false): array
    {
        $purchase = $receiving->purchase;
        $purchaseItems = $purchase?->purchaseItems ?? collect();
        $fullyReceived = $purchaseItems->filter(fn ($item) => $item->isFullyReceived())->count();
        $partiallyReceived = $purchaseItems->filter(fn ($item) => $item->isPartiallyReceived())->count();

        $data = [
            'id' => $receiving->id,
            'receiving_number' => $receiving->receiving_number,
            'purchase_id' => $receiving->purchase_id,
            'delivery_note_number' => $receiving->delivery_note_number,
            'date' => $receiving->date?->format('Y-m-d'),
            'quantity_ordered' => $receiving->quantity_ordered,
            'quantity_delivered' => $receiving->quantity_delivered,
            'condition' => $receiving->condition,
            'status' => $receiving->status,
            'description' => $receiving->description,
            'file' => $receiving->file,
            'purchase_items_count' => $purchaseItems->count(),
            'fully_received_count' => $fullyReceived,
            'partially_received_count' => $partiallyReceived,
            'pending_count' => max(0, $purchaseItems->count() - $fullyReceived),
            'needs_inspection' => $receiving->needsInspection(),
            'has_inspection' => $receiving->hasInspection(),
            'created_at' => $receiving->created_at?->toISOString(),
        ];

        if ($receiving->relationLoaded('supplier') && $receiving->supplier) {
            $data['supplier'] = [
                'id' => $receiving->supplier->id,
                'name' => $receiving->supplier->name,
            ];
        }

        if ($receiving->relationLoaded('project') && $receiving->project) {
            $data['project'] = [
                'id' => $receiving->project->id,
                'name' => $receiving->project->project_name ?? $receiving->project->name,
            ];
        }

        if ($receiving->relationLoaded('purchase') && $receiving->purchase) {
            $data['purchase'] = [
                'id' => $receiving->purchase->id,
                'document_number' => $receiving->purchase->document_number ?? 'PO-' . $receiving->purchase->id,
            ];
        }

        if ($receiving->relationLoaded('receivedBy') && $receiving->receivedBy) {
            $data['received_by'] = [
                'id' => $receiving->receivedBy->id,
                'name' => $receiving->receivedBy->name,
            ];
        }

        if ($detailed) {
            $data['purchase_items'] = $purchaseItems->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'quantity_received' => $item->quantity_received,
                'status' => $item->status,
                'boq_item' => $item->boqItem ? [
                    'id' => $item->boqItem->id,
                    'description' => $item->boqItem->description,
                    'item_code' => $item->boqItem->item_code,
                ] : null,
            ])->values();

            $data['inspections'] = $receiving->relationLoaded('inspections')
                ? $receiving->inspections->map(fn ($inspection) => [
                    'id' => $inspection->id,
                    'inspection_number' => $inspection->inspection_number,
                    'inspection_date' => $inspection->inspection_date?->format('Y-m-d'),
                    'quantity_accepted' => $inspection->quantity_accepted,
                    'overall_result' => $inspection->overall_result,
                    'status' => $inspection->status,
                ])->values()
                : [];
        }

        return $data;
    }
}
