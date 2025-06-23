<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Collection;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CollectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($this->handleCrud($request, 'Collection')) {
            return back();
        }
//        $collections = Collection::whereDate('date', DB::raw('CURDATE()'))->get();
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supervisor_id = $request->input('supervisor_id');
        if($supervisor_id == 0){
            $collections = DB::table('collections')
                ->join('supervisors', 'supervisors.id', '=', 'collections.supervisor_id')
                ->join('banks', 'banks.id', '=', 'collections.bank_id')
                ->select('collections.*','banks.name as bank_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $collections = DB::table('collections')
                ->join('supervisors', 'supervisors.id', '=', 'collections.supervisor_id')
                ->join('banks', 'banks.id', '=', 'collections.bank_id')
                ->select('collections.*','banks.name as bank_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supervisor_id','=',$supervisor_id)
                ->get();
        }
        $supervisors = Supervisor::where('employee_id',1)->get();

        $data = [
            'supervisors' => $supervisors,
            'collections' => $collections
        ];
        return view('pages.collection.collection_index')->with($data);
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
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function show(Collection $collection)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function edit(Collection $collection)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Collection $collection)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Collection  $collection
     * @return \Illuminate\Http\Response
     */
    public function destroy(Collection $collection)
    {
        //
    }

    public function search(Request $request){
        if($this->handleCrud($request, 'Collection')) {
            return back();
        }
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $supervisor_id = $request->input('supervisor_id');
        if($supervisor_id == 0){
            $collections = DB::table('collections')
                ->join('supervisors', 'supervisors.id', '=', 'collections.supervisor_id')
                ->join('banks', 'banks.id', '=', 'collections.bank_id')
                ->select('collections.*','banks.name as bank_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->get();
        }else{
            $collections = DB::table('collections')
                ->join('supervisors', 'supervisors.id', '=', 'collections.supervisor_id')
                ->join('banks', 'banks.id', '=', 'collections.bank_id')
                ->select('collections.*','banks.name as bank_name','supervisors.name as supervisor_name')
                ->where('date','>=',$start_date)
                ->where('date','<=',$end_date)
                ->where('supervisor_id','=',$supervisor_id)
                ->get();
        }

        $supervisors = Supervisor::all();
        return view('pages.collection.collection_index',compact('collections','supervisors'));
    }

    public function collection($id,$document_type_id){
        $collection = \App\Models\Collection::where('id',$id)->get()->first();
        $approvalStages = Approval::getApprovalStages($id,$document_type_id);
        $nextApproval = Approval::getNextApproval($id,$document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id,$document_type_id);
        $rejected = Approval::isRejected($id,$document_type_id);
        $document_id = $id;
        $data = [
            'collection' => $collection,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.collection.collection')->with($data);
    }
}
