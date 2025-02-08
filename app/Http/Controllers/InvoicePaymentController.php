<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class InvoicePaymentController extends Controller
{
    /**
     * Store a new payment
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

            $payment = InvoicePayment::create($request->all());

            return response()->json([
                'message' => 'Payment added successfully',
                'payment' => $payment
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a payment
     */
    public function destroy(InvoicePayment $payment)
    {
        try {
            $payment->delete();
            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

