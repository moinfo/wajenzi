<div class="block-content">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Supplier Name</label>
            <select name="supplier_id" id="supplier_id" class="form-control select2" required>

                <option value="">Select Supplier</option>

                @foreach ($suppliers_with_balances as $supplier)
{{--                    @if($supplier->is_transferred == 'YES'|| $supplier->is_transferred == 'CAN BE BOTH')--}}
                        <option
                            value="{{$supplier->supplier_id}}" {{( $supplier->supplier_id == $object->supplier_id) ? 'selected' : ''}}> {{ $supplier->name  }} </option>
{{--                    @endif--}}
                @endforeach

            </select>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-cost">Beneficiary</label>
                    <select name="beneficiary_id" id="beneficiary_id" class="form-control">
                        <option>Choose</option>
                        @foreach ($beneficiaries as $beneficiary)
                            <option
                                value="{{$beneficiary->id}}" {{ ( $beneficiary->id == $object->beneficiary_id) ? 'selected' : '' }}> {{ $beneficiary->name }} </option>
                        @endforeach

                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-cost">Account</label>
                    <select name="beneficiary_account_id" id="beneficiary_account_id" class="form-control" required>
                        <option value="">Choose</option>
                        @foreach ($beneficiary_accounts as $beneficiary_account)
                            <option
                                value="{{$beneficiary_account->id}}" {{ ( $beneficiary_account->id == $object->beneficiary_account_id) ? 'selected' : '' }}> {{ $beneficiary_account->account }} </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-email">Efd Name</label>
                    <select name="efd_id" id="input-ifd-id" class="form-control select2" required>
                        <option value="">Choose</option>

                    @foreach ($efds as $efd)
                            <option
                                value="{{$efd->id}}" {{ ( $efd->id == $object->efd_id) ? 'selected' : '' }}> {{ $efd->name }} </option>
                        @endforeach

                    </select>
                </div>
            </div>
            <div class="col-sm-6">
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
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-reference">Reference</label>
                    <input type="text" class="form-control" id="input-reference" name="reference"
                           value="{{ $object->reference ?? '' }}" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-email">Supplier Means</label>
                    <select name="type" id="type" class="form-control" required>
{{--                        <option value="">Choose</option>--}}
                        @foreach ($supplier_target_types as $supplier_target_type)
                            <option value="{{ $supplier_target_type['name'] }}" {{ ( $supplier_target_type['name'] == $object->type) ? 'selected' : '' }}> {{ $supplier_target_type['name'] }} </option>
                        @endforeach

                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-email">Payment Type</label>
                    <select name="bank_id" id="bank-id" class="form-control" required>

                            <option value="">Choose</option>

                        @foreach ($banks as $bank)
                            <option
                                value="{{$bank->id}}" {{ ( $bank->id == $object->bank_id) ? 'selected' : '' }}> {{ $bank->name }} </option>
                        @endforeach

                    </select>
                </div>
            </div>
            <div class="col-sm-6">

                <div class="form-group">
                    <label for="example-nf-email">Payment Mode</label>
                    <select name="payment_type" id="payment_type" class="form-control" required>

{{--                        <option value="">Choose</option>--}}

                        @foreach ($bank_reconciliation_payment_types as $bank_reconciliation_payment_type)
                            <option
                                value="{{$bank_reconciliation_payment_type['name']}}" {{ ( $bank_reconciliation_payment_type['name'] == $object->payment_type) ? 'selected' : '' }}> {{ $bank_reconciliation_payment_type['name'] }} </option>
                        @endforeach

                    </select>
                </div>
            </div>
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
            <label for="example-nf-description">Description</label>
            <textarea type="text" row="3" class="form-control" id="input-description"
                      name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-debit">Amount/Debit</label>
            <input type="number" min="0" step=".01" class="form-control amount" onkeydown="return preventNegative(event)" id="input-debit" name="debit"
                   value="{{ $object->debit ?? '' }}" placeholder="Total Amount" required>
        </div>
        @if($object->id ?? null)
            <div class="form-group">
                <label for="example-nf-date">Before Edited Date</label>
                <input type="text" class="form-control" id="input-date-edited" name="date_edited"
                       value="{{ $object->date }}" readonly>
            </div>
        @endif
        <div class="form-group">
            <label for="example-nf-date">Date</label>
            @can('Change Date Bank Deposit')
                <input type="text" class="form-control datepicker" id="input-date" name="date"
                       value="{{ $object->date ?? date('Y-m-d') }}" required>
            @else
                <input type="text" class="form-control" id="input-date" name="date"
                       value="{{ date('Y-m-d') }}" readonly>
            @endcan
            {{--            <input type="date"  min="1997-01-01" max="2030-12-31" class="js-flatpickr form-control bg-white" id="example-flatpickr-default" name="example-flatpickr-default" placeholder="Y-m-d">--}}
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

