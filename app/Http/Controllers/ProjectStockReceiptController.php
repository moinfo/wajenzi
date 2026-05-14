<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStockItem;
use App\Models\ProjectStockReceipt;
use App\Models\ProjectStockReceiptItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectStockReceiptController extends Controller
{
    public function index(Request $request)
    {
        $projectId = $request->query('project_id');

        $receipts = ProjectStockReceipt::with(['project', 'createdBy', 'items'])
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderBy('id', 'desc')
            ->get();

        return view('pages.procurement.project_stock_receipts', [
            'receipts'  => $receipts,
            'projects'  => Project::orderBy('project_name')->get(),
            'projectId' => $projectId,
        ]);
    }

    public function create(Request $request)
    {
        $projectId = $request->query('project_id');

        $stockItems = $projectId
            ? ProjectStockItem::where('project_id', $projectId)->orderBy('description')->get()
            : collect();

        return view('pages.procurement.project_stock_receipt_create', [
            'projects'   => Project::orderBy('project_name')->get(),
            'projectId'  => $projectId,
            'stockItems' => $stockItems,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id'   => 'required|exists:projects,id',
            'receipt_date' => 'required|date',
            'supplier'     => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
            'items'        => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.unit'        => 'required|string|max:50',
            'items.*.quantity'    => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request) {
            $receipt = ProjectStockReceipt::create([
                'project_id'    => $request->project_id,
                'receipt_date'  => $request->receipt_date,
                'supplier'      => $request->supplier,
                'notes'         => $request->notes,
                'created_by_id' => auth()->id(),
            ]);

            foreach ($request->items as $i => $itemData) {
                $stockItemId = !empty($itemData['stock_item_id']) ? $itemData['stock_item_id'] : null;

                if ($stockItemId) {
                    // Add to existing stock item
                    ProjectStockItem::where('id', $stockItemId)
                        ->increment('quantity_on_hand', (float) $itemData['quantity']);
                } else {
                    // Create new stock item for this project
                    $newStock = ProjectStockItem::create([
                        'project_id'       => $request->project_id,
                        'description'      => $itemData['description'],
                        'unit'             => $itemData['unit'],
                        'quantity_on_hand' => (float) $itemData['quantity'],
                        'created_by_id'    => auth()->id(),
                    ]);
                    $stockItemId = $newStock->id;
                }

                ProjectStockReceiptItem::create([
                    'receipt_id'    => $receipt->id,
                    'stock_item_id' => $stockItemId,
                    'description'   => $itemData['description'],
                    'unit'          => $itemData['unit'],
                    'quantity'      => $itemData['quantity'],
                    'sort_order'    => $i,
                ]);
            }
        });

        return redirect()->route('project_stock_receipts.index')
            ->with('success', 'Stock receipt saved and quantities updated.');
    }

    public function show($id)
    {
        $receipt = ProjectStockReceipt::with(['project', 'createdBy', 'items.stockItem'])
            ->findOrFail($id);

        return view('pages.procurement.project_stock_receipt_show', [
            'receipt' => $receipt,
        ]);
    }

    public function destroy($id)
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

        return back()->with('success', 'Receipt deleted and quantities reversed.');
    }

    // AJAX: return stock items for a given project
    public function stockItemsForProject(Request $request)
    {
        $items = ProjectStockItem::where('project_id', $request->project_id)
            ->orderBy('description')
            ->get(['id', 'item_code', 'description', 'unit', 'quantity_on_hand']);

        return response()->json($items);
    }
}
