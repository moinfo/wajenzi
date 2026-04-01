<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingPaymentResource;
use App\Models\BillingDocument;
use App\Models\BillingPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $validated = $request->validate([
            'document_id' => 'sometimes|exists:billing_documents,id',
            'payment_date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|string',
            'reference_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'cheque_number' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        if (array_key_exists('document_id', $validated)) {
            $validated['client_id'] = BillingDocument::find($validated['document_id'])?->client_id;
        }

        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data' => new BillingPaymentResource($payment->fresh(['document.client', 'client'])),
        ]);
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
