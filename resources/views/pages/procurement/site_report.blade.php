@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">
            Site Cost Report
            <div class="float-right">
                <a href="{{ route('purchase_orders.site_report.export', request()->only('project_id')) }}"
                   class="btn btn-rounded btn-outline-success min-width-100 mb-10">
                    <i class="si si-cloud-download"></i> Download Excel
                </a>
                <a href="{{ route('purchase_orders') }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="si si-arrow-left"></i> Purchase Orders
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Material, Labour, Overhead &amp; Drawing — per site</h3>
            </div>
            <div class="block-content">
                <p class="text-muted" style="font-size:13px;">
                    <i class="fa fa-info-circle"></i>
                    <strong>Material</strong> = approved Purchase Orders &middot;
                    <strong>Labour</strong> = active/on-hold/completed Labour Charge contracts &middot;
                    <strong>Overhead</strong> &amp; <strong>Drawing Charges</strong> = recorded project expenses.
                    All amounts in TZS.
                </p>

                <form method="GET" action="{{ route('purchase_orders.site_report') }}" class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">Site</span>
                            <select name="project_id" class="form-control" onchange="this.form.submit()">
                                <option value="">— All sites —</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p->id }}" {{ (string) $projectId === (string) $p->id ? 'selected' : '' }}>
                                        {{ $p->project_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($projectId)
                        <div class="col-md-2">
                            <a href="{{ route('purchase_orders.site_report') }}" class="btn btn-alt-secondary">Clear</a>
                        </div>
                    @endif
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width:50px;" class="text-center">#</th>
                                <th>Site</th>
                                <th>Document No</th>
                                <th class="text-right">Material Cost</th>
                                <th class="text-right">Labour Cost</th>
                                <th class="text-right">Overhead</th>
                                <th class="text-right">Drawing Charges</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $i => $r)
                                <tr>
                                    <td class="text-center">{{ $i + 1 }}</td>
                                    <td>{{ $r['project_name'] }}</td>
                                    <td>{{ $r['document_no'] ?: '-' }}</td>
                                    <td class="text-right">{{ number_format($r['material'], 2) }}</td>
                                    <td class="text-right">{{ number_format($r['labour'], 2) }}</td>
                                    <td class="text-right">{{ number_format($r['overhead'], 2) }}</td>
                                    <td class="text-right">{{ number_format($r['drawing'], 2) }}</td>
                                    <td class="text-right font-w700">{{ number_format($r['total'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                                        No site costs found for the current selection.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($rows->isNotEmpty())
                            <tfoot>
                                <tr class="font-w700" style="background:#f8fafc;">
                                    <td colspan="3" class="text-right">GRAND TOTAL</td>
                                    <td class="text-right">{{ number_format($totals['material'], 2) }}</td>
                                    <td class="text-right">{{ number_format($totals['labour'], 2) }}</td>
                                    <td class="text-right">{{ number_format($totals['overhead'], 2) }}</td>
                                    <td class="text-right">{{ number_format($totals['drawing'], 2) }}</td>
                                    <td class="text-right">{{ number_format($totals['total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
