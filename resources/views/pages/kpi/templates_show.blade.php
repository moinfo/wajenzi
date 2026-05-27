@extends('layouts.backend')

@section('content')
<style>
.tpl-header { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:18px 22px; margin-bottom:18px; }
.tpl-section { background:#fff; border-radius:12px; border:1px solid #eef0f3; box-shadow:0 1px 4px rgba(0,0,0,.06); margin-bottom:18px; overflow:hidden; }
.tpl-section-head { background:#1a2332; color:#fff; padding:12px 18px; display:flex; justify-content:space-between; align-items:center; }
.tpl-section-head .title { font-weight:700; font-size:14px; letter-spacing:.3px; }
.tpl-section-head .meta { background:rgba(255,255,255,.18); padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.tpl-tbl { width:100%; border-collapse:collapse; font-size:12.5px; }
.tpl-tbl thead th { background:#f8fafc; color:#475569; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; padding:9px 10px; border-bottom:1px solid #e5e7eb; }
.tpl-tbl tbody td { padding:8px 10px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
.tpl-tbl input[type="text"], .tpl-tbl input[type="number"], .tpl-tbl textarea { width:100%; border:1.5px solid transparent; border-radius:6px; padding:5px 7px; font-size:12.5px; background:transparent; }
.tpl-tbl input:focus, .tpl-tbl textarea:focus { border-color:#1BC5BD; outline:none; background:#fff; box-shadow:0 0 0 3px rgba(27,197,189,.12); }
.tpl-tbl input[type="number"] { width:75px; text-align:center; }
.tpl-tbl textarea { resize:vertical; min-height:34px; }
.tpl-btn-save { background:#1BC5BD; color:#fff; border:none; padding:5px 12px; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; }
.tpl-btn-save:hover { background:#159e97; }
.tpl-btn-del { background:transparent; color:#dc2626; border:none; cursor:pointer; padding:5px 8px; }
.tpl-btn-del:hover { background:#fee2e2; border-radius:6px; }
.tpl-save-all { display:flex; justify-content:flex-end; align-items:center; gap:12px; padding:12px 18px; background:#f8fafc; border-top:1px solid #eef0f3; }
.tpl-save-all .hint { font-size:11.5px; color:#94a3b8; margin-right:auto; }
.tpl-btn-save-all { background:#1BC5BD; color:#fff; border:none; padding:9px 20px; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; }
.tpl-btn-save-all:hover { background:#159e97; }
.tpl-add-row { background:#f8fafc; padding:12px 18px; }
.tpl-add-row form { display:grid; grid-template-columns:1.2fr 2fr 1.5fr 80px 80px; gap:8px; align-items:start; }
.tpl-add-row input, .tpl-add-row textarea { border:1.5px solid #cbd5e1; border-radius:6px; padding:6px 9px; font-size:12.5px; width:100%; }
.tpl-add-row button { background:#1BC5BD; color:#fff; border:none; padding:6px 12px; border-radius:6px; font-weight:600; font-size:12px; cursor:pointer; }
.tpl-edit-btn { background:#f3f4f6; color:#475569; padding:7px 13px; border-radius:8px; border:none; font-weight:600; font-size:13px; cursor:pointer; }
.tpl-edit-btn:hover { background:#e5e7eb; }
.tpl-edit-panel { margin-top:16px; padding-top:16px; border-top:1px solid #f1f5f9; }
.tpl-edit-panel form { display:grid; grid-template-columns:2fr 1fr; gap:12px 16px; align-items:start; }
.tpl-edit-panel .full { grid-column:1 / -1; }
.tpl-edit-panel label { display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.tpl-edit-panel input[type=text], .tpl-edit-panel select, .tpl-edit-panel textarea { width:100%; border:1.5px solid #cbd5e1; border-radius:6px; padding:7px 9px; font-size:13px; }
.tpl-edit-panel textarea { resize:vertical; min-height:38px; }
.tpl-edit-panel .checkbox-row { display:flex; align-items:center; gap:8px; }
.tpl-edit-panel .checkbox-row label { margin:0; text-transform:none; letter-spacing:0; font-size:13px; color:#1a2332; }
.tpl-edit-panel button[type=submit] { background:#1BC5BD; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-weight:600; font-size:13px; cursor:pointer; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    @php $editHasErrors = $errors->hasAny(['name', 'frequency', 'description']); @endphp
    <div class="tpl-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2 style="font-size:20px; font-weight:800; color:#1a2332; margin:0;">
                    {{ $template->name }}
                    @unless($template->is_active)
                        <span style="background:#f1f5f9; color:#64748b; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; margin-left:8px;">Inactive</span>
                    @endunless
                </h2>
                <div style="font-size:13px; color:#8a92a6; margin-top:4px;">
                    <i class="fa fa-shield-alt"></i> Role: <strong>{{ $template->role->name ?? '—' }}</strong>
                    &nbsp;&middot;&nbsp;
                    <i class="fa fa-calendar"></i> Frequency: <strong>{{ ucfirst($template->frequency) }}</strong>
                    &nbsp;&middot;&nbsp;
                    <i class="fa fa-code"></i> Code: <code>{{ $template->code }}</code>
                </div>
                @if($template->description)
                    <p style="font-size:13px; color:#64748b; margin:8px 0 0;">{{ $template->description }}</p>
                @endif
            </div>
            <div style="display:flex; gap:10px;">
                <button type="button" class="tpl-edit-btn" onclick="toggleEditDetails()">
                    <i class="fa fa-pen"></i> Edit Details
                </button>
                <a href="{{ route('performance.templates') }}"
                   style="background:#f3f4f6; color:#475569; padding:7px 13px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;">
                    <i class="fa fa-arrow-left"></i> All Templates
                </a>
            </div>
        </div>

        <div class="tpl-edit-panel" id="editDetailsPanel" style="display:{{ $editHasErrors ? 'block' : 'none' }};">
            <form method="POST" action="{{ route('performance.templates.update', $template->id) }}">
                @csrf @method('PATCH')
                <div>
                    <label>Template Name</label>
                    <input type="text" name="name" value="{{ old('name', $template->name) }}" required>
                </div>
                <div>
                    <label>Frequency</label>
                    <select name="frequency" required>
                        @foreach(['monthly','quarterly','biannual','annual'] as $freq)
                            <option value="{{ $freq }}" {{ old('frequency', $template->frequency) === $freq ? 'selected' : '' }}>
                                {{ ucfirst($freq) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="full">
                    <label>Description <span style="font-weight:400; text-transform:none;">(optional)</span></label>
                    <textarea name="description" rows="2" placeholder="Internal note about this template…">{{ old('description', $template->description) }}</textarea>
                </div>
                <div class="full checkbox-row">
                    <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                    <label for="is_active">Active — employees in this role can start reviews from this template</label>
                </div>
                <div class="full">
                    <button type="submit"><i class="fa fa-save"></i> Save Details</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleEditDetails() {
            var p = document.getElementById('editDetailsPanel');
            p.style.display = p.style.display === 'none' ? 'block' : 'none';
        }
    </script>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin:0; padding-left:20px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    @foreach($template->sections as $section)
        @php $sectionWeight = $section->items->sum('weight'); @endphp
        <div class="tpl-section">
            <div class="tpl-section-head">
                <span class="title">Section {{ $section->code }} — {{ $section->title }}</span>
                <span class="meta">
                    {{ $section->items->count() }} items &middot;
                    Total: {{ rtrim(rtrim(number_format($sectionWeight, 2), '0'), '.') }}%
                    @if(abs($sectionWeight - $section->weight_total) > 0.01)
                        <span style="color:#fca5a5;">⚠ target {{ rtrim(rtrim(number_format($section->weight_total, 2), '0'), '.') }}%</span>
                    @endif
                </span>
            </div>

            @if($section->items->isEmpty())
                <div style="padding:24px; text-align:center; color:#94a3b8;">
                    <i class="fa fa-inbox fa-2x mb-2" style="display:block;"></i>
                    <p style="margin:0; font-size:13px;">No KPI items yet. Add one below.</p>
                </div>
            @else
                <form method="POST" action="{{ route('performance.templates.items.bulk', $template->id) }}">
                    @csrf @method('PATCH')
                    <table class="tpl-tbl">
                        <thead>
                            <tr>
                                <th style="width:160px;">KPA</th>
                                <th>Measure</th>
                                <th style="width:200px;">Target</th>
                                <th style="width:80px; text-align:center;">Weight</th>
                                <th style="width:70px; text-align:center;">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($section->items as $item)
                                <tr>
                                    <td><input type="text" name="items[{{ $item->id }}][kpa]" value="{{ $item->kpa }}" required></td>
                                    <td><textarea name="items[{{ $item->id }}][measure]" rows="2" required>{{ $item->measure }}</textarea></td>
                                    <td><textarea name="items[{{ $item->id }}][target]" rows="2">{{ $item->target }}</textarea></td>
                                    <td><input type="number" name="items[{{ $item->id }}][weight]" step="0.01" min="0" max="100" value="{{ $item->weight }}" required></td>
                                    <td style="text-align:center;">
                                        {{-- Targets the separate delete form below via form="", so it
                                             does NOT submit this bulk-save form. --}}
                                        <button type="submit" form="del-form-{{ $item->id }}" class="tpl-btn-del" title="Delete item"
                                                onclick="return confirm('Delete this KPI item? Historical reviews keep their snapshot.');">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="tpl-save-all">
                        <span class="hint">Edit any cells above, then save the whole section in one click.</span>
                        <button type="submit" class="tpl-btn-save-all"><i class="fa fa-save"></i> Save All Changes</button>
                    </div>
                </form>

                {{-- Per-row delete forms live OUTSIDE the bulk form — HTML forbids
                     nested forms, so the trash buttons reach them via form="". --}}
                @foreach($section->items as $item)
                    <form id="del-form-{{ $item->id }}" method="POST"
                          action="{{ route('performance.templates.items.destroy', [$template->id, $item->id]) }}" style="display:none;">
                        @csrf @method('DELETE')
                    </form>
                @endforeach
            @endif

            <div class="tpl-add-row">
                <form method="POST" action="{{ route('performance.templates.items.store', $template->id) }}">
                    @csrf
                    <input type="hidden" name="kpi_template_section_id" value="{{ $section->id }}">
                    <input type="text" name="kpa" placeholder="KPA (e.g. Administration)" required>
                    <textarea name="measure" rows="1" placeholder="Measure / KPI text" required></textarea>
                    <textarea name="target" rows="1" placeholder="Target (e.g. 90% on-time)"></textarea>
                    <input type="number" name="weight" placeholder="0.0" step="0.01" min="0" max="100" required>
                    <button type="submit"><i class="fa fa-plus"></i> Add</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
