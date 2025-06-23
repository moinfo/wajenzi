<?php

namespace App\Http\Controllers;

use App\Models\SupplierTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierTargetController extends Controller
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SupplierTarget  $supplierTarget
     * @return \Illuminate\Http\Response
     */
    public function show(SupplierTarget $supplierTarget)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SupplierTarget  $supplierTarget
     * @return \Illuminate\Http\Response
     */
    public function edit(SupplierTarget $supplierTarget)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SupplierTarget  $supplierTarget
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SupplierTarget $supplierTarget)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SupplierTarget  $supplierTarget
     * @return \Illuminate\Http\Response
     */
    public function destroy(SupplierTarget $supplierTarget)
    {
        //
    }

    public function getTargetDetails(Request $request)
    {
        $target = SupplierTarget::with(['beneficiary'])
            ->where('id', $request->target_id)
            ->first();

        if (!$target) {
            return response()->json([
                'error' => 'Target not found'
            ], 404);
        }

        // Get bank details
        $bankAccount = DB::table('beneficiary_accounts')
            ->join('banks', 'banks.id', '=', 'beneficiary_accounts.bank_id')
            ->where('beneficiary_accounts.beneficiary_id', $target->beneficiary_id)
            ->select('banks.name as bank_name', 'beneficiary_accounts.account')
            ->first();

        // Calculate used amount
        $used_amount = DB::table('supplier_target_preparations')
            ->where('supplier_target_id', $target->id)
            ->sum('amount');

        $remaining_balance = $target->amount - $used_amount;

        return response()->json([
            'beneficiary_name' => $target->beneficiary->name,
            'bank_name' => $bankAccount ? $bankAccount->bank_name : '',
            'account_number' => $bankAccount ? $bankAccount->account : '',
            'target_amount' => $target->amount,
            'used_amount' => $used_amount,
            'remaining_balance' => $remaining_balance
        ]);
    }
}
