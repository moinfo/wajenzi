
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
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
            <label for="example-nf-supplier" class="control-label required">User Group</label>
            <select name="user_group_id" id="input-user_group-id" class="form-control" required>

                <option value="">Select User Group</option>

                @foreach ($user_groups as $user_group)
                    <option value="{{ $user_group->id }}" {{ ( $user_group->id == $object->user_group_id) ? 'selected' : '' }}> {{ $user_group->name }} </option>
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
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
