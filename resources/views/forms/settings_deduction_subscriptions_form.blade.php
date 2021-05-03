
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Staff</label>
            <select name="staff_id" id="input-employee-id" class="form-control" required>

                <option value="">Select Staff</option>
                @foreach ($staffs as $staff)
                    <option value="{{ $staff['id'] }}" {{ ( $staff['id'] == $object->staff_id) ? 'selected' : '' }}> {{ $staff['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Deduction</label>
            <select name="deduction_id" id="input-deduction-id" class="form-control" required>

                <option value="">Select Deduction</option>
                @foreach ($deduction_subscriptions as $deduction_subscription)
                    <option value="{{ $deduction_subscription['id'] }}" {{ ( $deduction_subscription['id'] == $object->deduction_subscription_id) ? 'selected' : '' }}> {{ $deduction_subscription['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Membership Number</label>
            <input type="text" class="form-control" id="input-membership_number" name="membership_number" value="{{ $object->membership_number ?? '' }}" placeholder="Membership Number">
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
