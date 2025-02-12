{{-- Project Team Member Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project" class="form-control" required="required">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == $object->project_id) ? 'selected' : '' }}>{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="user_id" class="control-label required">Team Member</label>
                    <select name="user_id" id="input-user" class="form-control" required="required">
                        <option value="">Select Team Member</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ ($user->id == $object->user_id) ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="role" class="control-label required">Role</label>
                    <select name="role" id="input-role" class="form-control" required="required">
                        <option value="">Select Role</option>
                        <option value="project_manager" {{ ($object->role == 'project_manager') ? 'selected' : '' }}>Project Manager</option>
                        <option value="supervisor" {{ ($object->role == 'supervisor') ? 'selected' : '' }}>Supervisor</option>
                        <option value="engineer" {{ ($object->role == 'engineer') ? 'selected' : '' }}>Engineer</option>
                        <option value="worker" {{ ($object->role == 'worker') ? 'selected' : '' }}>Worker</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="assigned_date" class="control-label required">Assigned Date</label>
                    <input type="text" class="form-control datepicker" id="input-assigned-date" name="assigned_date" value="{{ $object->assigned_date ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="text" class="form-control datepicker" id="input-end-date" name="end_date" value="{{ $object->end_date ?? '' }}">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="status" class="control-label required">Status</label>
                    <select name="status" id="input-status" class="form-control" required="required">
                        <option value="">Select Status</option>
                        <option value="active" {{ ($object->status == 'active') ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($object->status == 'inactive') ? 'selected' : '' }}>Inactive</option>
                        <option value="completed" {{ ($object->status == 'completed') ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="TeamMember">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
