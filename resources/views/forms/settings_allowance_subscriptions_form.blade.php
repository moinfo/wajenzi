
<div class="block-content">
    <form  method="post" action="{{route('allowance_subscriptions')}}"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Staff</label>
            <select name="staff_id" id="input-employee-id" class="form-control" required>

                <option value="">Select Staff</option>
                @foreach ($staffs as $staff)
                    <option value="{{ $staff['id'] }}" {{ ( $staff['id'] == $object->staff_id) ? 'selected' : '' }}> {{ $staff['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Allowance</label>
            <select name="allowance_id" id="input-allowance-id" class="form-control" required>

                <option value="">Select Allowance</option>
                @foreach ($allowance_subscriptions as $allowance_subscription)
                    <option value="{{ $allowance_subscription['id'] }}" {{ ( $allowance_subscription['id'] == $object->allowance_subscription_id) ? 'selected' : '' }}> {{ $allowance_subscription['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Amount</label>
            <input type="text" class="form-control amount" id="input-amount" name="amount" value="{{ $object->amount ?? '' }}" placeholder="Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Date</label>
            <input type="text" class="form-control datepicker"  id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="AllowanceSubscription">Submit</button>
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
