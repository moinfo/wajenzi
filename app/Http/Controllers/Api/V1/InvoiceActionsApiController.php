<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BillingDocument;
use App\Models\User;
use App\Services\ClientApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What the web Invoices page offers beyond the list, with the web's own rules
 * (App\Http\Controllers\Billing\InvoiceController):
 *
 *   GET  billing/invoices/summary          the four cards: Total Invoices (+ overdue),
 *                                          Total Billed, Amount Collected, Outstanding
 *   POST billing/invoices/{id}/payments    Record Payment
 *   POST billing/invoices/{id}/void        Void
 *   POST billing/invoices/{id}/duplicate   Duplicate
 */
class InvoiceActionsApiController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $stats = BillingDocument::where('document_type', 'invoice')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->client_id, fn ($q, $id) => $q->where('client_id', $id))
            ->when($request->from_date, fn ($q, $d) => $q->where('issue_date', '>=', $d))
            ->when($request->to_date, fn ($q, $d) => $q->where('issue_date', '<=', $d))
            ->when($request->invoice_type, fn ($q, $t) => $q->where('invoice_type', $t))
            ->selectRaw(
                'COUNT(*) as total_count,
                 SUM(total_amount) as total_amount,
                 SUM(paid_amount) as paid_amount,
                 SUM(total_amount - paid_amount) as balance_amount,
                 SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_count'
            )->first();

        return response()->json([
            'success' => true,
            'data' => [
                'total_count' => (int) ($stats->total_count ?? 0),
                'overdue_count' => (int) ($stats->overdue_count ?? 0),
                'total_amount' => (float) ($stats->total_amount ?? 0),
                'paid_amount' => (float) ($stats->paid_amount ?? 0),
                'balance_amount' => (float) ($stats->balance_amount ?? 0),
            ],
        ]);
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $invoice = $this->invoice($id);

        // Same rules as the web (including the balance cap).
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,credit_card,mobile_money,online,other',
            'reference_number' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $payment = $invoice->payments()->create([
                'client_id' => $invoice->client_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'bank_name' => $request->bank_name,
                'cheque_number' => $request->cheque_number,
                'transaction_id' => $request->transaction_id,
                'notes' => $request->notes,
                'status' => 'completed',
                'received_by' => $request->user()->id,
            ]);

            // Auto-approve the client's workflow on the first payment
            // (only when the invoice belongs to a client, not a lead).
            if ($invoice->client_id) {
                ClientApprovalService::autoApproveOnFirstPayment($invoice->client_id, $payment);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error recording payment: ' . $e->getMessage()], 500);
        }

        // Tell the users who watch invoice due dates, like the web does.
        try {
            $notify = User::permission('View All Invoice Due Dates')->where('id', '!=', $request->user()->id)->get();
            $this->sendNotification(
                $notify,
                'Payment Recorded',
                'Payment of TZS ' . number_format($request->amount, 2) . " recorded for Invoice {$invoice->document_number}.",
                "/billing/invoices/{$invoice->id}",
                $invoice->id
            );
        } catch (\Throwable $e) {
            \Log::warning('Payment notification failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => 'Payment recorded successfully.']);
    }

    public function void(int $id): JsonResponse
    {
        $invoice = $this->invoice($id);

        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Cannot void a paid invoice.'], 422);
        }

        $invoice->update(['status' => 'void']);

        return response()->json(['success' => true, 'message' => 'Invoice voided successfully.']);
    }

    public function duplicate(int $id): JsonResponse
    {
        $new = $this->invoice($id)->duplicate();

        return response()->json([
            'success' => true,
            'message' => 'Invoice duplicated successfully.',
            'data' => ['id' => $new->id, 'document_number' => $new->document_number],
        ], 201);
    }

    private function invoice(int $id): BillingDocument
    {
        return BillingDocument::where('document_type', 'invoice')->findOrFail($id);
    }
}
