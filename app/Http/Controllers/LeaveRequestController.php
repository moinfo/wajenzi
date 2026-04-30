<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index()
    {
        $leaveRequests = LeaveRequest::with(['user', 'leaveType'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        $leaveTypes = LeaveType::all();

        return view('pages.leaves.index', compact('leaveRequests', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $startDate = Carbon::parse($validated['start_date']);
        $today = Carbon::today();

        // Check if enough notice is given (except for sick leave)
        if ($leaveType->notice_days > 0) {
            $minimumStartDate = $today->copy()->addDays($leaveType->notice_days);

            if ($startDate->lt($minimumStartDate)) {
                throw ValidationException::withMessages([
                    'start_date' => "This leave type requires {$leaveType->notice_days} days advance notice. Earliest possible start date is {$minimumStartDate->format('M d, Y')}.",
                ]);
            }
        }

        $endDate = Carbon::parse($validated['end_date']);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Check for overlapping leaves
        $overlapping = LeaveRequest::where('user_id', auth()->id())
            ->whereNotIn('status', ['rejected', 'REJECTED'])
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($nested) use ($startDate, $endDate) {
                        $nested->whereDate('start_date', '<=', $startDate->toDateString())
                            ->whereDate('end_date', '>=', $endDate->toDateString());
                    });
            })->exists();

        if ($overlapping) {
            throw ValidationException::withMessages([
                'start_date' => 'You already have another leave request in the selected date range.',
            ]);
        }

        // Check remaining leave balance
        $remainingBalance = auth()->user()->getRemainingLeaveBalance($validated['leave_type_id']);

        if ($totalDays > $remainingBalance) {
            throw ValidationException::withMessages([
                'end_date' => "Insufficient leave balance. You have {$remainingBalance} days remaining.",
            ]);
        }

        LeaveRequest::create([
            'user_id' => auth()->id(),
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return redirect()->route('leave_request')
            ->with('success', 'Leave request submitted successfully');
    }
    public function dashboard()
    {
        $user = auth()->user();
        $leaveTypes = LeaveType::all();
        $leaveBalances = [];

        foreach ($leaveTypes as $type) {
            $leaveBalances[$type->id] = [
                'name' => $type->name,
                'total' => $type->days_allowed,
                'used' => $user->leaveRequests()
                    ->where('leave_type_id', $type->id)
                    ->where('status', 'approved')
                    ->whereYear('start_date', date('Y'))
                    ->sum('total_days'),
            ];
            $leaveBalances[$type->id]['remaining'] =
                $leaveBalances[$type->id]['total'] - $leaveBalances[$type->id]['used'];
        }

        $recentRequests = $user->leaveRequests()
            ->with('leaveType')
            ->latest()
            ->take(5)
            ->get();

        return view('pages.leaves.dashboard', compact('leaveBalances', 'recentRequests'));
    }

    public function leave_managements()
    {
        $leaveRequests = LeaveRequest::with(['user', 'leaveType'])
            ->latest()
            ->paginate(10);

        return view('pages.leaves.leave_managements', compact('leaveRequests'));
    }

    public function update(Request $request, LeaveRequest $leaveRequest)
    {
//        $request->validate([
//            'status' => 'required|in:approved,rejected',
//            'admin_remarks' => 'required|string|min:10'
//        ]);

        $leaveRequest->update([
            'status' => $request->status,
            'admin_remarks' => $request->admin_remarks
        ]);

        return back()->with('success', 'Leave request updated successfully');
    }

}
