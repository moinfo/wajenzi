<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillingDocumentResource;
use App\Http\Resources\ProjectClientResource;
use App\Models\BillingDocument;
use App\Models\BillingDocumentItem;
use App\Models\ProjectClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingDocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BillingDocument::with(['client', 'project'])
            ->orderBy('issue_date', 'desc');

        if ($request->document_type) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->client_id) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->project_id) {
            $query->where('project_id', $request->project_id);
        }

        $documents = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => BillingDocumentResource::collection($documents),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|in:invoice,quotation,proforma',
            'client_id' => 'required|exists:project_clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'valid_until_date' => 'nullable|date|after_or_equal:issue_date',
            'payment_terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();
        try {
            $document = BillingDocument::create([
                'document_type' => $validated['document_type'],
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'] ?? null,
                'valid_until_date' => $validated['valid_until_date'] ?? null,
                'payment_terms' => $validated['payment_terms'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms_conditions' => $validated['terms_conditions'] ?? null,
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $index => $itemData) {
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $discountPct = $itemData['discount_percentage'] ?? 0;
                $taxPct = $itemData['tax_percentage'] ?? 0;

                $subtotal = $quantity * $unitPrice;
                $discountAmount = $subtotal * ($discountPct / 100);
                $afterDiscount = $subtotal - $discountAmount;
                $taxAmount = $afterDiscount * ($taxPct / 100);
                $totalAmount = $afterDiscount + $taxAmount;

                BillingDocumentItem::create([
                    'document_id' => $document->id,
                    'description' => $itemData['description'],
                    'quantity' => $quantity,
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => $unitPrice,
                    'discount_percentage' => $discountPct,
                    'discount_amount' => $discountAmount,
                    'tax_percentage' => $taxPct,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'sort_order' => $index,
                ]);
            }

            $document->calculateTotals();

            DB::commit();

            $document->load(['client', 'project', 'items']);

            return response()->json([
                'success' => true,
                'message' => 'Billing document created successfully.',
                'data' => new BillingDocumentResource($document),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create document: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $document = BillingDocument::with(['client', 'project', 'items', 'payments'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BillingDocumentResource($document),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $document = BillingDocument::findOrFail($id);

        if (!in_array($document->status, ['draft'])) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft documents can be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|exists:project_clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'issue_date' => 'sometimes|date',
            'due_date' => 'nullable|date',
            'valid_until_date' => 'nullable|date',
            'payment_terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $document->update([
                'client_id' => $validated['client_id'] ?? $document->client_id,
                'project_id' => $validated['project_id'] ?? $document->project_id,
                'issue_date' => $validated['issue_date'] ?? $document->issue_date,
                'due_date' => $validated['due_date'] ?? $document->due_date,
                'valid_until_date' => $validated['valid_until_date'] ?? $document->valid_until_date,
                'payment_terms' => $validated['payment_terms'] ?? $document->payment_terms,
                'notes' => $validated['notes'] ?? $document->notes,
                'terms_conditions' => $validated['terms_conditions'] ?? $document->terms_conditions,
            ]);

            if (isset($validated['items'])) {
                $document->items()->delete();
                foreach ($validated['items'] as $index => $itemData) {
                    $quantity = $itemData['quantity'];
                    $unitPrice = $itemData['unit_price'];
                    $discountPct = $itemData['discount_percentage'] ?? 0;
                    $taxPct = $itemData['tax_percentage'] ?? 0;

                    $subtotal = $quantity * $unitPrice;
                    $discountAmount = $subtotal * ($discountPct / 100);
                    $afterDiscount = $subtotal - $discountAmount;
                    $taxAmount = $afterDiscount * ($taxPct / 100);
                    $totalAmount = $afterDiscount + $taxAmount;

                    BillingDocumentItem::create([
                        'document_id' => $document->id,
                        'description' => $itemData['description'],
                        'quantity' => $quantity,
                        'unit' => $itemData['unit'] ?? null,
                        'unit_price' => $unitPrice,
                        'discount_percentage' => $discountPct,
                        'discount_amount' => $discountAmount,
                        'tax_percentage' => $taxPct,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $totalAmount,
                        'sort_order' => $index,
                    ]);
                }
                $document->calculateTotals();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Document updated successfully.',
                'data' => new BillingDocumentResource($document->fresh(['client', 'project', 'items'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update document: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $document = BillingDocument::findOrFail($id);

        if ($document->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft documents can be deleted.',
            ], 403);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    public function send(Request $request, int $id): JsonResponse
    {
        $document = BillingDocument::with('client')->findOrFail($id);

        if (!$document->client?->email) {
            return response()->json([
                'success' => false,
                'message' => 'Client does not have an email address.',
            ], 422);
        }

        $document->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        // TODO: Dispatch email job here

        return response()->json([
            'success' => true,
            'message' => 'Document sent to client.',
            'data' => new BillingDocumentResource($document->fresh()),
        ]);
    }

    public function pdf(int $id): JsonResponse
    {
        $document = BillingDocument::with(['client', 'project', 'items'])->findOrFail($id);

        // Return PDF URL or generate on-the-fly
        return response()->json([
            'success' => true,
            'data' => [
                'pdf_url' => route('billing.document.pdf', $document->id),
            ],
        ]);
    }

    public function clients(Request $request): JsonResponse
    {
        $clients = ProjectClient::orderBy('first_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => ProjectClientResource::collection($clients),
        ]);
    }
}
