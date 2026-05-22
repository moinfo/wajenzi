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
.tpl-add-row { background:#f8fafc; padding:12px 18px; }
.tpl-add-row form { display:grid; grid-template-columns:1.2fr 2fr 1.5fr 80px 80px; gap:8px; align-items:start; }
.tpl-add-row input, .tpl-add-row textarea { border:1.5px solid #cbd5e1; border-radius:6px; padding:6px 9px; font-size:12.5px; width:100%; }
.tpl-add-row button { background:#1BC5BD; color:#fff; border:none; padding:6px 12px; border-radius:6px; font-weight:600; font-size:12px; cursor:pointer; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    <div class="tpl-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h2 style="font-size:20px; font-weight:800; color:#1a2332; margin:0;">
                    {{ $template->name }}
                </h2>
                <div style="font-size:13px; color:#8a92a6; margin-top:4px;">
                    <i class="fa fa-shield-alt"></i> Role: <strong>{{ $template->role->name ?? '—' }}</strong>
                    &nbsp;&middot;&nbsp;
                    <i class="fa fa-calendar"></i> Frequency: <strong>{{ ucfirst($template->frequency) }}</strong>
                    &nbsp;&middot;&nbsp;
                    <i class="fa fa-code"></i> Code: <code>{{ $template->code }}</code>
                </div>
            </div>
            <a href="{{ route('performance.templates') }}"
               style="background:#f3f4f6; color:#475569; padding:7px 13px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px;">
                <i class="fa fa-arrow-left"></i> All Templates
            </a>
        </div>
    </div>

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
                <table class="tpl-tbl">
                    <thead>
                        <tr>
                            <th style="width:160px;">KPA</th>
                            <th>Measure</th>
                            <th style="width:200px;">Target</th>
                            <th style="width:80px; text-align:center;">Weight</th>
                            <th style="width:110px; text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section->items as $item)
                            <tr>
                                <form method="POST" action="{{ route('performance.templates.items.update', [$template->id, $item->id]) }}" id="form-item-{{ $item->id }}">
                                    @csrf @method('PATCH')
                                    <td><input type="text" name="kpa" value="{{ $item->kpa }}" form="form-item-{{ $item->id }}"></td>
                                    <td><textarea name="measure" rows="2" form="form-item-{{ $item->id }}">{{ $item->measure }}</textarea></td>
                                    <td><textarea name="target" rows="2" form="form-item-{{ $item->id }}">{{ $item->target }}</textarea></td>
                                    <td><input type="number" name="weight" step="0.01" min="0" max="100" value="{{ $item->weight }}" form="form-item-{{ $item->id }}"></td>
                                </form>
                                <td style="text-align:center; white-space:nowrap;">
                                    <button type="submit" class="tpl-btn-save" form="form-item-{{ $item->id }}" title="Save changes">
                                        <i class="fa fa-save"></i>
                                    </button>
                                    <form method="POST" action="{{ route('performance.templates.items.destroy', [$template->id, $item->id]) }}" style="display:inline;"
                                          onsubmit="return confirm('Delete this KPI item? Historical reviews keep their snapshot.');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="tpl-btn-del" title="Delete item">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
