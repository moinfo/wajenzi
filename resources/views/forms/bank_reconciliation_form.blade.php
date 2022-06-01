
<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Supplier Name</label>
            <select name="supplier_id" id="supplier_id" class="form-control" required>

                <option value="">Select Supplier</option>

                @foreach ($suppliers_with_balances as $supplier)
                    @php
                        if ($supplier->supplier_depend_on_system == 'WHITESTAR'){
                            $credit = \App\Models\Supplier::getWhitestarSupplierWithCredit($supplier->whitestar_supplier_id);
                            $debit_cash = \App\Models\Supplier::getWhitestarSupplierWithDebitInCash($supplier->whitestar_supplier_id);
                        }else{
                             $credit = \App\Models\Supplier::getBongeSupplierWithCredit($supplier->whitestar_supplier_id);
                                                             $debit_cash = 0;
                        }
                        $debit = \App\Models\Supplier::getLemuruSupplierWithDebitWithoutTransferToday($supplier->id) + \App\Models\Supplier::getLemuruSupplierWithDebitWithTransfer($supplier->id) + $supplier->debit + $debit_cash;
                        $balance = $credit - $debit;
                    @endphp
                    @if($balance != 0 || $supplier->is_transferred == 'YES'|| $supplier->is_transferred == 'CAN BE BOTH')
                    <option value="{{$supplier->id}}" {{( $supplier->id == $object->supplier_id) ? 'selected' : ''}}> {{ $supplier->name . ' - '. number_format($balance) }} </option>
                    @endif
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Efd Name</label>
            <select name="efd_id" id="input-ifd-id" class="form-control" required>

                <option value="">Select Efd</option>

                @foreach ($efds as $efd)
                    <option value="{{$efd->id}}" {{ ( $efd->id == $object->efd_id) ? 'selected' : '' }}> {{ $efd->name }} </option>
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Payment Type</label>
            <select name="payment_type" id="payment_type" class="form-control" required>

                <option value="">Select Payment Type</option>

                @foreach ($bank_reconciliation_payment_types as $bank_reconciliation_payment_type)
                    <option value="{{$bank_reconciliation_payment_type['name']}}" {{ ( $bank_reconciliation_payment_type['name'] == $object->payment_type) ? 'selected' : '' }}> {{ $bank_reconciliation_payment_type['name'] }} </option>
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-reference">Reference</label>
            <input type="text" class="form-control" id="input-reference" name="reference" value="{{ $object->reference ?? '' }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-description">Description</label>
            <textarea type="text" row="3" class="form-control" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-debit">Amount/Debit</label>
            <input type="number" step=".01" class="form-control amount" id="input-debit" name="debit"
                   value="{{ $object->debit ?? '' }}" placeholder="Total Amount" required>
        </div>
        @if($object->id ?? null)

            <div class="form-group">
                <label for="example-nf-credit">Credit</label>
                <input type="number" step=".01" class="form-control amount" id="input-credit" name="credit"
                       value="{{ $object->credit ?? '' }}" placeholder="Total Amount" required>
            </div>
            @endif
        <div class="form-group">
            <label for="example-nf-date">Date</label>
            <input type="text" class="form-control datepicker"  id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BankReconciliation">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
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
</script>

