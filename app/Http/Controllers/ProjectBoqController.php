<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBoq;
use App\Models\ProjectBoqItem;
use App\Models\ProjectBoqSection;
use App\Models\ProjectBoqTemplate;
use App\Models\Approval;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use PDF;

class ProjectBoqController extends Controller
{
    protected $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }
    public function index(Request $request) {
        //handle crud operations
        if($this->handleCrud($request, 'ProjectBoq')) {
            return back();
        }

        $boqs = ProjectBoq::with(['project', 'approvalStatus'])->get();
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
            'approvalStatus',
        ]);

        $pendingRequests = \App\Models\ProjectMaterialRequest::with(['items.boqItem', 'requester', 'approvalStatus'])
            ->where('project_id', $boq->project_id)
            ->whereRaw('UPPER(status) NOT IN (?, ?)', ['APPROVED', 'COMPLETED'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Collect BOQ item IDs that already have pending requests
        $pendingBoqItemIds = $pendingRequests->flatMap(fn($r) => $r->items->pluck('boq_item_id'))->filter()->unique()->values()->all();

        return view('pages.projects.project_boq_items')->with([
            'boq' => $boq,
            'pendingRequests' => $pendingRequests,
            'pendingBoqItemIds' => $pendingBoqItemIds,
        ]);
    }

    public function boq($id, $document_type_id){
        $this->approvalService->markNotificationAsRead($id, $document_type_id, 'project_boq');

        $boq = ProjectBoq::with(['project', 'items', 'approvalStatus'])->findOrFail($id);

        $details = [
            'Project' => $boq->project->project_name ?? 'N/A',
            'Version' => $boq->version,
            'Type' => ucfirst($boq->type ?? 'N/A'),
            'Items' => $boq->items->count() . ' item(s)',
            'Total Amount' => number_format($boq->total_amount, 2),
        ];

        $data = [
            'approval_data' => $boq,
            'boq' => $boq,
            'document_id' => $id,
            'approval_document_type_id' => $document_type_id,
            'page_name' => 'Bill of Quantities',
            'approval_data_name' => $boq->document_number,
            'details' => $details,
            'model' => 'ProjectBoq',
            'route' => 'project_boq',
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

            // Column headers — flat editable format
            fputcsv($file, ['Section', 'Description', 'Type', 'Specification', 'Unit', 'Qty', 'Unit Price']);

            // Write sections recursively
            $this->writeCsvSectionFlat($file, $boq->rootSections, '');

            // Unsectioned items
            foreach ($boq->unsectionedItems as $item) {
                fputcsv($file, [
                    '',
                    $item->description,
                    $item->item_type,
                    $item->specification ?? '',
                    $item->unit,
                    $item->quantity,
                    $item->unit_price,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Recursively write sections to CSV in flat format.
     * Section path uses / for nesting: "PARENT/CHILD"
     */
    private function writeCsvSectionFlat($file, $sections, string $parentPath)
    {
        foreach ($sections as $section) {
            $sectionPath = $parentPath ? ($parentPath . '/' . $section->name) : $section->name;

            // Items in this section
            foreach ($section->items as $item) {
                fputcsv($file, [
                    $sectionPath,
                    $item->description,
                    $item->item_type,
                    $item->specification ?? '',
                    $item->unit,
                    $item->quantity,
                    $item->unit_price,
                ]);
            }

            // Recurse into children
            if ($section->childrenRecursive->count() > 0) {
                $this->writeCsvSectionFlat($file, $section->childrenRecursive, $sectionPath);
            }
        }
    }

    /**
     * Import BOQ items from CSV — replaces all existing sections & items.
     */
    public function importCsv(Request $request, $id)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $boq = ProjectBoq::findOrFail($id);
        $items = $this->parseCsvItems($request->file('csv_file')->getRealPath());

        if (is_string($items)) {
            return back()->with('error', $items);
        }

        // Delete existing sections & items
        $boq->items()->delete();
        $boq->sections()->delete();

        // Create sections
        $sectionMap = $this->createSectionsFromPaths(
            $items,
            fn($data) => $boq->sections()->create($data)
        );

        // Create items
        foreach ($items as $item) {
            $sectionId = $item['section_path'] !== '' ? ($sectionMap[$item['section_path']] ?? null) : null;
            $quantity = $item['quantity'];
            $unitPrice = $item['unit_price'];

            $boq->items()->create([
                'section_id' => $sectionId,
                'description' => $item['description'],
                'item_type' => in_array($item['item_type'], ['material', 'labour']) ? $item['item_type'] : 'material',
                'specification' => $item['specification'],
                'unit' => $item['unit'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'sort_order' => 0,
            ]);
        }

        $boq->recalculateTotals();

        return back()->with('success', count($items) . ' items imported successfully from CSV.');
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

    /**
     * List and manage BOQ templates.
     */
    public function templates(Request $request)
    {
        if ($this->handleCrud($request, 'ProjectBoqTemplate')) {
            return back();
        }

        $templates = ProjectBoqTemplate::with(['sourceBoq.project', 'creator'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('pages.projects.project_boq_templates')->with(['templates' => $templates]);
    }

    /**
     * Show a single template with all sections and items.
     */
    public function showTemplate($id)
    {
        $template = ProjectBoqTemplate::with([
            'sourceBoq.project',
            'creator',
            'rootSections.items',
            'rootSections.childrenRecursive.items',
            'unsectionedItems',
        ])->findOrFail($id);

        return view('pages.projects.project_boq_template_show')->with(['template' => $template]);
    }

    /**
     * Save an existing BOQ as a reusable template.
     */
    public function saveAsTemplate(Request $request, $id)
    {
        $request->validate([
            'template_name' => 'required|string|max:255',
            'template_description' => 'nullable|string',
        ]);

        $boq = ProjectBoq::findOrFail($id);

        ProjectBoqTemplate::createFromBoq(
            $boq,
            $request->template_name,
            $request->template_description
        );

        return back()->with('success', 'BOQ saved as template successfully.');
    }

    /**
     * Apply a template to an existing BOQ (clones sections + items).
     */
    public function applyTemplate(Request $request, $id)
    {
        $request->validate([
            'template_id' => 'required|exists:project_boq_templates,id',
        ]);

        $boq = ProjectBoq::findOrFail($id);
        $template = ProjectBoqTemplate::findOrFail($request->template_id);

        $template->applyToBoq($boq);

        return back()->with('success', 'Template applied successfully.');
    }

    /**
     * Delete a BOQ template.
     */
    public function deleteTemplate($id)
    {
        ProjectBoqTemplate::findOrFail($id)->delete();
        return back()->with('success', 'Template deleted.');
    }

    /**
     * Export a template as CSV (same flat format as BOQ export).
     */
    public function exportTemplateCsv($id)
    {
        $template = ProjectBoqTemplate::with([
            'rootSections.items',
            'rootSections.childrenRecursive.items',
            'unsectionedItems',
        ])->findOrFail($id);

        $filename = 'Template-' . str_replace(' ', '_', $template->name) . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($template) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['Section', 'Description', 'Type', 'Specification', 'Unit', 'Qty', 'Unit Price']);

            $this->writeCsvSectionFlat($file, $template->rootSections, '');

            foreach ($template->unsectionedItems as $item) {
                fputcsv($file, [
                    '',
                    $item->description,
                    $item->item_type,
                    $item->specification ?? '',
                    $item->unit,
                    $item->quantity,
                    $item->unit_price,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import CSV into a template — replaces all existing sections & items.
     */
    public function importTemplateCsv(Request $request, $id)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $template = ProjectBoqTemplate::findOrFail($id);
        $items = $this->parseCsvItems($request->file('csv_file')->getRealPath());

        if (is_string($items)) {
            return back()->with('error', $items);
        }

        // Delete existing
        $template->items()->delete();
        $template->sections()->delete();

        // Create sections
        $sectionMap = $this->createSectionsFromPaths(
            $items,
            fn($data) => $template->sections()->create($data)
        );

        // Create items
        foreach ($items as $item) {
            $sectionId = $item['section_path'] !== '' ? ($sectionMap[$item['section_path']] ?? null) : null;
            $quantity = $item['quantity'];
            $unitPrice = $item['unit_price'];

            $template->items()->create([
                'section_id' => $sectionId,
                'description' => $item['description'],
                'item_type' => in_array($item['item_type'], ['material', 'labour']) ? $item['item_type'] : 'material',
                'specification' => $item['specification'],
                'unit' => $item['unit'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $quantity * $unitPrice,
                'sort_order' => 0,
            ]);
        }

        // Update template total
        $template->update([
            'total_amount' => $template->items()->sum('total_price'),
        ]);

        return back()->with('success', count($items) . ' items imported successfully from CSV.');
    }

    /**
     * Parse CSV file into an array of item data. Returns error string on failure.
     */
    private function parseCsvItems(string $filePath): array|string
    {
        $csv = array_map('str_getcsv', file($filePath));

        if (!empty($csv[0][0])) {
            $csv[0][0] = preg_replace('/^\x{FEFF}/u', '', $csv[0][0]);
        }

        $headerIndex = null;
        foreach ($csv as $i => $row) {
            if (isset($row[0]) && strtolower(trim($row[0])) === 'section') {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            return 'CSV must have a header row with "Section" column. Export a CSV first to see the format.';
        }

        $headers = array_map(fn($h) => strtolower(trim($h)), $csv[$headerIndex]);
        $dataRows = array_slice($csv, $headerIndex + 1);

        $col = [
            'section' => array_search('section', $headers),
            'description' => array_search('description', $headers),
            'type' => array_search('type', $headers),
            'specification' => array_search('specification', $headers),
            'unit' => array_search('unit', $headers),
            'qty' => array_search('qty', $headers),
            'unit_price' => array_search('unit price', $headers),
        ];

        if ($col['description'] === false || $col['qty'] === false || $col['unit_price'] === false) {
            return 'CSV must have at least: Description, Qty, Unit Price columns.';
        }

        $items = [];
        foreach ($dataRows as $row) {
            $description = trim($row[$col['description']] ?? '');
            if ($description === '') continue;

            $items[] = [
                'section_path' => trim($row[$col['section']] ?? ''),
                'description' => $description,
                'item_type' => strtolower(trim($row[$col['type']] ?? 'material')) ?: 'material',
                'specification' => trim($row[$col['specification']] ?? '') ?: null,
                'unit' => trim($row[$col['unit']] ?? '') ?: null,
                'quantity' => (float) str_replace(',', '', $row[$col['qty']] ?? 0),
                'unit_price' => (float) str_replace(',', '', $row[$col['unit_price']] ?? 0),
            ];
        }

        if (empty($items)) {
            return 'No items found in CSV.';
        }

        return $items;
    }

    /**
     * Create hierarchical sections from parsed CSV section paths.
     * Returns map of 'SECTION/PATH' => section_id.
     */
    private function createSectionsFromPaths(array $items, \Closure $createFn): array
    {
        $sectionMap = [];
        $sortOrder = 0;

        foreach ($items as $item) {
            if ($item['section_path'] === '') continue;

            $parts = array_map('trim', explode('/', $item['section_path']));
            $currentPath = '';

            foreach ($parts as $depth => $name) {
                $currentPath = $depth === 0 ? $name : ($currentPath . '/' . $name);

                if (!isset($sectionMap[$currentPath])) {
                    $parentPath = $depth > 0 ? implode('/', array_slice($parts, 0, $depth)) : null;
                    $parentId = $parentPath ? ($sectionMap[$parentPath] ?? null) : null;

                    $section = $createFn([
                        'name' => $name,
                        'parent_id' => $parentId,
                        'sort_order' => $sortOrder++,
                    ]);
                    $sectionMap[$currentPath] = $section->id;
                }
            }
        }

        return $sectionMap;
    }
}
