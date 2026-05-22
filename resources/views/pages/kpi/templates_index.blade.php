@extends('layouts.backend')

@section('content')
<style>
.tpl-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(290px, 1fr)); gap:16px; }
.tpl-card { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:18px; transition:transform .15s, box-shadow .15s; cursor:pointer; }
.tpl-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.08); border-color:#1BC5BD; }
.tpl-icon { width:42px; height:42px; border-radius:10px; background:#e0f7f6; color:#0d9488; display:flex; align-items:center; justify-content:center; font-size:18px; margin-bottom:12px; }
.tpl-name { font-size:15px; font-weight:700; color:#1a2332; margin:0; }
.tpl-role { font-size:11px; color:#1BC5BD; font-weight:600; text-transform:uppercase; letter-spacing:.4px; margin-top:3px; }
.tpl-meta { display:flex; gap:14px; margin-top:14px; padding-top:12px; border-top:1px solid #f3f4f6; }
.tpl-meta-item { display:flex; flex-direction:column; }
.tpl-meta-label { font-size:10px; color:#94a3b8; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
.tpl-meta-value { font-size:14px; font-weight:700; color:#1a2332; margin-top:1px; font-variant-numeric:tabular-nums; }
.tpl-weight-warn { color:#dc2626 !important; }
.tpl-inactive { opacity:.55; }
.tpl-inactive-badge { background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; margin-left:8px; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 style="font-size:22px; font-weight:800; color:#1a2332; margin:0;">
                <i class="fa fa-cog" style="color:#1BC5BD;"></i> Manage KPI Templates
            </h2>
            <p style="margin:4px 0 0; font-size:13px; color:#8a92a6;">
                One template per role. Click into a template to edit its KPI items, targets, and weights.
            </p>
        </div>
        <a href="{{ route('performance.index') }}"
           style="background:#f3f4f6; color:#475569; padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="tpl-grid">
        @foreach($templates as $t)
            <a href="{{ route('performance.templates.show', $t->id) }}" style="text-decoration:none; color:inherit;">
                <div class="tpl-card {{ $t->is_active ? '' : 'tpl-inactive' }}">
                    <div class="tpl-icon"><i class="fa fa-file-signature"></i></div>
                    <h3 class="tpl-name">
                        {{ $t->name }}
                        @if(!$t->is_active)<span class="tpl-inactive-badge">Inactive</span>@endif
                    </h3>
                    <div class="tpl-role">{{ $t->role }} &middot; {{ ucfirst($t->frequency) }}</div>
                    <div class="tpl-meta">
                        <div class="tpl-meta-item">
                            <span class="tpl-meta-label">Items</span>
                            <span class="tpl-meta-value">{{ $t->item_count }}</span>
                        </div>
                        <div class="tpl-meta-item">
                            <span class="tpl-meta-label">Total Weight</span>
                            <span class="tpl-meta-value {{ abs($t->total_weight - 100) > 0.01 ? 'tpl-weight-warn' : '' }}">
                                {{ rtrim(rtrim(number_format($t->total_weight, 2), '0'), '.') }}%
                                @if(abs($t->total_weight - 100) > 0.01)
                                    <i class="fa fa-exclamation-triangle" title="Weights should total 100%"></i>
                                @endif
                            </span>
                        </div>
                        <div class="tpl-meta-item">
                            <span class="tpl-meta-label">Code</span>
                            <span class="tpl-meta-value" style="font-size:11px; color:#64748b;">{{ $t->code }}</span>
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
