<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Gross;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $grosses = Gross::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::where('employee_id',1)->get();

        $data = [
            'supervisors' => $supervisors,
            'grosses' => $grosses
        ];
        return view('pages.gross.gross_index')->with($data);
    }

    public function gross($id,$document_type_id){
        $gross = \App\Models\Gross::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'gross' => $gross,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.gross.gross')->with($data);
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

    public function search(Request $request){
        if($this->handleCrud($request, 'Gross')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supervisor_id = $request->input('supervisor_id');
        if($supervisor_id == 0){
            $grosses = DB::table('grosses')
                ->join('supervisors', 'supervisors.id', '=', 'grosses.supervisor_id')
                ->select('grosses.*','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $grosses = DB::table('grosses')
                ->join('supervisors', 'supervisors.id', '=', 'grosses.supervisor_id')
                ->select('grosses.*','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supervisor_id','=',$supervisor_id)
                ->get();
        }

        $supervisors = Supervisor::all();
        return view('pages.gross.gross_index',compact('grosses','supervisors'));
    }
}
