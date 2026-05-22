@extends('layouts.backend')

@section('content')
<style>
.sup-stat-card { background:#fff; border-radius:10px; padding:14px 18px; box-shadow:0 1px 4px rgba(0,0,0,.06); border:1px solid #eef0f3; display:flex; align-items:center; gap:14px; }
.sup-stat-icon { width:42px; height:42px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
.sup-stat-label { font-size:11px; color:#8a92a6; font-weight:700; text-transform:uppercase; letter-spacing:.4px; }
.sup-stat-value { font-size:20px; font-weight:800; color:#1a2332; margin-top:2px; line-height:1.1; }
.sup-table { width:100%; border-collapse:collapse; }
.sup-table thead th { background:#1a2332; color:#fff; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; padding:11px 14px; }
.sup-table tbody td { padding:9px 14px; border-bottom:1px solid #f3f4f6; font-size:13px; vertical-align:middle; }
.sup-table tbody tr:hover { background:#fafbfc; }
.sup-table select { border:1.5px solid #e5e7eb; border-radius:7px; padding:5px 10px; font-size:12.5px; min-width:240px; background:#fff; }
.sup-table select.missing { border-color:#fca5a5; background:#fef2f2; }
.sup-missing-pill { display:inline-block; background:#fee2e2; color:#b91c1c; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700; margin-left:6px; }
</style>

<div class="container-fluid" style="padding:24px 28px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 style="font-size:22px; font-weight:800; color:#1a2332; margin:0;">
                <i class="fa fa-sitemap" style="color:#1BC5BD;"></i> Supervisor Assignments
            </h2>
            <p style="margin:4px 0 0; font-size:13px; color:#8a92a6;">
                Set each staff member's line manager. The supervisor is the first approver of KPI reviews.
            </p>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div class="row mb-4" style="row-gap:14px;">
        <div class="col-md-4">
            <div class="sup-stat-card">
                <div class="sup-stat-icon" style="background:#e8f8f7;"><i class="fa fa-users" style="color:#1BC5BD;"></i></div>
                <div>
                    <div class="sup-stat-label">Total Staff</div>
                    <div class="sup-stat-value">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sup-stat-card">
                <div class="sup-stat-icon" style="background:#dcfce7;"><i class="fa fa-check-circle" style="color:#16a34a;"></i></div>
                <div>
                    <div class="sup-stat-label">With Supervisor</div>
                    <div class="sup-stat-value" style="color:#16a34a;">{{ $stats['assigned'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sup-stat-card">
                <div class="sup-stat-icon" style="background:#fee2e2;"><i class="fa fa-exclamation-triangle" style="color:#b91c1c;"></i></div>
                <div>
                    <div class="sup-stat-label">Missing Supervisor</div>
                    <div class="sup-stat-value" style="color:#b91c1c;">{{ $stats['missing'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('supervisor_assignments.update') }}">
        @csrf
        @method('PATCH')

        <div style="background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.06); border:1px solid #eef0f3; overflow:hidden;">
            <table class="sup-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Staff</th>
                        <th>Email</th>
                        <th style="width:280px;">Supervisor (line manager)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $i => $user)
                        <tr>
                            <td style="color:#94a3b8;">{{ $i + 1 }}</td>
                            <td>
                                <strong>{{ $user->name }}</strong>
                                @if(!$user->supervisor_id)
                                    <span class="sup-missing-pill">No supervisor</span>
                                @endif
                            </td>
                            <td style="color:#64748b; font-size:12px;">{{ $user->email }}</td>
                            <td>
                                <select name="assignments[{{ $user->id }}][supervisor_id]"
                                        class="{{ !$user->supervisor_id ? 'missing' : '' }}">
                                    <option value="">— None —</option>
                                    @foreach($candidates as $c)
                                        @if($c->id !== $user->id)
                                            <option value="{{ $c->id }}"
                                                    @if($user->supervisor_id == $c->id) selected @endif>
                                                {{ $c->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
            <a href="{{ route('hr_settings') }}"
               style="background:#f3f4f6; color:#475569; padding:9px 18px; border-radius:8px; font-weight:600; font-size:13px; text-decoration:none;">Back to Settings</a>
            <button type="submit"
                    style="background:#1BC5BD; color:#fff; padding:9px 24px; border-radius:8px; font-weight:700; font-size:13px; border:none;">
                <i class="fa fa-save"></i> Save All Assignments
            </button>
        </div>
    </form>
</div>
@endsection
