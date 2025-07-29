<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="process_approval_flow_id" class="control-label required">Approval Flow</label>
            <select name="process_approval_flow_id" id="input-process_approval_flow-id" class="form-control" required>
                <option value="">Select Approval Flow</option>
                @foreach ($process_approval_flows as $flow)
                    <option value="{{ $flow->id }}" {{ ($flow->id == $object->process_approval_flow_id) ? 'selected' : '' }}>
                        {{ $flow->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="role_id" class="control-label required">Role</label>
            <select name="role_id" id="input-role-id" class="form-control" required>
                <option value="">Select Role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ ($role->id == $object->role_id) ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="action" class="control-label required">Action</label>
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
            <label for="order" class="control-label required">Order</label>
            <input type="number" class="form-control" id="input-order" name="order" 
                   value="{{ $object->order ?? '' }}" placeholder="Step order (e.g., 1, 2, 3)" required>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" id="input-description" name="description" rows="3" 
                      placeholder="Optional description for this approval step">{{ $object->description ?? '' }}</textarea>
        </div>

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem">
                    <i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProcessApprovalFlowStep">
                    Submit
                </button>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>