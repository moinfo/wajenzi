<?php

use App\Models\PettyCashRefillRequest;
$document_id = \App\Classes\Utility::getLastId('PettyCashRefillRequest') + 1;
$balance = PettyCashRefillRequest::getCurrentBalanceBetweenPettyCashRefillRequestAndImprestRequest();
$chart_of_accounts_variable = \App\Models\ChartAccountVariable::where('variable', 'PETTY_CASH_LIMIT')->first()->value;
$refill_amount = $chart_of_accounts_variable - $balance;
?>
<div class="block-content">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Account</label>
            <select name="charts_account_id" id="charts_account_id" class="form-control" required>
                @foreach ($charts_account_petty_cashs as $charts_of_account)
                    <option
                        value="{{ $charts_of_account->id }}" {{ ( $charts_of_account->id == $object->charts_account_id) ? 'selected' : '' }}> {{ $charts_of_account->code }}
                        :{{ $charts_of_account->account_name }} </option>
                @endforeach

            </select>
        </div>

        <div class="form-group">
            <label for="example-nf-balance" class="control-label required">Balance</label>
            <input type="number" step=".01" class="form-control" id="balance" name="balance"
                   value="{{ $object->balance ?? $balance }}" placeholder="Balance" readonly>
        </div>

        <div class="form-group">
            <label for="example-nf-refill_amount" class="control-label required">Refill Amount</label>
            <input type="number" step=".01" class="form-control" id="refill_amount" name="refill_amount"
                   value="{{ $object->refill_amount ?? $refill_amount }}" placeholder="Refill Amount" readonly>
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Date</label>
            <input type="text" class="form-control datepicker" id="input-date" name="date"
                   value="{{ $object->date ?? '' }}" required>
        </div>
        <div class="form-group">
            <label class="control-label" for="chooseFile">Choose file</label>
            <input type="file" name="file" class="form-control" id="chooseFile">
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="created_by_id" value="{{Auth::user()->id}}">
                <input type="hidden" name="document_number" value="PCRF/{{date('Y')}}/{{$document_id}}">
                <input type="hidden" name="document_type_id" value="12">
                <input type="hidden" name="link" value="finance/petty_cash_management/petty_cash_refill_requests/{{$document_id}}/12">
                @if($refill_amount>0)
                    <button type="submit" class="btn btn-alt-primary col" name="addItem" value="PettyCashRefillRequest">
                        Submit
                    </button>
                @endif
            @endif
        </div>
    </form>
</div>
<script>
    $("input.amount").each((i, ele) => {
        let clone = $(ele).clone(false)
        clone.attr("type", "text")
        let ele1 = $(ele)
        clone.val(Number(ele1.val()).toLocaleString("en"))
        $(ele).after(clone)
        $(ele).hide()
        clone.mouseenter(() => {

            ele1.show()
            clone.hide()
        })
        setInterval(() => {
            let newv = Number(ele1.val()).toLocaleString("en")
            if (clone.val() != newv) {
                clone.val(newv)
            }
        }, 10)

        $(ele).mouseleave(() => {
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


