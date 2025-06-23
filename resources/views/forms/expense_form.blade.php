<?php
$document_id = \App\Classes\Utility::getLastId('Expense')+1;
?>
<div class="block-content">
    <form method="post"  enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Expense Sub Category</label>
            <select name="expenses_sub_category_id" id="input-ifd-id" class="form-control" required>

                <option value="">Select Expense Sub Category</option>

                @foreach ($expenses_sub_categories as $expenses_sub_category)
                    <option value="{{ $expenses_sub_category->id }}" {{ ( $expenses_sub_category->id == $object->expenses_sub_category_id) ? 'selected' : '' }}> {{ $expenses_sub_category->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group" >
            <label for="example-nf-description" class="control-label required">Description</label>
            <input type="text" class="form-control" id="input-description" name="description"
                   value="{{ $object->description ?? '' }}" placeholder="Description" required>
        </div>
        <div class="form-group">
            <label for="example-nf-amount" class="control-label required">Amount</label>
            <input type="number" step=".01" class="form-control amount" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Total Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Date</label>
            <input type="text" class="form-control datepicker"  id="input-date" name="date"
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
                <input type="hidden" name="document_number" value="EXPS/{{$document_id}}/{{date('Y')}}">
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="5">
                <input type="hidden" name="link" value="expenses/{{$document_id}}/5">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Expense">Submit</button>
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


