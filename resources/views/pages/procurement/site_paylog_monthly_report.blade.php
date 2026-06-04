@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Site Paylog — Monthly Report
            <div class="float-right">
                <a href="{{ route('site_paylog', request()->only('site_id')) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="si si-arrow-left"></i> Back to Paylog
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-content">
                <form method="GET" action="{{ route('site_paylog.monthly_report') }}" class="row mb-2">
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
                            <span class="input-group-text">Month</span>
                            <input type="month" name="month" value="{{ $month }}" class="form-control" onchange="this.form.submit()">
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if($siteId)
        {{-- Summary built by SitePaylogController::monthlyReport() — the CONTRIBUTION POINT --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">{{ $site->name ?? 'Site' }} — {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }} summary</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-vcenter">
                        <thead>
                            <tr>
                                @foreach(($summary->first() ?? []) as $key => $val)
                                    <th class="{{ is_numeric($val) ? 'text-right' : '' }}">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary as $row)
                                <tr>
                                    @foreach($row as $val)
                                        <td class="{{ is_numeric($val) ? 'text-right' : '' }}">{{ is_numeric($val) ? number_format((float)$val) : $val }}</td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td class="text-center text-muted py-4">No payments this month.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-success">
                                <th>Grand Total</th>
                                <th colspan="{{ max(0, ($summary->first() ? count($summary->first()) : 1) - 2) }}"></th>
                                <th class="text-right">{{ number_format($grandTotal) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Full month ledger --}}
        <div class="block">
            <div class="block-header block-header-default"><h3 class="block-title">All payments</h3></div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>Date</th><th>Category</th><th>Payee</th><th>Reason</th>
                                <th>Bank/Mobile</th><th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $p)
                                <tr>
                                    <td>{{ $p->payment_date->format('d M') }}</td>
                                    <td>{{ ucfirst($p->category) }}</td>
                                    <td>{{ $p->payee_name }}</td>
                                    <td>{{ $p->reason }}</td>
                                    <td>{{ $p->channel->name ?? '—' }}</td>
                                    <td class="text-right">{{ number_format((float)$p->amount) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">No payments this month.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
            <div class="block"><div class="block-content text-center text-muted py-5">Select a site to view its monthly report.</div></div>
        @endif
    </div>
</div>
@endsection
