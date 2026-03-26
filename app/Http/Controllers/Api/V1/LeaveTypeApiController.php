<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveTypeApiController extends Controller
{
    public function index(): JsonResponse
    {
        $items = LeaveType::query()
            ->withCount('leaveRequests')
            ->orderBy('name')
            ->get()
            ->map(fn (LeaveType $item) => $this->transform($item));

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $item = LeaveType::query()->withCount('leaveRequests')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->transform($item),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name',
            'days_allowed' => 'required|integer|min:0',
            'notice_days' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $item = LeaveType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Leave type created successfully.',
            'data' => $this->transform($item->fresh()->loadCount('leaveRequests')),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = LeaveType::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:leave_types,name,' . $item->id,
            'days_allowed' => 'required|integer|min:0',
            'notice_days' => 'required|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $item->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Leave type updated successfully.',
            'data' => $this->transform($item->fresh()->loadCount('leaveRequests')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $item = LeaveType::query()->withCount('leaveRequests')->findOrFail($id);

        if ($item->leave_requests_count > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete leave type that already has leave requests.',
            ], 422);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave type deleted successfully.',
        ]);
    }

    private function transform(LeaveType $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'days_allowed' => (int) ($item->days_allowed ?? 0),
            'notice_days' => (int) ($item->notice_days ?? 0),
            'description' => $item->description,
            'leave_requests_count' => (int) ($item->leave_requests_count ?? 0),
            'created_at' => $item->created_at?->toISOString(),
            'updated_at' => $item->updated_at?->toISOString(),
        ];
    }
}
