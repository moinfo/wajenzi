<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        $data = [];
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
        $data = [];
        return view('pages.reports.reports_collection_report')->with($data);
    }

}
