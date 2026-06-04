@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Site Paylog — Daily Report
            <div class="float-right">
                @if($siteId)
                <a href="{{ route('site_paylog.daily_report', array_merge(request()->only('site_id', 'date'), ['export' => 'csv'])) }}" class="btn btn-rounded btn-outline-success min-width-100 mb-10">
                    <i class="si si-cloud-download"></i> Download CSV
                </a>
                @endif
                <a href="{{ route('site_paylog', request()->only('site_id', 'date')) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="si si-arrow-left"></i> Back to Paylog
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-content">
                <form method="GET" action="{{ route('site_paylog.daily_report') }}" class="row mb-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text">Site</span>
                            <select name="site_id" class="form-control" onchange="this.form.submit()">
                                <option value="">— Select a site —</option>
                                @foreach($sites as $s)
                                    <option value="{{ $s->id }}" {{ (string)$siteId === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
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
                </form>
            </div>
        </div>

        @if($siteId)
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ $site->name ?? 'Site' }} — {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Payee</th>
                                <th>Reason</th>
                                <th>Bank/Mobile</th>
                                <th>Account Name</th>
                                <th class="text-right">Amount (TZS)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $p)
                                <tr>
                                    <td>{{ ucfirst($p->category) }}</td>
                                    <td>{{ $p->payee_name }}</td>
                                    <td>{{ $p->reason }}</td>
                                    <td>{{ $p->channel->name ?? '—' }}</td>
                                    <td>{{ $p->account_name ?? '—' }}</td>
                                    <td class="text-right">{{ number_format((float)$p->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No payments for this date.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr><th colspan="5" class="text-right">Material</th><th class="text-right">{{ number_format($totals['material']) }}</th></tr>
                            <tr><th colspan="5" class="text-right">Labour</th><th class="text-right">{{ number_format($totals['labour']) }}</th></tr>
                            <tr class="table-success"><th colspan="5" class="text-right">TOTAL</th><th class="text-right">{{ number_format($totals['all']) }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @else
            <div class="block"><div class="block-content text-center text-muted py-5">Select a site to view its daily report.</div></div>
        @endif
    </div>
</div>
@endsection
