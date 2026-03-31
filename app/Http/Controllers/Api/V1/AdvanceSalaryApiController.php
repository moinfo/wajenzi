<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdvanceSalary;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvanceSalaryApiController extends Controller
{
    public function index(): JsonResponse
    {
        $items = AdvanceSalary::with(['staff:id,name', 'approvalStatus'])->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn (AdvanceSalary $item) => $this->transform($item))->values(),
            'meta' => [
                'total' => $items->count(),
            ],
        ]);
    }

    public function referenceData(): JsonResponse
    {
        $staffs = collect(User::onlyStaffs())->sortBy('name')->values();

        return response()->json([
            'success' => true,
            'data' => [
                'staffs' => $staffs->map(fn ($staff) => [
                    'id' => $staff->id,
                    'name' => $staff->name,
                ])->values(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = AdvanceSalary::with(['staff:id,name', 'approvalStatus'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Advance salary not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transform($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $nextId = ((int) AdvanceSalary::max('id')) + 1;

        $item = new AdvanceSalary();
        $item->staff_id = (int) $validated['staff_id'];
        $item->amount = $validated['amount'];
        $item->date = $validated['date'];
        $item->description = $validated['description'] ?? null;
        $item->status = 'PENDING';
        $item->create_by_id = $request->user()?->id;
        $item->document_number = sprintf('ADVS/%d/%s', $nextId, date('Y'));
        $item->save();
        $item->load(['staff:id,name', 'approvalStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Advance salary created successfully',
            'data' => $this->transform($item),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = AdvanceSalary::with(['staff:id,name', 'approvalStatus'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Advance salary not found',
            ], 404);
        }

        $status = $item->approvalStatus?->status ?? strtoupper((string) ($item->status ?? 'PENDING'));
        if (in_array($status, ['APPROVED', 'PAID', 'COMPLETED'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Approved advance salary cannot be edited',
            ], 422);
        }

        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $item->staff_id = (int) $validated['staff_id'];
        $item->amount = $validated['amount'];
        $item->date = $validated['date'];
        $item->description = $validated['description'] ?? null;
        $item->save();
        $item->load(['staff:id,name', 'approvalStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Advance salary updated successfully',
            'data' => $this->transform($item),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = AdvanceSalary::with('approvalStatus')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Advance salary not found',
            ], 404);
        }

        $status = $item->approvalStatus?->status ?? strtoupper((string) ($item->status ?? 'PENDING'));
        if (in_array($status, ['APPROVED', 'PAID', 'COMPLETED'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Approved advance salary cannot be deleted',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advance salary deleted successfully',
        ]);
    }

    private function transform(AdvanceSalary $item): array
    {
        return [
            'id' => $item->id,
            'staff_id' => $item->staff_id,
            'staff_name' => $item->staff?->name,
            'amount' => (float) $item->amount,
            'date' => $item->date,
            'description' => $item->description,
            'status' => $item->approvalStatus?->status ?? strtoupper((string) ($item->status ?? 'PENDING')),
            'document_number' => $item->document_number,
            'file' => $item->file ?? null,
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }
}
