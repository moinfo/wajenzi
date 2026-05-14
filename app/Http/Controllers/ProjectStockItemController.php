<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStockItem;
use Illuminate\Http\Request;

class ProjectStockItemController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::orderBy('project_name')->get();
        $projectId = $request->query('project_id');

        $items = ProjectStockItem::with('project')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderBy('project_id')
            ->orderBy('description')
            ->get();

        return view('pages.procurement.project_stock_items', [
            'items'     => $items,
            'projects'  => $projects,
            'projectId' => $projectId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'project_id'      => 'required|exists:projects,id',
            'description'     => 'required|string|max:255',
            'unit'            => 'required|string|max:50',
            'quantity_on_hand' => 'required|numeric|min:0',
            'notes'           => 'nullable|string',
        ]);

        ProjectStockItem::create([
            'project_id'       => $request->project_id,
            'description'      => $request->description,
            'unit'             => $request->unit,
            'quantity_on_hand' => $request->quantity_on_hand,
            'notes'            => $request->notes,
            'created_by_id'    => auth()->id(),
        ]);

        return back()->with('success', 'Stock item added successfully.');
    }

    public function update(Request $request, $id)
    {
        $item = ProjectStockItem::findOrFail($id);

        $request->validate([
            'description'      => 'required|string|max:255',
            'unit'             => 'required|string|max:50',
            'quantity_on_hand' => 'required|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        $item->update($request->only('description', 'unit', 'quantity_on_hand', 'notes'));

        return back()->with('success', 'Stock item updated.');
    }

    public function destroy($id)
    {
        $item = ProjectStockItem::findOrFail($id);
        $item->delete();

        return back()->with('success', 'Stock item deleted.');
    }

    // AJAX: return stock items for a given project as JSON (used by transfer form)
    public function forProject(Request $request)
    {
        $items = ProjectStockItem::where('project_id', $request->project_id)
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('description')
            ->get(['id', 'item_code', 'description', 'unit', 'quantity_on_hand']);

        return response()->json($items);
    }
}
