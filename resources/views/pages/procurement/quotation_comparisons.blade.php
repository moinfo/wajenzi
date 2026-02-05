@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Quotation Comparisons
            <div class="float-right">
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">All Comparisons</h3>
            </div>
            <div class="block-content">
                <form method="post" id="filter-form" autocomplete="off">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">Start Date</span>
                                <input type="text" name="start_date" class="form-control datepicker"
                                    value="{{ $start_date ?? date('Y-m-01') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">End Date</span>
                                <input type="text" name="end_date" class="form-control datepicker"
                                    value="{{ $end_date ?? date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Comparison #</th>
                                <th>Material Request</th>
                                <th>Selected Supplier</th>
                                <th>Selected Amount</th>
                                <th>Quotations</th>
                                <th>Prepared By</th>
                                <th class="text-center">Approvals</th>
                                <th class="text-center">Status</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($comparisons as $comparison)
                                <tr id="comparison-tr-{{ $comparison->id }}">
                                    <td class="text-center">{{ $loop->index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('quotation_comparison', ['id' => $comparison->id, 'document_type_id' => 0]) }}">
                                            <strong>{{ $comparison->comparison_number }}</strong>
                                        </a>
                                    </td>
                                    <td>
                                        {{ $comparison->materialRequest?->request_number ?? 'N/A' }}
                                        <br>
                                        <small class="text-muted">{{ $comparison->materialRequest?->project?->name ?? '' }}</small>
                                    </td>
                                    <td>{{ $comparison->selectedQuotation?->supplier?->name ?? 'Not Selected' }}</td>
                                    <td class="text-right">
                                        <strong>{{ number_format($comparison->selectedQuotation?->grand_total ?? 0, 2) }}</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info">{{ $comparison->quotation_count }} quotes</span>
                                    </td>
                                    <td>{{ $comparison->preparedBy?->name ?? 'N/A' }}</td>
                                    <td class="text-center">
                                        <x-ringlesoft-approval-status-summary :model="$comparison" />
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $comparison->status_badge_class }}">
                                            {{ ucfirst($comparison->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="{{ route('quotation_comparison', ['id' => $comparison->id, 'document_type_id' => 0]) }}"
                                                class="btn btn-sm btn-success" title="View">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @if($comparison->isApproved() && !$comparison->purchases()->exists())
                                                <a href="{{ route('quotation_comparison.create_purchase', $comparison->id) }}"
                                                    class="btn btn-sm btn-primary" title="Create Purchase Order"
                                                    onclick="return confirm('Create purchase order from this comparison?')">
                                                    <i class="fa fa-shopping-cart"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });
    });
</script>
@endsection
