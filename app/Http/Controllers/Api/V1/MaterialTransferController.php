<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ExpensesSubCategory;
use App\Models\MaterialTransfer;
use App\Models\MaterialTransferItem;
use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\ProjectMaterialRequest;
use App\Models\ProjectStockItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RingleSoft\LaravelProcessApproval\Enums\ApprovalStatusEnum;

/**
 * Mobile API for inter-site material transfers.
 * Mirrors web {@see \App\Http\Controllers\MaterialTransferController}.
 */
class MaterialTransferController extends Controller
{
    /**
     * GET /api/v1/material-transfers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = MaterialTransfer::with([
                    'fromProject:id,project_name,document_number',
                    'toProject:id,project_name,document_number',
                    'requester:id,name',
                    'approver:id,name',
                    'approvalStatus',
                    'items',
                ])
                ->orderByDesc('id');

            if ($request->filled('from_project_id')) {
                $query->where('from_project_id', $request->from_project_id);
            }
            if ($request->filled('to_project_id')) {
                $query->where('to_project_id', $request->to_project_id);
            }
            if ($request->filled('status')) {
                // Accept "pending|approved|rejected" — match case-insensitively.
                $query->whereRaw('UPPER(status) = ?', [strtoupper($request->status)]);
            }
            if ($request->filled('my_requests')) {
                $query->where('requester_id', $request->user()->id);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transfer_number', 'like', "%{$search}%")
                      ->orWhere('vehicle_info', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('fromProject', fn ($p) => $p->where('project_name', 'like', "%{$search}%"))
                      ->orWhereHas('toProject',   fn ($p) => $p->where('project_name', 'like', "%{$search}%"));
                });
            }

            $perPage = (int) ($request->per_page ?? 20);
            $transfers = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $transfers->getCollection()->map(fn (MaterialTransfer $t) => $this->transform($t))->values(),
                    'meta' => [
                        'current_page' => $transfers->currentPage(),
                        'last_page'    => $transfers->lastPage(),
                        'per_page'     => $transfers->perPage(),
                        'total'        => $transfers->total(),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialTransfer index error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material transfers: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/material-transfers/reference-data
     * Reference lists for the create form (projects, expense sub-categories,
     * pending material requests). BOQ + free-stock items are fetched lazily
     * via ?from_project_id when set.
     */
    public function referenceData(Request $request): JsonResponse
    {
        $fromProjectId = $request->query('from_project_id');

        $sourceBoqItems = $fromProjectId
            ? ProjectBoqItem::select(['id', 'item_code', 'description', 'unit', 'specification', 'quantity_received', 'quantity_used', 'boq_id'])
                ->whereHas('boq', fn ($q) => $q->where('project_id', $fromProjectId))
                ->get()
                ->filter(fn ($i) => max(0, ((float) $i->quantity_received) - ((float) $i->quantity_used)) > 0)
                ->map(fn ($i) => [
                    'id'                => $i->id,
                    'item_code'         => $i->item_code,
                    'description'       => $i->description,
                    'unit'              => $i->unit,
                    'specification'     => $i->specification,
                    'available_quantity'=> (float) max(0, ((float) $i->quantity_received) - ((float) $i->quantity_used)),
                ])
                ->values()
            : collect();

        $sourceStockItems = $fromProjectId
            ? ProjectStockItem::where('project_id', $fromProjectId)
                ->where('quantity_on_hand', '>', 0)
                ->orderBy('description')
                ->get(['id', 'item_code', 'description', 'unit', 'quantity_on_hand', 'notes'])
                ->map(fn ($i) => [
                    'id'                => $i->id,
                    'item_code'         => $i->item_code,
                    'description'       => $i->description,
                    'unit'              => $i->unit,
                    'available_quantity'=> (float) $i->quantity_on_hand,
                    'notes'             => $i->notes,
                ])
            : collect();

        return response()->json([
            'success' => true,
            'data' => [
                'projects' => Project::orderBy('project_name')->get(['id', 'project_name', 'document_number'])->map(fn ($p) => [
                    'id'              => $p->id,
                    'name'            => $p->project_name,
                    'document_number' => $p->document_number,
                ]),
                'expenses_sub_categories' => ExpensesSubCategory::orderBy('name')->get(['id', 'name']),
                'pending_material_requests' => ProjectMaterialRequest::whereRaw("UPPER(status) NOT IN ('APPROVED','COMPLETED','REJECTED')")
                    ->orWhereNull('status')
                    ->orderByDesc('id')
                    ->limit(200)
                    ->get(['id', 'request_number', 'project_id', 'status'])
                    ->map(fn ($r) => [
                        'id'             => $r->id,
                        'request_number' => $r->request_number,
                        'project_id'     => $r->project_id,
                        'status'         => $r->status,
                    ]),
                'source_boq_items'   => $sourceBoqItems,
                'source_stock_items' => $sourceStockItems,
            ],
        ]);
    }

    /**
     * GET /api/v1/material-transfers/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $transfer = MaterialTransfer::with([
                'fromProject:id,project_name,document_number',
                'toProject:id,project_name,document_number',
                'requester:id,name',
                'approver:id,name',
                'approvalStatus',
                'items.sourceBoqItem:id,item_code,description,unit',
                'items.destinationBoqItem:id,item_code,description,unit',
                'items.sourceStockItem:id,item_code,description,unit',
                'items.destinationStockItem:id,item_code,description,unit',
                'expensesSubCategory:id,name',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->transform($transfer, full: true),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialTransfer show error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch material transfer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-transfers
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'from_project_id' => 'required|exists:projects,id|different:to_project_id',
                'to_project_id'   => 'required|exists:projects,id',
                'transfer_date'   => 'required|date',
                'expected_arrival_date'    => 'nullable|date|after_or_equal:transfer_date',
                'loading_cost'             => 'nullable|numeric|min:0',
                'offloading_cost'          => 'nullable|numeric|min:0',
                'transportation_cost'      => 'nullable|numeric|min:0',
                'expenses_sub_category_id' => 'nullable|exists:expenses_sub_categories,id',
                'material_request_id'      => 'nullable|exists:project_material_requests,id',
                'vehicle_info'             => 'nullable|string|max:255',
                'notes'                    => 'nullable|string',
                'items'                            => 'required|array|min:1',
                'items.*.source_boq_item_id'       => 'nullable|exists:project_boq_items,id',
                'items.*.source_stock_item_id'     => 'nullable|exists:project_stock_items,id',
                'items.*.destination_boq_item_id'  => 'nullable|exists:project_boq_items,id',
                'items.*.destination_stock_item_id'=> 'nullable|exists:project_stock_items,id',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity'    => 'required|numeric|min:0.01',
                'items.*.unit'        => 'required|string|max:50',
            ]);

            // Source stock check — cannot transfer more than what's on hand.
            foreach ($validated['items'] as $i => $item) {
                if (!empty($item['source_boq_item_id'])) {
                    $src = ProjectBoqItem::find($item['source_boq_item_id']);
                    $available = max(0, ((float) $src->quantity_received) - ((float) $src->quantity_used));
                    if ((float) $item['quantity'] > $available) {
                        return response()->json([
                            'success' => false,
                            'message' => "BOQ item {$src->item_code}: only {$available} {$src->unit} available.",
                            'errors'  => ["items.{$i}.quantity" => ['Quantity exceeds available stock.']],
                        ], 422);
                    }
                } elseif (!empty($item['source_stock_item_id'])) {
                    $src = ProjectStockItem::find($item['source_stock_item_id']);
                    if ($src && (float) $item['quantity'] > (float) $src->quantity_on_hand) {
                        return response()->json([
                            'success' => false,
                            'message' => "Stock item \"{$src->description}\": only {$src->quantity_on_hand} {$src->unit} on hand.",
                            'errors'  => ["items.{$i}.quantity" => ['Quantity exceeds available stock.']],
                        ], 422);
                    }
                }
            }

            $transfer = DB::transaction(function () use ($validated, $request) {
                $transfer = MaterialTransfer::create([
                    'from_project_id'          => $validated['from_project_id'],
                    'to_project_id'            => $validated['to_project_id'],
                    'material_request_id'      => $validated['material_request_id'] ?? null,
                    'requester_id'             => $request->user()->id,
                    'status'                   => 'pending',
                    'transfer_date'            => $validated['transfer_date'],
                    'expected_arrival_date'    => $validated['expected_arrival_date'] ?? null,
                    'loading_cost'             => $validated['loading_cost'] ?? 0,
                    'offloading_cost'          => $validated['offloading_cost'] ?? 0,
                    'transportation_cost'      => $validated['transportation_cost'] ?? 0,
                    'expenses_sub_category_id' => $validated['expenses_sub_category_id'] ?? null,
                    'vehicle_info'             => $validated['vehicle_info'] ?? null,
                    'notes'                    => $validated['notes'] ?? null,
                ]);

                foreach ($validated['items'] as $i => $item) {
                    $boqSrc   = !empty($item['source_boq_item_id'])   ? ProjectBoqItem::find($item['source_boq_item_id'])   : null;
                    $stockSrc = !empty($item['source_stock_item_id']) ? ProjectStockItem::find($item['source_stock_item_id']) : null;

                    MaterialTransferItem::create([
                        'material_transfer_id'      => $transfer->id,
                        'source_boq_item_id'        => $boqSrc?->id,
                        'source_stock_item_id'      => $stockSrc?->id,
                        'destination_boq_item_id'   => $item['destination_boq_item_id'] ?? null,
                        'destination_stock_item_id' => $item['destination_stock_item_id'] ?? null,
                        'description'               => $item['description'],
                        'quantity'                  => $item['quantity'],
                        'unit'                      => $item['unit'],
                        'specification'             => $boqSrc?->specification ?? $stockSrc?->notes,
                        'sort_order'                => $i,
                    ]);
                }

                return $transfer;
            });

            $transfer->load(['fromProject', 'toProject', 'requester', 'items', 'approvalStatus']);

            return response()->json([
                'success' => true,
                'message' => 'Material transfer created and submitted for approval.',
                'data'    => $this->transform($transfer, full: true),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('MaterialTransfer store error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create material transfer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/v1/material-transfers/{id}
     * Only allowed while the transfer is still pending and not approved.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $transfer = MaterialTransfer::findOrFail($id);
            if ($transfer->isApproved()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete an approved transfer.',
                ], 422);
            }
            $transfer->items()->delete();
            $transfer->delete();
            return response()->json([
                'success' => true,
                'message' => 'Material transfer deleted.',
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialTransfer destroy error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete material transfer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-transfers/{id}/approve
     * Marks the next pending approval step as approved (via RingleSoft).
     * If this completes the flow, the model's onApprovalCompleted runs and
     * moves stock between projects + auto-creates the expense.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        try {
            $transfer = MaterialTransfer::findOrFail($id);

            $ok = $transfer->approve($request->input('comment'));

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to approve this transfer at the current step.',
                ], 403);
            }

            $transfer->refresh()->load(['fromProject', 'toProject', 'requester', 'approver', 'items', 'approvalStatus']);

            return response()->json([
                'success' => true,
                'message' => 'Material transfer approved.',
                'data'    => $this->transform($transfer, full: true),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialTransfer approve error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve material transfer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/material-transfers/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate(['reason' => 'required|string|max:500']);

            $transfer = MaterialTransfer::findOrFail($id);
            $ok = $transfer->reject($request->reason);

            if ($ok === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to reject this transfer at the current step.',
                ], 403);
            }

            $transfer->refresh()->load(['fromProject', 'toProject', 'requester', 'items', 'approvalStatus']);

            return response()->json([
                'success' => true,
                'message' => 'Material transfer rejected.',
                'data'    => $this->transform($transfer, full: true),
            ]);
        } catch (\Throwable $e) {
            Log::error('MaterialTransfer reject error: '.$e->getMessage().' | '.$e->getFile().':'.$e->getLine());
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject material transfer: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Shape a MaterialTransfer for the API. `$full` toggles inclusion of items
     * + approval history (heavier; only used on show + after mutations).
     */
    private function transform(MaterialTransfer $t, bool $full = false): array
    {
        $approvalStatus = $t->approvalStatus?->status
            ?? (string) ($t->status ?? '');

        $base = [
            'id'                    => $t->id,
            'transfer_number'       => $t->transfer_number,
            'from_project_id'       => $t->from_project_id,
            'from_project_name'     => $t->fromProject?->project_name,
            'to_project_id'         => $t->to_project_id,
            'to_project_name'       => $t->toProject?->project_name,
            'material_request_id'   => $t->material_request_id,
            'requester_id'          => $t->requester_id,
            'requester_name'        => $t->requester?->name,
            'approved_by'           => $t->approved_by,
            'approver_name'         => $t->approver?->name,
            'status'                => $t->status,
            'approval_status'       => $approvalStatus,
            'transfer_date'         => optional($t->transfer_date)->format('Y-m-d'),
            'expected_arrival_date' => optional($t->expected_arrival_date)->format('Y-m-d'),
            'loading_cost'          => (float) $t->loading_cost,
            'offloading_cost'       => (float) $t->offloading_cost,
            'transportation_cost'   => (float) $t->transportation_cost,
            'total_cost'            => (float) $t->total_cost,
            'expenses_sub_category_id' => $t->expenses_sub_category_id,
            'vehicle_info'          => $t->vehicle_info,
            'notes'                 => $t->notes,
            'approved_date'         => optional($t->approved_date)->toIso8601String(),
            'item_count'            => $t->items?->count() ?? 0,
            'created_at'            => optional($t->created_at)->toIso8601String(),
        ];

        if (!$full) {
            return $base;
        }

        $base['items'] = $t->items->map(fn (MaterialTransferItem $i) => [
            'id'                         => $i->id,
            'source_boq_item_id'         => $i->source_boq_item_id,
            'source_boq_item_label'      => $i->sourceBoqItem
                ? trim(($i->sourceBoqItem->item_code ?? '').' '.($i->sourceBoqItem->description ?? ''))
                : null,
            'destination_boq_item_id'    => $i->destination_boq_item_id,
            'destination_boq_item_label' => $i->destinationBoqItem
                ? trim(($i->destinationBoqItem->item_code ?? '').' '.($i->destinationBoqItem->description ?? ''))
                : null,
            'source_stock_item_id'       => $i->source_stock_item_id,
            'source_stock_item_label'    => $i->sourceStockItem?->description,
            'destination_stock_item_id'  => $i->destination_stock_item_id,
            'description'                => $i->description,
            'quantity'                   => (float) $i->quantity,
            'unit'                       => $i->unit,
            'specification'              => $i->specification,
            'sort_order'                 => (int) $i->sort_order,
        ])->values();

        if ($t->relationLoaded('approvalStatus') && $t->approvalStatus) {
            $base['approval_steps'] = $t->approvalStatus->steps ?? [];
        }

        return $base;
    }
}
