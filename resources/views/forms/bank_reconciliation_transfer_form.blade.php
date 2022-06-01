<?php
$document_id = \App\Classes\Utility::getLastId('BankReconciliation')+1;
?>
<div class="block-content">
    <form method="post" action="{{route('transfer')}}" enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <input type="hidden" class="form-control" id="input-reference" name="reference" value="TRANSFER{{ $document_id  }}">
        <div class="row" style="border: 1px solid black; border-radius: 10px; padding: 10px; background: lightgrey; margin-top: -30px!important;">
            <div class="col-md-12 text-center text-dark font-weight-bold"><span>Available Balance</span></div>
            <div class="col-md-12 text-center">
                <span>Current Balance</span>
                <select name="cash_available" id="cash_available" class="form-control">
                    <option></option>
                </select>
            </div>
        </div>
        <br/>
        <div class="form-group">
            <label for="example-nf-date">Date</label>
            <input type="text" class="form-control datepicker"  id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
        </div>
        <div class="form-group">
            <label for="example-nf-email">From</label>
            <select name="from" id="from" class="form-control" required>


                @foreach ($kassim_supplier as $supplier)
                    <option value="{{$supplier->id}}" {{( $supplier->id == $object->supplier_id) ? 'selected' : ''}}> {{ $supplier->name }} </option>
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">To</label>
            <select name="to" id="to" class="form-control" required>

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
                    @if($balance != 0)
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
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Sale">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $("#input-ifd-id").change(function () {
        var efd_id = $(this).val();
        var date = $('#input-date').val();
        var supplier_from = $('#from').find('option:selected').val();
        var supplier_to = $('#to').find('option:selected').val();
        var url = '/transfer_balance';

        $.ajax({
            url: url,
            type: 'post',
            data: {efd_id: efd_id,date: date,supplier_from: supplier_from,supplier_to: supplier_to, _token: csrf_token},
            dataType: 'json',
            success: function (response) {
                var len = response.length;
                $("#cash_available").empty();
                for (var i = 0; i < len; i++) {
                    var cash_available = response[i]['cash_available'];

                    $("#cash_available").append("<option value='" + cash_available + "'>" + cash_available + "</option>");


                    var max = ($("#cash_available").find(":selected").val());

                    // var max = accType == 1 ? ($("#float_available").find(":selected").val()) : ($("#cash_available").find(":selected").val());
                    $("#input-debit").attr('max', max);
                }
            }
        });
    });
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

