<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Gross;
use App\Models\Supervisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $reports = [
            ['name' => 'General Report', 'route' => 'reports_general_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Supervisor Report', 'route' => 'reports_supervisor_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Supplier Report', 'route' => 'reports_supplier_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Collection Report', 'route' => 'reports_collection_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Gross Summary Report', 'route' => 'reports_gross_summary_report', 'icon' => 'si si-book-open', 'badge' => 0],


        ];
        $data = [
            'reports' => $reports
        ];
        return view('pages.reports.reports_index')->with($data);
    }

    public function general_report(Request $request){
        $data = [];
        return view('pages.reports.reports_general_report')->with($data);
    }

    public function gross_summary_report(Request $request){
        $grosses = Gross::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::all();
        $supervisor_with_amount_of_grosses = DB::select('SELECT SUM(c.amount) as total_gross, s.name as supervisor_name,c.date as gross_date FROM grosses c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_grosses' => $supervisor_with_amount_of_grosses,
            'supervisors' => $supervisors,
            '$grosses' => $grosses
        ];
        return view('pages.reports.reports_gross_summary_report')->with($data);
    }

    public function supervisor_report(Request $request){
        $data = [];
        return view('pages.reports.reports_supervisor_report')->with($data);
    }

    public function supplier_report(Request $request){
        $data = [];
        return view('pages.reports.reports_supplier_report')->with($data);
    }

    public function collection_report(Request $request){
        $collections = Collection::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::all();
        $supervisor_with_amount_of_collections = DB::select('SELECT SUM(c.amount) as total_collection, s.name as supervisor_name,c.date as collection_date FROM collections c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_collections' => $supervisor_with_amount_of_collections,
            'supervisors' => $supervisors,
            'collections' => $collections
        ];
        return view('pages.reports.reports_collection_report')->with($data);
    }

}
