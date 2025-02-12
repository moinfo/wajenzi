<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use App\Models\Approval;
use Illuminate\Http\Request;

class ProjectBoqController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectBoq')) {
            return back();
        }

        $boqs = ProjectBoq::with(['project'])->get();
        $projects = Project::all();

        $data = [
            'boqs' => $boqs,
            'projects' => $projects
        ];
        return view('pages.projects.project_boqs')->with($data);
    }

    public function boq($id, $document_type_id){
        $boq = ProjectBoq::where('id', $id)->first();
        $approvalStages = Approval::getApprovalStages($id, $document_type_id);
        $nextApproval = Approval::getNextApproval($id, $document_type_id);
        $approvalCompleted = Approval::isApprovalCompleted($id, $document_type_id);
        $rejected = Approval::isRejected($id, $document_type_id);
        $document_id = $id;

        $boqItems = ProjectBoqItem::where('boq_id', $id)->get();

        $data = [
            'boq' => $boq,
            'boqItems' => $boqItems,
            'approvalStages' => $approvalStages,
            'nextApproval' => $nextApproval,
            'approvalCompleted' => $approvalCompleted,
            'rejected' => $rejected,
            'document_id' => $document_id,
        ];
        return view('pages.projects.project_boq')->with($data);
    }

    // Get next version number for a project
    public function getNextVersion(Request $request) {
        $projectId = $request->project_id;
        $latestVersion = ProjectBoq::where('project_id', $projectId)
            ->max('version');
        return response()->json(['version' => ($latestVersion + 1)]);
    }

    // Calculate BOQ totals
    public function calculateTotals($id) {
        $boq = ProjectBoq::findOrFail($id);
        $totalAmount = ProjectBoqItem::where('boq_id', $id)
            ->sum('total_price');

        $boq->update(['total_amount' => $totalAmount]);
        return response()->json(['total_amount' => $totalAmount]);
    }
}
