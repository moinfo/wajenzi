
<div class="block-content">
    @php($permissionTypes = ['MENU', 'SETTING', 'REPORT', 'CRUD'])
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $object->name ?? '' }}" placeholder="Permission Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-permission" class="control-label required">Permission Type</label>
            <select name="permission_type" id="input-permission-type-id" class="form-control" required>
                <option value="">Select Permission Type</option>
                @foreach ($permissionTypes as $permissionType)
                    <option value="{{$permissionType}}" {{ ( $permissionType == ($object->permission_type ?? '')) ? 'selected' : '' }}> {{ $permissionType }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-description">Description</label>
            <input type="text" class="form-control" id="description" name="description" value="{{ $object->description ?? '' }}" placeholder="Short description">
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <input type="hidden" name="guard_name" value="{{ $object->guard_name ?? 'web' }}">
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
