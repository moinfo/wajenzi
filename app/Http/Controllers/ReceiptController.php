<?php

namespace App\Http\Controllers;

use App\Models\InvoiceAdjustment;
use App\Models\InvoicePayment;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
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
            DB::beginTransaction();

            $receipt = new Receipt;

            // Basic receipt information
            $receipt->company_name = $request->company_name;
            $receipt->p_o_box = $request->p_o_box;
            $receipt->mobile = $request->mobile;
            $receipt->tin = $request->tin;
            $receipt->vrn = $request->vrn;
            $receipt->serial_no = $request->serial_no;
            $receipt->uin = $request->uin;
            $receipt->tax_office = $request->tax_office;
            $receipt->customer_name = $request->customer_name;
            $receipt->customer_id_type = $request->customer_id_type;
            $receipt->customer_id = $request->customer_id;
            $receipt->customer_mobile = $request->customer_mobile;
            $receipt->receipt_number = $request->receipt_number;
            $receipt->receipt_z_number = $request->receipt_z_number;
            $receipt->receipt_date = $request->receipt_date;
            $receipt->receipt_time = $request->receipt_time;
            $receipt->receipt_verification_code = $request->receipt_verification_code;
            $receipt->receipt_total_excl_of_tax = $request->receipt_total_excl_of_tax ?? 0;
            $receipt->receipt_total_tax = $request->receipt_total_tax ?? 0;
            $receipt->receipt_total_incl_of_tax = $request->receipt_total_incl_of_tax ?? 0;
            $receipt->date = $request->receipt_date;

            // TANESCO specific fields
            $receipt->kwh_charge = $request->kwh_charge ?? null;
            $receipt->kva_charge = $request->kva_charge ?? null;
            $receipt->service_charge = $request->service_charge ?? null;
            $receipt->interest_amount = $request->interest_amount ?? null;
            $receipt->receipt_rea = $request->receipt_rea ?? 0;
            $receipt->receipt_property_tax = $request->receipt_property_tax ?? 0;
            $receipt->receipt_ewura = $request->receipt_ewura ?? 0;

            // Set TANESCO flag
            $receipt->is_tanesco = str_contains(strtolower($request->company_name), 'tanzania electric supply') ||
                str_contains(strtolower($request->company_name), 'tanesco');

            $receipt->save();

            // Handle items with flexible field names
            if ($request->has('items') && is_array($request->items)) {
                foreach($request->items as $item) {
                    ReceiptItem::create([
                        'receipt_id' => $receipt->id,
                        'description' => $item['item_description'] ?? $item['description'] ?? '',
                        'qty' => $item['item_qty'] ?? $item['qty'] ?? 1,
                        'amount' => $item['item_amount'] ?? $item['amount'] ?? 0
                    ]);
                }
            }

            // Handle invoice adjustments
            if ($request->has('adjustments') && is_array($request->adjustments)) {
                foreach($request->adjustments as $adjustment) {
                    InvoiceAdjustment::create([
                        'receipt_id' => $receipt->id,
                        'type' => $adjustment['type'] ?? 'CR',
                        'description' => $adjustment['description'] ?? 'Marekebisho/Adjustment',
                        'amount' => $adjustment['amount'] ?? 0
                    ]);
                }
            }

            // Handle invoice payments
            if ($request->has('payments') && is_array($request->payments)) {
                foreach($request->payments as $payment) {
                    InvoicePayment::create([
                        'receipt_id' => $receipt->id,
                        'type' => $payment['type'] ?? 'CR',
                        'description' => $payment['description'] ?? 'Kiasi kilichobaki/Balance B/Fwd',
                        'amount' => $payment['amount'] ?? 0
                    ]);
                }
            }

            // Additional handling for default TANESCO adjustments and payments if none provided
            if ($receipt->is_tanesco && !$request->has('adjustments')) {
                InvoiceAdjustment::create([
                    'receipt_id' => $receipt->id,
                    'type' => 'CR',
                    'description' => 'Marekebisho/Adjustment',
                    'amount' => 0
                ]);
            }

            if ($receipt->is_tanesco && !$request->has('payments')) {
                InvoicePayment::create([
                    'receipt_id' => $receipt->id,
                    'type' => 'CR',
                    'description' => 'Kiasi kilichobaki/Balance B/Fwd',
                    'amount' => 0
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Receipt added successfully',
                'receipt_id' => $receipt->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Receipt creation failed: ' . $e->getMessage());
            \Log::error('Request data: ' . json_encode($request->all()));

            return response()->json([
                'error' => 'Failed to add receipt',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function show(Receipt $receipt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function edit(Receipt $receipt)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Receipt $receipt)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Receipt  $receipt
     * @return \Illuminate\Http\Response
     */
    public function destroy(Receipt $receipt)
    {
        //
    }
}
