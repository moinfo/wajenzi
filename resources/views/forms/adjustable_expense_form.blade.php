
<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-total_amount">Amount</label>
            <input type="number" class="form-control amount" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-invoice_date">Date</label>
            <input type="text" class="form-control datepicker" id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
        </div>

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="AdjustmentExpense">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>

    $(document).ready(function () {
        $('#input-total_amount').keyup(calculate);
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
