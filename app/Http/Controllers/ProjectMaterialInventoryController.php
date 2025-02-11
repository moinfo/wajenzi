<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectMaterialInventory;
use Illuminate\Http\Request;

class ProjectMaterialInventoryController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectMaterialInventory')) {
            return back();
        }

        $inventories = ProjectMaterialInventory::with(['project', 'material'])->get();
        $projects = Project::all();
        $materials = ProjectMaterial::all();

        $data = [
            'inventories' => $inventories,
            'projects' => $projects,
            'materials' => $materials
        ];
        return view('pages.projects.project_material_inventory')->with($data);
    }

    // Update inventory quantity
    public function updateQuantity(Request $request, $id) {
        $inventory = ProjectMaterialInventory::findOrFail($id);
        $oldQuantity = $inventory->quantity;

        switch($request->action) {
            case 'add':
                $inventory->quantity += $request->quantity;
                break;
            case 'subtract':
                if($inventory->quantity < $request->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient inventory quantity'
                    ], 400);
                }
                $inventory->quantity -= $request->quantity;
                break;
            case 'set':
                $inventory->quantity = $request->quantity;
                break;
        }

        $inventory->save();

        // Create inventory movement record
        \App\Models\ProjectMaterialMovement::create([
            'inventory_id' => $id,
            'action' => $request->action,
            'quantity' => $request->quantity,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $inventory->quantity,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'new_quantity' => $inventory->quantity
        ]);
    }

    // Check low inventory
    public function checkLowInventory() {
        $lowInventory = ProjectMaterialInventory::with(['material', 'project'])
            ->whereRaw('quantity <= materials.minimum_quantity')
            ->join('project_materials as materials', 'materials.id', '=', 'project_material_inventory.material_id')
            ->get();

        return response()->json($lowInventory);
    }

    // Generate inventory report
    public function generateReport(Request $request) {
        $inventories = ProjectMaterialInventory::with(['project', 'material']);

        if($request->project_id) {
            $inventories->where('project_id', $request->project_id);
        }

        if($request->material_id) {
            $inventories->where('material_id', $request->material_id);
        }

        $inventories = $inventories->get();

        // Generate report logic here...

        return back()->with('success', 'Report generated successfully');
    }
}
