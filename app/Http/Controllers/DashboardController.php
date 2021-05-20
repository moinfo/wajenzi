<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\Gross;
use App\Models\TransactionMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //
    public function index(Request $request) {
        $monday = strtotime("last monday");
        $monday = date('w', $monday)==date('w') ? $monday+7*86400 : $monday;
        $sunday = strtotime(date("Y-m-d",$monday)." +6 days");
        $this_week_sd = date("Y-m-d",$monday);
        $this_week_ed = date("Y-m-d",$sunday);
        $collection_in_week = Collection::whereBetween('date', [$this_week_sd, $this_week_ed])->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
        $expenses_in_week = Expense::whereBetween('date', [$this_week_sd, $this_week_ed])->select([DB::raw("SUM(amount) as total_amount")])->get()->first();
        $collections = Collection::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $collection_in_month = Collection::whereMonth('date', date('m'))->whereYear('date', date('Y'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $expenses_in_month = Expense::whereMonth('date', date('m'))->whereYear('date', date('Y'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $transactions = TransactionMovement::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $expenses = Expense::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $gross = Gross::Where('date',DB::raw('CURDATE()'))->select([DB::raw("SUM(amount) as total_amount")])->groupBy('date')->get()->first();
        $data = [
            'collections' => $collections,
            'collection_in_month' => $collection_in_month,
            'expenses_in_month' => $expenses_in_month,
            'expenses_in_week' => $expenses_in_week,
            'collection_in_week' => $collection_in_week,
            'transactions' => $transactions,
            'expenses' => $expenses,
            'gross' => $gross,
        ];
        $user = Auth::user()->name;
        $this->notify('Welcome to a Financial Analysis System', 'Hello'.' '.$user, 'success');
//        $this->notify_toast('success','hello');
        session()->put('success','Item created successfully.');
        return view('pages.dashboard')->with($data);
    }

}
