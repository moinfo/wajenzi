<div class="block-content">
    <form method="post">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Financial Charge Category</label>
            <select name="financial_charge_category_id" id="input-ifd-id" class="form-control">

                <option>Select Financial Charge Category</option>

                @foreach ($financial_charge_categories as $financial_charge_category)
                    <option value="{{ $financial_charge_category->id }}" {{ ( $financial_charge_category->id == $object->financial_charge_category_id) ? 'selected' : '' }}> {{ $financial_charge_category->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-description">Description</label>
            <input type="text" class="form-control" id="input-description" name="description"
                   value="{{ $object->description ?? '' }}" placeholder="Description" required>
        </div>
        <div class="form-group">
            <label for="example-nf-amount">Amount</label>
            <input type="number" step=".01" class="form-control" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Total Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date">Date</label>
            <input type="text" class="form-control datepicker" id="input-date" name="date"
                   value="{{ $object->invoice_date ?? date('Y-m-d') }}" required>
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Financial Charge">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>


