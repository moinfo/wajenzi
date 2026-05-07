@extends('layouts.backend')

@section('content')
<div class="container-fluid">
    <div class="content">
        <div class="content-heading">Upload Payment Attachment
            <div class="float-right">
                <a href="{{ route('purchase_orders') }}" class="btn btn-rounded btn-outline-info min-width-100 mb-10">
                    <i class="fa fa-arrow-left"></i> Back to POs
                </a>
            </div>
        </div>

        {{-- PO Summary --}}
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title">
                    Purchase Order: <strong>{{ $purchase->document_number ?? 'PO-' . $purchase->id }}</strong>
                </h3>
            </div>
            <div class="block-content">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Supplier:</strong><br>{{ $purchase->supplier?->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Project:</strong><br>{{ $purchase->project?->name ?? 'N/A' }}
                    </div>
                    <div class="col-md-3">
                        <strong>PO Date:</strong><br>{{ $purchase->date }}
                    </div>
                    <div class="col-md-3">
                        <strong>Total Amount:</strong><br>
                        <span class="text-success font-weight-bold">
                            TZS {{ number_format($purchase->total_amount, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Upload Form --}}
        <div class="block">
            <div class="block-header block-header-default" style="background-color: #e8f5e9;">
                <h3 class="block-title">
                    <i class="fa fa-paperclip"></i> Attach Payment Proof
                </h3>
            </div>
            <div class="block-content">
                <form method="post"
                      action="{{ route('purchase_order.store_payment', $purchase->id) }}"
                      enctype="multipart/form-data"
                      autocomplete="off">
                    @csrf

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="payment_date"
                                       class="form-control datepicker"
                                       value="{{ old('payment_date', date('Y-m-d')) }}"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Payment Reference (Cheque / Bank Ref) <span class="text-danger">*</span></label>
                                <input type="text"
                                       name="payment_reference"
                                       class="form-control"
                                       value="{{ old('payment_reference') }}"
                                       placeholder="e.g. CHQ-001234 or TT-20260507"
                                       maxlength="100"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Payment Slip / Receipt <span class="text-danger">*</span></label>
                                <input type="file"
                                       name="payment_attachment"
                                       class="form-control"
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       required>
                                <small class="text-muted">PDF, JPG or PNG — max 5 MB</small>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Note (optional)</label>
                                <textarea name="payment_note"
                                          class="form-control"
                                          rows="2"
                                          maxlength="500"
                                          placeholder="Any additional note for procurement…">{{ old('payment_note') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-upload"></i> Upload Payment Attachment
                        </button>
                        <a href="{{ route('purchase_orders') }}" class="btn btn-secondary ml-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

        {{-- PO Items (read-only reference) --}}
        @if($purchase->purchaseItems->count())
        <div class="block">
            <div class="block-header block-header-default">
                <h3 class="block-title"><i class="fa fa-list"></i> Order Items</h3>
            </div>
            <div class="block-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-vcenter">
                        <thead>
                            <tr>
                                <th style="width:40px">#</th>
                                <th>Description</th>
                                <th class="text-center">Unit</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Unit Price</th>
                                <th class="text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchase->purchaseItems as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->unit }}</td>
                                <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                                <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-right">{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-right"><strong>Total</strong></td>
                                <td class="text-right"><strong>TZS {{ number_format($purchase->total_amount, 2) }}</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('js')
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
</script>
@endsection