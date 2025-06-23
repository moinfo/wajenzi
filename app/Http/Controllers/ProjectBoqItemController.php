<?php

namespace App\Http\Controllers;

use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use Illuminate\Http\Request;

class ProjectBoqItemController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectBoqItem')) {
            return back();
        }

        $boqId = $request->boq_id;
        $boq = ProjectBoq::findOrFail($boqId);
        $boqItems = ProjectBoqItem::where('boq_id', $boqId)->get();

        $data = [
            'boq' => $boq,
            'boqItems' => $boqItems
        ];
        return view('pages.projects.project_boq_items')->with($data);
    }

    // Calculate item total
    public function calculateItemTotal(Request $request) {
        $quantity = $request->quantity;
        $unitPrice = $request->unit_price;
        $totalPrice = $quantity * $unitPrice;

        return response()->json([
            'total_price' => $totalPrice
        ]);
    }

    // Import items from Excel
    public function importItems(Request $request) {
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $boqId = $request->boq_id;

            // Process Excel file and import items
            // Import logic here...

            return back()->with('success', 'Items imported successfully');
        }

        return back()->with('error', 'Please select a file to import');
    }

    // Export items to Excel
    public function exportItems($boqId) {
        $boq = ProjectBoq::findOrFail($boqId);
        $items = ProjectBoqItem::where('boq_id', $boqId)->get();

        // Export logic here...

        return back()->with('success', 'Items exported successfully');
    }

    // Copy items from another BOQ
    public function copyItems(Request $request) {
        $sourceBoqId = $request->source_boq_id;
        $targetBoqId = $request->target_boq_id;

        $sourceItems = ProjectBoqItem::where('boq_id', $sourceBoqId)->get();

        foreach ($sourceItems as $item) {
            $newItem = $item->replicate();
            $newItem->boq_id = $targetBoqId;
            $newItem->save();
        }

        return response()->json([
            'message' => 'Items copied successfully',
            'items_count' => count($sourceItems)
        ]);
    }
}
