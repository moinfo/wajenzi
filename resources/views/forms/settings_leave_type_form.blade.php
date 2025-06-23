
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Name</label>
            <input type="text" class="form-control" id="input-allowance-name" name="name" value="{{ $object->name ?? '' }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-password">Description</label>
            <textarea class="form-control" id="input-allowance-description" name="description" placeholder="Short Description">{{$object->description ?? ''}}</textarea>
        </div>

        <div class="form-group">
            <label for="example-nf-days_allowed">Days Allowed</label>
            <input type="number" class="form-control" id="input-days_allowed" name="days_allowed" value="{{ $object->days_allowed ?? '' }}" required>
        </div>

        <div class="form-group">
            <label for="example-nf-notice_days">Notice Days</label>
            <input type="number" class="form-control" id="input-notice_days" name="notice_days" value="{{ $object->notice_days ?? '' }}" required>
        </div>

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="LeaveType">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
