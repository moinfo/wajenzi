
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Asset</label>
            <select name="asset_id" id="input-employee-id" class="form-control" required>

                <option value="">Select Asset</option>
                @foreach ($assets as $asset)
                    <option value="{{ $asset['id'] }}" {{ ( $asset['id'] == $object->asset_id) ? 'selected' : '' }}> {{ $asset['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Property Name</label>
            <input type="text" class="form-control" id="input-allowance-name" name="name" value="{{ $object->name ?? '' }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-password">Description</label>
            <textarea class="form-control" id="input-allowance-description" name="description" placeholder="Short Description">{{$object->description ?? ''}}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-supplier" class="control-label required">Staff</label>
            <select name="user_id" id="input-user-id" class="form-control" required>

                <option value="">Select Staff</option>

                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ ( $user->id == $object->user_id) ? 'selected' : '' }}> {{ $user->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Bank">Submit</button>
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
