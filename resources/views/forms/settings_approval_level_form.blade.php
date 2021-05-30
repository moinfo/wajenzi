<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-supplier" class="control-label required">Approval Document Type</label>
            <select name="approval_document_types_id" id="input-approval_document_types-id" class="form-control" required>

                <option value="">Select Approval Document Type</option>

                @foreach ($approval_document_types as $approval_document_type)
                    <option value="{{$approval_document_type->id}}" {{($approval_document_type->id == $object->approval_document_types_id) ? 'selected' : '' }}> {{$approval_document_type->name}} </option>
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
            <label for="example-nf-email">Description</label>
            <textarea type="text" class="form-control" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Order</label>
            <input type="number" class="form-control" id="input-order" name="order" value="{{ $object->order ?? '' }}" placeholder="Keyword" required>
        </div>

        <div class="form-group">
            <label for="example-nf-supplier" class="control-label required">Action</label>
            <select name="action" id="input-action" class="form-control" required>

                <option value="">Select Action</option>

                @foreach ($actions as $action)
                    <option value="{{$action['name']}}" {{ ( $action['name'] == $object->action) ? 'selected' : '' }}> {{$action['name']}} </option>
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
