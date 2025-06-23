
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Foreign Currency</label>
            <select name="foreign_currency_id" id="foreign_currency_id" class="form-control" required>

                <option value="">Foreign Currency</option>
                @foreach ($foreign_currencies as $foreign_currency)
                    <option value="{{ $foreign_currency['id'] }}" {{ ( $foreign_currency['id'] == $object->foreign_currency_id) ? 'selected' : '' }}> {{ $foreign_currency['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Base Currency</label>
            <select name="base_currency_id" id="foreign_currency_id" class="form-control" required>

                <option value="">Base Currency</option>
                <option value="">Foreign Currency</option>
                @foreach ($base_currencies as $base_currency)
                    <option value="{{ $base_currency['id'] }}" {{ ( $base_currency['id'] == $object->base_currency_id) ? 'selected' : '' }}> {{ $base_currency['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Rate</label>
            <input type="number" class="form-control" id="rate" name="rate" value="{{ $object->rate ?? '' }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Month</label>
            <input type="number" class="form-control" id="month" name="month" value="{{ $object->month ?? '' }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Year</label>
            <input type="number" class="form-control" id="year" name="year" value="{{ $object->year ?? '' }}" required>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ExchangeRate">Submit</button>
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
