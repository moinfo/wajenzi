@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Site Paylog — Payment Requests
            <div class="float-right">
                <a href="{{ route('site_paylog') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="si si-arrow-left"></i> Back to Daily Payments
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-content">
                <form method="GET" action="{{ route('site_paylog.requests') }}" class="row mb-2">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text">Site</span>
                            <select name="site_id" class="form-control" onchange="this.form.submit()">
                                <option value="">— All sites —</option>
                                @foreach($sites as $s)
                                    <option value="{{ $s->id }}" {{ (string)$siteId === (string)$s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">Status</span>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">— Any —</option>
                                @foreach(['PENDING' => 'Pending', 'APPROVED' => 'Approved', 'PAID' => 'Paid', 'REJECTED' => 'Rejected'] as $val => $label)
                                    <option value="{{ $val }}" {{ $status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="block">
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th>Request No</th>
                                <th>Site</th>
                                <th>Date</th>
                                <th class="text-right">Total (TZS)</th>
                                <th>Status</th>
                                <th>Initiated By</th>
                                <th style="width:60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr>
                                    <td><a href="{{ route('site_paylog.requests.show', $req->id) }}"><strong>{{ $req->request_number }}</strong></a></td>
                                    <td>{{ $req->site->name ?? '—' }}</td>
                                    <td>{{ optional($req->payment_date)->format('d M Y') }}</td>
                                    <td class="text-right">{{ number_format((float)$req->total_amount) }}</td>
                                    <td><span class="badge badge-{{ $req->statusBadgeClass() }}">{{ $req->displayStatus() }}</span></td>
                                    <td>{{ $req->creator->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('site_paylog.requests.show', $req->id) }}" class="btn btn-sm btn-outline-primary"><i class="fa fa-eye"></i></a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">No payment requests found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
