<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Models\ReceiptItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'receipt_id' => 'required|exists:receipts,id',
                'description' => 'required|string|max:255',
                'qty' => 'required|numeric|min:0',
                'amount' => 'required|numeric|min:0'
            ]);

            // Begin transaction
            DB::beginTransaction();

            // Create receipt item
            $receiptItem = ReceiptItem::create([
                'receipt_id' => $validatedData['receipt_id'],
                'description' => $validatedData['description'],
                'qty' => $validatedData['qty'],
                'amount' => $validatedData['amount']
            ]);

            // If TANESCO receipt and contains specific keywords, update parent receipt
            $receipt = Receipt::find($validatedData['receipt_id']);
            if ($receipt->is_tanesco) {
                $description = strtolower($validatedData['description']);

                if (str_contains($description, 'kwh')) {
                    $receipt->kwh_charge = $validatedData['amount'];
                } elseif (str_contains($description, 'kva')) {
                    $receipt->kva_charge = $validatedData['amount'];
                } elseif (str_contains($description, 'service charge')) {
                    $receipt->service_charge = $validatedData['amount'];
                }

                $receipt->save();
            }

            DB::commit();

            return response()->json([
                'message' => 'Receipt item added successfully',
                'data' => $receiptItem
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to add receipt item',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function show(ReceiptItem $receiptItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function edit(ReceiptItem $receiptItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ReceiptItem $receiptItem)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ReceiptItem  $receiptItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(ReceiptItem $receiptItem)
    {
        //
    }
}
