
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Name</label>
            <input type="text" class="form-control" id="input-allowance-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Permission Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-password">Description</label>
            <textarea class="form-control" id="input-allowance-description" name="description" placeholder="Short Description" required>{{$object->description ?? ''}}</textarea>

        <div class="form-group">
            <label for="example-nf-permission" class="control-label required">Permission Type</label>
            <select name="permission_id" id="input-permission-id" class="form-control" required>
                <option value="">Select Permission</option>
                @foreach ($permissions as $permission)
                    <option value="{{ $permission['name'] }}" {{ ( $permission['name'] == $object->permission_id) ? 'selected' : '' }}> {{ $permission['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Allowance">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
