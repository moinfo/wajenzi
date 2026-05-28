<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContentCreatorCrew;
use App\Models\ContentCreatorPlatformTarget;
use App\Models\ContentCreatorTask;
use App\Models\ContentCreatorTaskComment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Content Creator API (mobile).
 *
 * Mirrors {@see \App\Http\Controllers\ContentCreatorController} (portal).
 *
 * Resources:
 *   - tasks     (the unit of work; has board status + progress)
 *   - comments  (per-task discussion)
 *   - targets   (per-platform monthly post targets)
 *   - crew      (active content team members with online status)
 *
 * The board groups tasks by `status` (todo, in_progress, in_review, published).
 * Search is supported on title, description, and instructions.
 */
class ContentCreatorApiController extends Controller
{
    private const STATUSES   = ['todo', 'in_progress', 'in_review', 'published'];
    private const PLATFORMS  = ['instagram', 'tiktok', 'facebook', 'linkedin', 'youtube', 'general'];
    private const TASK_TYPES = ['video_shoot', 'post_publish', 'design_task', 'review_approval', 'other'];
    private const PRIORITIES = ['high', 'medium', 'low'];

    // ────────────────────────── Index ──────────────────────────

    public function index(Request $request): JsonResponse
    {
        try {
            $month = $request->input('month', now()->format('Y-m'));
            [$year, $mon] = $this->parseMonth($month);

            return response()->json([
                'success' => true,
                'data' => [
                    'month'  => $month,
                    'tasks'  => $this->buildTasks($request),
                    'board'  => $this->buildBoard($request),
                    'stats'  => $this->buildStats((int) $year, (int) $mon),
                    'crew'   => $this->buildCrew(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('ContentCreatorApi index error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch content creator data: '.$e->getMessage(),
            ], 500);
        }
    }

    public function referenceData(): JsonResponse
    {
        $creatorRoles = ['Content creator and IT', 'Digital Marketing and Content Creator'];

        $assignees = User::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']);
        $creators  = User::where('status', 'ACTIVE')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $creatorRoles))
            ->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'statuses'   => array_map(fn ($s) => ['value' => $s, 'label' => $this->labelize($s)], self::STATUSES),
                'platforms'  => array_map(fn ($p) => ['value' => $p, 'label' => ucfirst($p)], self::PLATFORMS),
                'task_types' => array_map(fn ($t) => ['value' => $t, 'label' => $this->labelize($t)], self::TASK_TYPES),
                'priorities' => array_map(fn ($p) => ['value' => $p, 'label' => ucfirst($p)], self::PRIORITIES),
                'assignees'  => $assignees,
                'creators'   => $creators,
            ],
        ]);
    }

    public function board(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->buildBoard($request),
        ]);
    }

    // ────────────────────────── Tasks CRUD ──────────────────────────

    public function showTask(int $id): JsonResponse
    {
        $task = ContentCreatorTask::with([
            'assignee:id,name',
            'creator:id,name',
            'approver:id,name',
            'comments.user:id,name',
        ])->find($id);

        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->transformTask($task, withComments: true),
        ]);
    }

    public function storeTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'assigned_to'  => 'nullable|exists:users,id',
            'deadline'     => 'nullable|date',
            'deadline_time' => 'nullable|string',
            'priority'     => 'required|in:'.implode(',', self::PRIORITIES),
            'platform'     => 'required|in:'.implode(',', self::PLATFORMS),
            'task_type'    => 'required|in:'.implode(',', self::TASK_TYPES),
            'instructions' => 'nullable|string',
        ]);

        $task = ContentCreatorTask::create($validated + [
            'created_by' => Auth::id(),
            'status'     => 'todo',
            'progress'   => 'not_started',
        ]);

        $task->load(['assignee:id,name', 'creator:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Task created',
            'data' => $this->transformTask($task),
        ], 201);
    }

    public function updateTask(Request $request, int $id): JsonResponse
    {
        $task = ContentCreatorTask::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $validated = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'assigned_to'  => 'nullable|exists:users,id',
            'deadline'     => 'nullable|date',
            'priority'     => 'sometimes|required|in:'.implode(',', self::PRIORITIES),
            'platform'     => 'sometimes|required|in:'.implode(',', self::PLATFORMS),
            'task_type'    => 'sometimes|required|in:'.implode(',', self::TASK_TYPES),
            'instructions' => 'nullable|string',
        ]);

        $task->update($validated);
        $task->load(['assignee:id,name', 'creator:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated',
            'data' => $this->transformTask($task),
        ]);
    }

    public function destroyTask(int $id): JsonResponse
    {
        $task = ContentCreatorTask::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }
        $task->delete();
        return response()->json(['success' => true, 'message' => 'Task deleted']);
    }

    public function updateProgress(Request $request, int $id): JsonResponse
    {
        $task = ContentCreatorTask::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $validated = $request->validate([
            'progress' => 'required|in:not_started,in_progress,completed',
            'status'   => 'sometimes|in:'.implode(',', self::STATUSES),
        ]);

        $update = ['progress' => $validated['progress']];

        if ($validated['progress'] === 'completed' && $task->status === 'in_progress') {
            $update['status'] = 'in_review';
            $update['submitted_at'] = now();
        } elseif ($validated['progress'] === 'in_progress' && $task->status === 'todo') {
            $update['status'] = 'in_progress';
        }

        if (!empty($validated['status'])) {
            $update['status'] = $validated['status'];
        }

        $task->update($update);
        $task->load(['assignee:id,name', 'creator:id,name']);

        return response()->json([
            'success' => true,
            'data' => $this->transformTask($task),
        ]);
    }

    public function approveTask(int $id): JsonResponse
    {
        $task = ContentCreatorTask::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $task->update([
            'status'      => 'published',
            'progress'    => 'completed',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        $task->load(['assignee:id,name', 'creator:id,name', 'approver:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Task approved & published',
            'data' => $this->transformTask($task),
        ]);
    }

    // ────────────────────────── Comments ──────────────────────────

    public function addComment(Request $request, int $taskId): JsonResponse
    {
        $task = ContentCreatorTask::find($taskId);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $comment = ContentCreatorTaskComment::create([
            'task_id' => $taskId,
            'user_id' => Auth::id(),
            'comment' => $validated['comment'],
        ]);
        $comment->load('user:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Comment added',
            'data' => $this->transformComment($comment),
        ], 201);
    }

    // ────────────────────────── Targets ──────────────────────────

    public function setTarget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platform'     => 'required|in:instagram,tiktok,facebook,linkedin,youtube',
            'target_posts' => 'required|integer|min:0',
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer',
        ]);

        $target = ContentCreatorPlatformTarget::updateOrCreate(
            ['platform' => $validated['platform'], 'month' => $validated['month'], 'year' => $validated['year']],
            ['target_posts' => $validated['target_posts']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Target saved',
            'data' => [
                'id'           => $target->id,
                'platform'     => $target->platform,
                'target_posts' => (int) $target->target_posts,
                'month'        => (int) $target->month,
                'year'         => (int) $target->year,
            ],
        ]);
    }

    // ────────────────────────── Builders ──────────────────────────

    private function buildTasks(Request $request): array
    {
        $q = ContentCreatorTask::with(['assignee:id,name', 'creator:id,name']);

        if ($request->filled('search')) {
            $s = $request->input('search');
            $q->where(function ($qq) use ($s) {
                $qq->where('title', 'like', "%{$s}%")
                   ->orWhere('description', 'like', "%{$s}%")
                   ->orWhere('instructions', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('platform')) {
            $q->where('platform', $request->input('platform'));
        }
        if ($request->filled('assigned_to')) {
            $q->where('assigned_to', (int) $request->input('assigned_to'));
        }
        if ($request->filled('mine') && (bool) $request->input('mine')) {
            $q->where('assigned_to', Auth::id());
        }

        return $q->latest()->limit(200)->get()
            ->map(fn ($t) => $this->transformTask($t))->all();
    }

    private function buildBoard(Request $request): array
    {
        $board = [];
        $search = $request->input('search');
        foreach (self::STATUSES as $status) {
            $q = ContentCreatorTask::where('status', $status)
                ->with(['assignee:id,name', 'creator:id,name']);
            if ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('title', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%")
                       ->orWhere('instructions', 'like', "%{$search}%");
                });
            }
            $board[$status] = $q->latest()->limit(50)->get()
                ->map(fn ($t) => $this->transformTask($t))->all();
        }
        return $board;
    }

    private function buildStats(int $year, int $mon): array
    {
        $tasks = ContentCreatorTask::whereYear('created_at', $year)->whereMonth('created_at', $mon);

        $total     = (clone $tasks)->count();
        $published = (clone $tasks)->where('status', 'published')->count();
        $overdue   = (clone $tasks)->where('status', '!=', 'published')
            ->whereNotNull('deadline')->where('deadline', '<', now()->toDateString())->count();
        $videos    = (clone $tasks)->where('task_type', 'video_shoot')->count();
        $designs   = (clone $tasks)->where('task_type', 'design_task')->count();
        $onTime    = $total > 0 ? max(0, $total - $overdue) : 0;
        $onTimeRate = $total > 0 ? (int) round(($onTime / $total) * 100) : 100;

        return [
            'total'        => $total,
            'published'    => $published,
            'overdue'      => $overdue,
            'videos'       => $videos,
            'designs'      => $designs,
            'on_time'      => $onTime,
            'on_time_rate' => $onTimeRate,
        ];
    }

    private function buildCrew(): array
    {
        return ContentCreatorCrew::with('user:id,name')
            ->whereHas('user', fn ($q) => $q->where('status', 'ACTIVE'))
            ->get()
            ->map(fn ($c) => [
                'id'            => $c->id,
                'user_id'       => $c->user_id,
                'name'          => $c->user?->name,
                'role'          => $c->role,
                'skills'        => $c->skills ?? [],
                'online_status' => $c->online_status,
            ])->all();
    }

    // ────────────────────────── Transformers ──────────────────────────

    private function transformTask(ContentCreatorTask $task, bool $withComments = false): array
    {
        $out = [
            'id'            => $task->id,
            'title'         => $task->title,
            'description'   => $task->description,
            'instructions'  => $task->instructions,
            'assigned_to'   => $task->assigned_to,
            'assignee_name' => $task->assignee?->name,
            'created_by'    => $task->created_by,
            'creator_name'  => $task->creator?->name,
            'deadline'      => optional($task->deadline)->format('Y-m-d'),
            'deadline_time' => $task->deadline_time,
            'priority'      => $task->priority,
            'status'        => $task->status,
            'progress'      => $task->progress,
            'platform'      => $task->platform,
            'task_type'     => $task->task_type,
            'attachments'   => $task->attachments ?? [],
            'is_overdue'    => method_exists($task, 'isOverdue') ? $task->isOverdue() : false,
            'submitted_at'  => optional($task->submitted_at)->toIso8601String(),
            'approved_at'   => optional($task->approved_at)->toIso8601String(),
            'approved_by'   => $task->approved_by,
            'approver_name' => $task->approver?->name,
            'created_at'    => optional($task->created_at)->toIso8601String(),
            'updated_at'    => optional($task->updated_at)->toIso8601String(),
        ];

        if ($withComments && $task->relationLoaded('comments')) {
            $out['comments'] = $task->comments->map(fn ($c) => $this->transformComment($c))->all();
        }

        return $out;
    }

    private function transformComment(ContentCreatorTaskComment $comment): array
    {
        return [
            'id'         => $comment->id,
            'task_id'    => $comment->task_id,
            'user_id'    => $comment->user_id,
            'user_name'  => $comment->user?->name,
            'comment'    => $comment->comment,
            'created_at' => optional($comment->created_at)->toIso8601String(),
        ];
    }

    // ────────────────────────── Helpers ──────────────────────────

    private function parseMonth(string $month): array
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        return explode('-', $month);
    }

    private function labelize(string $value): string
    {
        return ucwords(str_replace('_', ' ', $value));
    }
}
