@extends('layouts.backend')

@section('css_after')
<style>
/* ── Ticker ──────────────────────────────────────────────────────── */
.cc-ticker {
    background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
    color: #fff;
    padding: 7px 0;
    overflow: hidden;
    white-space: nowrap;
}
.cc-ticker-track { display: inline-block; animation: ticker-scroll 40s linear infinite; }
.cc-ticker-track:hover { animation-play-state: paused; }
@keyframes ticker-scroll {
    0%   { transform: translateX(100vw); }
    100% { transform: translateX(-100%); }
}
.cc-ticker-item { display: inline-block; margin: 0 48px; font-size: 12.5px; opacity: .92; }
.cc-ticker-sep { opacity: .4; margin: 0 4px; }

/* ── Page background ─────────────────────────────────────────────── */
.cc-page { background: #f1f5f9; min-height: calc(100vh - 100px); padding: 0; }

/* ── Layout ──────────────────────────────────────────────────────── */
.cc-wrapper { display: flex; gap: 16px; padding: 16px; align-items: flex-start; }

/* ── Sidebar (crew panel) ────────────────────────────────────────── */
.cc-sidebar {
    width: 220px;
    flex-shrink: 0;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
    padding: 0;
    overflow: hidden;
    position: sticky;
    top: 16px;
}
.cc-sidebar-head {
    padding: 14px 16px 10px;
    border-bottom: 1px solid #f1f5f9;
}
.cc-sidebar-title {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: #94a3b8;
    margin: 0;
}
.cc-crew-list { padding: 6px 0; }
.cc-crew-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 14px;
    cursor: pointer;
    transition: background .12s;
    border-radius: 0;
}
.cc-crew-item:hover { background: #f8fafc; }
.cc-crew-item.active { background: #eff6ff; }
.cc-crew-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
}
.cc-crew-name { font-size: 12.5px; font-weight: 600; color: #1e293b; line-height: 1.2; }
.cc-crew-role { font-size: 10.5px; color: #94a3b8; }
.cc-status-dot { font-size: 9px; margin-left: auto; flex-shrink: 0; }
.cc-sidebar-add {
    padding: 10px 14px 14px;
    border-top: 1px solid #f1f5f9;
}

/* ── Main ────────────────────────────────────────────────────────── */
.cc-main { flex: 1; min-width: 0; }

/* ── Stats row ───────────────────────────────────────────────────── */
.cc-stats-row { display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-bottom: 16px; }
.cc-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px 16px 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    border: 1px solid #e8edf3;
    position: relative;
    overflow: hidden;
}
.cc-stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--stat-color, #2563eb);
    border-radius: 12px 12px 0 0;
}
.cc-stat-icon {
    width: 34px; height: 34px;
    border-radius: 8px;
    background: var(--stat-bg, #eff6ff);
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    margin-bottom: 10px;
}
.cc-stat-value { font-size: 26px; font-weight: 800; line-height: 1; color: var(--stat-color, #1e293b); }
.cc-stat-label { font-size: 11px; color: #64748b; margin-top: 3px; font-weight: 500; }
.cc-stat-bar { height: 3px; border-radius: 2px; margin-top: 12px; background: #f1f5f9; }
.cc-stat-bar-fill { height: 100%; border-radius: 2px; background: var(--stat-color, #2563eb); transition: width .6s ease; }

/* ── Tab bar ─────────────────────────────────────────────────────── */
.cc-tab-bar {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8edf3;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    padding: 4px;
    display: flex;
    align-items: center;
    gap: 2px;
    margin-bottom: 16px;
}
.cc-tab-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    transition: all .15s;
    white-space: nowrap;
}
.cc-tab-btn:hover { background: #f8fafc; color: #334155; text-decoration: none; }
.cc-tab-btn.active {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 2px 6px rgba(37,99,235,.35);
}
.cc-tab-btn.active i { color: #fff; }
.cc-tab-month { margin-left: auto; }
.cc-tab-month input {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 6px 10px;
    font-size: 12.5px;
    color: #334155;
    outline: none;
}

/* ── Calendar ────────────────────────────────────────────────────── */
.cc-cal-wrap {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8edf3;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
}
.cc-cal-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
    flex-wrap: wrap;
    gap: 10px;
}
.cc-cal-title { font-size: 13.5px; font-weight: 700; color: #1e293b; }
.cc-calendar-grid {
    display: grid;
    grid-template-columns: 150px repeat(7, 1fr);
    gap: 0;
    border-top: 1px solid #f1f5f9;
}
.cc-cal-header {
    background: #f8fafc;
    color: #64748b;
    font-size: 11px;
    font-weight: 700;
    text-align: center;
    padding: 10px 4px;
    text-transform: uppercase;
    letter-spacing: .6px;
    border-bottom: 1px solid #e8edf3;
    border-right: 1px solid #f1f5f9;
}
.cc-cal-header span { display: block; font-size: 17px; font-weight: 800; color: #1e293b; letter-spacing: 0; margin-top: 2px; }
.cc-cal-header.today-col { background: #eff6ff; color: #2563eb; }
.cc-cal-header.today-col span { color: #2563eb; }
.cc-cal-row-label {
    background: #fafbfc;
    display: flex;
    align-items: center;
    padding: 0 14px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #e8edf3;
    min-height: 64px;
}
.cc-cal-cell {
    background: #fff;
    min-height: 64px;
    padding: 5px 4px;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    position: relative;
}
.cc-cal-cell.today-col { background: #fafeff; }
.cc-cell-add {
    position: absolute;
    bottom: 3px;
    right: 3px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 1px dashed #cbd5e1;
    background: #fff;
    color: #94a3b8;
    font-size: 9px;
    line-height: 1;
    padding: 0;
    cursor: pointer;
    opacity: 0;
    transition: opacity .12s, background .12s, color .12s;
}
.cc-cal-cell:hover .cc-cell-add { opacity: 1; }
.cc-cell-add:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
.cc-chip {
    display: block;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 6px;
    margin: 2px 0;
    cursor: pointer;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.chip-video  { background: #dbeafe; color: #1d4ed8; border-left: 2px solid #2563eb; }
.chip-post   { background: #dcfce7; color: #166534; border-left: 2px solid #22c55e; }
.chip-design { background: #ede9fe; color: #6d28d9; border-left: 2px solid #7c3aed; }
.chip-review { background: #fef9c3; color: #854d0e; border-left: 2px solid #f59e0b; }
.chip-dayoff { background: #fee2e2; color: #991b1b; border-left: 2px solid #ef4444; }

/* ── Legend chips ────────────────────────────────────────────────── */
.cc-legend { display: flex; flex-wrap: wrap; gap: 6px; }
.cc-legend-chip {
    display: flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 600;
    padding: 3px 8px; border-radius: 20px;
}

/* ── Kanban ──────────────────────────────────────────────────────── */
.cc-kanban { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.cc-kanban-col {
    background: #f8fafc;
    border-radius: 12px;
    border: 1px solid #e8edf3;
    padding: 0;
    min-height: 400px;
    display: flex;
    flex-direction: column;
}
.cc-kanban-col-header {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
    padding: 12px 14px 10px;
    border-radius: 12px 12px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e8edf3;
}
.cc-kanban-count {
    font-size: 11px;
    font-weight: 800;
    width: 22px; height: 22px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
}
.cc-kanban-body { padding: 10px; flex: 1; }
.cc-task-card {
    background: #fff;
    border-radius: 9px;
    padding: 11px 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,.07);
    border: 1px solid #e8edf3;
    border-left: 3px solid #e2e8f0;
    cursor: grab;
    transition: box-shadow .12s, transform .12s;
}
.cc-task-card { position: relative; }
.cc-task-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.1); transform: translateY(-1px); }
.cc-card-del {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: none;
    background: transparent;
    color: #cbd5e1;
    font-size: 10px;
    line-height: 1;
    padding: 0;
    cursor: pointer;
    opacity: 0;
    transition: opacity .12s, background .12s, color .12s;
}
.cc-task-card:hover .cc-card-del { opacity: 1; }
.cc-card-del:hover { background: #fee2e2; color: #b91c1c; }
.cc-task-title { font-size: 12.5px; font-weight: 600; color: #1e293b; margin-bottom: 7px; line-height: 1.35; }
.cc-task-meta { font-size: 11px; color: #64748b; display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.cc-task-assignee { display: flex; align-items: center; gap: 4px; }
.cc-task-assignee-avatar {
    width: 18px; height: 18px; border-radius: 50%;
    background: linear-gradient(135deg,#2563eb,#7c3aed);
    color: #fff; font-size: 8px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
}
.border-todo        { border-left-color: #94a3b8; }
.border-in_progress { border-left-color: #2563eb; }
.border-in_review   { border-left-color: #f59e0b; }
.border-published   { border-left-color: #22c55e; }

/* ── Workability ─────────────────────────────────────────────────── */
.cc-workability-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.cc-workability-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
    border: 1px solid #e8edf3;
}
.cc-workload-bar { height: 7px; background: #f1f5f9; border-radius: 4px; margin: 8px 0 14px; }
.cc-workload-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #2563eb, #7c3aed); transition: width .6s; }
.cc-skill-tag { display: inline-block; background: #f0f9ff; color: #0369a1; font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 20px; margin: 2px; border: 1px solid #bae6fd; }

/* ── Targets ─────────────────────────────────────────────────────── */
.cc-platform-row { display: flex; align-items: center; gap: 14px; padding: 13px 0; border-bottom: 1px solid #f1f5f9; }
.cc-platform-row:last-child { border-bottom: none; }
.cc-platform-bar { flex: 1; background: #f1f5f9; height: 8px; border-radius: 4px; }
.cc-platform-fill { height: 100%; border-radius: 4px; transition: width .7s ease; }

/* ── Matrix ──────────────────────────────────────────────────────── */
.cc-matrix table th, .cc-matrix table td { vertical-align: middle; font-size: 12px; padding: 8px 10px; }
.badge-primary-role   { background: #2563eb; color: #fff; font-size: 10px; border-radius: 4px; padding: 2px 7px; font-weight: 700; }
.badge-support-role   { background: #0ea5e9; color: #fff; font-size: 10px; border-radius: 4px; padding: 2px 7px; font-weight: 700; }
.badge-review-role    { background: #f59e0b; color: #fff; font-size: 10px; border-radius: 4px; padding: 2px 7px; font-weight: 700; }
.badge-oversight-role { background: #8b5cf6; color: #fff; font-size: 10px; border-radius: 4px; padding: 2px 7px; font-weight: 700; }

/* ── Comments / modal ────────────────────────────────────────────── */
.comment-list { max-height: 240px; overflow-y: auto; }
.comment-item { display: flex; gap: 10px; margin-bottom: 12px; }
.comment-avatar { width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg,#2563eb,#7c3aed); color: #fff; font-size: 10px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.overdue-badge { background: #fef2f2; color: #b91c1c; font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 20px; border: 1px solid #fecaca; }

/* ── Section cards ───────────────────────────────────────────────── */
.cc-card {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e8edf3;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
    overflow: hidden;
}
.cc-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid #f1f5f9;
}
.cc-card-title { font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0; }
</style>
@endsection

@section('content')
<div class="cc-page">

{{-- ── Live Ticker ────────────────────────────────────────── --}}
<div class="cc-ticker">
    <div class="cc-ticker-track">
        @forelse($tickerItems as $item)
            <span class="cc-ticker-item"><i class="fas fa-circle" style="font-size:5px;vertical-align:middle;margin-right:6px;opacity:.6;"></i>{{ $item }}</span>
        @empty
            <span class="cc-ticker-item"><i class="fas fa-circle" style="font-size:5px;vertical-align:middle;margin-right:6px;opacity:.6;"></i>Welcome to the Content Creator Dashboard — assign tasks to get started!</span>
        @endforelse
    </div>
</div>

<div class="cc-wrapper">

{{-- ── Sidebar ─────────────────────────────────────────────── --}}
<div class="cc-sidebar">
    <div class="cc-sidebar-head">
        <div class="cc-sidebar-title">👥 Crew Panel</div>
    </div>
    <div class="cc-crew-list">
        @if($crew->isNotEmpty())
            {{-- Registered crew members with roles & status dots --}}
            @foreach($crew as $member)
            <div class="cc-crew-item" onclick="filterByCrew({{ $member->user_id }})">
                <div class="cc-crew-avatar">{{ strtoupper(substr($member->user->name ?? 'U', 0, 2)) }}</div>
                <div style="min-width:0;">
                    <div class="cc-crew-name">{{ Str::limit($member->user->name ?? 'Unknown', 16) }}</div>
                    <div class="cc-crew-role">{{ $member->role }}</div>
                </div>
                <div class="cc-status-dot">
                    @php $dot = match($member->online_status) { 'online' => '🟢', 'busy' => '🟠', 'away' => '🟡', default => '⚫' }; @endphp
                    {{ $dot }}
                </div>
            </div>
            @endforeach
        @else
            {{-- Fallback: content-creator-role users only --}}
            @forelse($creatorUsers as $u)
            <div class="cc-crew-item" onclick="filterByCrew({{ $u->id }})">
                <div class="cc-crew-avatar">{{ strtoupper(substr($u->name, 0, 2)) }}</div>
                <div style="min-width:0;">
                    <div class="cc-crew-name">{{ Str::limit($u->name, 16) }}</div>
                    <div class="cc-crew-role">{{ Str::limit($u->roles->first()?->name ?? 'Creator', 20) }}</div>
                </div>
                <div class="cc-status-dot">🟢</div>
            </div>
            @empty
            <div style="padding:20px 16px;font-size:12px;color:#94a3b8;text-align:center;">
                <i class="fas fa-users" style="font-size:26px;display:block;margin-bottom:8px;opacity:.25;"></i>
                No content creators found.<br>
                <small>Assign the "Content creator and IT" role to users.</small>
            </div>
            @endforelse
        @endif
    </div>
    <div class="cc-sidebar-add">
        <button class="btn btn-primary btn-sm w-100 rounded-pill" data-toggle="modal" data-target="#createTaskModal">
            <i class="fas fa-plus mr-1"></i> New Task
        </button>
    </div>
</div>

{{-- ── Main ────────────────────────────────────────────────── --}}
<div class="cc-main">

    {{-- Stats Row --}}
    @php
        $s = $statsThisMonth;
        $statsCards = [
            ['label' => 'Posts Published', 'value' => $s['published'], 'icon' => '📢', 'color' => '#2563eb', 'bg' => '#eff6ff', 'pct' => min(100, $s['published'] * 5)],
            ['label' => 'Videos Shot',     'value' => $s['videos'],   'icon' => '🎬', 'color' => '#7c3aed', 'bg' => '#f5f3ff', 'pct' => min(100, $s['videos'] * 10)],
            ['label' => 'Designs Made',    'value' => $s['designs'],  'icon' => '🎨', 'color' => '#0ea5e9', 'bg' => '#f0f9ff', 'pct' => min(100, $s['designs'] * 10)],
            ['label' => 'On-Time Rate',    'value' => $s['onTimeRate'].'%', 'icon' => '✅', 'color' => '#16a34a', 'bg' => '#f0fdf4', 'pct' => $s['onTimeRate']],
            ['label' => 'Overdue Tasks',   'value' => $s['overdue'],  'icon' => '⚠️', 'color' => '#dc2626', 'bg' => '#fef2f2', 'pct' => $s['total'] > 0 ? min(100, (int)round($s['overdue']/$s['total']*100)) : 0],
        ];
    @endphp
    <div class="cc-stats-row">
        @foreach($statsCards as $st)
        <div class="cc-stat-card" style="--stat-color:{{ $st['color'] }};--stat-bg:{{ $st['bg'] }};">
            <div class="cc-stat-icon">{{ $st['icon'] }}</div>
            <div class="cc-stat-value">{{ $st['value'] }}</div>
            <div class="cc-stat-label">{{ $st['label'] }}</div>
            <div class="cc-stat-bar"><div class="cc-stat-bar-fill" style="width:{{ $st['pct'] }}%"></div></div>
        </div>
        @endforeach
    </div>

    {{-- Tab bar --}}
    <div class="cc-tab-bar">
        @foreach([
            ['calendar',     'fas fa-calendar-alt', 'Content Calendar'],
            ['workability',  'fas fa-users',         'Crew Workability'],
            ['kanban',       'fas fa-columns',       'Task Board'],
            ['targets',      'fas fa-bullseye',      'Targets & Matrix'],
        ] as [$t, $icon, $label])
        <a class="cc-tab-btn {{ $tab === $t ? 'active' : '' }}"
           href="{{ route('content_creator.index', ['tab' => $t, 'month' => $month]) }}">
            <i class="{{ $icon }}"></i> {{ $label }}
        </a>
        @endforeach
        <div class="cc-tab-month">
            <form method="GET" action="{{ route('content_creator.index') }}">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>

    {{-- ── TAB 1: Content Calendar ── --}}
    @if($tab === 'calendar')
    @php
        $weekStart = ($calendarData['start'] ?? \Carbon\Carbon::now()->startOfWeek())->copy();
        $weekDates = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i));
        $prevWeek  = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek  = $weekStart->copy()->addWeek()->toDateString();
        $thisWeek  = \Carbon\Carbon::now()->startOfWeek()->toDateString();
        $calTasks  = $calendarData['tasks'] ?? collect();
        $calSched  = $calendarData['schedules'] ?? collect();
        // Calendar rows: registered crew if exists, else content-creator-role users only
        $calCrew = $crew->isNotEmpty()
            ? $crew
            : $creatorUsers->map(fn($u) => (object)['user_id' => $u->id, 'user' => $u, 'role' => null, 'online_status' => 'online']);
        $chipMap   = ['video_shoot' => 'chip-video', 'post_publish' => 'chip-post', 'design_task' => 'chip-design', 'review_approval' => 'chip-review', 'day_off' => 'chip-dayoff'];
        $today     = \Carbon\Carbon::today();
    @endphp
    <div class="cc-cal-wrap">
        <div class="cc-cal-top">
            <div class="cc-cal-title d-flex align-items-center" style="gap:8px;">
                <a href="{{ route('content_creator.index', ['tab' => 'calendar', 'month' => $month, 'week' => $prevWeek]) }}"
                   class="btn btn-sm btn-light border" title="Previous week"><i class="fas fa-chevron-left"></i></a>
                <span><i class="fas fa-calendar-week mr-2" style="color:#2563eb;"></i>
                    {{ $weekStart->format('M d') }} – {{ $weekStart->copy()->endOfWeek()->format('M d, Y') }}
                </span>
                <a href="{{ route('content_creator.index', ['tab' => 'calendar', 'month' => $month, 'week' => $nextWeek]) }}"
                   class="btn btn-sm btn-light border" title="Next week"><i class="fas fa-chevron-right"></i></a>
                <a href="{{ route('content_creator.index', ['tab' => 'calendar', 'month' => now()->format('Y-m'), 'week' => $thisWeek]) }}"
                   class="btn btn-sm btn-outline-primary" title="Jump to this week">Today</a>
            </div>
            <div class="cc-legend">
                <span class="cc-legend-chip chip-video">🎬 Video Shoot</span>
                <span class="cc-legend-chip chip-post">📢 Post/Publish</span>
                <span class="cc-legend-chip chip-design">🎨 Design</span>
                <span class="cc-legend-chip chip-review">✅ Review</span>
                <span class="cc-legend-chip chip-dayoff">🔴 Day Off</span>
            </div>
        </div>
        <div class="cc-calendar-grid">
            <div class="cc-cal-header">Member</div>
            @foreach($weekDates as $d)
                <div class="cc-cal-header {{ $d->isSameDay($today) ? 'today-col' : '' }}">
                    {{ $d->format('D') }}
                    <span>{{ $d->format('d') }}</span>
                </div>
            @endforeach
            @forelse($calCrew as $member)
            @php
                $mid    = $member->user_id ?? ($member->id ?? null);
                $mname  = $member->user->name ?? ($member->name ?? 'Unknown');
                $mTasks = $calTasks->filter(fn($t) => $t->assigned_to == $mid);
                $mSched = $calSched->filter(fn($s) => $s->user_id == $mid);
            @endphp
            <div class="cc-cal-row-label">{{ Str::limit($mname, 15) }}</div>
            @foreach($weekDates as $d)
            @php
                $dTasks = $mTasks->filter(fn($t) => $t->deadline && $t->deadline->isSameDay($d));
                $dSched = $mSched->filter(fn($s) => $s->date->isSameDay($d));
            @endphp
            <div class="cc-cal-cell {{ $d->isSameDay($today) ? 'today-col' : '' }}">
                @foreach($dSched as $sc)
                <span class="cc-chip {{ $chipMap[$sc->task_type] ?? '' }}" title="{{ e($sc->title) }}">{{ Str::limit($sc->title, 18) }}</span>
                @endforeach
                @foreach($dTasks as $dt)
                <span class="cc-chip {{ $chipMap[$dt->task_type] ?? '' }}" title="{{ e($dt->title) }}" onclick="viewTask({{ $dt->id }})">{{ Str::limit($dt->title, 18) }}</span>
                @endforeach
                <button type="button" class="cc-cell-add"
                        title="Add task for {{ $mname }} on {{ $d->format('D, M d') }}"
                        onclick="openCreateTaskFor({{ $mid ?? 'null' }}, '{{ $d->toDateString() }}')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            @endforeach
            @empty
            <div style="grid-column:1/-1;padding:48px;text-align:center;background:#fff;color:#94a3b8;">
                <i class="fas fa-users" style="font-size:32px;display:block;margin-bottom:10px;opacity:.3;"></i>
                No crew members. Add crew to populate the calendar.
            </div>
            @endforelse
        </div>
    </div>
    @endif

    {{-- ── TAB 2: Crew Workability ── --}}
    @if($tab === 'workability')
    <div class="cc-workability-grid">
        @forelse($crewWorkability ?? [] as $w)
        <div class="cc-workability-card">
            <div class="d-flex align-items-center mb-3" style="gap:12px;">
                <div class="cc-crew-avatar" style="width:44px;height:44px;font-size:16px;background:#1e293b;">
                    {{ strtoupper(substr($w->member->user->name ?? 'U', 0, 2)) }}
                </div>
                <div>
                    <div style="font-weight:700;font-size:14px;">{{ $w->member->user->name ?? 'Unknown' }}</div>
                    <div style="font-size:11px;color:#64748b;">{{ $w->member->role }}</div>
                </div>
                <div class="ml-auto">
                    @php $dot = match($w->member->online_status) { 'online' => '🟢', 'busy' => '🟠', 'away' => '🟡', default => '⚫' }; @endphp
                    {{ $dot }}
                </div>
            </div>
            <div style="font-size:12px;color:#475569;font-weight:600;">Workload: {{ $w->workload }}%</div>
            <div class="cc-workload-bar"><div class="cc-workload-fill" style="width:{{ $w->workload }}%"></div></div>
            <div class="mt-3" style="font-size:12px;">
                <div class="d-flex justify-content-between mb-1">
                    <span><i class="fas fa-check-circle text-success mr-1"></i>Done</span><span class="font-weight-bold">{{ $w->done }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span><i class="fas fa-spinner text-primary mr-1"></i>In Progress</span><span class="font-weight-bold">{{ $w->inProgress }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span><i class="fas fa-tasks text-secondary mr-1"></i>Total Tasks</span><span class="font-weight-bold">{{ $w->total }}</span>
                </div>
                @if($w->overdue > 0)
                <div class="d-flex justify-content-between">
                    <span><i class="fas fa-exclamation-triangle text-danger mr-1"></i>Overdue</span><span class="font-weight-bold text-danger">{{ $w->overdue }}</span>
                </div>
                @endif
            </div>
            @if(!empty($w->member->skills))
            <div class="mt-3">
                @foreach($w->member->skills as $skill)
                    <span class="cc-skill-tag">{{ $skill }}</span>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-5 text-muted" style="grid-column:1/-1;">No crew workability data for this period.</div>
        @endforelse
    </div>
    @endif

    {{-- ── TAB 3: Task Board (Kanban) ── --}}
    @if($tab === 'kanban')
    @php
        $cols = [
            'todo'        => ['label' => 'To Do',      'color' => '#64748b', 'bg' => '#f8fafc', 'dot' => '#94a3b8'],
            'in_progress' => ['label' => 'In Progress', 'color' => '#2563eb', 'bg' => '#eff6ff', 'dot' => '#2563eb'],
            'in_review'   => ['label' => 'In Review',   'color' => '#d97706', 'bg' => '#fffbeb', 'dot' => '#f59e0b'],
            'published'   => ['label' => 'Published',   'color' => '#16a34a', 'bg' => '#f0fdf4', 'dot' => '#22c55e'],
        ];
        $platformEmoji = ['instagram'=>'📸','tiktok'=>'🎵','facebook'=>'👍','linkedin'=>'💼','youtube'=>'▶️','general'=>'🌐'];
    @endphp
    <div class="cc-kanban">
        @foreach($cols as $status => $col)
        <div class="cc-kanban-col" data-status="{{ $status }}"
             ondragover="event.preventDefault()" ondrop="dropTask(event,'{{ $status }}')">
            <div class="cc-kanban-col-header" style="background:{{ $col['bg'] }};color:{{ $col['color'] }};">
                <div style="display:flex;align-items:center;gap:7px;">
                    <span style="width:9px;height:9px;border-radius:50%;background:{{ $col['dot'] }};display:inline-block;"></span>
                    {{ $col['label'] }}
                </div>
                <span class="cc-kanban-count" style="background:{{ $col['dot'] }};color:#fff;">{{ ($kanbanTasks[$status] ?? collect())->count() }}</span>
            </div>
            <div class="cc-kanban-body">
                @forelse($kanbanTasks[$status] ?? [] as $task)
                <div class="cc-task-card border-{{ $status }}"
                     draggable="true" data-task-id="{{ $task->id }}"
                     ondragstart="dragStart(event)" onclick="viewTask({{ $task->id }})">
                    <button type="button" class="cc-card-del"
                            title="Delete task"
                            onclick="event.stopPropagation(); deleteTaskById({{ $task->id }})">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="cc-task-title">{{ $task->title }}</div>
                    <div class="cc-task-meta">
                        @if($task->assignee)
                        <div class="cc-task-assignee">
                            <div class="cc-task-assignee-avatar">{{ strtoupper(substr($task->assignee->name, 0, 2)) }}</div>
                            <span>{{ Str::words($task->assignee->name, 1, '') }}</span>
                        </div>
                        @endif
                        <span>{{ $platformEmoji[$task->platform] ?? '🌐' }}</span>
                        @if($task->deadline)
                            @if($task->isOverdue())
                                <span class="overdue-badge">⚠ {{ $task->deadline->format('d M') }}</span>
                            @else
                                <span style="color:#64748b;">{{ $task->deadline->format('d M') }}</span>
                            @endif
                        @endif
                        <span style="margin-left:auto;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;
                            background:{{ $task->priority === 'high' ? '#fef2f2' : ($task->priority === 'medium' ? '#fefce8' : '#f0fdf4') }};
                            color:{{ $task->priority === 'high' ? '#b91c1c' : ($task->priority === 'medium' ? '#854d0e' : '#166534') }};">
                            {{ ucfirst($task->priority) }}
                        </span>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:30px 10px;">
                    <i class="fas fa-inbox" style="font-size:24px;color:#e2e8f0;display:block;margin-bottom:8px;"></i>
                    <span style="font-size:11px;color:#94a3b8;">Drop tasks here</span>
                </div>
                @endforelse
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── TAB 4: Targets & Responsibility Matrix ── --}}
    @if($tab === 'targets')
    @php
        $fillColors = ['instagram'=>'#e1306c','tiktok'=>'#333','facebook'=>'#1877f2','linkedin'=>'#0a66c2','youtube'=>'#ff0000'];
        $platformEmoji4 = ['instagram'=>'📸','tiktok'=>'🎵','facebook'=>'👍','linkedin'=>'💼','youtube'=>'▶️'];
    @endphp
    <div class="row">
        <div class="col-md-7">
            <div class="cc-card mb-4">
                <div class="cc-card-header">
                    <h6 class="cc-card-title"><i class="fas fa-bullseye mr-2" style="color:#2563eb;"></i>
                        Post Targets — {{ \Carbon\Carbon::createFromDate($year, $mon, 1)->format('F Y') }}
                    </h6>
                    <button class="btn btn-sm btn-primary rounded-pill" data-toggle="modal" data-target="#setTargetModal">
                        <i class="fas fa-edit mr-1"></i> Set Target
                    </button>
                </div>
                <div style="padding:8px 18px 4px;">
                    @foreach($platforms as $platform)
                    @php $pd = $targets[$platform] ?? ['target' => 0, 'current' => 0, 'percent' => 0]; $fc = $fillColors[$platform]; @endphp
                    <div class="cc-platform-row">
                        <div style="width:38px;height:38px;border-radius:10px;background:{{ $fc }}18;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                            {{ $platformEmoji4[$platform] ?? '🌐' }}
                        </div>
                        <div style="flex:0 0 90px;font-size:12.5px;font-weight:700;text-transform:capitalize;color:#334155;">{{ $platform }}</div>
                        <div style="flex:1;"><div class="cc-platform-bar"><div class="cc-platform-fill" style="width:{{ $pd['percent'] }}%;background:{{ $fc }};"></div></div></div>
                        <div style="font-size:12px;white-space:nowrap;min-width:60px;text-align:right;color:#475569;">
                            <strong style="color:#1e293b;">{{ $pd['current'] }}</strong> / {{ $pd['target'] ?: '—' }}
                        </div>
                        <div style="font-size:11.5px;font-weight:800;min-width:38px;text-align:right;
                            color:{{ $pd['percent'] >= 100 ? '#16a34a' : ($pd['percent'] >= 60 ? '#d97706' : '#dc2626') }};">
                            {{ $pd['percent'] }}%
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="cc-card cc-matrix">
                <div class="cc-card-header">
                    <h6 class="cc-card-title"><i class="fas fa-sitemap mr-2" style="color:#7c3aed;"></i>Responsibility Matrix</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="font-size:11.5px;">
                        <thead style="background:#f8fafc;">
                            <tr>
                                <th style="color:#64748b;font-weight:700;border-top:none;">Task Type</th>
                                <th style="border-top:none;"><span class="badge-primary-role">PRIMARY</span></th>
                                <th style="border-top:none;"><span class="badge-support-role">SUPPORT</span></th>
                                <th style="border-top:none;"><span class="badge-review-role">REVIEW</span></th>
                                <th style="border-top:none;"><span class="badge-oversight-role">OVERSIGHT</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([
                                ['Video Shoot',     'Videographer', 'Editor',      'Creative Lead', 'Manager'],
                                ['Post Design',     'Designer',     'Copywriter',  'Creative Lead', 'Manager'],
                                ['Captions',        'Copywriter',   'Designer',    'Creative Lead', 'Manager'],
                                ['Scheduling',      'Social Mgr',   'Copywriter',  'Manager',       '—'],
                                ['Analytics',       'Analyst',      'Social Mgr',  'Manager',       '—'],
                                ['Client Approval', 'Account Mgr',  '—',           'Creative Lead', 'Director'],
                            ] as $row)
                            <tr>
                                <td style="font-weight:700;color:#334155;">{{ $row[0] }}</td>
                                <td><span class="badge-primary-role">{{ $row[1] }}</span></td>
                                <td><span class="badge-support-role">{{ $row[2] }}</span></td>
                                <td><span class="badge-review-role">{{ $row[3] }}</span></td>
                                <td><span class="badge-oversight-role">{{ $row[4] }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>{{-- .cc-main --}}
</div>{{-- .cc-wrapper --}}
</div>{{-- .cc-page --}}

{{-- ═══════════════ MODALS ═══════════════ --}}

{{-- Create Task --}}
<div class="modal fade" id="createTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#0f172a;color:#fff;">
                <h5 class="modal-title"><i class="fas fa-plus-circle mr-2"></i>Create New Task</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="createTaskForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Task Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Shoot product video for Instagram Reels" required>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Describe what needs to be done…"></textarea>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Instructions for Assignee</label>
                                <textarea name="instructions" class="form-control" rows="2" placeholder="Specific steps, references, or notes…"></textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Assign To</label>
                                <select name="assigned_to" class="form-control">
                                    <option value="">— Unassigned —</option>
                                    @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-control" required>
                                    <option value="high">🔴 High</option>
                                    <option value="medium" selected>🟡 Medium</option>
                                    <option value="low">🟢 Low</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Platform <span class="text-danger">*</span></label>
                                <select name="platform" class="form-control" required>
                                    <option value="instagram">📸 Instagram</option>
                                    <option value="tiktok">🎵 TikTok</option>
                                    <option value="facebook">👍 Facebook</option>
                                    <option value="linkedin">💼 LinkedIn</option>
                                    <option value="youtube">▶️ YouTube</option>
                                    <option value="general">🌐 General</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Task Type <span class="text-danger">*</span></label>
                                <select name="task_type" class="form-control" required>
                                    <option value="video_shoot">🎬 Video Shoot</option>
                                    <option value="post_publish">📢 Post/Publish</option>
                                    <option value="design_task">🎨 Design Task</option>
                                    <option value="review_approval">✅ Review/Approval</option>
                                    <option value="other">📌 Other</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:13px;">Deadline</label>
                                <div class="input-group">
                                    <input type="text" name="deadline" id="createDeadlinePicker"
                                           class="form-control datepicker-cc" autocomplete="off"
                                           placeholder="Select date…" readonly>
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i>Create & Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- View Task --}}
<div class="modal fade" id="viewTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#1e293b;color:#fff;">
                <h5 class="modal-title" id="viewTaskTitle">Task Details</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-7">
                        <div class="d-flex flex-wrap mb-3" style="gap:6px;" id="taskBadgesContainer"></div>
                        <p id="taskDescription" class="text-muted" style="font-size:13px;"></p>
                        <div id="taskInstructions" class="alert alert-info" style="font-size:12px;display:none;"></div>
                        <div class="card bg-light border-0 mb-3">
                            <div class="card-body py-2 px-3">
                                <div class="font-weight-bold mb-2" style="font-size:12px;">Update Your Progress</div>
                                <div class="d-flex flex-wrap" style="gap:8px;">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="updateProgress('not_started')">Not Started</button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="updateProgress('in_progress')">In Progress</button>
                                    <button class="btn btn-sm btn-success" onclick="updateProgress('completed')">Mark Complete ✓</button>
                                </div>
                            </div>
                        </div>
                        {{-- Attachments --}}
                        <div class="mb-3">
                            <div class="font-weight-bold mb-2" style="font-size:13px;"><i class="fas fa-paperclip mr-1 text-secondary"></i>Attachments</div>
                            <div id="attachmentList" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px;"></div>
                            <label class="btn btn-sm btn-outline-secondary mb-0" style="cursor:pointer;">
                                <i class="fas fa-upload mr-1"></i> Attach File
                                <input type="file" id="attachmentInput" accept="image/*,video/*,.pdf,.ai,.psd,.svg,.zip,.sketch" style="display:none;" onchange="uploadAttachment(this)">
                            </label>
                            <span id="attachmentUploading" style="display:none;font-size:12px;color:#64748b;margin-left:8px;"><i class="fas fa-spinner fa-spin mr-1"></i>Uploading…</span>
                        </div>
                        <div class="mt-3">
                            <div class="font-weight-bold mb-2" style="font-size:13px;"><i class="fas fa-comments mr-1 text-primary"></i>Comments</div>
                            <div class="comment-list" id="commentList"></div>
                            <div class="d-flex mt-2" style="gap:8px;">
                                <input type="text" id="newCommentInput" class="form-control form-control-sm" placeholder="Ask a question or share an update…">
                                <button class="btn btn-sm btn-primary flex-shrink-0" onclick="addComment()"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card border-0" style="background:#f8fafc;">
                            <div class="card-body">
                                <div class="mb-3">
                                    <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Assigned To</div>
                                    <div id="taskAssignee" class="font-weight-bold mt-1" style="font-size:14px;"></div>
                                </div>
                                <div class="mb-3">
                                    <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Deadline</div>
                                    <div id="taskDeadline" class="font-weight-bold mt-1" style="font-size:14px;"></div>
                                </div>
                                <div class="mb-3">
                                    <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Platform</div>
                                    <div id="taskPlatform" class="mt-1" style="font-size:14px;"></div>
                                </div>
                                <div class="mb-3">
                                    <div style="font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;font-weight:700;">Created By</div>
                                    <div id="taskCreator" class="mt-1" style="font-size:13px;color:#475569;"></div>
                                </div>
                                <hr>
                                <div id="approveSection" style="display:none;">
                                    <button class="btn btn-success btn-block btn-sm" onclick="approveTask()">
                                        <i class="fas fa-check-circle mr-1"></i> Approve & Mark Published
                                    </button>
                                </div>
                                <button class="btn btn-outline-danger btn-block btn-sm mt-2" onclick="deleteTask()">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete Task
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Set Target --}}
<div class="modal fade" id="setTargetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-bullseye mr-2"></i>Set Platform Target</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="setTargetForm">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size:13px;">Platform</label>
                        <select name="platform" class="form-control" required>
                            <option value="instagram">📸 Instagram</option>
                            <option value="tiktok">🎵 TikTok</option>
                            <option value="facebook">👍 Facebook</option>
                            <option value="linkedin">💼 LinkedIn</option>
                            <option value="youtube">▶️ YouTube</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size:13px;">Target Posts This Month</label>
                        <input type="number" name="target_posts" class="form-control" min="0" placeholder="e.g. 30" required>
                    </div>
                    <input type="hidden" name="month" value="{{ (int)$mon }}">
                    <input type="hidden" name="year" value="{{ (int)$year }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Target</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js_after')
<style>
/* Datepicker must sit above Bootstrap modals (z-index 1050) */
.datepicker-dropdown { z-index: 1400 !important; }
</style>
<script>
// ─── Bootstrap Datepicker init ────────────────────────────────────
$(function () {
    // Use a dedicated class so we don't accidentally init other pickers
    $('.datepicker-cc').datepicker({
        format:        'yyyy-mm-dd',
        autoclose:     true,
        todayHighlight: true,
        startDate:     new Date(),
    });
});
</script>
<script>
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let currentTaskId = null;

    // ─── Helpers ────────────────────────────────────────────────
    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    function apiFetch(url, opts = {}) {
        const defaults = {
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' }
        };
        return fetch(url, Object.assign(defaults, opts)).then(r => r.json());
    }

    function showToast(msg, type) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ toast: true, position: 'top-end', icon: type, title: esc(msg),
                showConfirmButton: false, timer: 2200, timerProgressBar: true });
        }
    }

    // ─── Open Create Task pre-filled (per-day cell action) ───────
    window.openCreateTaskFor = function (userId, dateStr) {
        const form = document.getElementById('createTaskForm');
        if (!form) return;
        form.reset();
        if (dateStr) {
            // Bootstrap datepicker uses .datepicker('update', date) to sync UI
            try { $('#createDeadlinePicker').datepicker('update', dateStr); }
            catch (err) { form.querySelector('[name="deadline"]').value = dateStr; }
        }
        if (userId) {
            const sel = form.querySelector('[name="assigned_to"]');
            if (sel) sel.value = String(userId);
        }
        $('#createTaskModal').modal('show');
    };

    // ─── Create Task ─────────────────────────────────────────────
    document.getElementById('createTaskForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        apiFetch('{{ route("content_creator.tasks.store") }}', { method: 'POST', body: JSON.stringify(data) })
            .then(res => {
                if (res.success) {
                    $('#createTaskModal').modal('hide');
                    this.reset();
                    showToast('Task created and assigned!', 'success');
                    setTimeout(() => location.reload(), 1000);
                }
            });
    });

    // ─── View Task ────────────────────────────────────────────────
    window.viewTask = function (id) {
        currentTaskId = id;
        apiFetch(`/content-creator/tasks/${id}`)
            .then(task => {
                document.getElementById('viewTaskTitle').textContent = task.title;
                document.getElementById('taskDescription').textContent = task.description || 'No description provided.';

                const instEl = document.getElementById('taskInstructions');
                if (task.instructions) {
                    instEl.style.display = '';
                    instEl.textContent = '📌 ' + task.instructions;
                } else {
                    instEl.style.display = 'none';
                }

                document.getElementById('taskAssignee').textContent = task.assignee?.name || 'Unassigned';
                document.getElementById('taskCreator').textContent = 'by ' + (task.creator?.name || '—');

                if (task.deadline) {
                    document.getElementById('taskDeadline').textContent =
                        new Date(task.deadline).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                } else {
                    document.getElementById('taskDeadline').textContent = 'No deadline set';
                }

                const platformMap = { instagram: '📸 Instagram', tiktok: '🎵 TikTok', facebook: '👍 Facebook', linkedin: '💼 LinkedIn', youtube: '▶️ YouTube', general: '🌐 General' };
                document.getElementById('taskPlatform').textContent = platformMap[task.platform] || task.platform;

                renderBadges(task);
                renderComments(task.comments || []);
                renderAttachments(task.attachments || []);

                document.getElementById('approveSection').style.display = task.status === 'in_review' ? '' : 'none';
                $('#viewTaskModal').modal('show');
            });
    };

    function renderBadges(task) {
        const container = document.getElementById('taskBadgesContainer');
        container.innerHTML = '';

        const statusColors = { todo: '#94a3b8', in_progress: '#2563eb', in_review: '#f59e0b', published: '#22c55e' };
        const statusLabels = { todo: 'To Do', in_progress: 'In Progress', in_review: 'In Review', published: 'Published' };
        const priorityColors = { high: '#ef4444', medium: '#f59e0b', low: '#22c55e' };

        const statusBadge = document.createElement('span');
        statusBadge.className = 'badge';
        statusBadge.style.cssText = `background:${statusColors[task.status] || '#94a3b8'};color:#fff;font-size:12px;`;
        statusBadge.textContent = statusLabels[task.status] || task.status;
        container.appendChild(statusBadge);

        const priorityBadge = document.createElement('span');
        priorityBadge.className = 'badge';
        priorityBadge.style.cssText = `background:${priorityColors[task.priority] || '#94a3b8'};color:#fff;font-size:12px;`;
        priorityBadge.textContent = (task.priority.charAt(0).toUpperCase() + task.priority.slice(1)) + ' Priority';
        container.appendChild(priorityBadge);

        if (task.deadline && new Date(task.deadline) < new Date() && task.status !== 'published') {
            const overdueBadge = document.createElement('span');
            overdueBadge.className = 'overdue-badge';
            overdueBadge.textContent = 'OVERDUE';
            container.appendChild(overdueBadge);
        }
    }

    function renderComments(comments) {
        const list = document.getElementById('commentList');
        list.innerHTML = '';

        if (!comments.length) {
            const empty = document.createElement('div');
            empty.style.cssText = 'color:#94a3b8;font-size:12px;text-align:center;padding:10px;';
            empty.textContent = 'No comments yet. Be the first to comment.';
            list.appendChild(empty);
            return;
        }

        comments.forEach(c => {
            const item = document.createElement('div');
            item.className = 'comment-item';

            const avatar = document.createElement('div');
            avatar.className = 'comment-avatar';
            avatar.textContent = (c.user?.name || 'U').substring(0, 2).toUpperCase();

            const body = document.createElement('div');

            const name = document.createElement('div');
            name.style.cssText = 'font-size:11px;font-weight:700;';
            name.textContent = c.user?.name || 'User';

            const text = document.createElement('div');
            text.style.cssText = 'font-size:12px;color:#334155;';
            text.textContent = c.comment;

            const time = document.createElement('div');
            time.style.cssText = 'font-size:10px;color:#94a3b8;';
            time.textContent = new Date(c.created_at).toLocaleString();

            body.appendChild(name);
            body.appendChild(text);
            body.appendChild(time);
            item.appendChild(avatar);
            item.appendChild(body);
            list.appendChild(item);
        });
    }

    // ─── Attachments ──────────────────────────────────────────────
    function renderAttachments(attachments) {
        const list = document.getElementById('attachmentList');
        list.replaceChildren();
        if (!attachments || !attachments.length) {
            const empty = document.createElement('span');
            empty.style.cssText = 'font-size:12px;color:#94a3b8;';
            empty.textContent = 'No attachments yet.';
            list.appendChild(empty);
            return;
        }
        attachments.forEach(a => {
            const isImage = /^image\//.test(a.mime || '');
            const chip = document.createElement('a');
            chip.href = a.url;
            chip.target = '_blank';
            chip.rel = 'noopener';
            chip.style.cssText = 'display:inline-flex;align-items:center;gap:5px;padding:4px 10px;background:#f1f5f9;border-radius:20px;font-size:11.5px;color:#334155;text-decoration:none;border:1px solid #e2e8f0;max-width:200px;overflow:hidden;';
            chip.title = a.name;
            const icon = document.createElement('i');
            icon.className = isImage ? 'fas fa-image' : 'fas fa-file';
            icon.style.color = '#64748b';
            const label = document.createElement('span');
            label.style.cssText = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
            label.textContent = a.name;
            chip.appendChild(icon);
            chip.appendChild(label);
            list.appendChild(chip);
        });
    }

    window.uploadAttachment = function (input) {
        if (!input.files.length || !currentTaskId) return;
        const file = input.files[0];
        input.value = '';
        const uploading = document.getElementById('attachmentUploading');
        uploading.style.display = 'inline';
        const form = new FormData();
        form.append('file', file);
        fetch(`/content-creator/tasks/${currentTaskId}/attachments`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: form,
        })
        .then(r => r.json())
        .then(res => {
            uploading.style.display = 'none';
            if (res.success) {
                renderAttachments(res.attachments);
                showToast('File attached!', 'success');
            } else {
                showToast(res.message || 'Upload failed.', 'error');
            }
        })
        .catch(() => { uploading.style.display = 'none'; showToast('Upload failed.', 'error'); });
    };

    // ─── Update Progress ──────────────────────────────────────────
    window.updateProgress = function (progress) {
        apiFetch(`/content-creator/tasks/${currentTaskId}/progress`, {
            method: 'POST', body: JSON.stringify({ progress })
        }).then(res => {
            if (res.success) {
                showToast('Progress updated!', 'success');
                window.viewTask(currentTaskId);
            }
        });
    };

    // ─── Add Comment ──────────────────────────────────────────────
    window.addComment = function () {
        const input = document.getElementById('newCommentInput');
        const comment = input.value.trim();
        if (!comment) return;
        apiFetch(`/content-creator/tasks/${currentTaskId}/comments`, {
            method: 'POST', body: JSON.stringify({ comment })
        }).then(res => {
            if (res.success) {
                input.value = '';
                window.viewTask(currentTaskId);
            }
        });
    };

    // Enter key submits comment
    document.getElementById('newCommentInput').addEventListener('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); window.addComment(); }
    });

    // ─── Delete Task ──────────────────────────────────────────────
    function performDelete(id, onDone) {
        apiFetch(`/content-creator/tasks/${id}`, { method: 'DELETE' })
            .then(res => {
                if (res && res.success) {
                    showToast('Task deleted.', 'success');
                    if (typeof onDone === 'function') onDone();
                    setTimeout(() => location.reload(), 700);
                } else {
                    showToast('Could not delete task.', 'error');
                }
            })
            .catch(() => showToast('Could not delete task.', 'error'));
    }

    function confirmThen(message, action) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Delete this task?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Yes, delete it'
            }).then(r => {
                // SweetAlert2 v9 (installed) returns r.value; v10+ uses r.isConfirmed.
                // Accept both so the click reliably triggers the delete.
                if (r && (r.isConfirmed === true || r.value === true)) action();
            });
        } else if (window.confirm(message)) {
            action();
        }
    }

    window.deleteTask = function () {
        if (!currentTaskId) return;
        const id = currentTaskId;
        confirmThen('This cannot be undone.', () => {
            performDelete(id, () => $('#viewTaskModal').modal('hide'));
        });
    };

    window.deleteTaskById = function (id) {
        confirmThen('This cannot be undone.', () => performDelete(id));
    };

    // ─── Approve Task ─────────────────────────────────────────────
    window.approveTask = function () {
        apiFetch(`/content-creator/tasks/${currentTaskId}/approve`, { method: 'POST' })
            .then(res => {
                if (res.success) {
                    showToast('Task approved & published!', 'success');
                    $('#viewTaskModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                }
            });
    };

    // ─── Kanban drag & drop ───────────────────────────────────────
    let draggedTaskId = null;
    window.dragStart = function (e) {
        draggedTaskId = e.currentTarget.dataset.taskId;
        e.currentTarget.classList.add('dragging');
    };
    window.dropTask = function (e, newStatus) {
        e.preventDefault();
        if (!draggedTaskId) return;
        apiFetch(`/content-creator/tasks/${draggedTaskId}`, {
            method: 'PUT', body: JSON.stringify({ status: newStatus })
        }).then(res => {
            if (res.success) {
                showToast('Task moved!', 'success');
                setTimeout(() => location.reload(), 600);
            }
        });
        draggedTaskId = null;
    };

    // ─── Set Target form ──────────────────────────────────────────
    document.getElementById('setTargetForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this));
        apiFetch('{{ route("content_creator.targets.set") }}', { method: 'POST', body: JSON.stringify(data) })
            .then(res => {
                if (res.success) {
                    $('#setTargetModal').modal('hide');
                    showToast('Target saved!', 'success');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(res.message || 'Could not save target.', 'error');
                }
            })
            .catch(() => showToast('Could not save target.', 'error'));
    });

    // ─── Crew filter ──────────────────────────────────────────────
    window.filterByCrew = function (userId) {
        window.location = `{{ route('content_creator.index', ['tab' => 'workability']) }}&crew=${userId}`;
    };

}());
</script>
@endsection
