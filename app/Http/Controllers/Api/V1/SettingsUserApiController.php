<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceType;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SettingsUserApiController extends Controller
{
    public function referenceData(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'departments' => Department::orderBy('name')->get(['id', 'name']),
                'attendance_types' => AttendanceType::orderBy('name')->get(['id', 'name']),
                'genders' => [['name' => 'MALE'], ['name' => 'FEMALE']],
                'employee_types' => [
                    ['name' => 'STAFF'],
                    ['name' => 'INTERN'],
                    ['name' => 'EXTERNAL'],
                ],
                'employment_types' => [
                    ['name' => 'FULL_TIME'],
                    ['name' => 'CONTRACT'],
                    ['name' => 'INTERN'],
                ],
                'marital_statuses' => [
                    ['name' => 'SINGLE'],
                    ['name' => 'MARRIED'],
                    ['name' => 'DIVORCED'],
                    ['name' => 'OTHER'],
                ],
                'statuses' => [
                    ['name' => 'ACTIVE'],
                    ['name' => 'INACTIVE'],
                    ['name' => 'DORMANT'],
                ],
                'attendance_statuses' => [
                    ['name' => 'ENABLED'],
                    ['name' => 'DISABLED'],
                ],
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $status = strtoupper((string) $request->input('status', 'ACTIVE'));

            $items = User::with(['department:id,name', 'attendanceType:id,name'])
                ->when(in_array($status, ['ACTIVE', 'INACTIVE', 'DORMANT'], true), function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $items->map(fn (User $item) => $this->formatItem($item))->values(),
                'meta' => [
                    'total' => $items->count(),
                    'status' => $status,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('SettingsUser index error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $item = User::with(['department:id,name', 'attendanceType:id,name'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('SettingsUser show error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $this->validatePayload($request);

            $item = new User();
            $this->fillUser($item, $validated, true);
            $item->password = Hash::make('123456');
            $item->recruitment_date = $validated['recruitment_date'] ?? now()->format('Y-m-d');
            $item->save();

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh(['department:id,name', 'attendanceType:id,name'])),
                'message' => 'User created successfully',
            ], 201);
        } catch (\Throwable $e) {
            Log::error('SettingsUser store error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $item = User::findOrFail($id);
            $validated = $this->validatePayload($request, $item->id);

            $this->fillUser($item, $validated, false);
            $item->save();

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh(['department:id,name', 'attendanceType:id,name'])),
                'message' => 'User updated successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('SettingsUser update error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user: '.$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $item = User::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error('SettingsUser destroy error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
            ], 500);
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $item = User::findOrFail($id);
            $item->status = $item->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
            $item->save();

            return response()->json([
                'success' => true,
                'data' => $this->formatItem($item->fresh(['department:id,name', 'attendanceType:id,name'])),
                'message' => "User status updated to {$item->status}",
            ]);
        } catch (\Throwable $e) {
            Log::error('SettingsUser toggle status error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status',
            ], 500);
        }
    }

    private function validatePayload(Request $request, ?int $userId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['MALE', 'FEMALE'])],
            'address' => ['nullable', 'string', 'max:255'],
            'designation' => ['nullable', 'string', 'max:255'],
            'employee_number' => ['nullable', 'string', 'max:255'],
            'user_device_id' => ['nullable', 'integer'],
            'type' => ['required', Rule::in(['STAFF', 'INTERN', 'EXTERNAL'])],
            'dob' => ['nullable', 'date'],
            'employment_date' => ['nullable', 'date'],
            'tin' => ['nullable', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(['FULL_TIME', 'CONTRACT', 'INTERN'])],
            'marital_status' => ['nullable', Rule::in(['SINGLE', 'MARRIED', 'DIVORCED', 'OTHER'])],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'DORMANT'])],
            'department_id' => ['nullable', 'exists:departments,id'],
            'attendance_type_id' => ['nullable', 'exists:attendance_types,id'],
            'attendance_status' => ['nullable', Rule::in(['ENABLED', 'DISABLED'])],
            'recruitment_date' => ['nullable', 'date'],
        ]);
    }

    private function fillUser(User $item, array $validated, bool $isCreate): void
    {
        $fillable = [
            'name',
            'email',
            'phone_number',
            'gender',
            'employee_number',
            'employment_type',
            'recruitment_date',
            'employment_date',
            'address',
            'national_id',
            'tin',
            'dob',
            'status',
            'marital_status',
            'designation',
            'department_id',
            'user_device_id',
            'attendance_type_id',
            'attendance_status',
        ];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $validated)) {
                $item->{$field} = $validated[$field];
            }
        }

        if (array_key_exists('type', $validated)) {
            $item->type = $validated['type'];
        }

        if ($isCreate && blank($item->attendance_status)) {
            $item->attendance_status = 'ENABLED';
        }
    }

    private function formatItem(User $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'email' => $item->email,
            'phone_number' => $item->phone_number,
            'gender' => $item->gender,
            'address' => $item->address,
            'designation' => $item->designation,
            'employee_number' => $item->employee_number,
            'user_device_id' => $item->user_device_id,
            'type' => $item->type,
            'dob' => $item->dob,
            'employment_date' => $item->employment_date,
            'tin' => $item->tin,
            'national_id' => $item->national_id,
            'employment_type' => $item->employment_type,
            'marital_status' => $item->marital_status,
            'status' => $item->status,
            'department_id' => $item->department_id,
            'department_name' => $item->department->name ?? null,
            'attendance_type_id' => $item->attendance_type_id,
            'attendance_type_name' => $item->attendanceType->name ?? null,
            'attendance_status' => $item->attendance_status,
            'profile' => $item->profile,
            'signature' => $item->file,
            'contract' => $item->contract,
            'created_at' => optional($item->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($item->updated_at)->format('Y-m-d H:i:s'),
        ];
    }
}
