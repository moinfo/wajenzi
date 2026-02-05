<?php

namespace App\Http\Controllers;

use App\Models\MaterialInspection;
use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\SupplierReceiving;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialInspectionController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display listing of inspections
     */
    public function index(Request $request)
    {
        if ($this->handleCrud($request, 'MaterialInspection')) {
            return back();
        }

        $start_date = $request->input('start_date') ?? date('Y-m-01');
        $end_date = $request->input('end_date') ?? date('Y-m-d');
        $project_id = $request->input('project_id');

        $query = MaterialInspection::with([
                'supplierReceiving.supplier',
                'project',
                'boqItem',
                'inspector'
            ])
            ->whereBetween('inspection_date', [$start_date, $end_date])
            ->orderBy('created_at', 'desc');

        if ($project_id) {
            $query->where('project_id', $project_id);
        }

        $inspections = $query->get();
        $projects = Project::orderBy('name')->get();

        // Get receivings pending inspection
        $pendingReceivings = SupplierReceiving::with(['supplier', 'purchase.project'])
            ->pendingInspection()
            ->orderBy('date', 'desc')
            ->limit(20)
            ->get();

        return view('pages.procurement.material_inspections')->with([
            'inspections' => $inspections,
            'projects' => $projects,
            'pendingReceivings' => $pendingReceivings,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'selected_project' => $project_id
        ]);
    }

    /**
     * Show form to create inspection from receiving
     */
    public function create($receivingId)
    {
        $receiving = SupplierReceiving::with([
            'supplier',
            'purchase.project',
            'purchase.purchaseItems.boqItem'
        ])->findOrFail($receivingId);

        // Check if inspection already exists
        if ($receiving->inspections()->exists()) {
            $existingInspection = $receiving->inspections()->first();
            return redirect()->route('material_inspection', [
                'id' => $existingInspection->id,
                'document_type_id' => 0
            ])->with('info', 'An inspection already exists for this delivery');
        }

        // Get BOQ items from purchase
        $boqItems = collect();
        if ($receiving->purchase) {
            $boqItems = $receiving->purchase->purchaseItems()
                ->with('boqItem')
                ->get()
                ->pluck('boqItem')
                ->filter();
        }

        // If no BOQ items from purchase, get from project
        if ($boqItems->isEmpty() && $receiving->project_id) {
            $boqItems = ProjectBoqItem::byProject($receiving->project_id)->get();
        }

        $criteriaChecklist = [
            'packaging_intact' => 'Packaging is intact and undamaged',
            'quantity_correct' => 'Quantity matches delivery note',
            'specification_match' => 'Specifications match order requirements',
            'no_visible_defects' => 'No visible defects or damage',
            'proper_labeling' => 'Proper labeling and documentation',
            'storage_suitable' => 'Materials are suitable for storage'
        ];

        return view('pages.procurement.create_inspection')->with([
            'receiving' => $receiving,
            'boqItems' => $boqItems,
            'criteriaChecklist' => $criteriaChecklist
        ]);
    }

    /**
     * Store new inspection
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_receiving_id' => 'required|exists:supplier_receivings,id',
            'project_id' => 'required|exists:projects,id',
            'quantity_delivered' => 'required|numeric|min:0',
            'quantity_accepted' => 'required|numeric|min:0|lte:quantity_delivered',
            'overall_condition' => 'required|in:excellent,good,acceptable,poor,rejected',
            'inspection_notes' => 'nullable|string'
        ]);

        $receiving = SupplierReceiving::findOrFail($request->supplier_receiving_id);

        try {
            DB::beginTransaction();

            $criteriaChecklist = [];
            foreach ($request->all() as $key => $value) {
                if (strpos($key, 'criteria_') === 0) {
                    $criteriaKey = str_replace('criteria_', '', $key);
                    $criteriaChecklist[$criteriaKey] = $value === 'on' || $value === '1';
                }
            }

            $inspection = MaterialInspection::create([
                'supplier_receiving_id' => $request->supplier_receiving_id,
                'project_id' => $request->project_id,
                'boq_item_id' => $request->boq_item_id,
                'inspection_date' => now(),
                'quantity_delivered' => $request->quantity_delivered,
                'quantity_accepted' => $request->quantity_accepted,
                'quantity_rejected' => $request->quantity_delivered - $request->quantity_accepted,
                'overall_condition' => $request->overall_condition,
                'rejection_reason' => $request->rejection_reason,
                'inspection_notes' => $request->inspection_notes,
                'criteria_checklist' => $criteriaChecklist,
                'inspector_id' => auth()->id(),
                'status' => 'pending'
            ]);

            // Update receiving status
            $receiving->status = 'received';
            $receiving->save();

            // Auto-submit for approval
            $inspection->submit();

            DB::commit();

            return redirect()->route('material_inspections')
                ->with('success', 'Material inspection created: ' . $inspection->inspection_number);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create inspection: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show inspection details with approval workflow
     */
    public function inspection($id, $document_type_id)
    {
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'material_inspection');

        $inspection = MaterialInspection::with([
            'supplierReceiving.supplier',
            'supplierReceiving.purchase',
            'project',
            'boqItem',
            'inspector',
            'verifier'
        ])->findOrFail($id);

        $details = [
            'Inspection Number' => $inspection->inspection_number,
            'Inspection Date' => $inspection->inspection_date?->format('Y-m-d'),
            'Project' => $inspection->project?->name,
            'Supplier' => $inspection->supplierReceiving?->supplier?->name,
            'Delivery Note' => $inspection->supplierReceiving?->delivery_note_number,
            'Quantity Delivered' => number_format($inspection->quantity_delivered, 2),
            'Quantity Accepted' => number_format($inspection->quantity_accepted, 2),
            'Quantity Rejected' => number_format($inspection->quantity_rejected, 2),
            'Acceptance Rate' => number_format($inspection->acceptance_rate, 1) . '%',
            'Overall Condition' => ucfirst($inspection->overall_condition),
            'Result' => ucfirst($inspection->overall_result),
            'Inspector' => $inspection->inspector?->name,
            'Stock Updated' => $inspection->stock_updated ? 'Yes' : 'No'
        ];

        return view('approvals._approve_page')->with([
            'approval_data' => $inspection,
            'document_id' => $id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Material Inspection',
            'approval_data_name' => $inspection->inspection_number,
            'details' => $details,
            'model' => 'MaterialInspection',
            'route' => 'material_inspection',
            'inspection_notes' => $inspection->inspection_notes,
            'rejection_reason' => $inspection->rejection_reason,
            'criteria_checklist' => $inspection->criteria_checklist
        ]);
    }

    /**
     * Manually trigger stock update for approved inspection
     */
    public function updateStock($id)
    {
        $inspection = MaterialInspection::findOrFail($id);

        if (!$inspection->isApproved()) {
            return back()->with('error', 'Only approved inspections can update stock');
        }

        if ($inspection->stock_updated) {
            return back()->with('info', 'Stock has already been updated for this inspection');
        }

        try {
            $inspection->updateStock();
            return back()->with('success', 'Stock updated successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update stock: ' . $e->getMessage());
        }
    }

    /**
     * Approval actions
     */
    public function submit(MaterialInspection $inspection)
    {
        $inspection->status = 'pending';
        $inspection->save();
        $inspection->submit();

        return back()->with('success', 'Inspection submitted for approval');
    }

    public function approve(MaterialInspection $inspection)
    {
        $inspection->approve();
        return back()->with('success', 'Inspection approved. Stock has been updated.');
    }

    public function reject(MaterialInspection $inspection, Request $request)
    {
        $inspection->reject($request->reason);
        return back()->with('success', 'Inspection rejected');
    }
}
