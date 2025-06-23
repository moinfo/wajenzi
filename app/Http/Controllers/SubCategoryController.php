<?php

namespace App\Http\Controllers;

use App\Models\AssetProperty;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
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
     * @param  \App\Models\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function show(SubCategory $subCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(SubCategory $subCategory)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, SubCategory $subCategory)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SubCategory  $subCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(SubCategory $subCategory)
    {
        //
    }

    public function getSubCategories(Request $request){
        $sub_category_id = $request->input('sub_category_id');
        $sub_categories = SubCategory::where('id',$sub_category_id)->get();

        foreach ($sub_categories as $index => $sub_category) {
            $id = $sub_category->id;
            $billing_cycle = $sub_category->billing_cycle;
            $price = $sub_category->price;
            if($billing_cycle == 0){
                $billing_cycle_name = 'One Time';
            } elseif($billing_cycle == 1){
                $billing_cycle_name = 'Annually';
            }elseif($billing_cycle == 3){
                $billing_cycle_name = 'Quarterly';
            }elseif($billing_cycle == 6){
                $billing_cycle_name = 'Semi-Annually';
            }elseif($billing_cycle == 12){
                $billing_cycle_name = 'Monthly';
            }else{
                $billing_cycle_name = 'Nothing';
            }
            $sub_category_arr[] = array("id" => $id, "price" => $price, "billing_cycle" => $billing_cycle, "billing_cycle_name" => $billing_cycle_name);
        }
        echo json_encode($sub_category_arr);

    }
}
