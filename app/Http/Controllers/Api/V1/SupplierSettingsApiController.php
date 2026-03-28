<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Models\System;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SupplierSettingsApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $suppliers = Supplier::with(['system'])
                ->orderBy('name')
                ->get();

            $contacts = SupplierContact::with(['bank'])
                ->orderBy('account_name')
                ->get()
                ->groupBy('supplier_id');

            return response()->json([
                'success' => true,
                'data' => $suppliers->map(function (Supplier $supplier) use ($contacts) {
                    return $this->transformSupplier(
                        $supplier,
                        $contacts->get($supplier->id, collect())
                    );
                })->values(),
                'meta' => [
                    'total' => $suppliers->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier settings index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch suppliers',
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        try {
            $systems = System::orderBy('name')->get(['id', 'name']);
            $banks = Bank::orderBy('name')->get(['id', 'name']);
            $suppliers = Supplier::orderBy('name')->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => [
                    'systems' => $systems->map(fn (System $system) => [
                        'id' => $system->id,
                        'name' => $system->name,
                    ])->values(),
                    'banks' => $banks->map(fn (Bank $bank) => [
                        'id' => $bank->id,
                        'name' => $bank->name,
                    ])->values(),
                    'suppliers' => $suppliers->map(fn (Supplier $supplier) => [
                        'id' => $supplier->id,
                        'name' => $supplier->name,
                    ])->values(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier settings reference data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch reference data',
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'vrn' => 'required|string|max:255',
                'system_id' => 'required|exists:systems,id',
            ]);

            $supplier = Supplier::create($validated)->load('system');

            return response()->json([
                'success' => true,
                'data' => $this->transformSupplier($supplier, collect()),
                'message' => 'Supplier created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Supplier settings store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $supplier = Supplier::with('system')->findOrFail($id);
            $contacts = SupplierContact::with('bank')
                ->where('supplier_id', $supplier->id)
                ->orderBy('account_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $this->transformSupplier($supplier, $contacts),
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier settings show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Supplier not found',
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:255',
                'vrn' => 'required|string|max:255',
                'system_id' => 'required|exists:systems,id',
            ]);
            $supplier->update($validated);
            $supplier->load('system');

            $contacts = SupplierContact::with('bank')
                ->where('supplier_id', $supplier->id)
                ->orderBy('account_name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $this->transformSupplier($supplier, $contacts),
                'message' => 'Supplier updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier settings update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
            SupplierContact::where('supplier_id', $supplier->id)->delete();
            $supplier->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier settings destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier',
            ], 500);
        }
    }

    public function storeContact(Request $request, int $supplierId): JsonResponse
    {
        try {
            Supplier::findOrFail($supplierId);

            $validated = $request->validate([
                'bank_id' => 'required|exists:banks,id',
                'account_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
            ]);

            $contact = SupplierContact::create([
                'supplier_id' => $supplierId,
                ...$validated,
            ])->load('bank');

            return response()->json([
                'success' => true,
                'data' => $this->transformContact($contact),
                'message' => 'Supplier contact created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Supplier contact store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier contact: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateContact(Request $request, int $contactId): JsonResponse
    {
        try {
            $contact = SupplierContact::findOrFail($contactId);
            $validated = $request->validate([
                'supplier_id' => 'required|exists:suppliers,id',
                'bank_id' => 'required|exists:banks,id',
                'account_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:255',
            ]);
            $contact->update($validated);
            $contact->load('bank');

            return response()->json([
                'success' => true,
                'data' => $this->transformContact($contact),
                'message' => 'Supplier contact updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier contact update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update supplier contact: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyContact(int $contactId): JsonResponse
    {
        try {
            $contact = SupplierContact::findOrFail($contactId);
            $contact->delete();

            return response()->json([
                'success' => true,
                'message' => 'Supplier contact deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('Supplier contact destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete supplier contact',
            ], 500);
        }
    }

    private function transformSupplier(Supplier $supplier, $contacts): array
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'phone' => $supplier->phone,
            'address' => $supplier->address,
            'vrn' => $supplier->vrn,
            'system_id' => $supplier->system_id,
            'system_name' => $supplier->system?->name,
            'contacts' => collect($contacts)->map(fn (SupplierContact $contact) => $this->transformContact($contact))->values(),
            'created_at' => optional($supplier->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($supplier->updated_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function transformContact(SupplierContact $contact): array
    {
        return [
            'id' => $contact->id,
            'supplier_id' => $contact->supplier_id,
            'bank_id' => $contact->bank_id,
            'bank_name' => $contact->bank?->name,
            'account_name' => $contact->account_name,
            'account_number' => $contact->account_number,
            'created_at' => optional($contact->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($contact->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
