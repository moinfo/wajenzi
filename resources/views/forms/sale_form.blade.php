<?php
$document_id = \App\Classes\Utility::getLastId('Sale')+1;
?>
<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Efd Name</label>
            <select name="efd_id" id="input-ifd-id" class="form-control" required>

                <option value="">Select Efd</option>

                @foreach ($efds as $efd)
                    <option value="{{ $efd->id }}" {{ ( $efd->id == $object->efd_id) ? 'selected' : '' }}> {{ $efd->name }} </option>
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-amount">Turnover</label>
            <input type="number" step=".01" class="form-control amount" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Total Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-net">Net (A+B+C)</label>
            <input type="number" step=".01" class="form-control amount" id="input-net" name="net"
                   value="{{ $object->net ?? '' }}" placeholder="Total NET" required>
        </div>
        <div class="form-group">
            <label for="example-nf-tax">Tax</label>
            <input type="number" step=".01" class="form-control amount" id="input-tax" name="tax"
                   value="{{ $object->tax ?? '' }}" placeholder="Total Tax" required>
        </div>
        <div class="form-group">
            <label for="example-nf-turn_over">Turnover(EX + SR)</label>
            <input type="number" step=".01" class="form-control amount" id="input-turn_over" name="turn_over"
                   value="{{ $object->turn_over ?? '' }}" placeholder="Total Turnover" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date">Date</label>
            <input type="date" name="date" id="input-date"
                   class="form-control"
                   value="{{ old('date', $object->date ?? date('Y-m-d')) }}" required>
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
                <input type="hidden" name="document_number" value="SALE/{{$document_id}}/{{date('Y')}}">
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="2">
                <input type="hidden" name="link" value="sales/{{$document_id}}/2">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Sale">Submit</button>
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
</script>
