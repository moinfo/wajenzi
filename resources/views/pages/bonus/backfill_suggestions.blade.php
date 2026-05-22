@extends('layouts.backend')

@section('content')
<style>
.bf-card { background:#fff; border-radius:10px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:16px; }
.bf-card-head { padding:14px 18px; border-bottom:1px solid #f0f2f5; display:flex; justify-content:space-between; align-items:center; }
.bf-task-name { font-weight:700; color:#1a2332; font-size:14px; }
.bf-task-meta { color:#8a92a6; font-size:12px; margin-top:2px; }
.bf-card-body { padding:14px 18px; }
.bf-candidate-row { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:6px; background:#fafbfc; }
.bf-cand-name { font-weight:600; color:#1a2332; font-size:13px; }
.bf-cand-meta { color:#8a92a6; font-size:11px; margin-top:2px; }
.bf-sim { display:inline-block; padding:3px 9px; border-radius:12px; font-size:10px; font-weight:700; }
.bf-sim-high { background:#dcfce7; color:#166534; }
.bf-sim-mid  { background:#fef9c3; color:#854d0e; }
.bf-sim-low  { background:#f1f5f9; color:#475569; }
.bf-empty { padding:40px 20px; text-align:center; color:#94a3b8; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 style="font-size:22px; font-weight:800; color:#1a2332; margin:0;">Backfill Bonus → Schedule Links</h2>
            <p style="margin:4px 0 0; font-size:13px; color:#8a92a6;">
                Existing bonus tasks not yet linked to a project schedule. Pick the best match per task — or leave unlinked.
            </p>
        </div>
        <a href="{{ route('architect-bonus.index') }}"
           style="background:#f3f4f6; color:#475569; padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @forelse($suggestions as $s)
        <div class="bf-card">
            <div class="bf-card-head">
                <div>
                    <div class="bf-task-name">
                        <i class="fa fa-trophy" style="color:#f59e0b;"></i>
                        {{ $s->task->task_number }} — {{ $s->task->project_name }}
                    </div>
                    <div class="bf-task-meta">
                        Architect: {{ $s->task->architect->name ?? '—' }}
                        &middot; Status: <strong>{{ ucfirst($s->task->status) }}</strong>
                        &middot; Created: {{ $s->task->created_at->format('d M Y') }}
                    </div>
                </div>
                <a href="{{ url('/architect-bonus/' . $s->task->id) }}" style="color:#1BC5BD; font-size:13px; font-weight:600;">View task &rarr;</a>
            </div>
            <div class="bf-card-body">
                @if($s->candidates->isEmpty())
                    <div class="bf-empty">
                        <i class="fa fa-search fa-2x mb-2" style="display:block;"></i>
                        No matching project schedules found for this architect.
                    </div>
                @else
                    <form method="POST" action="{{ route('architect-bonus.link-schedule', $s->task->id) }}">
                        @csrf
                        @foreach($s->candidates as $c)
                            @php
                                $simClass = $c->similarity >= 0.7 ? 'bf-sim-high' : ($c->similarity >= 0.4 ? 'bf-sim-mid' : 'bf-sim-low');
                            @endphp
                            <label class="bf-candidate-row" style="cursor:pointer;">
                                <div style="flex:1;">
                                    <input type="radio" name="project_schedule_id" value="{{ $c->id }}" required style="margin-right:8px;">
                                    <span class="bf-cand-name">Schedule #{{ $c->id }} — {{ $c->name }}</span>
                                    <div class="bf-cand-meta">
                                        Start: {{ $c->start_date ?? '—' }}
                                        &middot; Status: {{ ucfirst(str_replace('_', ' ', $c->status)) }}
                                    </div>
                                </div>
                                <span class="bf-sim {{ $simClass }}">
                                    {{ (int) round($c->similarity * 100) }}% match
                                </span>
                            </label>
                        @endforeach
                        <div style="margin-top:10px; text-align:right;">
                            <button type="submit"
                                    style="background:#1BC5BD; color:#fff; padding:7px 16px; border-radius:8px; border:none; font-weight:600; font-size:13px;">
                                <i class="fa fa-link"></i> Link selected schedule
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="bf-card">
            <div class="bf-empty">
                <i class="fa fa-check-circle fa-2x mb-2" style="display:block; color:#22c55e;"></i>
                <p>All bonus tasks are already linked to schedules. Nothing to backfill.</p>
            </div>
        </div>
    @endforelse

</div>
@endsection
