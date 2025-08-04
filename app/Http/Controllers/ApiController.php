<?php

namespace App\Http\Controllers;

use App\Models\Api;
use App\Models\Attendance;
use App\Models\Receipt;
use App\Models\ReceiptItem;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $data = [];
        return response()->json($data);
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
            $validation = $request->validate([
                "data" => ['required', 'array'],
                "data.*.deviceUserId" => ['required'],
                "data.*.recordTime" => ['required'],
                "data.*.ip" => ['required'],
            ]);
            
            $data = $request->input('data');
            $newData = Attendance::recordFromDevice($data);

            return response()->json([
                'success' => true,
                'message' => 'Attendance records processed successfully',
                'data' => $newData,
                'count' => count($newData)
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing attendance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get receipts with all related data
     */
    public function receipts($id = null)
    {
        try {
            $query = Receipt::with([
                'items',
                'adjustments',
                'payments'
            ]);

            if ($id) {
                $receipt = $query->findOrFail($id);

                // Format the response data
                $responseData = [
                    'receipt' => $receipt,
                    'items' => $receipt->items,
                    'total_amount' => $receipt->items->sum('amount'),
                    'total_quantity' => $receipt->items->sum('qty')
                ];

                // Add TANESCO-specific data if applicable
                if ($receipt->is_tanesco) {
                    $responseData['tanesco_data'] = [
                        'kwh_charge' => $receipt->kwh_charge,
                        'kva_charge' => $receipt->kva_charge,
                        'service_charge' => $receipt->service_charge,
                        'interest_amount' => $receipt->interest_amount,
                        'rea_charge' => $receipt->receipt_rea,
                        'ewura_charge' => $receipt->receipt_ewura,
                        'property_tax' => $receipt->receipt_property_tax,
                        'adjustments' => $receipt->adjustments,
                        'payments' => $receipt->payments,
                        'balance' => $receipt->getBalanceAmount()
                    ];
                }

                return response()->json($responseData);
            }

            // For listing all receipts, paginate them
            $receipts = $query
                ->orderBy('receipt_date', 'desc')
                ->orderBy('receipt_time', 'desc')
                ->paginate(20);

            // Add summary data
            $summary = [
                'total_receipts' => $receipts->total(),
                'total_amount' => $receipts->sum('receipt_total_incl_of_tax'),
                'tanesco_receipts' => $receipts->where('is_tanesco', true)->count()
            ];

            return response()->json([
                'receipts' => $receipts,
                'summary' => $summary
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Receipt not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch receipts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get receipt items with optional filtering and pagination
     */
    public function receipt_items($id = null)
    {
        try {
            $query = ReceiptItem::with('receipt');

            if ($id) {
                // Verify receipt exists
                $receipt = Receipt::findOrFail($id);

                $items = $query->where('receipt_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->get();

                // Calculate totals
                $totals = [
                    'total_quantity' => $items->sum('qty'),
                    'total_amount' => $items->sum('amount'),
                    'items_count' => $items->count()
                ];

                return response()->json([
                    'receipt_info' => [
                        'id' => $receipt->id,
                        'company_name' => $receipt->company_name,
                        'receipt_number' => $receipt->receipt_number,
                        'receipt_date' => $receipt->receipt_date
                    ],
                    'items' => $items,
                    'totals' => $totals
                ]);
            }

            // For all items, use pagination
            $items = $query->orderBy('created_at', 'desc')
                ->paginate(50);

            $summary = [
                'total_items' => $items->total(),
                'total_amount' => $items->sum('amount'),
                'total_quantity' => $items->sum('qty')
            ];

            return response()->json([
                'items' => $items,
                'summary' => $summary
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Receipt not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch receipt items',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function employees($id = null)
    {
        return $id?User::find($id):User::all();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function show(Api $api)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function edit(Api $api)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Api $api)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Api  $api
     * @return \Illuminate\Http\Response
     */
    public function destroy(Api $api)
    {
        //
    }
}
