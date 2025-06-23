<?php

namespace App\Http\Controllers;

use App\Models\Wakala;
use Illuminate\Http\Request;

class WakalaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Wakala')) {
            return back();
        }

        $data = [
            'wakalas' => Wakala::all()
        ];
        return view('pages.wakala.wakala_index')->with($data);
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
     * @param  \App\Models\Wakala  $wakala
     * @return \Illuminate\Http\Response
     */
    public function show(Wakala $wakala)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Wakala  $wakala
     * @return \Illuminate\Http\Response
     */
    public function edit(Wakala $wakala)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Wakala  $wakala
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Wakala $wakala)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Wakala  $wakala
     * @return \Illuminate\Http\Response
     */
    public function destroy(Wakala $wakala)
    {
        //
    }
}
