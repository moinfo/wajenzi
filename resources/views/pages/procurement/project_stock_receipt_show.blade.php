@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading d-flex align-items-center justify-content-between">
            <span><i class="fa fa-inbox mr-2"></i>Stock Receipt — {{ $receipt->receipt_number }}</span>
            <div>
                <a href="{{ route('project_stock_receipts.index') }}" class="btn btn-sm btn-secondary mr-2">
                    <i class="fa fa-arrow-left mr-1"></i> Back
                </a>
                <form method="post" action="{{ route('project_stock_receipts.delete', $receipt->id) }}"
                      class="d-inline" onsubmit="return confirm('Delete this receipt? Quantities will be reversed.')">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="fa fa-trash mr-1"></i> Delete &amp; Reverse
                    </button>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="block">
                    <div class="block-header block-header-default">
                        <h3 class="block-title">Receipt Details</h3>
                    </div>
                    <div class="block-content">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <th style="width:40%;">Receipt Number</th>
                                <td><code>{{ $receipt->receipt_number }}</code></td>
                            </tr>
                            <tr>
                                <th>Project / Site</th>
                                <td>{{ $receipt->project->project_name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Receipt Date</th>
                                <td>{{ $receipt->receipt_date?->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <th>Supplier / Source</th>
                                <td>{{ $receipt->supplier ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th>Received By</th>
                                <td>{{ $receipt->createdBy->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Notes</th>
                                <td>{{ $receipt->notes ?: '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Items Received ({{ $receipt->items->count() }})</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Stock Item Code</th>
                                <th>Description</th>
                                <th>Unit</th>
                                <th class="text-right">Qty Received</th>
                                <th class="text-right">Current On-Hand</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receipt->items->sortBy('sort_order') as $i => $item)
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        @if($item->stockItem)
                                            <a href="{{ route('project_stock.index', ['project_id' => $receipt->project_id]) }}">
                                                <code>{{ $item->stockItem->item_code }}</code>
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->description }}</td>
                                    <td>{{ $item->unit }}</td>
                                    <td class="text-right text-success font-w600">
                                        +{{ number_format($item->quantity, 2) }}
                                    </td>
                                    <td class="text-right">
                                        @if($item->stockItem)
                                            {{ number_format($item->stockItem->quantity_on_hand, 2) }}
                                        @else
                                            —
                                        @endif
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
