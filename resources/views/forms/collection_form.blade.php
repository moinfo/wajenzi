<div class="block-content">
    <form method="post">
        @csrf
        <div class="form-group">
            <label for="example-nf-supervisor">Supervisor Name</label>
            <select name="supervisor_id" id="input-supervisor-id" class="form-control">

                <option>Select Supervisor</option>

                @foreach ($supervisors as $supervisor)
                    <option value="{{ $supervisor->id }}" {{ ( $supervisor->id == $object->supervisor_id) ? 'selected' : '' }}> {{ $supervisor->name }} </option>
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-bank">Bank Name</label>
            <select name="bank_id" id="input-bank-id" class="form-control">

                <option>Select Bank</option>

                @foreach ($banks as $bank)
                    <option value="{{ $bank->id }}" {{ ( $bank->id == $object->bank_id) ? 'selected' : '' }}> {{ $bank->name }} </option>
                @endforeach

                    </select>
        </div>
        <div class="form-group">
            <label for="example-nf-amount">Amount</label>
            <input type="number" step=".01" class="form-control" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Total Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-description">Description</label>
            <input type="text" class="form-control" id="input-description" name="description"
                   value="{{ $object->description ?? '' }}" placeholder="Description" required>
        </div>
        <div class="form-group">
            <label for="example-nf-date">Date</label>
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
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Sale">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $("input").on("change", function () {
        this.setAttribute(
            "data-date",
            moment(this.value, "YYYY-MM-DD")
                .format(this.getAttribute("data-date-format"))
        )
    }).trigger("change")
</script>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>

