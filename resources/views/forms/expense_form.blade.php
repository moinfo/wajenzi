<div class="block-content">
    <form method="post">
        @csrf

        <div class="form-group">
            <label for="example-nf-supervisor" class="control-label required">Supervisor Name</label>
            <select name="supervisor_id" id="input-ifd-id" class="form-control" required>

                <option>Select Supervisor</option>

                @foreach ($supervisors_and_drivers as $supervisor)
                    <option value="{{ $supervisor->id }}" {{ ( $supervisor->id == $object->supervisor_id) ? 'selected' : '' }}> {{ $supervisor->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Expense Category</label>
            <select name="expenses_category_id" id="input-ifd-id" class="form-control" required>

                <option>Select Expense Category</option>

                @foreach ($expenses_categories as $expenses_category)
                    <option value="{{ $expenses_category->id }}" {{ ( $expenses_category->id == $object->expenses_category_id) ? 'selected' : '' }}> {{ $expenses_category->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group" >
            <label for="example-nf-description" class="control-label required">Description</label>
            <input type="text" class="form-control" id="input-description" name="description"
                   value="{{ $object->description ?? '' }}" placeholder="Description" required>
        </div>
        <div class="form-group">
            <label for="example-nf-amount" class="control-label required">Amount</label>
            <input type="number" step=".01" class="form-control" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Total Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Date</label>
            <input type="text" class="form-control datepicker"  id="input-date" name="date"
                   value="{{ $object->invoice_date ?? date('Y-m-d') }}" required>
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Expense">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>


