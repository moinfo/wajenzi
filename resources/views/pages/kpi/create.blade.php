@extends('layouts.backend')

@section('content')
<div class="container-fluid" style="padding:24px 28px;">

    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('performance.index') }}"
           style="background:#f3f4f6; color:#475569; padding:7px 13px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px; margin-right:14px;">
            <i class="fa fa-arrow-left"></i>
        </a>
        <div>
            <h2 style="font-size:20px; font-weight:800; color:#1a2332; margin:0;">Start a New Performance Review</h2>
            <p style="margin:4px 0 0; font-size:13px; color:#8a92a6;">
                Self-assessment will be created from the matching template for your role.
            </p>
        </div>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <div style="background:#fff; border-radius:12px; padding:24px; max-width:640px; box-shadow:0 1px 4px rgba(0,0,0,.06); border:1px solid #eef0f3;">
        <form method="POST" action="{{ route('performance.store') }}">
            @csrf

            <div class="form-group">
                <label style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#8a92a6;">Template</label>
                @if($autoTemplate && $templates->count() === 1)
                    <div style="padding:10px 14px; background:#e0f7f6; border:1px solid #99e6e3; border-radius:8px; color:#0d9488; font-weight:600;">
                        <i class="fa fa-check-circle"></i> {{ $autoTemplate->name }}
                        <small style="font-weight:400; color:#0d9488; margin-left:8px;">(auto-detected from your role)</small>
                    </div>
                    <input type="hidden" name="kpi_template_id" value="{{ $autoTemplate->id }}">
                @else
                    <select name="kpi_template_id" class="form-control" required style="border-radius:8px;">
                        <option value="">— Select template —</option>
                        @foreach($templates as $t)
                            <option value="{{ $t->id }}" {{ $autoTemplate && $autoTemplate->id === $t->id ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div class="form-group mt-3">
                <label style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#8a92a6;">Period Label</label>
                <input type="text" name="period_label" class="form-control" required
                       value="{{ old('period_label', $defaultPeriod) }}"
                       placeholder="e.g. JUNE 2026" style="border-radius:8px;">
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <label style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#8a92a6;">Period Start</label>
                    <input type="date" name="period_start" class="form-control" required
                           value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}"
                           style="border-radius:8px;">
                </div>
                <div class="col-md-6">
                    <label style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#8a92a6;">Period End</label>
                    <input type="date" name="period_end" class="form-control" required
                           value="{{ old('period_end', now()->endOfMonth()->format('Y-m-d')) }}"
                           style="border-radius:8px;">
                </div>
            </div>

            @if(!auth()->user()->supervisor_id)
                <div class="alert alert-warning mt-4" style="border-radius:8px;">
                    <i class="fa fa-exclamation-triangle"></i>
                    You have no supervisor assigned. Please contact HR to set your supervisor before starting a review.
                </div>
            @endif

            <div class="mt-4 d-flex justify-content-end" style="gap:10px;">
                <a href="{{ route('performance.index') }}"
                   style="background:#f3f4f6; color:#475569; padding:9px 18px; border-radius:8px; font-weight:600; font-size:13px; text-decoration:none;">Cancel</a>
                <button type="submit"
                        style="background:#1BC5BD; color:#fff; padding:9px 22px; border-radius:8px; font-weight:700; font-size:13px; border:none;"
                        {{ !auth()->user()->supervisor_id ? 'disabled' : '' }}>
                    Create Review
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
