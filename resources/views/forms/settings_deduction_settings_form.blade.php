
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
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
            <label for="example-nf-email">Minimum Amount</label>
            <input type="number" class="form-control" id="input-minimum_amount" name="minimum_amount" value="{{ $object->minimum_amount ?? '' }}" placeholder="Minimum Amount">
        </div>
        <div class="form-group">
            <label for="example-nf-email">Maximum Amount</label>
            <input type="number" class="form-control" id="input-maximum_amount" name="maximum_amount" value="{{ $object->maximum_amount ?? '' }}" placeholder="Maximum Amount">
        </div>
        <div class="form-group">
            <label for="example-nf-email">Employee Percentage</label>
            <input type="number" step="any" class="form-control" id="input-employee_percentage" name="employee_percentage" value="{{ $object->employee_percentage ?? '' }}" placeholder="Employee Percentage">
        </div>
        <div class="form-group">
            <label for="example-nf-email">Employer Percentage</label>
            <input type="number" step="any" class="form-control" id="input-employer_percentage" name="employer_percentage" value="{{ $object->employer_percentage ?? '' }}" placeholder="Employer Percentage">
        </div>
        <div class="form-group">
            <label for="example-nf-email">Additional Amount</label>
            <input type="number" class="form-control" id="input-additional_amount" name="additional_amount" value="{{ $object->additional_amount ?? '' }}" placeholder="Additional Amount">
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
