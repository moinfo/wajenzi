<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = LeaveRequest::with(['leaveType'])
            ->where('user_id', $request->user()->id)
            ->orderBy('start_date', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->year) {
            $query->whereYear('start_date', $request->year);
        }

        $requests = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => LeaveRequestResource::collection($requests),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Check leave balance
        $remainingBalance = $user->getRemainingLeaveBalance($validated['leave_type_id']);
        if ($totalDays > $remainingBalance) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient leave balance. You have {$remainingBalance} days remaining.",
            ], 422);
        }

        $leaveRequest = LeaveRequest::create([
            'user_id' => $user->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        $leaveRequest->load('leaveType');

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully.',
            'data' => new LeaveRequestResource($leaveRequest),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::with(['leaveType', 'approver'])
            ->where('user_id', request()->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::where('user_id', $request->user()->id)->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be edited.',
            ], 403);
        }

        $validated = $request->validate([
            'leave_type_id' => 'sometimes|exists:leave_types,id',
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'sometimes|string|max:500',
        ]);

        if (isset($validated['start_date']) || isset($validated['end_date'])) {
            $startDate = Carbon::parse($validated['start_date'] ?? $leaveRequest->start_date);
            $endDate = Carbon::parse($validated['end_date'] ?? $leaveRequest->end_date);
            $validated['total_days'] = $startDate->diffInDays($endDate) + 1;
        }

        $leaveRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Leave request updated successfully.',
            'data' => new LeaveRequestResource($leaveRequest->fresh('leaveType')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $leaveRequest = LeaveRequest::where('user_id', request()->user()->id)->findOrFail($id);

        if ($leaveRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be cancelled.',
            ], 403);
        }

        $leaveRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Leave request cancelled successfully.',
        ]);
    }

    public function balance(Request $request): JsonResponse
    {
        $user = $request->user();
        $year = $request->year ?? date('Y');

        $leaveTypes = LeaveType::all()->map(function ($type) use ($user, $year) {
            $used = LeaveRequest::where('user_id', $user->id)
                ->where('leave_type_id', $type->id)
                ->where('status', 'approved')
                ->whereYear('start_date', $year)
                ->sum('total_days');

            return [
                'id' => $type->id,
                'name' => $type->name,
                'days_allowed' => $type->days_allowed,
                'days_used' => $used,
                'days_remaining' => $type->days_allowed - $used,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'balances' => $leaveTypes,
            ],
        ]);
    }

    public function types(): JsonResponse
    {
        $types = LeaveType::all()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'days_allowed' => $t->days_allowed,
            'description' => $t->description ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $types,
        ]);
    }
}
