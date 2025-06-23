
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $object->name ?? '' }}" placeholder="Role Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-permission" class="control-label required">Permission Type</label>
            <select name="permission_type" id="input-permission-type-id" class="form-control" required>
                <option value="">Select Permission</option>
                @foreach ($permissions as $permission)
                    <option value="{{$permission['name']}}" {{ ( $permission['name'] == $object->permission_type) ? 'selected' : '' }}> {{ $permission['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <input type="hidden" name="guard_name" value="web">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Permission">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
