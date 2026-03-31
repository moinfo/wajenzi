<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoanApiController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Loan::with(['staff:id,name', 'approvalStatus'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items->map(fn (Loan $item) => $this->transform($item))->values(),
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
        $item = Loan::with(['staff:id,name', 'approvalStatus'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Staff loan not found',
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
            'deduction' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        $nextId = ((int) Loan::max('id')) + 1;

        $item = new Loan();
        $item->staff_id = (int) $validated['staff_id'];
        $item->amount = $validated['amount'];
        $item->deduction = $validated['deduction'];
        $item->date = $validated['date'];
        $item->status = 'PENDING';
        $item->create_by_id = $request->user()?->id;
        $item->document_number = sprintf('LOAN/%d/%s', $nextId, date('Y'));
        $item->save();
        $item->load(['staff:id,name', 'approvalStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Staff loan created successfully',
            'data' => $this->transform($item),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = Loan::with(['staff:id,name', 'approvalStatus'])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Staff loan not found',
            ], 404);
        }

        $status = $item->approvalStatus?->status ?? strtoupper((string) ($item->status ?? 'PENDING'));
        if (in_array($status, ['APPROVED', 'PAID', 'COMPLETED'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Approved staff loan cannot be edited',
            ], 422);
        }

        $validated = $request->validate([
            'staff_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'deduction' => 'required|numeric|min:0.01',
            'date' => 'required|date',
        ]);

        $item->staff_id = (int) $validated['staff_id'];
        $item->amount = $validated['amount'];
        $item->deduction = $validated['deduction'];
        $item->date = $validated['date'];
        $item->save();
        $item->load(['staff:id,name', 'approvalStatus']);

        return response()->json([
            'success' => true,
            'message' => 'Staff loan updated successfully',
            'data' => $this->transform($item),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = Loan::with('approvalStatus')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Staff loan not found',
            ], 404);
        }

        $status = $item->approvalStatus?->status ?? strtoupper((string) ($item->status ?? 'PENDING'));
        if (in_array($status, ['APPROVED', 'PAID', 'COMPLETED'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Approved staff loan cannot be deleted',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff loan deleted successfully',
        ]);
    }

    private function transform(Loan $item): array
    {
        return [
            'id' => $item->id,
            'staff_id' => $item->staff_id,
            'staff_name' => $item->staff?->name,
            'amount' => (float) $item->amount,
            'deduction' => (float) $item->deduction,
            'date' => $item->date,
            'status' => $item->approvalStatus?->status ?? strtoupper((string) ($item->status ?? 'PENDING')),
            'document_number' => $item->document_number,
            'file' => $item->file ?? null,
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }
}
