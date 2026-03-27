<?php

namespace App\Http\Controllers\Api\V1;

use App\Classes\Utility;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 20);
            $search = trim((string) $request->input('search', ''));

            $query = Message::query()->latest();

            if ($search !== '') {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            }

            $messages = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $this->stats(),
                    'data' => collect($messages->items())->map(
                        fn($message) => $this->formatMessage($message)
                    )->values(),
                    'meta' => [
                        'current_page' => $messages->currentPage(),
                        'last_page' => $messages->lastPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total(),
                    ],
                    'filters' => [
                        'search' => $search,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MessageApi index error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SMS messages: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        try {
            $departments = Department::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($department) => [
                    'id' => $department->id,
                    'name' => $department->name,
                ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'departments' => $departments,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('MessageApi referenceData error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SMS reference data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function birthdays(): JsonResponse
    {
        try {
            $today = Carbon::today();

            $birthdays = User::where('status', 'ACTIVE')
                ->whereNotNull('dob')
                ->get()
                ->map(function ($user) use ($today) {
                    $dob = Carbon::parse($user->dob);
                    $birthday = $dob->copy()->year($today->year);

                    if ($birthday->lt($today)) {
                        $birthday->addYear();
                    }

                    $daysUntil = $today->diffInDays($birthday, false);

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone_number' => $user->phone_number,
                        'dob_formatted' => $dob->format('d M'),
                        'is_today' => $daysUntil === 0,
                        'days_until' => $daysUntil,
                    ];
                })
                ->sortBy('days_until')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $birthdays,
            ]);
        } catch (\Throwable $e) {
            Log::error('MessageApi birthdays error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch birthdays: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $balance = Utility::getSmsBalance();
            if ($balance === null || $balance <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send SMS. Insufficient SMS balance.',
                ], 422);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:30',
                'message' => 'required|string|max:5000',
            ]);

            $phoneNumber = $this->normalizePhoneNumber($validated['phone']);
            Utility::sendSingleDestination($phoneNumber, $validated['message']);

            $message = Message::create([
                'name' => $validated['name'],
                'phone' => preg_replace('/\D+/', '', $validated['phone']),
                'message' => $validated['message'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully.',
                'data' => $this->formatMessage($message->fresh()),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MessageApi store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $balance = Utility::getSmsBalance();
            if ($balance === null || $balance <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot send SMS. Insufficient SMS balance.',
                ], 422);
            }

            $validated = $request->validate([
                'department_id' => 'required|integer|min:0',
                'message' => 'required|string|max:5000',
            ]);

            $usersQuery = User::query()
                ->select('phone_number', 'name')
                ->where('status', 'ACTIVE')
                ->whereNotNull('phone_number');

            if ((int) $validated['department_id'] !== 0) {
                $usersQuery->where('department_id', $validated['department_id']);
            }

            $users = $usersQuery->get()->filter(
                fn($user) => filled($user->phone_number)
            )->values();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No recipients found for the selected department.',
                ], 422);
            }

            $destinations = $users
                ->map(fn($user) => $this->normalizePhoneNumber($user->phone_number))
                ->implode('","');

            Utility::sendSingleMessageMultipleDestination($destinations, $validated['message']);

            $timestamp = now();
            $rows = $users->map(fn($user) => [
                'name' => $user->name,
                'phone' => preg_replace('/\D+/', '', (string) $user->phone_number),
                'message' => $validated['message'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])->all();

            Message::insert($rows);

            return response()->json([
                'success' => true,
                'message' => 'Bulk SMS sent successfully.',
                'data' => [
                    'recipients_count' => count($rows),
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MessageApi bulkStore error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk SMS: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $message = Message::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatMessage($message),
            ]);
        } catch (\Throwable $e) {
            Log::error('MessageApi show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch SMS message: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:30',
                'message' => 'required|string|max:5000',
            ]);

            $message = Message::findOrFail($id);
            $message->update([
                'name' => $validated['name'],
                'phone' => preg_replace('/\D+/', '', $validated['phone']),
                'message' => $validated['message'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS record updated successfully.',
                'data' => $this->formatMessage($message->fresh()),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('MessageApi update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update SMS message: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $message = Message::findOrFail($id);
            $message->delete();

            return response()->json([
                'success' => true,
                'message' => 'SMS record deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            Log::error('MessageApi destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete SMS message: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function stats(): array
    {
        return [
            'sms_balance' => Utility::getSmsBalance(),
            'total_messages' => Message::count(),
            'today_messages' => Message::whereDate('created_at', today())->count(),
            'this_week_messages' => Message::whereBetween(
                'created_at',
                [now()->startOfWeek(), now()->endOfWeek()]
            )->count(),
            'this_month_messages' => Message::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    private function formatMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'name' => $message->name,
            'phone' => $message->phone,
            'message' => $message->message,
            'created_at' => $message->created_at?->toISOString(),
            'created_at_human' => $message->created_at?->diffForHumans(),
        ];
    }

    private function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '255' . substr($digits, 1);
        }

        if (str_starts_with($digits, '255')) {
            return $digits;
        }

        return '255' . $digits;
    }
}
