<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use App\Models\ProjectBoqSection;
use App\Models\Approval;
use Illuminate\Http\Request;
use PDF;

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

    public function show(Request $request, $id){
        // Handle CRUD: button value contains the model class name
        if ($request->isMethod('POST')) {
            $crudClass = $request->input('addItem') ?: ($request->input('updateItem') ?: 'ProjectBoqItem');
            if ($this->handleCrud($request, $crudClass)) {
                return back();
            }
        }

        $boq = ProjectBoq::findOrFail($id);

        // Recalculate totals from all items
        $boq->recalculateTotals();

        // Eager-load hierarchical data for the view
        $boq->load([
            'project',
            'rootSections.items',
            'rootSections.childrenRecursive.items',
            'unsectionedItems',
        ]);

        return view('pages.projects.project_boq_items')->with(['boq' => $boq]);
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
        $boq->recalculateTotals();
        return response()->json(['total_amount' => $boq->fresh()->total_amount]);
    }

    /**
     * Export BOQ as PDF.
     */
    public function exportPdf($id)
    {
        $boq = ProjectBoq::with([
            'project',
            'rootSections.items',
            'rootSections.childrenRecursive.items',
            'unsectionedItems',
        ])->findOrFail($id);

        $pdf = PDF::loadView('pages.projects.boq_pdf', compact('boq'));
        $pdf->setPaper('A4', 'portrait');

        $filename = 'BOQ-' . ($boq->project->project_name ?? 'Project') . '-v' . $boq->version . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export BOQ as CSV.
     */
    public function exportCsv($id)
    {
        $boq = ProjectBoq::with([
            'project',
            'rootSections.items',
            'rootSections.childrenRecursive.items',
            'unsectionedItems',
        ])->findOrFail($id);

        $filename = 'BOQ-' . ($boq->project->project_name ?? 'Project') . '-v' . $boq->version . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($boq) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header info
            fputcsv($file, ['BILL OF QUANTITIES']);
            fputcsv($file, ['Project', $boq->project->project_name ?? '']);
            fputcsv($file, ['Version', $boq->version]);
            fputcsv($file, ['Type', ucfirst($boq->type)]);
            fputcsv($file, ['Date', now()->format('Y-m-d')]);
            fputcsv($file, []);

            // Column headers
            fputcsv($file, ['Section', 'Item Code', 'Description', 'Type', 'Unit', 'Qty', 'Unit Price', 'Total']);

            // Write sections recursively
            $this->writeCsvSection($file, $boq->rootSections, 0);

            // Unsectioned items
            if ($boq->unsectionedItems->count() > 0) {
                fputcsv($file, ['Unsectioned Items', '', '', '', '', '', '', '']);
                foreach ($boq->unsectionedItems as $item) {
                    fputcsv($file, [
                        '',
                        $item->item_code,
                        $item->description,
                        ucfirst($item->item_type),
                        $item->unit,
                        $item->quantity,
                        $item->unit_price,
                        $item->total_price,
                    ]);
                }
                fputcsv($file, ['', '', '', '', '', '', 'Subtotal', $boq->unsectionedItems->sum('total_price')]);
            }

            // Grand total
            fputcsv($file, []);
            fputcsv($file, ['', '', '', '', '', '', 'GRAND TOTAL', $boq->total_amount]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Recursively write sections to CSV.
     */
    private function writeCsvSection($file, $sections, $depth)
    {
        $indent = str_repeat('  ', $depth);

        foreach ($sections as $section) {
            // Section header row
            fputcsv($file, [$indent . $section->name, '', '', '', '', '', '', '']);

            // Items in this section
            foreach ($section->items as $item) {
                fputcsv($file, [
                    '',
                    $item->item_code,
                    $item->description,
                    ucfirst($item->item_type),
                    $item->unit,
                    $item->quantity,
                    $item->unit_price,
                    $item->total_price,
                ]);
            }

            // Recurse into children
            if ($section->childrenRecursive->count() > 0) {
                $this->writeCsvSection($file, $section->childrenRecursive, $depth + 1);
            }

            // Section subtotal
            fputcsv($file, ['', '', '', '', '', '', 'Subtotal - ' . $section->name, $section->subtotal]);
        }
    }

    /**
     * Section CRUD - uses handleCrud pattern.
     */
    public function sections(Request $request)
    {
        if ($this->handleCrud($request, 'ProjectBoqSection')) {
            return back();
        }

        return back();
    }
}
