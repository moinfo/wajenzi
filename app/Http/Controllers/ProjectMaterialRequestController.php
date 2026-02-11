<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\ProjectMaterialRequest;
use App\Models\ProjectMaterialRequestItem;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMaterialRequestController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectMaterialRequest')) {
            return back();
        }

        $requests = ProjectMaterialRequest::with(['project', 'items.boqItem', 'requester', 'approvalStatus'])->get();
        $projects = Project::all();

        $data = [
            'requests' => $requests,
            'projects' => $projects,
        ];
        return view('pages.projects.project_material_requests')->with($data);
    }

    public function request($id, $document_type_id){
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'project_material_request');

        $materialRequest = ProjectMaterialRequest::with(['project', 'items.boqItem', 'requester', 'approver', 'quotations'])->where('id', $id)->first();

        $details = [
            'Request Number' => $materialRequest->request_number,
            'Project' => $materialRequest->project->name ?? 'N/A',
            'Items' => $materialRequest->items->count() . ' item(s)',
            'Priority' => ucfirst($materialRequest->priority ?? 'medium'),
            'Required Date' => $materialRequest->required_date ? \Carbon\Carbon::parse($materialRequest->required_date)->format('d M Y') : 'N/A',
            'Requested By' => $materialRequest->requester->name ?? 'N/A',
        ];

        $data = [
            'approval_data' => $materialRequest,
            'request' => $materialRequest,
            'document_id' => $id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Material Request',
            'approval_data_name' => $materialRequest->request_number,
            'details' => $details,
            'model' => 'ProjectMaterialRequest',
            'route' => 'project_material_request',
        ];
        return view('pages.projects.material_request')->with($data);
    }

    public function storeBulk(Request $request) {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'items' => 'required|array|min:1',
            'items.*.boq_item_id' => 'required|exists:project_boq_items,id',
            'items.*.quantity_requested' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'required_date' => 'required|date',
            'priority' => 'required|in:low,medium,high,urgent',
        ]);

        // Collect BOQ item IDs that already have pending requests
        $pendingBoqItemIds = ProjectMaterialRequestItem::whereHas('materialRequest', function ($q) use ($request) {
            $q->where('project_id', $request->project_id)
              ->whereRaw('UPPER(status) NOT IN (?, ?)', ['APPROVED', 'COMPLETED']);
        })->pluck('boq_item_id')->filter()->unique()->all();

        // Validate each item quantity and check for pending duplicates
        foreach ($request->items as $i => $itemData) {
            if (in_array($itemData['boq_item_id'], $pendingBoqItemIds)) {
                $boqItem = ProjectBoqItem::find($itemData['boq_item_id']);
                return back()->withErrors([
                    "items.{$i}.boq_item_id" => ($boqItem->item_code ?? 'Item') . " already has a pending request."
                ])->withInput();
            }

            $boqItem = ProjectBoqItem::find($itemData['boq_item_id']);
            if ($boqItem) {
                $available = max(0, $boqItem->quantity - $boqItem->quantity_requested);
                if ($itemData['quantity_requested'] > $available) {
                    return back()->withErrors([
                        "items.{$i}.quantity_requested" => "Quantity for {$boqItem->item_code} exceeds available ({$available})."
                    ])->withInput();
                }
            }
        }

        DB::transaction(function () use ($request) {
            $materialRequest = ProjectMaterialRequest::create([
                'project_id' => $request->project_id,
                'requester_id' => auth()->id(),
                'status' => 'pending',
                'required_date' => $request->required_date,
                'purpose' => $request->purpose,
                'priority' => $request->priority,
            ]);

            foreach ($request->items as $i => $itemData) {
                $boqItem = ProjectBoqItem::find($itemData['boq_item_id']);

                ProjectMaterialRequestItem::create([
                    'material_request_id' => $materialRequest->id,
                    'boq_item_id' => $itemData['boq_item_id'],
                    'quantity_requested' => $itemData['quantity_requested'],
                    'unit' => $itemData['unit'],
                    'description' => $boqItem->description ?? null,
                    'specification' => $boqItem->specification ?? null,
                    'sort_order' => $i,
                ]);
            }

            // RingleSoft auto-creates approval status on model creation
        });

        return back()->with('success', 'Material request submitted with ' . count($request->items) . ' items.');
    }
}
