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
.tpl-new-btn { background:#1BC5BD; color:#fff; padding:8px 16px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; }
.tpl-new-btn:hover { background:#159e97; }
.tpl-new-panel { background:#fff; border:1px solid #eef0f3; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:18px 22px; margin-bottom:18px; }
.tpl-new-panel form { display:grid; grid-template-columns:2fr 1.5fr 1fr auto; gap:12px; align-items:end; }
.tpl-new-panel label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.tpl-new-panel input, .tpl-new-panel select { width:100%; border:1.5px solid #cbd5e1; border-radius:6px; padding:7px 9px; font-size:13px; }
.tpl-new-panel button[type=submit] { background:#1BC5BD; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; }
.tpl-new-empty { font-size:13px; color:#94a3b8; margin:0; }
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
        <div style="display:flex; gap:10px;">
            <button type="button" class="tpl-new-btn" onclick="toggleNewTemplate()">
                <i class="fa fa-plus"></i> New Template
            </button>
            <a href="{{ route('performance.index') }}"
               style="background:#f3f4f6; color:#475569; padding:8px 16px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:20px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="tpl-new-panel" id="newTemplatePanel" style="display:{{ $errors->any() ? 'block' : 'none' }};">
        @if($availableRoles->isEmpty())
            <p class="tpl-new-empty">
                <i class="fa fa-check-circle" style="color:#1BC5BD;"></i>
                Every role already has a KPI template. Open an existing one to edit its items.
            </p>
        @else
            <form method="POST" action="{{ route('performance.templates.store') }}">
                @csrf
                <div>
                    <label>Template Name</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="e.g. Site Supervisor Performance Review" required>
                </div>
                <div>
                    <label>Department / Role</label>
                    <select name="role_id" required>
                        <option value="">— Select role —</option>
                        @foreach($availableRoles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Frequency</label>
                    <select name="frequency" required>
                        @foreach(['monthly','quarterly','biannual','annual'] as $freq)
                            <option value="{{ $freq }}" {{ old('frequency', 'monthly') === $freq ? 'selected' : '' }}>
                                {{ ucfirst($freq) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"><i class="fa fa-check"></i> Create</button>
            </form>
        @endif
    </div>

    <script>
        function toggleNewTemplate() {
            var p = document.getElementById('newTemplatePanel');
            p.style.display = p.style.display === 'none' ? 'block' : 'none';
        }
    </script>

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
