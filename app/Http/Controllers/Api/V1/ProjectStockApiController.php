<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectStockItem;
use App\Models\ProjectStockReceipt;
use App\Models\ProjectStockReceiptItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Site stock and stock receipts, the same rules as the web's
 * ProjectStockItemController and ProjectStockReceiptController (the web checks
 * no permissions on these pages, so neither does this API).
 *
 *   GET    project-stock[?project_id=]            stock items (+ projects for the filter)
 *   POST   project-stock                          add a stock item
 *   PUT    project-stock/{id}                     edit a stock item
 *   DELETE project-stock/{id}                     delete a stock item
 *   GET    project-stock-receipts[?project_id=]   receipts
 *   GET    project-stock-receipts/{id}            one receipt with its items
 *   POST   project-stock-receipts                 record goods received (adds to stock)
 *   DELETE project-stock-receipts/{id}            delete a receipt (reverses the stock)
 */
class ProjectStockApiController extends Controller
{
    // ── stock items ────────────────────────────────────────────────────────

    public function items(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id');

        $items = ProjectStockItem::with('project:id,project_name')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderBy('project_id')
            ->orderBy('description')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn ($i) => $this->item($i))->values(),
            'meta' => ['total' => $items->count(), 'projects' => $this->projects()],
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'description' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'quantity_on_hand' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $item = ProjectStockItem::create($data + ['created_by_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'data' => $this->item($item->load('project:id,project_name')),
            'message' => 'Stock item added successfully.',
        ], 201);
    }

    public function updateItem(Request $request, int $id): JsonResponse
    {
        $item = ProjectStockItem::findOrFail($id);
        $data = $request->validate([
            'description' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'quantity_on_hand' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $item->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->item($item->fresh('project:id,project_name')),
            'message' => 'Stock item updated.',
        ]);
    }

    public function destroyItem(int $id): JsonResponse
    {
        ProjectStockItem::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Stock item deleted.']);
    }

    // ── receipts ───────────────────────────────────────────────────────────

    public function receipts(Request $request): JsonResponse
    {
        $projectId = $request->query('project_id');

        $receipts = ProjectStockReceipt::with(['project:id,project_name', 'createdBy:id,name', 'items'])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $receipts->map(fn ($r) => $this->receipt($r, false))->values(),
            'meta' => ['total' => $receipts->count(), 'projects' => $this->projects()],
        ]);
    }

    public function showReceipt(int $id): JsonResponse
    {
        $receipt = ProjectStockReceipt::with(['project:id,project_name', 'createdBy:id,name', 'items.stockItem'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->receipt($receipt, true)]);
    }

    public function storeReceipt(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'receipt_date' => 'required|date',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit' => 'required|string|max:50',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $receipt = DB::transaction(function () use ($request) {
            $receipt = ProjectStockReceipt::create([
                'project_id' => $request->project_id,
                'receipt_date' => $request->receipt_date,
                'supplier' => $request->supplier,
                'notes' => $request->notes,
                'created_by_id' => $request->user()->id,
            ]);

            foreach ($request->items as $i => $itemData) {
                $stockItemId = !empty($itemData['stock_item_id']) ? $itemData['stock_item_id'] : null;

                if ($stockItemId) {
                    // Add to the existing stock item
                    ProjectStockItem::where('id', $stockItemId)
                        ->increment('quantity_on_hand', (float) $itemData['quantity']);
                } else {
                    // Create a new stock item for this project
                    $stockItemId = ProjectStockItem::create([
                        'project_id' => $request->project_id,
                        'description' => $itemData['description'],
                        'unit' => $itemData['unit'],
                        'quantity_on_hand' => (float) $itemData['quantity'],
                        'created_by_id' => $request->user()->id,
                    ])->id;
                }

                ProjectStockReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'stock_item_id' => $stockItemId,
                    'description' => $itemData['description'],
                    'unit' => $itemData['unit'],
                    'quantity' => $itemData['quantity'],
                    'sort_order' => $i,
                ]);
            }

            return $receipt;
        });

        return response()->json([
            'success' => true,
            'data' => $this->receipt($receipt->load(['project:id,project_name', 'createdBy:id,name', 'items.stockItem']), true),
            'message' => 'Stock receipt saved and quantities updated.',
        ], 201);
    }

    public function destroyReceipt(int $id): JsonResponse
    {
        $receipt = ProjectStockReceipt::with('items')->findOrFail($id);

        DB::transaction(function () use ($receipt) {
            // Reverse the quantity additions
            foreach ($receipt->items as $item) {
                if ($item->stock_item_id) {
                    ProjectStockItem::where('id', $item->stock_item_id)
                        ->decrement('quantity_on_hand', (float) $item->quantity);
                }
            }
            $receipt->items()->delete();
            $receipt->delete();
        });

        return response()->json(['success' => true, 'message' => 'Receipt deleted and quantities reversed.']);
    }

    // ── formatting ─────────────────────────────────────────────────────────

    private function projects(): array
    {
        return Project::orderBy('project_name')->get(['id', 'project_name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->project_name])->values()->all();
    }

    private function item(ProjectStockItem $i): array
    {
        return [
            'id' => $i->id,
            'project_id' => $i->project_id,
            'project_name' => $i->project?->project_name,
            'item_code' => $i->item_code,
            'description' => $i->description,
            'unit' => $i->unit,
            'quantity_on_hand' => (float) $i->quantity_on_hand,
            'notes' => $i->notes,
        ];
    }

    private function receipt(ProjectStockReceipt $r, bool $withItems): array
    {
        $out = [
            'id' => $r->id,
            'receipt_number' => $r->receipt_number,
            'project_id' => $r->project_id,
            'project_name' => $r->project?->project_name,
            'receipt_date' => optional($r->receipt_date)->format('Y-m-d'),
            'supplier' => $r->supplier,
            'notes' => $r->notes,
            'received_by' => $r->createdBy?->name,
            'items_count' => $r->items->count(),
        ];

        if ($withItems) {
            $out['items'] = $r->items->map(fn ($it) => [
                'id' => $it->id,
                'stock_item_id' => $it->stock_item_id,
                'item_code' => $it->stockItem?->item_code,
                'description' => $it->description,
                'unit' => $it->unit,
                'quantity' => (float) $it->quantity,
            ])->values();
        }

        return $out;
    }
}
