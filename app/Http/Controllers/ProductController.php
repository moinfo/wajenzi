<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class ProductController extends Controller
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

    public function statutory(Request $request,$product_id)
    {
        if($this->handleCrud($request, 'Invoice')) {
            return back();
        }
        $product = Product::where('id',$product_id)->get()->first();
        $sub_category_id = $product['sub_category_id'];
        $sub_category_name = SubCategory::getSubCategoryName($sub_category_id);
        $billing_cycle = $product['billing_cycle'];
        if($billing_cycle == 0){
                $billing_cycle_name = 'One Time';
            } elseif($billing_cycle == 12){
                $billing_cycle_name = 'Annually';
            }elseif($billing_cycle == 3){
                $billing_cycle_name = 'Quarterly';
            }elseif($billing_cycle == 6){
                $billing_cycle_name = 'Semi-Annually';
            }elseif($billing_cycle == 1){
                $billing_cycle_name = 'Monthly';
            }else{
                $billing_cycle_name = 'Nothing';
            }
        $last_date = $product['due_date'];
        $due_date = date('Y-m-d', strtotime("+$billing_cycle months", strtotime($last_date)));
        $data = [
            'invoices' => Invoice::where('product_id',$product_id)->get(),
            'product' => Product::find($product_id),
            'sub_category_name' => $sub_category_name,
            'sub_category_id' => $sub_category_id,
            'billing_cycle_name' => $billing_cycle_name,
            'billing_cycle' => $billing_cycle,
            'due_date' => $due_date,
        ];
        return view('pages.settings.invoices')->with($data);
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
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Product  $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
