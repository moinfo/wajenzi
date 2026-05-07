@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">
                Material Transfer: {{ $transfer->transfer_number }}
                <div class="float-right">
                    <a href="{{ route('material_transfers') }}" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
                </div>
            </div>

            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

            <div class="block">
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>From → To</h5>
                            <table class="table table-sm">
                                <tr><th>From Project</th><td>{{ $transfer->fromProject->project_name ?? '—' }}</td></tr>
                                <tr><th>To Project</th><td>{{ $transfer->toProject->project_name ?? '—' }}</td></tr>
                                <tr><th>Transfer Date</th><td>{{ optional($transfer->transfer_date)->format('d M Y') }}</td></tr>
                                <tr><th>Expected Arrival</th><td>{{ optional($transfer->expected_arrival_date)->format('d M Y') ?? '—' }}</td></tr>
                                <tr><th>Vehicle</th><td>{{ $transfer->vehicle_info ?: '—' }}</td></tr>
                                <tr><th>Requester</th><td>{{ $transfer->requester->name ?? '—' }}</td></tr>
                                <tr><th>Status</th><td><span class="badge badge-{{ $transfer->isApproved() ? 'success' : 'warning' }}">{{ strtoupper($transfer->status) }}</span></td></tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h5>Costs</h5>
                            <table class="table table-sm">
                                <tr><th>Loading</th><td class="text-right">{{ number_format($transfer->loading_cost, 2) }}</td></tr>
                                <tr><th>Offloading</th><td class="text-right">{{ number_format($transfer->offloading_cost, 2) }}</td></tr>
                                <tr><th>Transportation</th><td class="text-right">{{ number_format($transfer->transportation_cost, 2) }}</td></tr>
                                <tr style="font-size:1.1em;"><th>Total Cost</th><td class="text-right"><strong>{{ number_format($transfer->total_cost, 2) }}</strong></td></tr>
                                <tr><th>Expense Sub-Category</th><td>{{ $transfer->expensesSubCategory->name ?? '—' }}</td></tr>
                                @if($transfer->expense_id)
                                    <tr><th>Posted Expense</th><td><code>{{ $transfer->expense_id }}</code> on destination project</td></tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($transfer->notes)
                        <div class="alert alert-info"><strong>Notes:</strong> {{ $transfer->notes }}</div>
                    @endif

                    <h5 class="mt-4">Materials</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Source BOQ</th>
                                <th>Description</th>
                                <th class="text-right">Qty</th>
                                <th>Unit</th>
                                <th>Specification</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transfer->items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->sourceBoqItem->item_code ?? '—' }}</td>
                                    <td>{{ $item->description }}</td>
                                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td>{{ $item->specification ?: '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <hr>
                    <h5>Approval</h5>
                    <x-ringlesoft-approval-actions :model="$transfer" />
                </div>
            </div>
        </div>
    </div>
@endsection
