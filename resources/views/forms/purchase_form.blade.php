
<div class="block-content">
    <form method="post"  enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Supplier</label>
            <select name="supplier_id" id="input-supplier-id" class="form-control select2" required>
                <option value=""></option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ ( $supplier->id == $object->supplier_id) ? 'selected' : '' }}> {{ $supplier->name }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Item</label>
            <select name="item_id" id="input-item-id" class="form-control select2" required>
                <option value=""></option>
                @foreach($items as $item)
                    <option value="{{ $item->id }}" {{ ( $item->id == $object->item_id) ? 'selected' : '' }}> {{ $item->name }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Purchase Type</label>
            <select name="purchase_type" id="input-purchases_type" class="form-control" required>
                <option value="">Choose Purchases Type</option>
                @foreach($purchases_types as $purchases_type)
                    <option value="{{ $purchases_type['id'] }}" {{ ( $purchases_type['id'] == $object->purchase_type) ? 'selected' : '' }}> {{ $purchases_type['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-total_amount">Total Amount</label>
            <input type="number" step=".01" class="form-control" id="input-total_amount" name="total_amount"
                   value="{{ $object->total_amount ?? '' }}" placeholder="Total Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-tax_invoice">Tax Invoice</label>
            <input type="text" class="form-control" id="input-tax_invoice" name="tax_invoice"
                   value="{{ $object->tax_invoice ?? '' }}" placeholder="Tax Invoice" required>
        </div>
        <div class="form-group">
            <label for="example-nf-invoice_date">Invoice Date</label>
            <input type="text" class="form-control datepicker" id="input-invoice_date" name="invoice_date"
                   value="{{ $object->invoice_date ?? date('Y-m-d') }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-invoice_date">Date</label>
            <input type="text" class="form-control datepicker" id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
        </div>
        <div class="form-group">
            <label class="control-label" for="chooseFile">Choose file</label>
            <input type="file" name="file" class="form-control" id="chooseFile">
        </div>
        <div class="form-group" style="display: none;" id="amount_vat_exc">
            <label for="example-nf-amount_vat_exc">Amount VAT Exc</label>
            <input type="text" class="form-control" id="input-amount_vat_exc" name="amount_vat_exc"
                   value="{{ $object->amount_vat_exc ?? '' }}" readonly>
        </div>
        <div class="form-group" style="display: none;" id="vat_amount">
            <label for="example-nf-vat_amount"> VAT Amount</label>
            <input type="text" class="form-control" id="input-vat_amount" name="vat_amount"
                   value="{{ $object->vat_amount ?? '' }}" readonly>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Purchase">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $(document).ready(function () {
        $('#input-total_amount').keyup(calculate);
    });

    function calculate(e) {
        if($('#input-purchases_type').val() == '1') {
            $('#input-amount_vat_exc').val($('#input-total_amount').val() * 100 / 118);
            $('#input-vat_amount').val($('#input-amount_vat_exc').val() * 18 / 100);
        }else{
            $('#input-amount_vat_exc').val($('#input-total_amount').val() * 0);
            $('#input-vat_amount').val($('#input-amount_vat_exc').val() * 0);
        }
    }

    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });

    $(".select2").select2({
        theme: "bootstrap",
        placeholder: "Choose",
        width: 'auto',
        dropdownAutoWidth: true,
        allowClear: true,
    });
    $(function() {
        $('#amount_vat_exc').hide();
        $('#vat_amount').hide();
        $('#input-purchases_type').change(function(){
            if($('#input-purchases_type').val() == '1') {
                $('#amount_vat_exc').show();
                $('#vat_amount').show();

            } else {
                $('#amount_vat_exc').hide();
                $('#vat_amount').hide();
            }
        });
    });
</script>
