<?php

namespace App\Http\Controllers;

use App\Models\Gross;
use App\Models\Supervisor;
use Illuminate\Http\Request;

class GrossController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Gross')) {
            return back();
        }
        $grosses = Gross::all();
        $supervisors = Supervisor::all();

        $data = [
            'supervisors' => $supervisors,
            'grosses' => $grosses
        ];
        return view('pages.gross.gross_index')->with($data);
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
     * @param  \App\Models\Gross  $gross
     * @return \Illuminate\Http\Response
     */
    public function show(Gross $gross)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Gross  $gross
     * @return \Illuminate\Http\Response
     */
    public function edit(Gross $gross)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Gross  $gross
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Gross $gross)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Gross  $gross
     * @return \Illuminate\Http\Response
     */
    public function destroy(Gross $gross)
    {
        //
    }
}
