<?php

namespace App\Http\Controllers;

use App\Models\ContentCreatorCrew;
use App\Models\ContentCreatorPlatformTarget;
use App\Models\ContentCreatorTask;
use App\Models\ContentCreatorTaskComment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ContentCreatorController extends Controller
{
    // ─── Main dashboard ──────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $tab   = $request->get('tab', 'calendar');
        $month = $request->get('month', now()->format('Y-m'));
        [$year, $mon] = explode('-', $month);

        // Calendar week anchor — defaults to current week, but follows the
        // month picker (jumps to start of that month) and supports a `week`
        // query param (any date in the desired week, "YYYY-MM-DD") plus
        // explicit prev/next navigation handled by the view links.
        $weekParam = $request->get('week');
        if ($weekParam) {
            try {
                $weekAnchor = \Carbon\Carbon::parse($weekParam);
            } catch (\Throwable $e) {
                $weekAnchor = now();
            }
        } elseif ($request->has('month') && ($mon != now()->month || $year != now()->year)) {
            $weekAnchor = \Carbon\Carbon::createFromDate((int)$year, (int)$mon, 1);
        } else {
            $weekAnchor = now();
        }

        // DB stores status as 'ACTIVE' / 'INACTIVE' (uppercase)
        $activeUsers = User::where('status', 'ACTIVE')->orderBy('name')->get();

        $crew = ContentCreatorCrew::with('user')
            ->whereHas('user', fn($q) => $q->where('status', 'ACTIVE'))
            ->get();

        // When no crew records exist, fall back to users with content-creator roles only
        // so we don't flood the panel/calendar with architects, accountants, etc.
        $creatorRoles = ['Content creator and IT', 'Digital Marketing and Content Creator'];
        $creatorUsers = User::where('status', 'ACTIVE')
            ->whereHas('roles', fn($q) => $q->whereIn('name', $creatorRoles))
            ->orderBy('name')
            ->get();

        // "Assign To" dropdown stays as all active users (any role can be assigned a task)
        $users = $activeUsers;

        $platforms = ['instagram', 'tiktok', 'facebook', 'linkedin', 'youtube'];
        $targets = ContentCreatorPlatformTarget::where('month', $mon)->where('year', $year)->get()->keyBy('platform');

        $statsThisMonth = $this->buildStats((int)$year, (int)$mon);
        $tickerItems    = $this->buildTickerItems();

        $data = compact('tab', 'month', 'year', 'mon', 'crew', 'users', 'activeUsers', 'creatorUsers', 'platforms', 'targets', 'statsThisMonth', 'tickerItems', 'weekAnchor');

        switch ($tab) {
            case 'workability':
                $data['crewWorkability'] = $this->buildCrewWorkability((int)$year, (int)$mon, $crew);
                break;
            case 'kanban':
                $data['kanbanTasks'] = $this->buildKanban();
                break;
            case 'targets':
                $data['targets'] = $this->buildTargets((int)$year, (int)$mon, $platforms, $targets);
                break;
            default: // calendar
                $data['calendarData'] = $this->buildCalendar($weekAnchor);
                break;
        }

        return view('pages.content_creator.index', $data);
    }

    // ─── Task CRUD ───────────────────────────────────────────────────────────

    public function storeTask(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'assigned_to'  => 'nullable|exists:users,id',
            'deadline'     => 'nullable|date',
            'deadline_time'=> 'nullable',
            'priority'     => 'required|in:high,medium,low',
            'platform'     => 'required|in:instagram,tiktok,facebook,linkedin,youtube,general',
            'task_type'    => 'required|in:video_shoot,post_publish,design_task,review_approval,other',
            'instructions' => 'nullable|string',
        ]);

        $data['created_by'] = Auth::id();
        $data['status']     = 'todo';
        $data['progress']   = 'not_started';

        $task = ContentCreatorTask::create($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'task' => $task->load('assignee', 'creator')]);
        }
        return back()->with('success', 'Task created successfully.');
    }

    public function updateTask(Request $request, ContentCreatorTask $task)
    {
        $data = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'assigned_to'  => 'nullable|exists:users,id',
            'deadline'     => 'nullable|date',
            'priority'     => 'sometimes|in:high,medium,low',
            'platform'     => 'sometimes|in:instagram,tiktok,facebook,linkedin,youtube,general',
            'task_type'    => 'sometimes|in:video_shoot,post_publish,design_task,review_approval,other',
            'instructions' => 'nullable|string',
        ]);

        $task->update($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'task' => $task->fresh('assignee', 'creator')]);
        }
        return back()->with('success', 'Task updated.');
    }

    public function updateProgress(Request $request, ContentCreatorTask $task)
    {
        $request->validate([
            'progress' => 'required|in:not_started,in_progress,completed',
            'status'   => 'sometimes|in:todo,in_progress,in_review,published',
        ]);

        $update = ['progress' => $request->progress];

        if ($request->progress === 'completed' && $task->status === 'in_progress') {
            $update['status']       = 'in_review';
            $update['submitted_at'] = now();
        } elseif ($request->progress === 'in_progress' && $task->status === 'todo') {
            $update['status'] = 'in_progress';
        }

        if ($request->has('status')) {
            $update['status'] = $request->status;
        }

        $task->update($update);

        return response()->json(['success' => true, 'task' => $task->fresh()]);
    }

    public function addComment(Request $request, ContentCreatorTask $task)
    {
        $request->validate(['comment' => 'required|string|max:2000']);

        $comment = ContentCreatorTaskComment::create([
            'task_id' => $task->id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        return response()->json(['success' => true, 'comment' => $comment->load('user')]);
    }

    public function approveTask(ContentCreatorTask $task)
    {
        $task->update([
            'status'      => 'published',
            'progress'    => 'completed',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);

        return response()->json(['success' => true, 'task' => $task->fresh()]);
    }

    public function getTask(ContentCreatorTask $task)
    {
        return response()->json($task->load('assignee', 'creator', 'approver', 'comments.user'));
    }

    public function destroyTask(ContentCreatorTask $task)
    {
        $task->delete();
        return response()->json(['success' => true]);
    }

    public function uploadAttachment(Request $request, ContentCreatorTask $task)
    {
        $request->validate([
            'file' => 'required|file|max:51200|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,pdf,ai,psd,svg,zip,sketch',
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

        return response()->json(['success' => true, 'attachments' => $attachments]);
    }

    public function setTarget(Request $request)
    {
        $request->validate([
            'platform'     => 'required|in:instagram,tiktok,facebook,linkedin,youtube',
            'target_posts' => 'required|integer|min:0',
            'month'        => 'required|integer|min:1|max:12',
            'year'         => 'required|integer',
        ]);

        ContentCreatorPlatformTarget::updateOrCreate(
            ['platform' => $request->platform, 'month' => $request->month, 'year' => $request->year],
            ['target_posts' => $request->target_posts]
        );

        return response()->json(['success' => true]);
    }

    public function updateCrewStatus(Request $request, User $user)
    {
        $request->validate(['online_status' => 'required|in:online,busy,away,offline']);

        ContentCreatorCrew::updateOrCreate(
            ['user_id' => $user->id],
            ['online_status' => $request->online_status]
        );

        return response()->json(['success' => true]);
    }

    // ─── Data builders ───────────────────────────────────────────────────────

    private function buildStats(int $year, int $mon): array
    {
        $tasks = ContentCreatorTask::whereYear('created_at', $year)->whereMonth('created_at', $mon);

        $total     = (clone $tasks)->count();
        $published = (clone $tasks)->where('status', 'published')->count();
        $overdue   = (clone $tasks)->where('status', '!=', 'published')
            ->whereNotNull('deadline')->where('deadline', '<', now()->toDateString())->count();

        $videos  = (clone $tasks)->where('task_type', 'video_shoot')->count();
        $designs = (clone $tasks)->where('task_type', 'design_task')->count();
        $onTime  = $total > 0 ? max(0, $total - $overdue) : 0;
        $onTimeRate = $total > 0 ? (int) round(($onTime / $total) * 100) : 100;

        return compact('total', 'published', 'overdue', 'videos', 'designs', 'onTimeRate', 'onTime');
    }

    private function buildTickerItems(): array
    {
        $recent = ContentCreatorTask::with('assignee', 'creator')
            ->latest()->limit(10)->get();

        return $recent->map(function ($t) {
            $who  = $t->assignee?->name ?? $t->creator->name;
            $verb = match($t->status) {
                'published'   => 'published',
                'in_review'   => 'submitted for review',
                'in_progress' => 'started working on',
                default       => 'was assigned',
            };
            return "{$who} {$verb}: \"{$t->title}\" [{$t->platform}]";
        })->toArray();
    }

    private function buildCalendar(\Carbon\Carbon $anchor): array
    {
        $start = $anchor->copy()->startOfWeek();
        $end   = $start->copy()->endOfWeek();

        $tasks = ContentCreatorTask::with('assignee')
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [$start->toDateString(), $end->toDateString()])
            ->get();

        $schedules = \App\Models\ContentCreatorSchedule::with('user')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        return compact('tasks', 'schedules', 'start');
    }

    private function buildCrewWorkability(int $year, int $mon, $crew): \Illuminate\Support\Collection
    {
        return $crew->map(function ($member) use ($year, $mon) {
            $tasks = ContentCreatorTask::where('assigned_to', $member->user_id)
                ->whereYear('created_at', $year)->whereMonth('created_at', $mon)
                ->get();

            $total      = $tasks->count();
            $done       = $tasks->where('status', 'published')->count();
            $inProgress = $tasks->whereIn('status', ['in_progress', 'in_review'])->count();
            $overdue    = $tasks->filter(fn($t) => $t->isOverdue())->count();
            $workload   = $total > 0 ? min(100, (int) round(($inProgress + $done) / max($total, 1) * 100)) : 0;

            return (object) compact('member', 'tasks', 'total', 'done', 'inProgress', 'overdue', 'workload');
        });
    }

    private function buildKanban(): array
    {
        $statuses = ['todo', 'in_progress', 'in_review', 'published'];
        $result   = [];

        foreach ($statuses as $status) {
            $result[$status] = ContentCreatorTask::where('status', $status)
                ->with('assignee', 'creator')
                ->latest()->limit(50)->get();
        }

        return $result;
    }

    private function buildTargets(int $year, int $mon, array $platforms, $targets): array
    {
        $result = [];
        foreach ($platforms as $platform) {
            $target = $targets->get($platform);
            $current = ContentCreatorTask::where('platform', $platform)
                ->where('status', 'published')
                ->whereMonth('approved_at', $mon)
                ->whereYear('approved_at', $year)
                ->count();

            $result[$platform] = [
                'target'   => $target?->target_posts ?? 0,
                'current'  => $current,
                'percent'  => $target && $target->target_posts > 0
                    ? min(100, (int) round($current / $target->target_posts * 100))
                    : 0,
            ];
        }
        return $result;
    }
}
