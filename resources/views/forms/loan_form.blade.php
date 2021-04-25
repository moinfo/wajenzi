
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-details" class="control-label required">Staff</label>
            <select name="staff_id" id="input-staff-id" class="form-control" required>

                <option>Select Staff</option>
                @foreach ($staffs as $staff)
                    <option value="{{ $staff['id'] }}" {{ ( $staff['id'] == $object->staff_id) ? 'selected' : '' }}> {{ $staff['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Loan Amount</label>
            <input type="number" class="form-control" id="input-amount" name="amount" value="{{ $object->amount ?? '' }}" placeholder="Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Deduction Per Month</label>
            <input type="number" class="form-control" id="input-deduction" name="deduction" value="{{ $object->deduction ?? '' }}" placeholder="Deduction" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Date</label>
            <input type="text" class="form-control datepicker"  id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
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
