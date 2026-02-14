<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use App\Models\AttendanceType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * List attendance records for authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Attendance::where('user_id', $request->user()->id)
            ->orderBy('record_time', 'desc');

        // Filter by date range
        if ($request->start_date) {
            $query->where('record_time', '>=', Carbon::parse($request->start_date)->startOfDay());
        }
        if ($request->end_date) {
            $query->where('record_time', '<=', Carbon::parse($request->end_date)->endOfDay());
        }

        $attendances = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => AttendanceResource::collection($attendances),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ]);
    }

    /**
     * Record check-in with GPS location.
     */
    public function checkIn(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'comment' => 'nullable|string|max:500',
            'device_time' => 'nullable|date', // For offline sync
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // Check if already checked in today
        $existingCheckIn = Attendance::where('user_id', $user->id)
            ->where('type', 'in')
            ->whereDate('record_time', $today)
            ->first();

        if ($existingCheckIn) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in today.',
                'data' => new AttendanceResource($existingCheckIn),
            ], 422);
        }

        $recordTime = $request->device_time ? Carbon::parse($request->device_time) : now();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'record_time' => $recordTime,
            'type' => 'in',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'ip' => $request->ip(),
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-in recorded successfully.',
            'data' => new AttendanceResource($attendance),
        ], 201);
    }

    /**
     * Record check-out with GPS location.
     */
    public function checkOut(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'comment' => 'nullable|string|max:500',
            'device_time' => 'nullable|date',
        ]);

        $user = $request->user();
        $today = Carbon::today();

        // Check if checked in today
        $checkIn = Attendance::where('user_id', $user->id)
            ->where('type', 'in')
            ->whereDate('record_time', $today)
            ->first();

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'You must check in before checking out.',
            ], 422);
        }

        // Check if already checked out today
        $existingCheckOut = Attendance::where('user_id', $user->id)
            ->where('type', 'out')
            ->whereDate('record_time', $today)
            ->first();

        if ($existingCheckOut) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked out today.',
                'data' => new AttendanceResource($existingCheckOut),
            ], 422);
        }

        $recordTime = $request->device_time ? Carbon::parse($request->device_time) : now();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'record_time' => $recordTime,
            'type' => 'out',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'ip' => $request->ip(),
            'comment' => $request->comment,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Check-out recorded successfully.',
            'data' => new AttendanceResource($attendance),
        ], 201);
    }

    /**
     * Get today's attendance status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();

        $checkIn = Attendance::where('user_id', $user->id)
            ->where('type', 'in')
            ->whereDate('record_time', $today)
            ->first();

        $checkOut = Attendance::where('user_id', $user->id)
            ->where('type', 'out')
            ->whereDate('record_time', $today)
            ->first();

        $status = Attendance::getAttendanceStatus($user->id, $today->toDateString());

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today->toDateString(),
                'has_checked_in' => !is_null($checkIn),
                'has_checked_out' => !is_null($checkOut),
                'check_in' => $checkIn ? new AttendanceResource($checkIn) : null,
                'check_out' => $checkOut ? new AttendanceResource($checkOut) : null,
                'status' => $status[0] ?? 'absent',
                'is_late' => $status[3] ?? false,
                'working_hours' => $status[4] ?? null,
            ],
        ]);
    }

    /**
     * Daily attendance report — all staff with check-in status for a given date.
     */
    public function dailyReport(Request $request): JsonResponse
    {
        $date = $request->input('date', now()->toDateString());
        $search = $request->input('search');
        $attendanceTypeId = $request->input('attendance_type_id');

        $lateInTime = settings('ATTENDANCE_LATE_THRESHOLD', '09:00:00');

        // Get attendance types for filter options
        $attendanceTypes = AttendanceType::select('id', 'name')->orderBy('name')->get();

        // Build users query
        $usersQuery = User::select([
                'users.id',
                'users.name',
                'users.email',
                'users.user_device_id',
                'users.department_id',
                'users.attendance_type_id',
                'departments.name as department_name',
                'attendance_types.name as attendance_type_name',
            ])
            ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
            ->leftJoin('attendance_types', 'users.attendance_type_id', '=', 'attendance_types.id')
            ->where('users.status', 'ACTIVE')
            ->where('users.attendance_status', 'ENABLED');

        if ($attendanceTypeId) {
            $usersQuery->where('users.attendance_type_id', $attendanceTypeId);
        }

        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%")
                  ->orWhere('users.user_device_id', 'like', "%{$search}%");
            });
        }

        $users = $usersQuery->orderBy('attendance_types.name')
            ->orderBy('users.name')
            ->get();

        $userIds = $users->pluck('id')->toArray();

        // Single query for all attendance records on this date
        $attendanceData = DB::table('attendances')
            ->select([
                'user_id',
                DB::raw('MIN(record_time) as record_time'),
                DB::raw('MAX(comment) as comment'),
            ])
            ->whereIn('user_id', $userIds)
            ->whereDate('record_time', $date)
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $lateDateTime = $date . ' ' . $lateInTime;

        // Map users with attendance status
        $staff = $users->map(function ($user) use ($attendanceData, $lateDateTime) {
            $attendance = $attendanceData->get($user->id);
            $inTime = $attendance?->record_time;

            $status = 'ABSENT';
            if ($inTime) {
                $status = $inTime <= $lateDateTime ? 'ON_TIME' : 'LATE';
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department_name ?? 'N/A',
                'device_id' => $user->user_device_id,
                'attendance_type' => $user->attendance_type_name ?? 'N/A',
                'check_in' => $inTime ? Carbon::parse($inTime)->format('H:i') : null,
                'status' => $status,
                'comment' => $attendance?->comment,
            ];
        });

        // Stats
        $totalUsers = $staff->count();
        $present = $staff->where('status', '!=', 'ABSENT')->count();
        $onTime = $staff->where('status', 'ON_TIME')->count();
        $late = $staff->where('status', 'LATE')->count();
        $absent = $staff->where('status', 'ABSENT')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'late_threshold' => $lateInTime,
                'stats' => [
                    'total_users' => $totalUsers,
                    'present' => $present,
                    'on_time' => $onTime,
                    'late' => $late,
                    'absent' => $absent,
                ],
                'attendance_types' => $attendanceTypes,
                'staff' => $staff->values(),
            ],
        ]);
    }

    /**
     * Get attendance summary for a date range.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $totalDays = $startDate->diffInDays($endDate) + 1;
        $daysAttended = Attendance::getTotalDaysAttended(
            $user->id,
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        // Calculate late days
        $lateDays = 0;
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            if (Attendance::isLate($user->id, $currentDate->toDateString())) {
                $lateDays++;
            }
            $currentDate->addDay();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $totalDays,
                'days_attended' => $daysAttended,
                'days_absent' => $totalDays - $daysAttended,
                'late_days' => $lateDays,
                'attendance_rate' => $totalDays > 0 ? round(($daysAttended / $totalDays) * 100, 2) : 0,
            ],
        ]);
    }
}
