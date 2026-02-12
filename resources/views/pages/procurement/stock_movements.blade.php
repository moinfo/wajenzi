@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Material Movements — {{ $project->name }}
            <div class="float-right">
                <a href="{{ route('stock_register', $project->id) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Stock Register
                </a>
                <a href="{{ route('procurement_dashboard') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="si si-graph"></i> Dashboard
                </a>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Movements History</h3>
            </div>
            <div class="block-content">
                {{-- Filters --}}
                <form method="get" action="{{ route('stock_register.movements', $project->id) }}" autocomplete="off">
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">Start Date</span>
                                <input type="text" name="start_date" class="form-control datepicker"
                                    value="{{ $startDate }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text">End Date</span>
                                <input type="text" name="end_date" class="form-control datepicker"
                                    value="{{ $endDate }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="movement_type" class="form-control">
                                <option value="">All Types</option>
                                @foreach(['received', 'issued', 'adjustment', 'returned', 'transfer'] as $type)
                                    <option value="{{ $type }}" {{ $movementType === $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">#</th>
                                <th>Movement #</th>
                                <th>Date</th>
                                <th>BOQ Item</th>
                                <th class="text-center">Type</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Balance After</th>
                                <th>Location</th>
                                <th>Notes</th>
                                <th>Performed By</th>
                                <th class="text-center">Verified</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $mv)
                                <tr>
                                    <td class="text-center">{{ $loop->iteration + ($movements->currentPage() - 1) * $movements->perPage() }}</td>
                                    <td><strong>{{ $mv->movement_number }}</strong></td>
                                    <td>{{ $mv->movement_date?->format('Y-m-d') }}</td>
                                    <td>{{ Str::limit($mv->boqItem?->description ?? '-', 30) }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-{{ $mv->movement_type_badge_class }}">
                                            {{ $mv->movement_type_label }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        @if(in_array($mv->movement_type, ['issued', 'transfer']))
                                            <span class="text-danger">-{{ number_format($mv->quantity, 2) }}</span>
                                        @else
                                            <span class="text-success">+{{ number_format($mv->quantity, 2) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ $mv->balance_after !== null ? number_format($mv->balance_after, 2) : '-' }}</td>
                                    <td>{{ $mv->location ?? '-' }}</td>
                                    <td>{{ Str::limit($mv->notes ?? '-', 40) }}</td>
                                    <td>{{ $mv->performedBy?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        @if($mv->isVerified())
                                            <span class="badge badge-success" title="Verified by {{ $mv->verifiedBy?->name }} on {{ $mv->verified_at?->format('Y-m-d H:i') }}">
                                                Verified
                                            </span>
                                        @else
                                            <form method="post" action="{{ route('stock_register.movement.verify', [$project->id, $mv->id]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Verify this movement"
                                                    onclick="return confirm('Verify movement {{ $mv->movement_number }}?')">
                                                    <i class="fa fa-check"></i> Verify
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted">No movements found for the selected period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $movements->links() }}
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
