<div class="block-content">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
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
            <label for="example-nf-email">Beneficiary</label>
            <select name="beneficiary_id" id="beneficiary_id" class="form-control select2" required>

                <option value="">Select Supplier</option>

                @foreach ($beneficiaries as $beneficiary)
                    <option value="{{ $beneficiary->id }}" {{ ( $beneficiary->id == $object->beneficiary_id) ? 'selected' : '' }}> {{ $beneficiary->name }} </option>
                @endforeach

            </select>
        </div>

        <div class="form-group">
            <label for="example-nf-email">Type</label>
            <select name="type" id="type" class="form-control" required>
                @foreach ($supplier_target_types as $supplier_target_type)
                    <option value="{{ $supplier_target_type['name'] }}" {{ ( $supplier_target_type['name'] == $object->type) ? 'selected' : '' }}> {{ $supplier_target_type['name'] }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-amount">Description</label>
            <textarea type="text" class="form-control description" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-amount">Target</label>
            <input type="number" class="form-control amount" id="input-amount" name="amount"
                   value="{{ $object->amount ?? '' }}" placeholder="Target Amount" required>
        </div>

        <div class="form-group">
            <label for="example-nf-date">Date</label>
            <input type="text" class="form-control datepicker" id="input-date" name="date"
                   value="{{ $object->date ?? date('Y-m-d') }}" required>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="SupplierTarget">Submit
                </button>
            @endif
        </div>
    </form>
</div>
<script>
    $("input.amount").each((i, ele) => {
        let clone = $(ele).clone(false)
        clone.attr("type", "text")
        let ele1 = $(ele)
        clone.val(Number(ele1.val()).toLocaleString("en"))
        $(ele).after(clone)
        $(ele).hide()
        clone.mouseenter(() => {

            ele1.show()
            clone.hide()
        })
        setInterval(() => {
            let newv = Number(ele1.val()).toLocaleString("en")
            if (clone.val() != newv) {
                clone.val(newv)
            }
        }, 10)

        $(ele).mouseleave(() => {
            $(clone).show()
            $(ele1).hide()
        })


    });
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
    $(".select2").select2({
        theme: "bootstrap",
        placeholder: "Choose",
        width: 'auto',
        dropdownAutoWidth: true,
        allowClear: true,
    });
</script>

