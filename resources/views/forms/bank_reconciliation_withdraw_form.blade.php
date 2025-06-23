<?php
$document_id = \App\Classes\Utility::getLastId('BankReconciliation')+1;
?>
<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Supplier Name</label>
            <select name="supplier_id" id="input-supplier-id" class="form-control select2" required>

                <option value="">Select Supplier</option>

                @foreach ($withdraw_suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ ( $supplier->id == $object->supplier_id) ? 'selected' : '' }}> {{ $supplier->name }} </option>
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Efd Name</label>
            <select name="efd_id" id="input-ifd-id" class="form-control select2" required>
                @foreach ($efds as $efd)
                    @if ($efd->id == 10)
                        <option value="{{ $efd->id }}" selected>{{ $efd->name }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="example-nf-email">Payment Type</label>
            <select name="bank_id" id="bank-id" class="form-control" required>

{{--                <option value="">Select Payment Type</option>--}}

                @foreach ($banks as $bank)
                    <option
                        value="{{$bank->id}}" {{ ( $bank->id == $object->bank_id) ? 'selected' : '' }}> {{ $bank->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Payment Mode</label>
            <select name="payment_type" id="payment_type" class="form-control" required>

                <option value="">Select Payment Mode</option>

                @foreach ($bank_reconciliation_payment_types as $bank_reconciliation_payment_type)
                    <option value="{{$bank_reconciliation_payment_type['name']}}" {{ ( $bank_reconciliation_payment_type['name'] == $object->payment_type) ? 'selected' : '' }}> {{ $bank_reconciliation_payment_type['name'] }} </option>
                @endforeach

            </select>
        </div>
        <input type="hidden" class="form-control" id="input-reference" name="reference" value="{{ bin2hex(random_bytes(18)) }}">
        <div class="form-group">
            <label for="example-nf-description">Description</label>
            <textarea type="text" row="3" class="form-control" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-credit">Amount/Credit</label>
            <input type="number" min="0" onkeydown="return preventNegative(event)"  step=".01" class="form-control amount" id="input-credit" name="credit"
                   value="{{ $object->credit ?? '' }}" placeholder="Total Amount" required>
        </div>
        @if($object->id ?? null)
            <div class="form-group">
                <label for="example-nf-date">Before Edited Date</label>
                <input type="text" class="form-control" id="input-date-edited" name="date_edited"
                       value="{{ $object->date }}" readonly>
            </div>
        @endif
        <div class="form-group">
            <label for="example-nf-date">Date</label>
            @can('Change Date Bank Withdraw')
                <input type="text" class="form-control datepicker" id="input-date" name="date"
                       value="{{ $object->date ?? date('Y-m-d') }}" required>
            @else
                <input type="text" class="form-control" id="input-date" name="date"
                       value="{{ date('Y-m-d') }}" readonly>
            @endcan
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="12">
                <input type="hidden" name="link" value="bank_reconciliations/{{$document_id}}/12">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BankReconciliation">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    function preventNegative(event) {
        // Prevent the user from entering a minus sign
        if (event.key === '-' || event.key === 'e') {
            event.preventDefault();
        }
    }
    $("input.amount").each((i,ele)=>{
        let clone=$(ele).clone(false)
        clone.attr("type","text")
        let ele1=$(ele)
        clone.val(Number(ele1.val()).toLocaleString("en"))
        $(ele).after(clone)
        $(ele).hide()
        clone.mouseenter(()=>{

            ele1.show()
            clone.hide()
        })
        setInterval(()=>{
            let newv=Number(ele1.val()).toLocaleString("en")
            if(clone.val()!=newv){
                clone.val(newv)
            }
        },10)

        $(ele).mouseleave(()=>{
            $(clone).show()
            $(ele1).hide()
        })


    });
    $("input").on("change", function () {
        this.setAttribute(
            "data-date",
            moment(this.value, "YYYY-MM-DD")
                .format(this.getAttribute("data-date-format"))
        )
    }).trigger("change")
</script>
<script>
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
</script>

