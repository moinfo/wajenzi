<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Efd;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SaleApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Sale::with(['efd', 'approvalStatus']);

            if ($request->start_date) {
                $query->where('date', '>=', $request->start_date);
            }

            if ($request->end_date) {
                $query->where('date', '<=', $request->end_date);
            }

            if ($request->efd_id) {
                $query->where('efd_id', $request->efd_id);
            }

            $sales = $query->orderBy('date', 'desc')->get();
            $items = $sales->map(fn($s) => $this->formatSale($s));
            $totals = $this->buildTotals($items);

            return response()->json([
                'success' => true,
                'data' => [
                    'sales' => $items->values(),
                    'totals' => $totals,
                    'efds' => Efd::orderBy('name')->get(['id', 'name']),
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $items->count(),
                        'total' => $items->count(),
                    ],
                    // Legacy compatibility for older mobile parsing paths.
                    'data' => $items->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Sale index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $sale = Sale::with(['efd', 'approvalStatus'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatSale($sale, true),
            ]);
        } catch (\Throwable $e) {
            Log::error('Sale show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function efds(): JsonResponse
    {
        try {
            $efds = Efd::orderBy('name')->get()->map(fn($e) => [
                'id' => $e->id,
                'name' => $e->name,
            ]);

            return response()->json([
                'success' => true,
                'data' => $efds,
            ]);
        } catch (\Throwable $e) {
            Log::error('Efd index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch EFDs: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'efd_id' => 'required|exists:efds,id',
                'date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'net' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'turn_over' => 'nullable|numeric|min:0',
                'vat' => 'nullable|numeric|min:0',
                'file' => 'nullable|file|max:5120',
            ]);

            if ($request->hasFile('file')) {
                $validated['file'] = $request->file('file')->store('sales', 'public');
            }

            $validated['create_by_id'] = $request->user()->id;
            $validated['status'] = 'PENDING';

            $sale = Sale::create($validated);
            $sale->load(['efd', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully.',
                'data' => $this->formatSale($sale),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Sale store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $sale = Sale::findOrFail($id);

            $validated = $request->validate([
                'efd_id' => 'sometimes|exists:efds,id',
                'date' => 'sometimes|date',
                'amount' => 'sometimes|numeric|min:0',
                'net' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'turn_over' => 'nullable|numeric|min:0',
                'vat' => 'nullable|numeric|min:0',
                'file' => 'nullable|file|max:5120',
            ]);

            if ($request->hasFile('file')) {
                $validated['file'] = $request->file('file')->store('sales', 'public');
            }

            $sale->update($validated);
            $sale->load(['efd', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Sale updated successfully.',
                'data' => $this->formatSale($sale),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Sale update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $sale = Sale::findOrFail($id);
            $sale->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sale deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Sale destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatSale($sale, bool $detailed = false)
    {
        $approvalStatus = (string) ($sale->approvalStatus?->status ?? $sale->status ?? 'PENDING');
        $normalizedStatus = strtoupper($approvalStatus);
        $fileUrl = $this->resolveFileUrl($sale->file);
        $data = [
            'id' => $sale->id,
            'efd_id' => $sale->efd_id,
            'date' => $sale->date,
            'amount' => $sale->amount,
            'turnover' => $sale->amount,
            'net' => $sale->net,
            'tax' => $sale->tax,
            'turn_over' => $sale->turn_over,
            'turnover_exempt' => $sale->turn_over,
            'vat' => $sale->vat,
            'status' => $approvalStatus,
            'approval_status' => $approvalStatus,
            'approval_summary' => $this->approvalSummary($normalizedStatus),
            'document_number' => $sale->document_number,
            'has_attachment' => !empty($sale->file),
            'file_url' => $fileUrl,
            'created_at' => $sale->created_at?->toISOString(),
        ];

        if ($sale->relationLoaded('efd') && $sale->efd) {
            $data['efd'] = [
                'id' => $sale->efd->id,
                'name' => $sale->efd->name,
            ];
        }

        if (!empty($sale->create_by_id)) {
            $data['created_by_id'] = $sale->create_by_id;
        }

        if ($detailed) {
            $data['file'] = $sale->file;
            $data['approval_page_url'] = url("/sale/{$sale->id}/2");
        }

        return $data;
    }

    private function buildTotals(Collection $items): array
    {
        return [
            'turnover' => (float) $items->sum(fn ($item) => (float) ($item['amount'] ?? 0)),
            'net' => (float) $items->sum(fn ($item) => (float) ($item['net'] ?? 0)),
            'tax' => (float) $items->sum(fn ($item) => (float) ($item['tax'] ?? 0)),
            'turnover_exempt' => (float) $items->sum(fn ($item) => (float) ($item['turn_over'] ?? 0)),
        ];
    }

    private function resolveFileUrl(?string $file): ?string
    {
        return Sale::resolveAttachmentUrl($file);
    }

    private function approvalSummary(string $status): string
    {
        return match ($status) {
            'PENDING', 'CREATED' => 'Waiting for submission/approval',
            'SUBMITTED' => 'Submitted into approval workflow',
            'APPROVED', 'COMPLETED' => 'Approval completed',
            'REJECTED' => 'Rejected in approval workflow',
            'DISCARDED' => 'Discarded from approval workflow',
            'PAID' => 'Processed and paid',
            default => $status,
        };
    }
}
