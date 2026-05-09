<?php

namespace App\Http\Controllers;

use App\Models\CostCategory;
use App\Models\Project;
use App\Models\ProjectExpense;
use App\Models\ProjectPayment;
use App\Models\ProjectInvoice;
use App\Models\Approval;
use Illuminate\Http\Request;

class ProjectExpenseController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectExpense')) {
            return back();
        }

        $expenses = ProjectExpense::with(['project', 'costCategory', 'creator'])
            ->when($request->start_date, function($query) use ($request) {
                return $query->whereDate('expense_date', '>=', $request->start_date);
            })
            ->when($request->end_date, function($query) use ($request) {
                return $query->whereDate('expense_date', '<=', $request->end_date);
            })
            ->when($request->project_id, function($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->when($request->cost_category_id, function($query) use ($request) {
                return $query->where('cost_category_id', $request->cost_category_id);
            })
            ->orderBy('expense_date', 'desc')
            ->get();

        $projects = Project::all();
        $costCategories = CostCategory::orderBy('name')->get();
        $total_amount = $expenses->sum('amount');

        // Category summary — used by the new summary panel on the page.
        // Falls back to 0 when a canonical category isn't found.
        $catIds = $costCategories->pluck('id', 'name'); // [name => id]
        $categorySummary = [
            'Material'         => (float) $expenses->where('cost_category_id', $catIds['Material']         ?? 0)->sum('amount'),
            'Labour Charge'    => (float) $expenses->where('cost_category_id', $catIds['Labour Charge']    ?? 0)->sum('amount'),
            'Overhead Expense' => (float) $expenses->where('cost_category_id', $catIds['Overhead Expense'] ?? 0)->sum('amount'),
        ];

        $data = [
            'expenses' => $expenses,
            'projects' => $projects,
            'costCategories' => $costCategories,
            'total_amount' => $total_amount,
            'categorySummary' => $categorySummary,
        ];
        return view('pages.projects.project_expenses')->with($data);
    }

    public function expense($id, $document_type_id) {
        $expense = ProjectExpense::where('id', $id)->first();
        $approvalStages = Approval::getApprovalStages($id, $document_type_id);
        $nextApproval = Approval::getNextApproval($id, $document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id, $document_type_id);
        $rejected = Approval::isRejected($id, $document_type_id);
        $document_id = $id;

        $data = [
            'expense' => $expense,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.projects.project_expenses')->with($data);
    }

    public function monthlyReport(Request $request) {
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');

        $expenses = ProjectExpense::with(['project'])
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->when($request->project_id, function($query) use ($request) {
                return $query->where('project_id', $request->project_id);
            })
            ->get();

        return response()->json([
            'success' => true,
            'expenses' => $expenses,
            'total' => $expenses->sum('amount')
        ]);
    }
}
