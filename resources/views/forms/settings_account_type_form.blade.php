
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Type</label>
            <input type="text" class="form-control" id="type" name="type" value="{{ $object->type ?? '' }}" placeholder="Type" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Code</label>
            <input type="text" class="form-control" id="code" name="code" value="{{ $object->code ?? '' }}" placeholder="Code" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Normal Balance</label>
            <input type="text" class="form-control" id="normal_balance" name="normal_balance" value="{{ $object->normal_balance ?? '' }}" placeholder="DR/CR" required>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="AccountType">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
