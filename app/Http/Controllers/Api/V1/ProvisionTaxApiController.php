<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProvisionTax;
use App\Models\Bank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class ProvisionTaxApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', date('Y-m-d'));
            $endDate = $request->input('end_date', date('Y-m-d'));
            $perPage = $request->input('per_page', 20);

            $query = ProvisionTax::with(['bank'])
                ->where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->orderBy('date', 'desc');

            $provisionTaxes = $query->paginate($perPage);

            // Calculate summary statistics
            $totalAmount = ProvisionTax::where('date', '>=', $startDate)
                ->where('date', '<=', $endDate)
                ->sum('amount');

            $items = collect($provisionTaxes->items())->map(fn($tax) => $this->formatTax($tax));

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $items,
                    'meta' => [
                        'current_page' => $provisionTaxes->currentPage(),
                        'last_page' => $provisionTaxes->lastPage(),
                        'per_page' => $provisionTaxes->perPage(),
                        'total' => $provisionTaxes->total(),
                    ],
                    'summary' => [
                        'total_amount' => (float) $totalAmount,
                        'count' => $provisionTaxes->total(),
                    ],
                    'filters' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProvisionTax index error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch provision taxes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(Request $request): JsonResponse
    {
        try {
            $banks = Bank::orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'banks' => $banks->map(fn($bank) => [
                        'id' => $bank->id,
                        'value' => $bank->id,
                        'label' => $bank->name,
                        'name' => $bank->name,
                        'bank_name' => $bank->name,
                        'account_number' => $bank->account_number ?? null,
                    ]),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ProvisionTax referenceData error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $tax = ProvisionTax::with(['bank'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatTax($tax),
            ]);
        } catch (\Throwable $e) {
            Log::error('ProvisionTax show error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch provision tax: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $existingId = (int) $request->input('id', 0);
            if ($existingId > 0 && ProvisionTax::whereKey($existingId)->exists()) {
                return $this->update($request, $existingId);
            }

            $validated = $request->validate([
                'date' => 'required|date',
                'amount' => 'required|numeric|min:0',
                'description' => 'required|string|max:255',
                'bank_id' => 'nullable|exists:banks,id',
                'debit_number' => 'nullable|string|max:255',
                'file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:2048',
            ]);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('provision_taxes', $fileName, 'public');
                $validated['file'] = $filePath;
            }

            $tax = ProvisionTax::create($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatTax($tax->load(['bank'])),
                'message' => 'Provision tax created successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProvisionTax store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create provision tax: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $tax = ProvisionTax::findOrFail($id);

            $validated = $request->validate([
                'date' => 'sometimes|required|date',
                'amount' => 'sometimes|required|numeric|min:0',
                'description' => 'sometimes|required|string|max:255',
                'bank_id' => 'sometimes|nullable|exists:banks,id',
                'debit_number' => 'sometimes|nullable|string|max:255',
                'file' => 'nullable|file|mimes:pdf,jpeg,jpg,png|max:2048',
            ]);

            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($tax->file) {
                    $oldFilePath = storage_path('app/public/' . $tax->file);
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }

                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('provision_taxes', $fileName, 'public');
                $validated['file'] = $filePath;
            }

            $tax->update($validated);

            return response()->json([
                'success' => true,
                'data' => $this->formatTax($tax->load(['bank'])),
                'message' => 'Provision tax updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProvisionTax update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update provision tax: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $tax = ProvisionTax::findOrFail($id);

            // Delete file if exists
            if ($tax->file) {
                $filePath = storage_path('app/public/' . $tax->file);
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $tax->delete();

            return response()->json([
                'success' => true,
                'message' => 'Provision tax deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('ProvisionTax destroy error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete provision tax: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function formatTax($tax): array
    {
        return [
            'id' => $tax->id,
            'date' => $this->formatDateValue($tax->date),
            'amount' => (float) ($tax->amount ?? 0),
            'description' => $tax->description,
            'file' => $tax->file,
            'file_url' => $tax->file ? asset('storage/' . $tax->file) : null,
            'bank_id' => $tax->bank_id,
            'debit_number' => $tax->debit_number,
            'created_at' => $this->formatDateTimeValue($tax->created_at),
            'updated_at' => $this->formatDateTimeValue($tax->updated_at),
            'bank' => [
                'id' => $tax->bank?->id,
                'name' => $tax->bank?->name,
                'bank_name' => $tax->bank?->name,
                'account_number' => $tax->bank?->account_number ?? null,
            ],
        ];
    }

    private function formatDateValue($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatDateTimeValue($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
