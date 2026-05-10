<?php

namespace App\Http\Controllers;

use App\Models\BillingDocument;
use App\Models\ProjectClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CalculatorBillingController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'doc_type'           => 'required|in:quote,proforma,invoice',
            'client_id'          => 'required|exists:project_clients,id',
            'items'              => 'required|array|min:1',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.quantity'   => 'required|numeric|min:0.01',
        ]);

        DB::beginTransaction();
        try {
            $doc = new BillingDocument();
            $doc->document_type      = $request->doc_type;
            $doc->document_number    = $doc->generateDocumentNumber($request->doc_type);
            $doc->client_id          = $request->client_id;
            $doc->status             = 'draft';
            $doc->issue_date         = now()->toDateString();
            $doc->currency_code      = $request->currency_code ?? 'USD';
            $doc->exchange_rate      = $request->exchange_rate  ?? 1;
            $doc->notes              = $request->notes;
            $doc->service_description = $request->service_description;
            $doc->created_by         = auth()->id();
            $doc->save();

            foreach ($request->items as $idx => $item) {
                $qty        = (float) ($item['quantity']   ?? 1);
                $unitPrice  = (float) ($item['unit_price'] ?? 0);
                $lineTotal  = $qty * $unitPrice;

                $doc->items()->create([
                    'item_type'  => 'custom',
                    'item_name'  => $item['item_name'],
                    'description'=> $item['description'] ?? null,
                    'quantity'   => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'sort_order' => $idx + 1,
                ]);
            }

            $doc->calculateTotals();
            DB::commit();

            $routeMap = [
                'quote'    => 'billing.quotations.show',
                'proforma' => 'billing.proformas.show',
                'invoice'  => 'billing.invoices.show',
            ];

            return redirect()
                ->route($routeMap[$request->doc_type], $doc)
                ->with('success', ucfirst($request->doc_type) . ' created from calculator successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create document: ' . $e->getMessage());
        }
    }
}
