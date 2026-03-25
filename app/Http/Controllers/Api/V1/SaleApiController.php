<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Efd;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
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

            $sales = $query->orderBy('date', 'desc')
                ->paginate($request->per_page ?? 20);

            $items = collect($sales->items())->map(fn($s) => $this->formatSale($s));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $sales->currentPage(),
                        'last_page' => $sales->lastPage(),
                        'per_page' => $sales->perPage(),
                        'total' => $sales->total(),
                    ],
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
                'serial_number' => $e->serial_number ?? null,
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
        $data = [
            'id' => $sale->id,
            'efd_id' => $sale->efd_id,
            'date' => $sale->date,
            'amount' => $sale->amount,
            'net' => $sale->net,
            'tax' => $sale->tax,
            'turn_over' => $sale->turn_over,
            'vat' => $sale->vat,
            'status' => $sale->approvalStatus?->status ?? 'PENDING',
            'document_number' => $sale->document_number,
            'created_at' => $sale->created_at?->toISOString(),
        ];

        if ($sale->relationLoaded('efd') && $sale->efd) {
            $data['efd'] = [
                'id' => $sale->efd->id,
                'name' => $sale->efd->name,
                'serial_number' => $sale->efd->serial_number ?? null,
            ];
        }

        if (!empty($sale->create_by_id)) {
            $data['created_by_id'] = $sale->create_by_id;
        }

        if ($detailed) {
            $data['file'] = $sale->file;
        }

        return $data;
    }
}
