<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\TransactionMovement;
use Illuminate\Http\Request;

class TransactionMovementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'TransactionMovement')) {
            return back();
        }
        $transaction_movements = TransactionMovement::all();
        $suppliers = Supplier::all();

        $data = [
            'suppliers' => $suppliers,
            'transaction_movements' => $transaction_movements
        ];
        return view('pages.transaction_movement.transaction_movement_index')->with($data);
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
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function show(TransactionMovement $transactionMovement)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function edit(TransactionMovement $transactionMovement)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, TransactionMovement $transactionMovement)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\TransactionMovement  $transactionMovement
     * @return \Illuminate\Http\Response
     */
    public function destroy(TransactionMovement $transactionMovement)
    {
        //
    }
}
