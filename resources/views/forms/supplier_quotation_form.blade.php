{{-- Supplier Quotation Form --}}
<div class="block-content">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="material_request_id" class="control-label required">Material Request</label>
                    <select name="material_request_id" id="input-material-request" class="form-control select2" required>
                        <option value="">Select Material Request</option>
                        @foreach ($approved_material_requests ?? [] as $request)
                            <option value="{{ $request->id }}"
                                data-quantity="{{ $request->quantity_requested }}"
                                data-unit="{{ $request->unit }}"
                                {{ ($request->id == ($object->material_request_id ?? '')) ? 'selected' : '' }}>
                                {{ $request->request_number }} - {{ $request->project?->name ?? 'N/A' }}
                                ({{ number_format($request->quantity_requested, 2) }} {{ $request->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="supplier_id" class="control-label required">Supplier</label>
                    <select name="supplier_id" id="input-supplier" class="form-control select2" required>
                        <option value="">Select Supplier</option>
                        @foreach ($suppliers ?? [] as $supplier)
                            <option value="{{ $supplier->id }}" {{ ($supplier->id == ($object->supplier_id ?? '')) ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="quotation_date" class="control-label required">Quotation Date</label>
                    <input type="text" class="form-control datepicker" id="input-quotation-date"
                        name="quotation_date" value="{{ $object->quotation_date ?? date('Y-m-d') }}" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="valid_until" class="control-label">Valid Until</label>
                    <input type="text" class="form-control datepicker" id="input-valid-until"
                        name="valid_until" value="{{ $object->valid_until ?? date('Y-m-d', strtotime('+30 days')) }}">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="delivery_time_days" class="control-label">Delivery Time (Days)</label>
                    <input type="number" class="form-control" id="input-delivery-time"
                        name="delivery_time_days" value="{{ $object->delivery_time_days ?? 7 }}" min="1">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="quantity" class="control-label required">Quantity</label>
                    <input type="number" step="0.01" class="form-control" id="input-quantity"
                        name="quantity" value="{{ $object->quantity ?? '' }}" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="unit_price" class="control-label required">Unit Price</label>
                    <input type="number" step="0.01" class="form-control amount" id="input-unit-price"
                        name="unit_price" value="{{ $object->unit_price ?? '' }}" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="total_amount" class="control-label required">Total Amount</label>
                    <input type="number" step="0.01" class="form-control amount" id="input-total-amount"
                        name="total_amount" value="{{ $object->total_amount ?? '' }}" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="vat_amount" class="control-label">VAT Amount</label>
                    <input type="number" step="0.01" class="form-control amount" id="input-vat-amount"
                        name="vat_amount" value="{{ $object->vat_amount ?? 0 }}">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="grand_total" class="control-label">Grand Total</label>
                    <input type="number" step="0.01" class="form-control amount" id="input-grand-total"
                        value="{{ ($object->total_amount ?? 0) + ($object->vat_amount ?? 0) }}" readonly>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="payment_terms" class="control-label">Payment Terms</label>
                    <input type="text" class="form-control" id="input-payment-terms"
                        name="payment_terms" value="{{ $object->payment_terms ?? '' }}" placeholder="e.g., Net 30">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="file" class="control-label">Quotation Document</label>
                    <input type="file" class="form-control" id="input-file" name="file"
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                    @if($object->file ?? null)
                        <small class="text-muted">Current: <a href="{{ $object->file }}" target="_blank">View File</a></small>
                    @endif
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="notes" class="control-label">Notes</label>
                    <textarea class="form-control" id="input-notes" name="notes" rows="2">{{ $object->notes ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <input type="hidden" name="status" value="received">

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem">
                    <i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="SupplierQuotation">
                    Add Quotation
                </button>
            @endif
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });

        $(".select2").select2({
            theme: "bootstrap",
            placeholder: "Choose",
            width: '100%',
            dropdownAutoWidth: true,
            allowClear: true,
            dropdownParent: $('#ajax-loader-modal')
        });

        // Calculate total when quantity or unit price changes
        function calculateTotal() {
            var qty = parseFloat($('#input-quantity').val()) || 0;
            var unitPrice = parseFloat($('#input-unit-price').val()) || 0;
            var total = qty * unitPrice;
            $('#input-total-amount').val(total.toFixed(2));
            calculateGrandTotal();
        }

        // Calculate grand total
        function calculateGrandTotal() {
            var total = parseFloat($('#input-total-amount').val()) || 0;
            var vat = parseFloat($('#input-vat-amount').val()) || 0;
            $('#input-grand-total').val((total + vat).toFixed(2));
        }

        $('#input-quantity, #input-unit-price').on('input', calculateTotal);
        $('#input-total-amount, #input-vat-amount').on('input', calculateGrandTotal);

        // Auto-fill quantity from material request
        $('#input-material-request').change(function() {
            var selected = $(this).find(':selected');
            if (selected.val()) {
                var qty = selected.data('quantity');
                $('#input-quantity').val(qty);
                calculateTotal();
            }
        });
    });
</script>
