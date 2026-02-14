<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingDocumentResource;
use App\Models\BillingDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PDF;

class BillingController extends Controller
{
    /**
     * All billing documents for this client across all projects.
     */
    public function index(Request $request): JsonResponse
    {
        $client = $request->user();

        $documents = BillingDocument::where('client_id', $client->id)
            ->whereNotIn('status', ['draft', 'cancelled', 'void'])
            ->with(['project', 'payments'])
            ->orderBy('issue_date', 'desc')
            ->get();

        $invoices = $documents->where('document_type', 'invoice');

        $summary = [
            'total_invoiced' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('paid_amount'),
            'balance_due' => $invoices->sum('balance_amount'),
            'overdue_count' => $invoices->filter(fn($d) => $d->is_overdue)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'invoices' => BillingDocumentResource::collection($invoices->values()),
                'quotes' => BillingDocumentResource::collection($documents->where('document_type', 'quote')->values()),
                'proformas' => BillingDocumentResource::collection($documents->where('document_type', 'proforma')->values()),
                'credit_notes' => BillingDocumentResource::collection($documents->where('document_type', 'credit_note')->values()),
            ],
        ]);
    }

    /**
     * Download a billing document PDF (cross-project).
     */
    public function pdf(Request $request, $id)
    {
        $client = $request->user();

        $document = BillingDocument::where('id', $id)
            ->where('client_id', $client->id)
            ->with(['client', 'items', 'payments'])
            ->firstOrFail();

        $viewMap = [
            'invoice' => 'billing.invoices.pdf',
            'quote' => 'billing.quotations.pdf',
            'proforma' => 'billing.proformas.pdf',
        ];

        $view = $viewMap[$document->document_type] ?? 'billing.invoices.pdf';
        $varName = $document->document_type === 'quote' ? 'quotation' : ($document->document_type === 'proforma' ? 'proforma' : 'invoice');

        $pdf = PDF::loadView($view, [$varName => $document]);

        return $pdf->download("{$document->document_type}-{$document->document_number}.pdf");
    }
}
