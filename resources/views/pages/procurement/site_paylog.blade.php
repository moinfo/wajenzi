@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Site Paylog — Daily Payments
            <div class="float-right">
                <a href="{{ route('site_paylog.daily_report', request()->only('site_id', 'date')) }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-doc"></i> Daily Report
                </a>
                <a href="{{ route('site_paylog.monthly_report', request()->only('site_id')) }}" class="btn btn-rounded btn-outline-primary min-width-100 mb-10">
                    <i class="si si-calendar"></i> Monthly Report
                </a>
                <a href="{{ route('site_paylog.channels') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="si si-wrench"></i> Channels
                </a>
            </div>
        </div>

        {{-- Step 1 + 2: Select Site / View site --}}
        <div class="block">
            <div class="block-content">
                <form method="GET" action="{{ route('site_paylog') }}" class="row mb-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text">Site</span>
                            <select name="site_id" class="form-control" onchange="this.form.submit()">
                                <option value="">— Select a site —</option>
                                @foreach($sites as $s)
                                    <option value="{{ $s->id }}" {{ (string)$siteId === (string)$s->id ? 'selected' : '' }}>
                                        {{ $s->name }}@if($s->location) — {{ $s->location }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">Date</span>
                            <input type="date" name="date" value="{{ $date }}" class="form-control" onchange="this.form.submit()">
                        </div>
                    </div>
                    @if($siteId)
                    <div class="col-md-4 text-right">
                        <button type="button"
                            onclick="loadFormModal('site_paylog_form', {className: 'SitePaylog', site_id: '{{ $siteId }}', date: '{{ $date }}'}, 'Record Site Payments', 'modal-xl');"
                            class="btn btn-rounded btn-success min-width-125">
                            <i class="si si-plus"></i> Add Payments
                        </button>
                    </div>
                    @endif
                </form>

                @if($site)
                    <p class="text-muted mb-0" style="font-size:13px;">
                        <i class="fa fa-map-marker-alt"></i> <strong>{{ $site->name }}</strong>
                        @if($site->location) &middot; {{ $site->location }} @endif
                        @if($site->currentSupervisor) &middot; Supervisor: {{ $site->currentSupervisor->name ?? '—' }} @endif
                        &middot; {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                    </p>
                @endif
            </div>
        </div>

        @if($siteId)
        {{-- Step 5: Pull daily payments --}}
        <div class="row">
            <div class="col-md-4">
                <div class="block block-rounded text-center">
                    <div class="block-content">
                        <div class="font-size-sm text-muted text-uppercase">Material</div>
                        <div class="font-size-h3 font-w700">{{ number_format($totals['material']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="block block-rounded text-center">
                    <div class="block-content">
                        <div class="font-size-sm text-muted text-uppercase">Labour</div>
                        <div class="font-size-h3 font-w700">{{ number_format($totals['labour']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="block block-rounded text-center">
                    <div class="block-content">
                        <div class="font-size-sm text-muted text-uppercase">Total (TZS)</div>
                        <div class="font-size-h3 font-w700 text-success">{{ number_format($totals['all']) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Payments on {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Payee</th>
                                <th>Reason</th>
                                <th>Category</th>
                                <th>Bank/Mobile</th>
                                <th>Account Name</th>
                                <th class="text-right">Amount</th>
                                <th>Logged By</th>
                                <th style="width:50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $i => $p)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $p->payee_name }}</td>
                                    <td>{{ $p->reason }}</td>
                                    <td>
                                        <span class="badge badge-{{ $p->category === 'labour' ? 'info' : 'secondary' }}">{{ ucfirst($p->category) }}</span>
                                    </td>
                                    <td>{{ $p->channel->name ?? '—' }}</td>
                                    <td>{{ $p->account_name ?? '—' }}</td>
                                    <td class="text-right">{{ number_format((float)$p->amount) }}</td>
                                    <td>{{ $p->creator->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <form method="POST" action="{{ route('site_paylog') }}" onsubmit="return confirm('Delete this payment?');" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="id" value="{{ $p->id }}">
                                            <button type="submit" name="deleteItem" value="1" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No payments logged for this site on this date.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
            <div class="block"><div class="block-content text-center text-muted py-5">
                <i class="si si-pointer fa-2x mb-2"></i><br>Select a site to begin logging daily payments.
            </div></div>
        @endif
    </div>
</div>
@endsection
