<?php

namespace App\Http\Controllers;

use App\Models\ExpensesSubCategory;
use App\Models\MaterialTransfer;
use App\Models\MaterialTransferItem;
use App\Models\Project;
use App\Models\ProjectBoqItem;
use App\Models\ProjectMaterialRequest;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialTransferController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function index(Request $request)
    {
        $transfers = MaterialTransfer::with(['fromProject', 'toProject', 'requester', 'approvalStatus', 'items'])
            ->orderBy('id', 'desc')
            ->get();

        return view('pages.procurement.material_transfers', [
            'transfers' => $transfers,
            'projects' => Project::orderBy('project_name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $fromProjectId = $request->query('from_project_id');
        $sourceItems = $fromProjectId
            ? ProjectBoqItem::whereHas('boq', fn($q) => $q->where('project_id', $fromProjectId))
                ->get()
                ->filter(fn($i) => max(0, ((float)$i->quantity_received) - ((float)$i->quantity_used)) > 0)
                ->values()
            : collect();

        return view('pages.procurement.material_transfer_create', [
            'projects' => Project::orderBy('project_name')->get(),
            'fromProjectId' => $fromProjectId,
            'sourceItems' => $sourceItems,
            'expensesSubCategories' => ExpensesSubCategory::orderBy('name')->get(),
            'pendingMaterialRequests' => ProjectMaterialRequest::whereRaw("UPPER(status) NOT IN ('APPROVED','COMPLETED','REJECTED')")
                ->orWhereNull('status')
                ->orderBy('id', 'desc')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_project_id' => 'required|exists:projects,id|different:to_project_id',
            'to_project_id' => 'required|exists:projects,id',
            'transfer_date' => 'required|date',
            'expected_arrival_date' => 'nullable|date|after_or_equal:transfer_date',
            'loading_cost' => 'nullable|numeric|min:0',
            'offloading_cost' => 'nullable|numeric|min:0',
            'transportation_cost' => 'nullable|numeric|min:0',
            'expenses_sub_category_id' => 'nullable|exists:expenses_sub_categories,id',
            'material_request_id' => 'nullable|exists:project_material_requests,id',
            'vehicle_info' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.source_boq_item_id' => 'nullable|exists:project_boq_items,id',
            'items.*.destination_boq_item_id' => 'nullable|exists:project_boq_items,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string|max:50',
        ]);

        // Stock check on source: cannot transfer more than available at source.
        foreach ($request->items as $i => $itemData) {
            if (empty($itemData['source_boq_item_id'])) {
                continue;
            }
            $source = ProjectBoqItem::find($itemData['source_boq_item_id']);
            $available = max(0, ((float)$source->quantity_received) - ((float)$source->quantity_used));
            if ((float)$itemData['quantity'] > $available) {
                return back()->withErrors([
                    "items.{$i}.quantity" => "Item {$source->item_code}: only {$available} {$source->unit} available at source.",
                ])->withInput();
            }
        }

        DB::transaction(function () use ($request) {
            $transfer = MaterialTransfer::create([
                'from_project_id' => $request->from_project_id,
                'to_project_id' => $request->to_project_id,
                'material_request_id' => $request->material_request_id,
                'requester_id' => auth()->id(),
                'status' => 'pending',
                'transfer_date' => $request->transfer_date,
                'expected_arrival_date' => $request->expected_arrival_date,
                'loading_cost' => $request->loading_cost ?? 0,
                'offloading_cost' => $request->offloading_cost ?? 0,
                'transportation_cost' => $request->transportation_cost ?? 0,
                'expenses_sub_category_id' => $request->expenses_sub_category_id,
                'vehicle_info' => $request->vehicle_info,
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $i => $itemData) {
                $source = !empty($itemData['source_boq_item_id'])
                    ? ProjectBoqItem::find($itemData['source_boq_item_id'])
                    : null;

                MaterialTransferItem::create([
                    'material_transfer_id' => $transfer->id,
                    'source_boq_item_id' => $source?->id,
                    'destination_boq_item_id' => $itemData['destination_boq_item_id'] ?? null,
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'],
                    'specification' => $source?->specification,
                    'sort_order' => $i,
                ]);
            }
        });

        return redirect()->route('material_transfers')->with('success', 'Material transfer created and submitted for approval.');
    }

    public function show($id, $document_type_id = 0)
    {
        $transfer = MaterialTransfer::with(['fromProject', 'toProject', 'requester', 'approver', 'items.sourceBoqItem', 'items.destinationBoqItem', 'expensesSubCategory'])
            ->findOrFail($id);

        $details = [
            'Transfer Number' => $transfer->transfer_number,
            'From Project' => $transfer->fromProject->project_name ?? 'N/A',
            'To Project' => $transfer->toProject->project_name ?? 'N/A',
            'Items' => $transfer->items->count() . ' item(s)',
            'Transfer Date' => optional($transfer->transfer_date)->format('d M Y'),
            'Loading Cost' => number_format($transfer->loading_cost, 2),
            'Offloading Cost' => number_format($transfer->offloading_cost, 2),
            'Transportation' => number_format($transfer->transportation_cost, 2),
            'Total Cost' => number_format($transfer->total_cost, 2),
            'Vehicle' => $transfer->vehicle_info ?: '—',
            'Requester' => $transfer->requester->name ?? 'N/A',
        ];

        return view('pages.procurement.material_transfer_show', [
            'transfer' => $transfer,
            'approval_data' => $transfer,
            'document_id' => $transfer->id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Material Transfer',
            'approval_data_name' => $transfer->transfer_number,
            'details' => $details,
            'model' => 'MaterialTransfer',
            'route' => 'material_transfers',
        ]);
    }

    public function destroy($id)
    {
        $transfer = MaterialTransfer::findOrFail($id);
        if ($transfer->isApproved()) {
            return back()->with('error', 'Cannot delete an approved transfer.');
        }
        $transfer->items()->delete();
        $transfer->delete();
        return back()->with('success', 'Transfer deleted.');
    }
}
