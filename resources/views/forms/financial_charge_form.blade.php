<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Supplier Name</label>
            <select name="supplier_id" id="supplier_id" class="form-control select2" required>

                <option value="">Select Supplier</option>

                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" {{ ( $supplier->id == $object->supplier_id) ? 'selected' : '' }}> {{ $supplier->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Financial Charge </label>
            <select name="financial_charge_category_id" id="financial_charge_category_id" class="form-control">

                <option value="">Select Financial Charge </option>

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
            <select name="amount" id="amount" class="form-control">
                <option value=""></option>
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-date">Date</label>
            <input type="text" class="form-control datepicker" id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
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
    $("#financial_charge_category_id").change(function () {
        var financial_charge_category_id = $(this).val();

        var url = '/charge';
        $.ajax({
            url: url,
            type: 'post',
            data: {financial_charge_category_id: financial_charge_category_id, _token: csrf_token},
            dataType: 'json',
            success: function (response) {

                var len = response.length;
                $("#amount").empty();

                for (var i = 0; i < len; i++) {
                    var charge = response[i]['charge'];

                    $("#amount").append("<option value='" + charge + "'>" + charge + "</option>");
                }
            }
        });
    });
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
    $(".select2").select2({
        theme: "bootstrap",
        placeholder: "Choose",
        width: 'auto',
        dropdownAutoWidth: true,
        allowClear: true,
    });
</script>


