<?php
namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\ProjectMaterialInventory;
use App\Models\ProjectMaterialRequest;
use App\Models\Approval;
use Illuminate\Http\Request;

class ProjectMaterialController extends Controller
{
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectMaterial')) {
            return back();
        }

        $materials = ProjectMaterial::withCount(['inventory'])
            ->with('inventory')
            ->get();

        foreach($materials as $material) {
            $material->total_inventory = $material->inventory->sum('quantity');
        }

        $data = [
            'materials' => $materials,
            'units' => ['kg', 'pieces', 'meters', 'liters', 'boxes']  // Example units
        ];
        return view('pages.projects.project_materials')->with($data);
    }

    public function material($id){
        $material = ProjectMaterial::with(['inventory', 'requests'])->findOrFail($id);

        $data = [
            'material' => $material,
            'totalQuantity' => $material->inventory->sum('quantity'),
            'pendingRequests' => $material->requests->where('status', 'pending')->count()
        ];
        return view('pages.projects.project_material')->with($data);
    }

    // Update material price
    public function updatePrice(Request $request, $id) {
        $material = ProjectMaterial::findOrFail($id);
        $material->update([
            'current_price' => $request->price
        ]);

        return response()->json(['success' => true]);
    }
}
