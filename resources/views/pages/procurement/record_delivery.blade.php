@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Record Delivery
            <div class="float-right">
                <a href="{{ route('purchase_orders') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back to POs
                </a>
            </div>
        </div>

        {{-- PO Header Info --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    Purchase Order: <strong>{{ $purchase->document_number ?? 'PO-' . $purchase->id }}</strong>
                </h3>
            </div>
            <div class="block-content">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Supplier:</strong> {{ $purchase->supplier?->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-4">
                        <strong>Project:</strong> {{ $purchase->project?->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-4">
                        <strong>PO Date:</strong> {{ $purchase->date }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Delivery Form --}}
        <form method="POST" action="{{ route('purchase_order.store_delivery', $purchase->id) }}" enctype="multipart/form-data">
            @csrf

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Delivery Items</h3>
                </div>
                <div class="block-content">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-vcenter">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Description</th>
                                    <th>BOQ Item</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-right">Qty Ordered</th>
                                    <th class="text-right">Already Received</th>
                                    <th class="text-right">Pending</th>
                                    <th class="text-right" style="width: 150px;">Qty Delivering</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchase->purchaseItems as $pItem)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $pItem->description }}</td>
                                        <td>{{ $pItem->boqItem?->item_code ?? '-' }}</td>
                                        <td class="text-center">{{ $pItem->unit }}</td>
                                        <td class="text-right">{{ number_format($pItem->quantity, 2) }}</td>
                                        <td class="text-right">{{ number_format($pItem->quantity_received, 2) }}</td>
                                        <td class="text-right">
                                            @if($pItem->isFullyReceived())
                                                <span class="badge badge-success">Fully Received</span>
                                            @else
                                                {{ number_format($pItem->quantity_pending, 2) }}
                                            @endif
                                        </td>
                                        <td>
                                            <input type="number"
                                                name="items[{{ $pItem->id }}][quantity]"
                                                class="form-control text-right qty-input"
                                                step="0.01" min="0"
                                                max="{{ $pItem->quantity_pending }}"
                                                value="{{ old('items.' . $pItem->id . '.quantity', $pItem->isFullyReceived() ? 0 : $pItem->quantity_pending) }}"
                                                {{ $pItem->isFullyReceived() ? 'readonly' : '' }}>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="block">
                <div class="block-header block-header-default">
                    <h3 class="block-title">Delivery Details</h3>
                </div>
                <div class="block-content">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="delivery_note_number">Delivery Note Number <span class="text-danger">*</span></label>
                                <input type="text" name="delivery_note_number" id="delivery_note_number"
                                    class="form-control @error('delivery_note_number') is-invalid @enderror"
                                    value="{{ old('delivery_note_number') }}" required>
                                @error('delivery_note_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="date">Delivery Date <span class="text-danger">*</span></label>
                                <input type="text" name="date" id="date"
                                    class="form-control datepicker @error('date') is-invalid @enderror"
                                    value="{{ old('date', date('Y-m-d')) }}" required>
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="condition">Condition <span class="text-danger">*</span></label>
                                <select name="condition" id="condition"
                                    class="form-control @error('condition') is-invalid @enderror" required>
                                    <option value="good" {{ old('condition') == 'good' ? 'selected' : '' }}>Good</option>
                                    <option value="partial_damage" {{ old('condition') == 'partial_damage' ? 'selected' : '' }}>Partial Damage</option>
                                    <option value="damaged" {{ old('condition') == 'damaged' ? 'selected' : '' }}>Damaged</option>
                                </select>
                                @error('condition')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="description">Notes / Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="file">Delivery Note Scan</label>
                                <input type="file" name="file" id="file" class="form-control">
                                <small class="text-muted">Upload a scan/photo of the delivery note (optional)</small>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3 mb-3">
                        <div class="col-md-12 text-right">
                            <a href="{{ route('purchase_orders') }}" class="btn btn-secondary mr-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-truck"></i> Record Delivery
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
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
