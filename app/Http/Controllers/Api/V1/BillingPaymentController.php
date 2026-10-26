<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingPaymentResource;
use App\Models\BillingDocument;
use App\Models\BillingPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingPaymentController extends Controller
{
    public function referenceData(): JsonResponse
    {
        $documents = BillingDocument::with('client')
            ->orderByDesc('issue_date')
            ->limit(200)
            ->get()
            ->map(function (BillingDocument $document) {
                $clientName = $document->client->full_name 
                    ?? trim(($document->client->first_name ?? '') . ' ' . ($document->client->last_name ?? ''))
                    ?? 'Unknown Client';
                return [
                    'id' => $document->id,
                    'document_number' => $document->document_number,
                    'document_type' => $document->document_type,
                    'client_id' => $document->client_id,
                    'client_name' => $clientName,
                    'balance_amount' => $document->balance_amount,
                    'total_amount' => $document->total_amount,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'documents' => $documents,
                'payment_methods' => [
                    ['id' => 'cash', 'name' => 'Cash'],
                    ['id' => 'bank_transfer', 'name' => 'Bank Transfer'],
                    ['id' => 'mobile_money', 'name' => 'Mobile Money'],
                    ['id' => 'cheque', 'name' => 'Cheque'],
                    ['id' => 'card', 'name' => 'Card'],
                ],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = BillingPayment::with(['document.client', 'client', 'receiver'])
            ->orderBy('payment_date', 'desc');

        if ($request->document_id) {
            $query->where('document_id', $request->document_id);
        }

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Start Date / End Date on the payment date
        if ($request->start_date) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $payments = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => BillingPaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => 'required|exists:billing_documents,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,bank_transfer,mobile_money,cheque,card',
            'reference_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'cheque_number' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $validated['received_by'] = $request->user()->id;
        $validated['status'] = 'completed';
        $validated['client_id'] = BillingDocument::find($validated['document_id'])?->client_id;

        $payment = BillingPayment::create($validated);
        $payment->load(['document.client', 'client', 'receiver']);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => new BillingPaymentResource($payment),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $payment = BillingPayment::with(['document.client', 'client', 'receiver'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BillingPaymentResource($payment),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payment = BillingPayment::findOrFail($id);

        // Same rules as the web: a voided payment cannot be edited, the invoice
        // cannot change, and the amount cannot exceed the invoice's available balance.
        if ($payment->status === 'voided') {
            return response()->json(['success' => false, 'message' => 'Voided payments cannot be edited.'], 422);
        }

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string',
            'cheque_number' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $document = $payment->document;
        if ($document) {
            $available = $document->balance_amount + $payment->amount;
            if ($validated['amount'] > $available) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount cannot exceed available balance of ' . number_format($available, 2),
                ], 422);
            }
        }

        // The payment's own hooks recompute the invoice's paid amount and status.
        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data' => new BillingPaymentResource($payment->fresh(['document.client', 'client'])),
        ]);
    }

    /** Void a completed payment: the invoice's paid amount and status are recalculated. */
    public function void(int $id): JsonResponse
    {
        $payment = BillingPayment::with('document')->findOrFail($id);

        if ($payment->status === 'voided') {
            return response()->json(['success' => false, 'message' => 'Payment is already voided.'], 422);
        }

        // Voiding needs the 'voided' value in the status column (the web's void
        // relies on it too). Refuse instead of writing a blank status where a
        // database does not have it yet.
        $statusType = collect(DB::select('SHOW COLUMNS FROM billing_payments'))->firstWhere('Field', 'status')->Type ?? '';
        if (!str_contains($statusType, "'voided'")) {
            return response()->json([
                'success' => false,
                'message' => "This database cannot store voided payments yet (the payment status 'voided' is missing).",
            ], 409);
        }

        DB::beginTransaction();

        try {
            $document = $payment->document;

            $changes = ['status' => 'voided'];
            foreach (['voided_at' => now(), 'voided_by' => auth()->id()] as $column => $value) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('billing_payments', $column)) {
                    $changes[$column] = $value;
                }
            }
            // voided_at / voided_by are not mass-assignable on the model, so set them directly
            $payment->forceFill($changes)->save();

            if ($document) {
                $document->refresh();
                $document->paid_amount = $document->payments()->where('status', 'completed')->sum('amount');
                $document->balance_amount = $document->total_amount - $document->paid_amount;

                if ($document->balance_amount > 0) {
                    $document->status = $document->paid_amount > 0 ? 'partial_paid' : 'pending';
                    $document->paid_at = null;
                }

                $document->save();
            }

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Payment voided successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error voiding payment: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $payment = BillingPayment::findOrFail($id);
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully.',
        ]);
    }
}
