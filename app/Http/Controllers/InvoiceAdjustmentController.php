<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\InvoiceAdjustment;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceAdjustmentController extends Controller
{
    /**
     * Store a new adjustment
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'receipt_id' => 'required|exists:receipts,id',
                'type' => 'required|string|max:50',
                'description' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0'
            ]);

            $adjustment = InvoiceAdjustment::create($request->all());

            return response()->json([
                'message' => 'Adjustment added successfully',
                'adjustment' => $adjustment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add adjustment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an adjustment
     */
    public function destroy(InvoiceAdjustment $adjustment)
    {
        try {
            $adjustment->delete();
            return response()->json(['message' => 'Adjustment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete adjustment',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
