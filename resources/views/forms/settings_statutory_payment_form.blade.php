<?php
$document_id = \App\Classes\Utility::getLastId('StatutoryPayment')+1;
?>
<div class="block-content">
    <form method="post"  enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Sub Category</label>
            <select name="sub_category_id" id="input-sub-category-id" class="form-control" required>
                <option value="">Select Sub Category</option>
                @foreach ($sub_categories as $sub_category)
                    <option value="{{ $sub_category->id }}" {{ ( $sub_category->id == $object->sub_category_id) ? 'selected' : '' }}> {{ $sub_category->name }} </option>
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
            <input type="number" step=".01" class="form-control" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Total Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-amount">Control Number</label>
            <input type="number" class="form-control" id="input-control_number" name="control_number"
                   value="{{ $object->control_number ?? '' }}" placeholder="Control Number">
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Issues Date</label>
            <input type="text" class="form-control datepicker"  id="input-issue-date" name="issue_date"
                   value="{{ $object->issue_date ?? date('Y-m-d') }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Due Date</label>
            <input type="text" class="form-control datepicker"  id="input-due_date" name="due_date"
                   value="{{ $object->due_date ?? date('Y-m-d') }}" required>
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
                <input type="hidden" name="link" value="settings/statutory_payments/{{$document_id}}/1">
                <input type="hidden" name="document_type_id" value="1">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="StatutoryPayment">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    // window.Echo.private('details.' + window.Laravel.user)
    //     .listen('Approved', (e) => {
    //         console.log(e);
    //     });
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>


