<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

//        $request->validate([
//            'leave_type_id' => 'required|exists:leave_types,id',
//            'start_date' => 'required|date',
//            'end_date' => 'required|date|after_or_equal:start_date',
//            'reason' => 'required|string|min:10'
//        ]);

        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        $startDate = Carbon::parse($request->start_date);
        $today = Carbon::today();
//        dump($leaveType);
//        return;

        // Check if enough notice is given (except for sick leave)
        if ($leaveType->notice_days > 0) {
            $minimumStartDate = $today->copy()->addDays($leaveType->notice_days);

            if ($startDate->lt($minimumStartDate)) {
                return back()->with('error',
                    "This leave type requires {$leaveType->notice_days} days advance notice. " .
                    "Earliest possible start date is {$minimumStartDate->format('M d, Y')}");
            }
        }

        $endDate = Carbon::parse($request->end_date);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Check for overlapping leaves
        $overlapping = LeaveRequest::where('user_id', auth()->id())
            ->where('status', '!=', 'rejected')
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate]);
            })->exists();

        if ($overlapping) {
            return back()->with('error', 'You have overlapping leave requests for the selected dates');
        }

        // Check remaining leave balance
        $usedLeaves = LeaveRequest::where('user_id', auth()->id())
            ->where('leave_type_id', $request->leave_type_id)
            ->where('status', 'approved')
            ->whereYear('start_date', date('Y'))
            ->sum('total_days');

        if (($usedLeaves + $totalDays) > $leaveType->days_allowed) {
            return back()->with('error', 'Insufficient leave balance');
        }

        LeaveRequest::create([
            'user_id' => auth()->id(),
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_days' => $totalDays,
            'reason' => $request->reason,
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
