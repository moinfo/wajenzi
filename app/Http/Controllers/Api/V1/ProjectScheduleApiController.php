<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProjectSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectScheduleApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->hasAnyRole(['System Administrator', 'Managing Director']);

        $schedules = ProjectSchedule::with([
            'lead:id,lead_number,name',
            'client:id,first_name,last_name',
            'assignedArchitect:id,name',
            'activities:id,project_schedule_id,status',
        ])
            ->when(!$isAdmin, fn ($query) => $query->where(function ($query) use ($user) {
                $query->where('assigned_architect_id', $user->id)
                    ->orWhereHas('activities', fn ($activityQuery) => $activityQuery->where('assigned_to', $user->id));
            }))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules->map(fn (ProjectSchedule $schedule) => $this->transformSchedule($schedule)),
            'meta' => [
                'total' => $schedules->count(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->hasAnyRole(['System Administrator', 'Managing Director']);

        $schedule = ProjectSchedule::with([
            'lead.client',
            'assignedArchitect:id,name',
            'activities.assignedUser:id,name',
            'activities.role:id,name',
        ])->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Project schedule not found',
            ], 404);
        }

        if (
            !$isAdmin &&
            $schedule->assigned_architect_id !== $user->id &&
            !$schedule->activities->contains(fn ($activity) => (int) $activity->assigned_to === (int) $user->id)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view this project schedule',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformSchedule($schedule, true, $isAdmin, $user->id),
        ]);
    }

    private function transformSchedule(
        ProjectSchedule $schedule,
        bool $includeActivities = false,
        bool $isAdmin = false,
        ?int $userId = null
    ): array {
        $progress = $schedule->progress_details;
        $clientName = $schedule->client
            ? trim(($schedule->client->first_name ?? '') . ' ' . ($schedule->client->last_name ?? ''))
            : null;

        $data = [
            'id' => $schedule->id,
            'lead_id' => $schedule->lead_id,
            'client_id' => $schedule->client_id,
            'lead_number' => $schedule->lead->lead_number ?? null,
            'lead_name' => $schedule->lead->name ?? null,
            'client_name' => $clientName,
            'assigned_architect_id' => $schedule->assigned_architect_id,
            'assigned_architect_name' => $schedule->assignedArchitect->name ?? null,
            'start_date' => optional($schedule->start_date)->format('Y-m-d'),
            'end_date' => optional($schedule->end_date)->format('Y-m-d'),
            'status' => $schedule->status,
            'notes' => $schedule->notes,
            'confirmed_at' => $schedule->confirmed_at?->toIso8601String(),
            'progress' => [
                'total' => $progress['total'] ?? 0,
                'completed' => $progress['completed'] ?? 0,
                'in_progress' => $progress['in_progress'] ?? 0,
                'pending' => $progress['pending'] ?? 0,
                'overdue' => $progress['overdue'] ?? 0,
                'percentage' => $progress['percentage'] ?? 0,
            ],
            'created_at' => $schedule->created_at?->toIso8601String(),
            'updated_at' => $schedule->updated_at?->toIso8601String(),
        ];

        if ($includeActivities) {
            $activities = $schedule->activities;
            if (!$isAdmin && $schedule->assigned_architect_id !== $userId) {
                $activities = $activities->filter(fn ($activity) => (int) $activity->assigned_to === (int) $userId)->values();
            }

            $data['activities'] = $activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'activity_code' => $activity->activity_code,
                    'name' => $activity->name,
                    'phase' => $activity->phase,
                    'discipline' => $activity->discipline,
                    'start_date' => optional($activity->start_date)->format('Y-m-d'),
                    'end_date' => optional($activity->end_date)->format('Y-m-d'),
                    'duration_days' => $activity->duration_days,
                    'predecessor_code' => $activity->predecessor_code,
                    'assigned_to' => $activity->assigned_to,
                    'assigned_user_name' => $activity->assignedUser->name ?? null,
                    'role_name' => $activity->role->name ?? null,
                    'status' => $activity->status,
                    'started_at' => $activity->started_at?->toIso8601String(),
                    'completed_at' => $activity->completed_at?->toIso8601String(),
                    'notes' => $activity->notes,
                    'completion_notes' => $activity->completion_notes,
                ];
            })->values();
        }

        return $data;
    }
}
