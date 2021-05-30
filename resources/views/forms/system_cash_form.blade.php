<?php
$document_id = \App\Classes\Utility::getLastId('SystemCash')+1;
?>
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">System</label>
            <select name="system_id" id="input-system-id" class="form-control" required>

                <option value="">Select System</option>
                @foreach ($systems as $system)
                    <option value="{{ $system['id'] }}" {{ ( $system['id'] == $object->system_id) ? 'selected' : '' }}> {{ $system['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-phone" class="control-label required">Amount</label>
            <input type="number" step=".01"  class="form-control" id="input-amount" name="amount" value="{{ $object->amount ?? '' }}" placeholder="Cash Amount" required>
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
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="14">
                <input type="hidden" name="link" value="system_cash/{{$document_id}}/14">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="SystemCash">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
