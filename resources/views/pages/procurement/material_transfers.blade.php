@extends('layouts.backend')

@section('content')
    <div class="container-fluid">
        <div class="content">
            <div class="content-heading">Material Transfers
                <div class="float-right">
                    @can('Add Material Transfer')
                        <a href="{{ route('material_transfer.create') }}" class="btn btn-rounded min-width-125 mb-10 action-btn add-btn">
                            <i class="si si-plus">&nbsp;</i>New Transfer
                        </a>
                    @endcan
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">All Material Transfers</h3>
                </div>
                <div class="block-content">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-vcenter js-dataTable-full">
                            <thead>
                            <tr>
                                <th class="text-center" style="width: 60px;">#</th>
                                <th>Transfer No.</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Items</th>
                                <th>Transfer Date</th>
                                <th class="text-right">Loading</th>
                                <th class="text-right">Offload</th>
                                <th class="text-right">Transport</th>
                                <th class="text-right">Total</th>
                                <th>Status</th>
                                <th>Requester</th>
                                <th class="text-center" style="width: 110px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($transfers as $t)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration }}</td>
                                    <td>{{ $t->transfer_number }}</td>
                                    <td>{{ $t->fromProject->project_name ?? '—' }}</td>
                                    <td>{{ $t->toProject->project_name ?? '—' }}</td>
                                    <td class="text-center">{{ $t->items->count() }}</td>
                                    <td>{{ optional($t->transfer_date)->format('d M Y') }}</td>
                                    <td class="text-right">{{ number_format($t->loading_cost, 2) }}</td>
                                    <td class="text-right">{{ number_format($t->offloading_cost, 2) }}</td>
                                    <td class="text-right">{{ number_format($t->transportation_cost, 2) }}</td>
                                    <td class="text-right"><strong>{{ number_format($t->total_cost, 2) }}</strong></td>
                                    <td>
                                        @php $up = strtoupper($t->status); @endphp
                                        @if($up === 'APPROVED' || $up === 'COMPLETED')
                                            <span class="badge badge-success">{{ $up }}</span>
                                        @elseif($up === 'REJECTED')
                                            <span class="badge badge-danger">{{ $up }}</span>
                                        @else
                                            <span class="badge badge-warning">{{ strtoupper($t->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $t->requester->name ?? '—' }}</td>
                                    <td class="text-center">
                                        <a class="btn btn-sm btn-success" href="{{ route('material_transfer', ['id' => $t->id, 'document_type_id' => 0]) }}" title="View">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @can('Delete Material Transfer')
                                            @if(strtoupper($t->status) !== 'APPROVED' && strtoupper($t->status) !== 'COMPLETED')
                                                <form method="post" action="{{ route('material_transfer.delete', $t->id) }}" style="display:inline;"
                                                      onsubmit="return confirm('Delete this transfer?');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="fa fa-times"></i></button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                            @if($transfers->isEmpty())
                                <tr><td colspan="13" class="text-center text-muted py-4">No transfers yet.</td></tr>
                            @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
