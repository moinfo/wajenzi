<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StatutoryPayment;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StatutoryPaymentApiController extends Controller
{
    public function index(): JsonResponse
    {
        $payments = StatutoryPayment::with('subCategory:id,name,billing_cycle,price')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $payments->map(fn (StatutoryPayment $payment) => $this->transformPayment($payment))->values(),
            'meta' => [
                'total' => $payments->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $subCategories = SubCategory::query()
            ->orderBy('name')
            ->get(['id', 'name', 'billing_cycle', 'price']);

        return response()->json([
            'success' => true,
            'data' => [
                'sub_categories' => $subCategories->map(fn (SubCategory $subCategory) => [
                    'id' => $subCategory->id,
                    'name' => $subCategory->name,
                    'billing_cycle' => (int) ($subCategory->billing_cycle ?? 0),
                    'billing_cycle_name' => $this->billingCycleName((int) ($subCategory->billing_cycle ?? 0)),
                    'price' => (float) ($subCategory->price ?? 0),
                ])->values(),
                'statuses' => ['CREATED', 'PENDING', 'APPROVED', 'REJECTED', 'PAID', 'COMPLETED'],
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $payment = StatutoryPayment::with('subCategory:id,name,billing_cycle,price')->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Statutory payment not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformPayment($payment),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sub_category_id' => 'required|exists:sub_categories,id',
            'description' => 'required|string|max:1000',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'control_number' => 'nullable|numeric',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx,csv,txt|max:5120',
        ]);

        $nextId = ((int) StatutoryPayment::max('id')) + 1;
        $payload = [
            'sub_category_id' => $validated['sub_category_id'],
            'description' => $validated['description'],
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'control_number' => $validated['control_number'] ?? null,
            'status' => 'CREATED',
            'document_number' => sprintf('STPT/%d/%s', $nextId, date('Y')),
        ];

        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->storeAs(
                'uploads',
                time() . '_' . $request->file('file')->getClientOriginalName(),
                'public'
            );
            $payload['file'] = '/storage/' . $filePath;
        }

        $payment = StatutoryPayment::create($payload);
        $payment->load('subCategory:id,name,billing_cycle,price');

        return response()->json([
            'success' => true,
            'message' => 'Statutory payment created successfully',
            'data' => $this->transformPayment($payment),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $payment = StatutoryPayment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Statutory payment not found',
            ], 404);
        }

        $validated = $request->validate([
            'sub_category_id' => 'sometimes|required|exists:sub_categories,id',
            'description' => 'sometimes|required|string|max:1000',
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date',
            'amount' => 'sometimes|required|numeric|min:0',
            'control_number' => 'nullable|numeric',
            'status' => 'nullable|in:CREATED,PENDING,APPROVED,REJECTED,PAID,COMPLETED',
            'file' => 'nullable|file|mimes:png,jpg,jpeg,pdf,doc,docx,xls,xlsx,csv,txt|max:5120',
        ]);

        $payload = [];
        foreach (['sub_category_id', 'description', 'issue_date', 'due_date', 'amount', 'control_number', 'status'] as $field) {
            if (array_key_exists($field, $validated)) {
                $payload[$field] = $validated[$field];
            }
        }

        if ($request->hasFile('file')) {
            if (!empty($payment->file) && str_starts_with($payment->file, '/storage/')) {
                Storage::disk('public')->delete(substr($payment->file, strlen('/storage/')));
            }

            $filePath = $request->file('file')->storeAs(
                'uploads',
                time() . '_' . $request->file('file')->getClientOriginalName(),
                'public'
            );
            $payload['file'] = '/storage/' . $filePath;
        }

        $payment->update($payload);
        $payment->load('subCategory:id,name,billing_cycle,price');

        return response()->json([
            'success' => true,
            'message' => 'Statutory payment updated successfully',
            'data' => $this->transformPayment($payment),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $payment = StatutoryPayment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Statutory payment not found',
            ], 404);
        }

        if (!empty($payment->file) && str_starts_with($payment->file, '/storage/')) {
            Storage::disk('public')->delete(substr($payment->file, strlen('/storage/')));
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Statutory payment deleted successfully',
        ]);
    }

    private function transformPayment(StatutoryPayment $payment): array
    {
        return [
            'id' => $payment->id,
            'document_number' => $payment->document_number,
            'sub_category_id' => $payment->sub_category_id,
            'sub_category_name' => $payment->subCategory?->name,
            'billing_cycle' => (int) ($payment->subCategory?->billing_cycle ?? 0),
            'billing_cycle_name' => $this->billingCycleName((int) ($payment->subCategory?->billing_cycle ?? 0)),
            'description' => $payment->description,
            'status' => $payment->status,
            'issue_date' => $payment->issue_date,
            'due_date' => $payment->due_date,
            'amount' => (float) $payment->amount,
            'control_number' => $payment->control_number,
            'file' => $payment->file,
            'file_url' => $payment->file ? url($payment->file) : null,
            'created_at' => $payment->created_at?->toIso8601String(),
            'updated_at' => $payment->updated_at?->toIso8601String(),
        ];
    }

    private function billingCycleName(int $billingCycle): string
    {
        return match ($billingCycle) {
            0 => 'One Time',
            1 => 'Annually',
            3 => 'Quarterly',
            6 => 'Semi-Annually',
            12 => 'Monthly',
            default => 'Unknown',
        };
    }
}
