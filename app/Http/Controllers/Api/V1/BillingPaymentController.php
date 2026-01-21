<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingPaymentResource;
use App\Models\BillingPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BillingPayment::with(['document', 'client', 'receiver'])
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

        $payment = BillingPayment::create($validated);
        $payment->load(['document', 'client', 'receiver']);

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded successfully.',
            'data' => new BillingPaymentResource($payment),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $payment = BillingPayment::with(['document', 'client', 'receiver'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BillingPaymentResource($payment),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payment = BillingPayment::findOrFail($id);

        $validated = $request->validate([
            'payment_date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|string',
            'reference_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'cheque_number' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully.',
            'data' => new BillingPaymentResource($payment->fresh(['document', 'client'])),
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
