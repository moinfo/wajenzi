<?php

namespace App\Http\Controllers;

use App\Models\AssetProperty;
use App\Models\Beneficiary;
use App\Models\BeneficiaryAccount;
use App\Models\SupplierTarget;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Beneficiary')) {
            return back();
        }

        $data = [
            'beneficiaries' => Beneficiary::all()
        ];
        return view('pages.beneficiary.beneficiary_index')->with($data);
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
     * @param  \App\Models\Beneficiary  $beneficiary
     * @return \Illuminate\Http\Response
     */
    public function show(Beneficiary $beneficiary)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Beneficiary  $beneficiary
     * @return \Illuminate\Http\Response
     */
    public function edit(Beneficiary $beneficiary)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Beneficiary  $beneficiary
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Beneficiary $beneficiary)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Beneficiary  $beneficiary
     * @return \Illuminate\Http\Response
     */
    public function destroy(Beneficiary $beneficiary)
    {
        //
    }

//    public function getSupplierBeneficiary(Request $request){
//        $supplier_id = $request->input('supplier_id');
//        $supplier_targets = SupplierTarget::where('supplier_id',$supplier_id)->where('date',date('Y-m-d'))->get();
//
//        foreach ($supplier_targets as $index => $supplier_target) {
//            $id = $supplier_target->beneficiary_id;
//            $account_name = $supplier_target->beneficiary->name;
//            $beneficiary_arr[] = array("id" => $id, "account_name" => $account_name);
//        }
//        echo json_encode($beneficiary_arr);
//
//    }

    public function getSupplierBeneficiary(Request $request)
    {
        // Validate the incoming request
//        $request->validate([
//            'supplier_id' => 'required|exists:suppliers,supplier_id',
//        ]);

        $supplier_id = $request->input('supplier_id');

        // Fetch supplier targets for the current date
        $supplier_targets = SupplierTarget::where('supplier_id', $supplier_id)
            ->where('date', date('Y-m-d'))
            ->with('beneficiary') // Eager load beneficiary relationship
            ->get();

        // Prepare the response array
        $beneficiary_arr = [];
        foreach ($supplier_targets as $supplier_target) {
            $beneficiary_arr[] = [
                "id" => $supplier_target->beneficiary_id,
                "account_name" => $supplier_target->beneficiary->name,
            ];
        }

        // Return a JSON response
        return response()->json($beneficiary_arr);
    }

    public function getSupplierBeneficiaryAccount(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'beneficiary_id' => 'required|exists:beneficiary_accounts,beneficiary_id',
        ]);

        $beneficiary_id = $request->input('beneficiary_id');

        // Fetch accounts related to the beneficiary
        $beneficiary_accounts = BeneficiaryAccount::where('beneficiary_id', $beneficiary_id)
            ->with('bank') // Eager load bank relationship
            ->get();

        // Prepare the response array
        $beneficiary_arr = [];
        foreach ($beneficiary_accounts as $beneficiary_account) {
            $beneficiary_arr[] = [
                "id" => $beneficiary_account->id,
                // Make sure this matches the JavaScript expectation
                "account_name" => $beneficiary_account->bank->name . ' - ' . $beneficiary_account->account,
            ];
        }

        // Return a JSON response
        return response()->json($beneficiary_arr);
    }


//    public function getSupplierBeneficiaryAccount(Request $request){
//        $beneficiary_id = $request->input('beneficiary_id');
//        $beneficiary_accounts = BeneficiaryAccount::where('beneficiary_id',$beneficiary_id)->get();
//
//        foreach ($beneficiary_accounts as $index => $beneficiary_account) {
//            $id = $beneficiary_account->id;
//            $account_name = $beneficiary_account->bank->name .' - ACC NO. '.$beneficiary_account->account;
//            $beneficiary_arr[] = array("id" => $id, "account_name" => $account_name);
//        }
//        echo json_encode($beneficiary_arr);
//
//    }
}
