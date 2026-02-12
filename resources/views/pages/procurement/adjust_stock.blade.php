@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Adjust Stock — {{ $project->name }}
            <div class="float-right">
                <a href="{{ route('stock_register', $project->id) }}" class="btn btn-rounded btn-outline-secondary min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Stock Register
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">Stock Adjustment</h3>
            </div>
            <div class="block-content">
                {{-- Current item info --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th style="width: 40%;">Item Code</th>
                                <td>{{ $inventory->boqItem?->item_code ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td>{{ $inventory->boqItem?->description ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Unit</th>
                                <td>{{ $inventory->boqItem?->unit ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Qty Received (Total)</th>
                                <td>{{ number_format($inventory->quantity, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Qty Used</th>
                                <td>{{ number_format($inventory->quantity_used, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Qty Available</th>
                                <td>
                                    <strong>{{ number_format($inventory->quantity_available, 2) }}</strong>
                                    <span class="badge badge-{{ $inventory->stock_status_badge_class }} ml-1">
                                        {{ $inventory->stock_status_label }}
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <form method="post" action="{{ route('stock_register.adjust.store', [$project->id, $inventory->id]) }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="new_quantity"><strong>New Total Quantity (Received)</strong></label>
                                <input type="number" name="new_quantity" id="new_quantity" class="form-control"
                                    step="0.01" min="0" value="{{ old('new_quantity', $inventory->quantity) }}" required>
                                <small class="form-text text-muted">
                                    Current: {{ number_format($inventory->quantity, 2) }}. Change this to adjust the total received quantity.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="reason"><strong>Reason for Adjustment</strong> <span class="text-danger">*</span></label>
                                <textarea name="reason" id="reason" class="form-control" rows="3"
                                    required placeholder="e.g. Damaged materials found during site inspection, physical count correction...">{{ old('reason') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button type="submit" class="btn btn-warning">
                            <i class="fa fa-sliders-h"></i> Submit Adjustment
                        </button>
                        <a href="{{ route('stock_register', $project->id) }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
