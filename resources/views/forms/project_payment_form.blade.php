{{-- Project Payment Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="invoice_id" class="control-label required">Invoice</label>
                    <select name="invoice_id" id="input-invoice" class="form-control" required="required">
                        <option value="">Select Invoice</option>
                        @foreach ($invoices as $invoice)
                            <option value="{{ $invoice->id }}" {{ ($invoice->id == $object->invoice_id) ? 'selected' : '' }}>{{ $invoice->invoice_number }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="amount" class="control-label required">Amount</label>
                    <input type="number" step="0.01" class="form-control" id="input-amount" name="amount" value="{{ $object->amount ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="payment_method" class="control-label required">Payment Method</label>
                    <select name="payment_method" id="input-payment-method" class="form-control" required="required">
                        <option value="">Select Method</option>
                        <option value="cash" {{ ($object->payment_method == 'cash') ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ ($object->payment_method == 'bank_transfer') ? 'selected' : '' }}>Bank Transfer</option>
                        <option value="cheque" {{ ($object->payment_method == 'cheque') ? 'selected' : '' }}>Cheque</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="reference_number">Reference Number</label>
                    <input type="text" class="form-control" id="input-reference" name="reference_number" value="{{ $object->reference_number ?? '' }}">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="status" class="control-label required">Status</label>
                    <select name="status" id="input-status" class="form-control" required="required">
                        <option value="">Select Status</option>
                        <option value="pending" {{ ($object->status == 'pending') ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ ($object->status == 'completed') ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ ($object->status == 'failed') ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Payment">Submit</button>
            @endif
        </div>
    </form>
</div>
