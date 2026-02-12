<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectMaterialMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectMaterialInventoryController extends Controller
{
    /**
     * Project selector page — menu entry point.
     */
    public function selectProject()
    {
        $projects = Project::whereHas('boqs')->orderBy('project_name')->get();

        return view('pages.procurement.stock_register_select', compact('projects'));
    }

    /**
     * Stock register for a single project.
     */
    public function stockRegister($projectId)
    {
        $project = Project::findOrFail($projectId);

        $inventories = ProjectMaterialInventory::forProject($projectId)
            ->with(['boqItem', 'material'])
            ->get();

        $stats = [
            'total'        => $inventories->count(),
            'in_stock'     => $inventories->filter(fn ($i) => $i->stock_status === 'in_stock')->count(),
            'low_stock'    => $inventories->filter(fn ($i) => $i->stock_status === 'low_stock')->count(),
            'out_of_stock' => $inventories->filter(fn ($i) => $i->stock_status === 'out_of_stock')->count(),
        ];

        return view('pages.procurement.stock_register', compact('project', 'inventories', 'stats'));
    }

    /**
     * Material movements history for a project.
     */
    public function movements(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $query = ProjectMaterialMovement::forProject($projectId)
            ->with(['boqItem', 'inventory', 'performedBy'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('id', 'desc');

        // Date range filter
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date', now()->toDateString());
        $query->inDateRange($startDate, $endDate);

        // Movement type filter
        if ($request->filled('movement_type')) {
            $query->ofType($request->movement_type);
        }

        $movements     = $query->paginate(25)->appends($request->query());
        $movementType  = $request->input('movement_type', '');

        return view('pages.procurement.stock_movements', compact(
            'project', 'movements', 'startDate', 'endDate', 'movementType'
        ));
    }

    /**
     * Show the issue-materials form.
     */
    public function issueForm($projectId)
    {
        $project = Project::findOrFail($projectId);

        $inventories = ProjectMaterialInventory::forProject($projectId)
            ->inStock()
            ->with('boqItem')
            ->get();

        return view('pages.procurement.issue_materials', compact('project', 'inventories'));
    }

    /**
     * Process material issue.
     */
    public function storeIssue(Request $request, $projectId)
    {
        $project = Project::findOrFail($projectId);

        $request->validate([
            'items'              => 'required|array|min:1',
            'items.*.inventory_id' => 'required|exists:project_material_inventory,id',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.location'   => 'nullable|string|max:255',
            'items.*.notes'      => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request, $projectId) {
            foreach ($request->items as $item) {
                $inventory = ProjectMaterialInventory::findOrFail($item['inventory_id']);

                if ($item['quantity'] > $inventory->quantity_available) {
                    abort(422, "Cannot issue {$item['quantity']} — only {$inventory->quantity_available} available for {$inventory->boqItem?->description}.");
                }

                ProjectMaterialMovement::createIssue(
                    $projectId,
                    $inventory->boq_item_id,
                    (float) $item['quantity'],
                    $inventory->boqItem?->unit,
                    $item['notes'] ?? null,
                    $item['location'] ?? null,
                );
            }
        });

        return redirect()
            ->route('stock_register', $projectId)
            ->with('success', 'Materials issued successfully.');
    }

    /**
     * Show the stock adjustment form for one inventory item.
     */
    public function adjustForm($projectId, $inventoryId)
    {
        $project   = Project::findOrFail($projectId);
        $inventory = ProjectMaterialInventory::where('project_id', $projectId)
            ->with('boqItem')
            ->findOrFail($inventoryId);

        return view('pages.procurement.adjust_stock', compact('project', 'inventory'));
    }

    /**
     * Process a stock adjustment.
     */
    public function storeAdjustment(Request $request, $projectId, $inventoryId)
    {
        $project   = Project::findOrFail($projectId);
        $inventory = ProjectMaterialInventory::where('project_id', $projectId)
            ->findOrFail($inventoryId);

        $request->validate([
            'new_quantity' => 'required|numeric|min:0',
            'reason'       => 'required|string|max:500',
        ]);

        $inventory->adjust((float) $request->new_quantity, $request->reason);

        return redirect()
            ->route('stock_register', $projectId)
            ->with('success', 'Stock adjusted successfully.');
    }

    /**
     * Verify a material movement.
     */
    public function verifyMovement($projectId, $movementId)
    {
        $movement = ProjectMaterialMovement::where('project_id', $projectId)
            ->findOrFail($movementId);

        if ($movement->isVerified()) {
            return back()->with('info', 'Movement already verified.');
        }

        $movement->verify();

        return back()->with('success', "Movement {$movement->movement_number} verified.");
    }
}
