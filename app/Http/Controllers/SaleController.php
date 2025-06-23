<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Efd;
use App\Models\Sale;
use App\Models\SubCategory;
use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Services\ApprovalService;
class SaleController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request) {
        if($this->handleCrud($request, 'Sale')) {
            return back();
        }

        $sale = new \App\Models\Sale();
        $start_date = $request->input('start_date') ?? date('Y-m-d');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $efd_id = $request->input('efd_id') ?? null;

//        $sales = $sale->getAll($start_date,$end_date,$efd_id); TODO to be fixed
        $sales = $sale->All();
        $efds = Efd::all();

        $data = [
            'efds' => $efds,
           'sales' => $sales
        ];
        return view('pages.sales.sales_index')->with($data);
    }

    public function sale($id,$document_type_id){
        // Mark notification as read
        $this->approvalService->markNotificationAsRead($id, $document_type_id,'sale');

        $approval_data = \App\Models\Sale::where('id',$id)->get()->first();
        $document_id = $id;

        $details = [
            'Turnover' => number_format($approval_data->amount),
            'NET (A+B+C)' => number_format($approval_data->net),
            'Tax' => number_format($approval_data->tax),
            'Turnover (EX + SR)' => number_format($approval_data->turn_over),
            'Date' => $approval_data->date,
            'Uploaded File' => $approval_data->file
        ];

        $data = [
            'approval_data' => $approval_data,
            'document_id' => $document_id,
            'approval_document_type_id' => $document_type_id, //improve $approval_document_type_id
            'page_name' => 'Sales',
            'approval_data_name' => $approval_data->efd->name,
            'details' => $details,
            'model' => 'Sale',
            'route' => 'sale',

        ];
        return view('approvals._approve_page')->with($data);
    }

    public function getLastEfdNumber(Request $request){
        $efd_id = $request->input('efd_id');
        $last_id = Sale::getLastEfdNumber($efd_id);

        $last_z_report_number_arr[] = array("id" => $last_id, "name" => $last_id);
        echo json_encode($last_z_report_number_arr);

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
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function show(Sale $sale)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function edit(Sale $sale)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Sale $sale)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sale  $sale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sale $sale)
    {
        //
    }
}
