<div class="block-content">
    <form method="post" action="{{route('unrepresented_slip')}}" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="example-nf-debit">Amount/Debit</label>
            <input type="number" min="0" step=".01" class="form-control amount" onkeydown="return preventNegative(event)" id="input-debit" name="debit"
                   value="{{ $object->debit ?? '' }}" placeholder="Total Amount" readonly>
        </div>
        <div class="form-group">
            <label for="example-nf-reference">Reference</label>
            <input type="text" class="form-control" id="input-reference" name="reference"
                   value="{{ $object->reference ?? '' }}" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Wakala Name</label>
            <select name="wakala_id" id="input-wakala-id" class="form-control select2" required>

                <option value="">Select Wakala</option>

                @foreach ($wakalas as $wakala)
                    <option
                        value="{{$wakala->id}}" {{ ( $wakala->id == $object->wakala_id) ? 'selected' : '' }}> {{ $wakala->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Is Slip Presented?</label>
            <select name="slip_presentation" id="slip_presentation" class="form-control" required>
                {{--                        <option value="">Choose</option>--}}
                @foreach ($slip_presentations as $slip_presentation)
                    <option value="{{ $slip_presentation['name'] }}" {{ ( $slip_presentation['name'] == $object->slip_presentation) ? 'selected' : '' }}> {{ $slip_presentation['name'] }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BankReconciliation">Submit
                </button>
            @endif
        </div>
    </form>
</div>
<script>
    $("#supplier_id").change(function () {
        var supplier_id = $(this).val();
        $.ajax({
            url: '/supplier_beneficiary',
            type: 'POST',
            data: {supplier_id: supplier_id, _token: csrf_token},
            dataType: 'json',
            success: function (response) {
                // Clear previous options
                $("#beneficiary_id").empty().append("<option value=''>Choose</option>");
                $("#beneficiary_account_id").empty().append("<option value=''>Choose</option>");

                // Populate beneficiaries
                response.forEach(function (beneficiary) {
                    $("#beneficiary_id").append("<option value='" + beneficiary.id + "'>" + beneficiary.account_name + "</option>");
                });
            },
            error: function (xhr, status, error) {
                console.error("An error occurred: " + error);
            }
        });
    });
    // When beneficiary is changed, update the account dropdown
    $("#beneficiary_id").change(function () {
        var beneficiary_id = $(this).val();
        var url = '/supplier_beneficiary_account'; // URL to get accounts by beneficiary
        $.ajax({
            url: url,
            type: 'POST',
            data: { beneficiary_id: beneficiary_id, _token: csrf_token }, // CSRF token for security
            dataType: 'json',
            success: function (response) {
                $("#beneficiary_account_id").empty();
                $("#beneficiary_account_id").append("<option value=''>Choose</option>");
                response.forEach(function (beneficiary) {
                    // Ensure this matches the property defined in the PHP response
                    $("#beneficiary_account_id").append("<option value='" + beneficiary.id + "'>" + beneficiary.account_name + "</option>");
                });
            },
            error: function (xhr, status, error) {
                console.error("An error occurred: " + error);
            }
        });
    });


    function preventNegative(event) {
        // Prevent the user from entering a minus sign
        if (event.key === '-' || event.key === 'e') {
            event.preventDefault();
        }
    }
</script>
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

