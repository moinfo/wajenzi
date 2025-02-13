
<div class="block-content">
    <form  method="post"  autocomplete="off">
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
            <label for="example-nf-details" class="control-label required">Bank</label>
            <select name="bank_id" id="input-employee-id" class="form-control" required>

                <option value="">Select Bank</option>
                @foreach ($banks as $bank)
                    <option value="{{ $bank['id'] }}" {{ ( $bank['id'] == $object->bank_id) ? 'selected' : '' }}> {{ $bank['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Account Number</label>
            <input type="text" class="form-control" id="input-account_number" name="account_number" value="{{ $object->account_number ?? '' }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Branch</label>
            <textarea type="text" class="form-control" id="input-branch" name="branch" required>{{ $object->branch ?? '' }}</textarea>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else

                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="StaffBankDetail">Submit</button>
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
