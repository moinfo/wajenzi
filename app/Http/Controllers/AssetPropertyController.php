<?php

namespace App\Http\Controllers;

use App\Models\AssetProperty;
use Illuminate\Http\Request;

class AssetPropertyController extends Controller
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
     * @param  \App\Models\AssetProperty  $assetProperty
     * @return \Illuminate\Http\Response
     */
    public function show(AssetProperty $assetProperty)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\AssetProperty  $assetProperty
     * @return \Illuminate\Http\Response
     */
    public function edit(AssetProperty $assetProperty)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\AssetProperty  $assetProperty
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AssetProperty $assetProperty)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\AssetProperty  $assetProperty
     * @return \Illuminate\Http\Response
     */
    public function destroy(AssetProperty $assetProperty)
    {
        //
    }

    public function getAssetProperties(Request $request){
        $asset_id = $request->input('asset_id');
        $asset_properties = AssetProperty::where('asset_id',$asset_id)->get();

        foreach ($asset_properties as $index => $asset) {
            $id = $asset->id;
            $asset_property = $asset->name;
            $asset_property_arr[] = array("id" => $id, "name" => $asset_property);
        }
        echo json_encode($asset_property_arr);

    }
}
