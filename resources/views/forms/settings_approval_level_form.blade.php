<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="input-approval_document_types-id" class="control-label required">Approval Document Type</label>
            <select name="approval_document_types_id" id="input-approval_document_types-id" class="form-control" required>
                <option value="">Select Approval Document Type</option>
                @foreach ($approval_document_types as $approval_document_type)
                    <option value="{{ $approval_document_type->id }}"
                            {{ ($approval_document_type->id == $object->approval_document_types_id) ? 'selected' : '' }}>
                        {{ $approval_document_type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="input-role-id" class="control-label required">Approver Role</label>
            <select name="role_id" id="input-role-id" class="form-control" required>
                <option value="">Select Role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ ($role->id == $object->role_id) ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted">
                Users with this Spatie role will be notified when a document reaches this stage.
            </small>
            @if(!empty($object->user_group_id) && empty($object->role_id))
                <div class="alert alert-warning mt-2 mb-0 py-2 px-3" style="font-size: 12px;">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Legacy user group set:</strong>
                    @php
                        $legacyGroup = $user_groups->firstWhere('id', $object->user_group_id);
                    @endphp
                    "{{ $legacyGroup->name ?? 'group #' . $object->user_group_id }}" — pick a Role above and save to migrate this level.
                </div>
            @endif
            {{-- Keep legacy user_group_id around (hidden) so old data is preserved until admin picks a Role --}}
            <input type="hidden" name="user_group_id" value="{{ $object->user_group_id ?? '' }}">
        </div>

        <div class="form-group">
            <label for="input-description">Description</label>
            <textarea type="text" class="form-control" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>

        <div class="form-group">
            <label for="input-order">Order</label>
            <input type="number" class="form-control" id="input-order" name="order"
                   value="{{ $object->order ?? '' }}" placeholder="0 = creator, 1+ = approval steps" required>
        </div>

        <div class="form-group">
            <label for="input-action" class="control-label required">Action</label>
            <select name="action" id="input-action" class="form-control" required>
                <option value="">Select Action</option>
                @foreach ($actions as $action)
                    <option value="{{ $action['name'] }}" {{ ($action['name'] == $object->action) ? 'selected' : '' }}>
                        {{ $action['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem">
                    <i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ApprovalLevel">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({ format: 'yyyy-mm-dd' });
</script>
