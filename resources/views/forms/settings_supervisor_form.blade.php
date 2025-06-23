
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-name" class="control-label required">Name</label>
            <input type="text" class="form-control" id="input-allowance-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Supervisor Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-phone" class="control-label required">Phone Number</label>
            <input type="text" class="form-control" id="input-allowance-phone" name="phone" value="{{ $object->phone ?? '' }}" placeholder="Phone Number" required>
        </div>
        <div class="form-group">
            <label for="example-nf-details">Other Details</label>
            <textarea class="form-control" id="input-details" name="details" placeholder="Short Details" >{{$object->details ?? ''}}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Employee Type</label>
            <select name="employee_id" id="input-employee-id" class="form-control" required>

                <option value="">Select employee type</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee['id'] }}" {{ ( $employee['id'] == $object->employee_id) ? 'selected' : '' }}> {{ $employee['name'] }} </option>
            @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">System</label>
            <select name="system_id" id="input-system-id" class="form-control" required>

                <option value="">Select System</option>
            @foreach ($systems as $system)
                <option value="{{ $system['id'] }}" {{ ( $system['id'] == $object->system_id) ? 'selected' : '' }}> {{ $system['name'] }} </option>
            @endforeach
            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Supervisor">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
