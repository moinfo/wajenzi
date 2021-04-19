<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\ExpensesCategory;
use App\Models\Gross;
use App\Models\Supervisor;
use App\Models\Supplier;
use App\Models\TransactionMovement;
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
            ['name' => 'Expenses Report', 'route' => 'reports_expenses_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Expenses Categories Report', 'route' => 'reports_expenses_categories_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Supplier Transaction Report', 'route' => 'reports_supplier_transaction_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Supplier Receiving Report', 'route' => 'reports_supplier_receiving_report', 'icon' => 'si si-book-open', 'badge' => 0],
            ['name' => 'Transaction Movement Report', 'route' => 'reports_transaction_movement_report', 'icon' => 'si si-book-open', 'badge' => 0],
        ];
        $data = [
            'reports' => $reports
        ];
        return view('pages.reports.reports_index')->with($data);
    }

    public function general_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::all();
        $supervisor_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expenses, s.name as supervisor_name,c.date as expense_date FROM expenses c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_expenses' => $supervisor_with_amount_of_expenses,
            'supervisors' => $supervisors,
            'expenses' => $expenses
        ];
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

    public function transaction_movement_report(Request $request){
        $transactions = DB::select('SELECT s.name, SUM(c.amount) AS amount FROM supervisors s JOIN collections c ON (c.supervisor_id = s.id) GROUP BY s.id,c.supervisor_id');
        $payments = DB::select('SELECT s.name, SUM(c.amount) AS amount FROM suppliers s JOIN transaction_movements c ON (c.supplier_id = s.id) GROUP BY s.id,c.supplier_id');
        $data = [
            'payments' => $payments,
            'transactions' => $transactions
        ];
        return view('pages.reports.reports_transaction_movement_report')->with($data);
    }

    public function supplier_report(Request $request){
        $data = [];
        return view('pages.reports.reports_supplier_report')->with($data);
    }

    public function transaction_movement_report_search(Request $request){
        $start_date = $request->input('start_date') ?? '2000-01-01';
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $submit = $request->input('submit');

        $transactions = DB::select("SELECT s.name, SUM(c.amount) AS amount FROM supervisors s JOIN collections c ON (c.supervisor_id = s.id) WHERE c.date BETWEEN '$start_date' AND '$end_date' GROUP BY s.id,c.supervisor_id");
        $payments = DB::select("SELECT s.name, SUM(c.amount) AS amount FROM suppliers s JOIN transaction_movements c ON (c.supplier_id = s.id) WHERE c.date BETWEEN '$start_date' AND '$end_date' GROUP BY s.id,c.supplier_id");


        return view('pages.reports.reports_transaction_movement_report',compact('transactions','payments'));
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

    public function supplier_transaction_report(Request $request){
        $transaction_movements = TransactionMovement::whereDate('date', DB::raw('CURDATE()'))->get();
        $suppliers = Supplier::all();
        $supplier_with_amount_of_transaction_movements = DB::select('SELECT SUM(c.amount) as total_transaction_movement, s.name as supplier_name,c.date as transaction_movement_date FROM transaction_movements c JOIN suppliers s ON (s.id = c.supplier_id) GROUP BY c.supplier_id,c.date');
        $data = [
            'supplier_with_amount_of_transaction_movements' => $supplier_with_amount_of_transaction_movements,
            'suppliers' => $suppliers,
            'transaction_movements' => $transaction_movements
        ];
        return view('pages.reports.reports_supplier_transaction_report')->with($data);
    }

    public function supplier_receiving_report(Request $request){
        $supplier_receivings = TransactionMovement::whereDate('date', DB::raw('CURDATE()'))->get();
        $suppliers = Supplier::all();
        $supplier_with_amount_of_supplier_receivings = DB::select('SELECT SUM(c.amount) as total_supplier_receiving, s.name as supplier_name,c.date as supplier_receiving_date FROM supplier_receivings c JOIN suppliers s ON (s.id = c.supplier_id) GROUP BY c.supplier_id,c.date');
        $data = [
            'supplier_with_amount_of_supplier_receivings' => $supplier_with_amount_of_supplier_receivings,
            'suppliers' => $suppliers,
            'supplier_receivings' => $supplier_receivings
        ];
        return view('pages.reports.reports_supplier_receiving_report')->with($data);
    }

    public function expenses_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $supervisors = Supervisor::all();
        $supervisor_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expenses, s.name as supervisor_name,c.date as expense_date FROM expenses c JOIN supervisors s ON (s.id = c.supervisor_id) GROUP BY c.supervisor_id,c.date');
        $data = [
            'supervisor_with_amount_of_expenses' => $supervisor_with_amount_of_expenses,
            'supervisors' => $supervisors,
            'expenses' => $expenses
        ];
        return view('pages.reports.reports_expenses_report')->with($data);
    }
    public function expenses_categories_report(Request $request){
        $expenses = Expense::whereDate('date', DB::raw('CURDATE()'))->get();
        $categories = ExpensesCategory::all();
        $categories_with_amount_of_expenses = DB::select('SELECT SUM(c.amount) as total_expenses, s.name as category_name,c.date as expense_date FROM expenses c JOIN expenses_categories s ON (s.id = c.expenses_category_id) GROUP BY c.expenses_category_id,c.date');
        $data = [
            'categories_with_amount_of_expenses' => $categories_with_amount_of_expenses,
            'expenses_categories' => $categories,
            'expenses' => $expenses
        ];
        return view('pages.reports.reports_expenses_categories_report')->with($data);
    }

}
