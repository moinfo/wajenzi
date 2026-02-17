<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\Utility;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Efd;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Receipt;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\VatPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VatController extends Controller
{
    // ─── Reference data for form dropdowns ───────────────────────────────

    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'efds' => Efd::select('id', 'name')->orderBy('name')->get(),
                'suppliers' => Supplier::select('id', 'name', 'vrn')->orderBy('name')->get(),
                'items' => Item::select('id', 'name')->orderBy('name')->get(),
                'banks' => Bank::select('id', 'name')->orderBy('name')->get(),
                'purchase_types' => [
                    ['id' => 1, 'name' => 'VAT'],
                    ['id' => 2, 'name' => 'EXEMPT'],
                ],
            ],
        ]);
    }

    // ─── SALES ───────────────────────────────────────────────────────────

    public function sales(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));
        $efdId = $request->input('efd_id');

        $query = Sale::with('efd')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date', 'desc');

        if ($efdId) {
            $query->where('efd_id', $efdId);
        }

        $sales = $query->get()->map(fn($s) => $this->formatSale($s));

        $totals = [
            'turnover' => $sales->sum('turnover'),
            'net' => $sales->sum('net'),
            'tax' => $sales->sum('tax'),
            'turnover_exempt' => $sales->sum('turnover_exempt'),
        ];

        $efds = Efd::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sales' => $sales->values(),
                'totals' => $totals,
                'efds' => $efds,
            ],
        ]);
    }

    public function storeSale(Request $request): JsonResponse
    {
        $request->validate([
            'efd_id' => 'required|exists:efds,id',
            'amount' => 'required|numeric',
            'net' => 'required|numeric',
            'tax' => 'required|numeric',
            'turn_over' => 'required|numeric',
            'date' => 'required|date',
        ]);

        $nextId = Utility::getLastId('Sale') + 1;

        $sale = Sale::create([
            'efd_id' => $request->efd_id,
            'amount' => $request->amount,
            'net' => $request->net,
            'tax' => $request->tax,
            'turn_over' => $request->turn_over,
            'date' => $request->date,
            'status' => 'CREATED',
            'create_by_id' => $request->user()->id,
            'document_number' => "SALE/{$nextId}/" . date('Y'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatSale($sale->load('efd')),
        ], 201);
    }

    public function showSale(int $id): JsonResponse
    {
        $sale = Sale::with('efd')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $this->formatSale($sale),
        ]);
    }

    public function updateSale(Request $request, int $id): JsonResponse
    {
        $sale = Sale::findOrFail($id);

        $sale->update($request->only([
            'efd_id', 'amount', 'net', 'tax', 'turn_over', 'date',
        ]));

        return response()->json([
            'success' => true,
            'data' => $this->formatSale($sale->load('efd')),
        ]);
    }

    public function destroySale(int $id): JsonResponse
    {
        Sale::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    private function formatSale(Sale $s): array
    {
        return [
            'id' => $s->id,
            'date' => $s->date,
            'efd_id' => $s->efd_id,
            'efd_name' => $s->efd->name ?? null,
            'turnover' => (float) $s->amount,
            'net' => (float) $s->net,
            'tax' => (float) $s->tax,
            'turnover_exempt' => (float) $s->turn_over,
            'status' => $s->status,
            'has_attachment' => !empty($s->file),
            'document_number' => $s->document_number,
        ];
    }

    // ─── PURCHASES ───────────────────────────────────────────────────────

    public function purchases(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $purchases = Purchase::with(['supplier', 'item'])
            ->whereNotNull('item_id')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($p) => $this->formatPurchase($p));

        $totals = [
            'total_amount' => $purchases->sum('total_amount'),
            'amount_vat_exc' => $purchases->sum('amount_vat_exc'),
            'vat_amount' => $purchases->sum('vat_amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'purchases' => $purchases->values(),
                'totals' => $totals,
            ],
        ]);
    }

    public function storePurchase(Request $request): JsonResponse
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'item_id' => 'required|exists:items,id',
            'purchase_type' => 'required|in:1,2',
            'total_amount' => 'required|numeric',
            'tax_invoice' => 'required|string',
            'invoice_date' => 'required|date',
            'date' => 'required|date',
        ]);

        $nextId = Utility::getLastId('Purchase') + 1;

        // Calculate VAT fields based on purchase_type
        $totalAmount = (float) $request->total_amount;
        $amountVatExc = $request->purchase_type == 1 ? $totalAmount * 100 / 118 : 0;
        $vatAmount = $request->purchase_type == 1 ? $amountVatExc * 18 / 100 : 0;

        $purchase = Purchase::create([
            'supplier_id' => $request->supplier_id,
            'item_id' => $request->item_id,
            'purchase_type' => $request->purchase_type,
            'is_expense' => $request->input('is_expense', 'NO'),
            'total_amount' => $totalAmount,
            'amount_vat_exc' => $amountVatExc,
            'vat_amount' => $vatAmount,
            'tax_invoice' => $request->tax_invoice,
            'invoice_date' => $request->invoice_date,
            'date' => $request->date,
            'status' => 'CREATED',
            'create_by_id' => $request->user()->id,
            'document_number' => "PCHS/{$nextId}/" . date('Y'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPurchase($purchase->load(['supplier', 'item'])),
        ], 201);
    }

    public function showPurchase(int $id): JsonResponse
    {
        $purchase = Purchase::with(['supplier', 'item'])->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $this->formatPurchase($purchase),
        ]);
    }

    public function updatePurchase(Request $request, int $id): JsonResponse
    {
        $purchase = Purchase::findOrFail($id);

        $data = $request->only([
            'supplier_id', 'item_id', 'purchase_type', 'is_expense',
            'total_amount', 'tax_invoice', 'invoice_date', 'date',
        ]);

        // Recalculate VAT if total_amount or purchase_type changed
        $purchaseType = $data['purchase_type'] ?? $purchase->purchase_type;
        $totalAmount = (float) ($data['total_amount'] ?? $purchase->total_amount);
        $data['amount_vat_exc'] = $purchaseType == 1 ? $totalAmount * 100 / 118 : 0;
        $data['vat_amount'] = $purchaseType == 1 ? $data['amount_vat_exc'] * 18 / 100 : 0;

        $purchase->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->formatPurchase($purchase->load(['supplier', 'item'])),
        ]);
    }

    public function destroyPurchase(int $id): JsonResponse
    {
        Purchase::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    private function formatPurchase(Purchase $p): array
    {
        return [
            'id' => $p->id,
            'date' => $p->date,
            'supplier_id' => $p->supplier_id,
            'supplier_name' => $p->supplier->name ?? null,
            'supplier_vrn' => $p->supplier->vrn ?? null,
            'item_id' => $p->item_id,
            'goods' => $p->item->name ?? null,
            'purchase_type' => $p->purchase_type,
            'is_expense' => $p->is_expense,
            'tax_invoice' => $p->tax_invoice,
            'invoice_date' => $p->invoice_date,
            'total_amount' => (float) $p->total_amount,
            'amount_vat_exc' => (float) $p->amount_vat_exc,
            'vat_amount' => (float) $p->vat_amount,
            'status' => $p->status,
            'has_attachment' => !empty($p->file),
            'document_number' => $p->document_number,
        ];
    }

    // ─── AUTO PURCHASES ──────────────────────────────────────────────────

    public function autoPurchases(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', date('Y-01-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $receipts = Receipt::whereBetween('receipt_date', [$startDate, $endDate])
            ->orderBy('receipt_date', 'desc')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'date' => $r->receipt_date,
                'supplier_name' => $r->company_name,
                'supplier_vrn' => $r->vrn,
                'receipt_number' => $r->receipt_number,
                'invoice_date' => $r->receipt_date,
                'amount_vat_exc' => (float) $r->receipt_total_excl_of_tax,
                'vat_amount' => (float) $r->receipt_total_tax,
                'total_amount' => (float) $r->receipt_total_incl_of_tax,
                'discount' => (float) $r->receipt_total_discount,
                'verification_code' => $r->receipt_verification_code,
                'is_expense' => $r->is_expense,
            ]);

        $totals = [
            'amount_vat_exc' => $receipts->sum('amount_vat_exc'),
            'vat_amount' => $receipts->sum('vat_amount'),
            'total_amount' => $receipts->sum('total_amount'),
            'discount' => $receipts->sum('discount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'receipts' => $receipts->values(),
                'totals' => $totals,
            ],
        ]);
    }

    // ─── VAT PAYMENTS ────────────────────────────────────────────────────

    public function payments(Request $request): JsonResponse
    {
        $startDate = $request->input('start_date', date('Y-m-01'));
        $endDate = $request->input('end_date', date('Y-m-d'));

        $payments = VatPayment::with('bank')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($v) => $this->formatPayment($v));

        $totals = [
            'amount' => $payments->sum('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'payments' => $payments->values(),
                'totals' => $totals,
            ],
        ]);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'amount' => 'required|numeric',
            'date' => 'required|date',
        ]);

        $nextId = Utility::getLastId('VatPayment') + 1;

        $payment = VatPayment::create([
            'bank_id' => $request->bank_id,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
            'status' => 'CREATED',
            'create_by_id' => $request->user()->id,
            'document_number' => "VATP/{$nextId}/" . date('Y'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formatPayment($payment->load('bank')),
        ], 201);
    }

    public function showPayment(int $id): JsonResponse
    {
        $payment = VatPayment::with('bank')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $this->formatPayment($payment),
        ]);
    }

    public function updatePayment(Request $request, int $id): JsonResponse
    {
        $payment = VatPayment::findOrFail($id);
        $payment->update($request->only(['bank_id', 'amount', 'description', 'date']));

        return response()->json([
            'success' => true,
            'data' => $this->formatPayment($payment->load('bank')),
        ]);
    }

    public function destroyPayment(int $id): JsonResponse
    {
        VatPayment::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    private function formatPayment(VatPayment $v): array
    {
        return [
            'id' => $v->id,
            'date' => $v->date,
            'bank_id' => $v->bank_id,
            'bank_name' => $v->bank->name ?? null,
            'description' => $v->description,
            'amount' => (float) $v->amount,
            'status' => $v->status,
            'has_attachment' => !empty($v->file),
            'document_number' => $v->document_number,
        ];
    }
}
