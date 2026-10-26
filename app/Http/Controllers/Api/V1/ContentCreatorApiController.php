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
use Illuminate\Support\Facades\Storage;

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

    /** Roles allowed to act on any content task. */
    private const MANAGER_ROLES = [
        'Managing Director', 'CEO', 'Chief Executive Officer',
        'Content Manager', 'Marketing Manager',
        'System Administrator', 'Admin',
    ];

    private function isManager(): bool
    {
        $user = Auth::user();
        return $user !== null && $user->hasAnyRole(self::MANAGER_ROLES);
    }

    /** Owner = assignee or creator. Managers always pass. */
    private function ensureCanEditTask(ContentCreatorTask $task): void
    {
        $uid = Auth::id();
        abort_unless(
            $this->isManager() || $task->assigned_to === $uid || $task->created_by === $uid,
            403,
            'You are not authorized to modify this task.',
        );
    }

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
        $this->ensureCanEditTask($task);

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
        $this->ensureCanEditTask($task);
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
        // Approval is a manager-only action.
        abort_unless($this->isManager(), 403, 'Only managers can approve tasks.');

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

    // Upload a file attachment onto a task (mirrors web uploadAttachment).
    public function uploadAttachment(Request $request, int $id): JsonResponse
    {
        $task = ContentCreatorTask::find($id);
        if (!$task) {
            return response()->json(['success' => false, 'message' => 'Task not found'], 404);
        }

        // Same ownership gate the controller applies to task edits (owner or
        // manager) — web has no authz here, but mobile deliberately does.
        $this->ensureCanEditTask($task);

        // svg intentionally excluded (stored-XSS vector when served
        // same-origin); consistent with the app's other mobile upload
        // endpoints. Designers can still upload SVG via the web app.
        $request->validate([
            'file' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,pdf,ai,psd,zip,sketch',
        ]);

        $file = $request->file('file');
        $path = $file->store("content-creator/attachments/{$task->id}", 'public');

        $attachments   = $task->attachments ?? [];
        $attachments[] = [
            'name'        => $file->getClientOriginalName(),
            'path'        => $path,
            'url'         => Storage::url($path),
            'mime'        => $file->getMimeType(),
            'uploaded_by' => Auth::id(),
            'uploaded_at' => now()->toISOString(),
        ];

        $task->update(['attachments' => $attachments]);

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded',
            'attachments' => $attachments,
        ]);
    }

    // Update a crew member's online presence (mirrors web updateCrewStatus).
    public function updateCrewStatus(Request $request, int $userId): JsonResponse
    {
        if (!User::where('id', $userId)->exists()) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        // Users may set their own presence; only managers may set another
        // crew member's status (prevents presence spoofing).
        abort_unless(
            Auth::id() === $userId || $this->isManager(),
            403,
            'You can only update your own status.',
        );

        $validated = $request->validate([
            'online_status' => 'required|in:online,busy,away,offline',
        ]);

        ContentCreatorCrew::updateOrCreate(
            ['user_id' => $userId],
            ['online_status' => $validated['online_status']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Status updated',
            'data' => ['user_id' => $userId, 'online_status' => $validated['online_status']],
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
        $crew = ContentCreatorCrew::with('user:id,name')
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

        if (!empty($crew)) {
            return $crew;
        }

        // No crew records yet: the web shows users holding a content-creator role.
        return User::where('status', 'ACTIVE')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Content creator and IT', 'Digital Marketing and Content Creator']))
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn ($u) => [
                'id'            => null,
                'user_id'       => $u->id,
                'name'          => $u->name,
                'role'          => $u->roles->first()?->name ?? 'Creator',
                'skills'        => [],
                'online_status' => 'online',
            ])->all();
    }

    // ────────────────────────── Web tabs ──────────────────────────

    /** The five stat tiles above the tabs. */
    public function stats(Request $request): JsonResponse
    {
        [$year, $mon] = $this->parseMonth($request->input('month', now()->format('Y-m')));

        return response()->json(['success' => true, 'data' => $this->buildStats((int) $year, (int) $mon)]);
    }

    /** Content Calendar tab: one week, a row per crew member, each day's tasks and schedule. */
    public function calendar(Request $request): JsonResponse
    {
        try {
            $anchor = $request->filled('week') ? \Carbon\Carbon::parse($request->input('week')) : now();
        } catch (\Throwable $e) {
            $anchor = now();
        }
        $start = $anchor->copy()->startOfWeek();
        $end = $start->copy()->endOfWeek();

        $tasks = ContentCreatorTask::with('assignee:id,name')->whereNotNull('deadline')
            ->whereBetween('deadline', [$start->toDateString(), $end->toDateString()])->get();
        $schedules = \App\Models\ContentCreatorSchedule::whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();

        $days = collect(range(0, 6))->map(fn ($i) => $start->copy()->addDays($i)->toDateString())->all();

        $rows = collect($this->buildCrew())->map(function ($m) use ($tasks, $schedules, $days) {
            $uid = $m['user_id'];
            $cells = [];
            foreach ($days as $d) {
                $items = [];
                foreach ($schedules->where('user_id', $uid) as $sc) {
                    if ($sc->date->toDateString() === $d) {
                        $items[] = ['kind' => 'schedule', 'id' => $sc->id, 'title' => $sc->title, 'task_type' => $sc->task_type];
                    }
                }
                foreach ($tasks->where('assigned_to', $uid) as $t) {
                    if ($t->deadline && $t->deadline->toDateString() === $d) {
                        $items[] = ['kind' => 'task', 'id' => $t->id, 'title' => $t->title, 'task_type' => $t->task_type];
                    }
                }
                $cells[$d] = $items;
            }

            return ['user_id' => $uid, 'name' => $m['name'], 'days' => $cells];
        })->values()->all();

        return response()->json(['success' => true, 'data' => [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'days' => $days,
            'members' => $rows,
        ]]);
    }

    /** Crew Workability tab. */
    public function workability(Request $request): JsonResponse
    {
        [$year, $mon] = $this->parseMonth($request->input('month', now()->format('Y-m')));

        $rows = collect($this->buildCrew())->map(function ($m) use ($year, $mon) {
            $tasks = ContentCreatorTask::where('assigned_to', $m['user_id'])
                ->whereYear('created_at', (int) $year)->whereMonth('created_at', (int) $mon)->get();
            $total = $tasks->count();
            $done = $tasks->where('status', 'published')->count();
            $inProgress = $tasks->whereIn('status', ['in_progress', 'in_review'])->count();
            $overdue = $tasks->filter(fn ($t) => $t->isOverdue())->count();

            return $m + [
                'total' => $total,
                'done' => $done,
                'in_progress' => $inProgress,
                'overdue' => $overdue,
                'workload' => $total > 0 ? min(100, (int) round(($inProgress + $done) / $total * 100)) : 0,
            ];
        })->values()->all();

        return response()->json(['success' => true, 'data' => $rows]);
    }

    /** Targets & Matrix tab: per-platform post targets plus the responsibility matrix. */
    public function targets(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        [$year, $mon] = $this->parseMonth($month);

        $targets = ContentCreatorPlatformTarget::where('month', (int) $mon)->where('year', (int) $year)->get()->keyBy('platform');

        $platforms = collect(['instagram', 'tiktok', 'facebook', 'linkedin', 'youtube'])->map(function ($p) use ($targets, $year, $mon) {
            $target = (int) ($targets->get($p)?->target_posts ?? 0);
            $current = ContentCreatorTask::where('platform', $p)->where('status', 'published')
                ->whereMonth('approved_at', (int) $mon)->whereYear('approved_at', (int) $year)->count();

            return [
                'platform' => $p,
                'target' => $target,
                'current' => $current,
                'percent' => $target > 0 ? min(100, (int) round($current / $target * 100)) : 0,
            ];
        })->all();

        $matrix = collect([
            ['Video Shoot', 'Videographer', 'Editor', 'Creative Lead', 'Manager'],
            ['Post Design', 'Designer', 'Copywriter', 'Creative Lead', 'Manager'],
            ['Captions', 'Copywriter', 'Designer', 'Creative Lead', 'Manager'],
            ['Scheduling', 'Social Mgr', 'Copywriter', 'Manager', '—'],
            ['Analytics', 'Analyst', 'Social Mgr', 'Manager', '—'],
            ['Client Approval', 'Account Mgr', '—', 'Creative Lead', 'Director'],
        ])->map(fn ($r) => ['task_type' => $r[0], 'primary' => $r[1], 'support' => $r[2], 'review' => $r[3], 'oversight' => $r[4]])->all();

        return response()->json(['success' => true, 'data' => ['month' => $month, 'platforms' => $platforms, 'matrix' => $matrix]]);
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
