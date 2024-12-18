
<style>
    .block-rounded {
        border-radius: 0.25rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        background-color: #fff;
        margin-bottom: 1rem;
        transition: transform 0.2s ease-in-out;
    }

    .block-rounded:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.12);
    }

    .block-content {
        padding: 1.25rem;
    }

    .border-3x {
        border-left-width: 4px !important;
    }

    .border-primary {
        border-left-color: #5c80d1 !important;
    }

    .border-info {
        border-left-color: #3c90df !important;
    }

    .border-success {
        border-left-color: #46c37b !important;
    }

    .border-warning {
        border-left-color: #f3b760 !important;
    }

    .border-purple {
        border-left-color: #8657ff !important;
    }

    .text-primary {
        color: #5c80d1 !important;
    }

    .text-info {
        color: #3c90df !important;
    }

    .text-success {
        color: #46c37b !important;
    }

    .text-warning {
        color: #f3b760 !important;
    }

    .text-purple {
        color: #8657ff !important;
    }

    .text-danger {
        color: #e04f1a !important;
    }

    .bg-primary-dark {
        background-color: #3e4a59;
    }

    .badge {
        padding: 0.5em 1em;
        font-weight: 600;
    }

    .badge-primary {
        background-color: #5c80d1;
        color: #fff;
    }

    .badge-success {
        background-color: #46c37b;
        color: #fff;
    }

    .badge-warning {
        background-color: #f3b760;
        color: #fff;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: #fff;
    }

    .progress {
        background-color: #e9ecef;
        border-radius: 2px;
        overflow: hidden;
    }
</style>
<div class="block">
    <div class="block-header bg-success">
        <h3 class="block-title text-white">Supplier Target Summary</h3>
    </div>
    <div class="block-content pb-2">
        <div class="row">
            <!-- Summary Cards -->
            <div class="col-md-3">
                <div class="block block-rounded border-left border-primary border-3x">
                    <div class="block-content">
                        <div class="font-size-sm font-w600 text-uppercase text-primary">Supplier Name</div>
                        <div class="font-size-h4 font-w600 text-dark">{{ $efd->name ?? null }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded border-left border-info border-3x">
                    <div class="block-content">
                        <div class="font-size-sm font-w600 text-uppercase text-info">Beneficiary</div>
                        <div class="font-size-h4 font-w600 text-dark">{{ $beneficiary->name ?? null }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded border-left border-success border-3x">
                    <div class="block-content">
                        <div class="font-size-sm font-w600 text-uppercase text-success">Target Amount</div>
                        <div class="font-size-h4 font-w600 text-dark">{{ number_format($object->amount ?? 0) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="block block-rounded border-left border-warning border-3x">
                    <div class="block-content">
                        <div class="font-size-sm font-w600 text-uppercase text-warning">Bonge Sales</div>
                        <div class="font-size-h4 font-w600 text-dark">{{ number_format($bonge_sales ?? 0) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance and Status Row -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="block block-rounded border-left border-purple border-3x">
                    <div class="block-content">
                        <div class="font-size-sm font-w600 text-uppercase text-purple">Balance</div>
                        <div class="font-size-h4 font-w600 text-dark">
                            @php
                                $balance = ($object->amount ?? 0) - ($bonge_sales ?? 0);
                                $balanceClass = $balance > 0 ? 'text-danger' : 'text-success';
                            @endphp
                            <span class="{{ $balanceClass }}">
                                {{ number_format(abs($balance)) }}
                                @if($balance > 0)
                                    (Remaining)
                                @else
                                    (Exceeded)
                                @endif
                            </span>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            @php
                                $percentage = $object->amount > 0 ? min(($bonge_sales ?? 0) / $object->amount * 100, 100) : 0;
                            @endphp
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="block block-rounded bg-light">
                    <div class="block-content">
                        <div class="font-size-sm font-w600 text-uppercase text-muted">Status</div>
                        <div class="mt-2">
                            @php
                                $statusClass = '';
                                $statusText = $object->status ?? 'Pending';

                                switch(strtolower($statusText)) {
                                    case 'active':
                                        $statusClass = 'badge-primary';
                                        break;
                                    case 'completed':
                                        $statusClass = 'badge-success';
                                        break;
                                    case 'pending':
                                        $statusClass = 'badge-warning';
                                        break;
                                    default:
                                        $statusClass = 'badge-secondary';
                                }
                            @endphp
                            <span class="badge {{ $statusClass }} font-size-sm">{{ $statusText }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<div class="block-content">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-email">Efd Name</label>
                    <select name="efd_id" id="efd_id" class="form-control" required>

                        <option value="">Select Supplier</option>

                        @foreach ($efds as $efd)
                            <option value="{{ $efd->id }}" {{ ( $efd->id == $object->efd_id) ? 'selected' : '' }}> {{ $efd->name }} </option>
                        @endforeach

                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-email">Today Supplier Target List</label>
                    <select name="supplier_target_id" id="supplier_target_id" class="form-control" required>
                        <option value="">Select Target</option>
                        @foreach ($todayTargets as $target)
                            <option value="{{ $target->id }}"
                                {{ ($target->id == $object->supplier_target_id) ? 'selected' : '' }}>
                                {{ $target->beneficiary_name }} - Balance: {{ number_format($target->remaining_balance) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="example-nf-amount">Description</label>
                    <textarea type="text" class="form-control description" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-amount">Amount</label>
                    <input type="number" class="form-control amount" id="input-amount" name="amount"
                           value="{{ $object->amount ?? '' }}" placeholder="Target Amount" required>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="example-nf-date">Date</label>
                    <input type="text" class="form-control datepicker" id="input-date" name="date"
                           value="{{ $object->date ?? date('Y-m-d') }}" required>
                </div>
            </div>
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
    $("#efd_id").change(function () {
        var efd_id = $(this).val();
        var date = $("#input-date").val();

        // AJAX call for Bonge sales
        $.ajax({
            url: '/get-bonge-sales',
            type: 'POST',
            data: {
                efd_id: efd_id,
                date: date,
                _token: csrf_token
            },
            dataType: 'json',
            success: function (response) {
                // Update the Bonge sales amount in the summary
                $(".block-content .text-warning").next('.font-size-h4').text(
                    Number(response.bonge_sales).toLocaleString()
                );

                // Recalculate balance
                var targetAmount = parseFloat($(".text-success").next('.font-size-h4').text().replace(/,/g, ''));
                var bongeSales = parseFloat(response.bonge_sales);
                var balance = targetAmount - bongeSales;

                // Update balance display
                var balanceElement = $(".text-purple").next('.font-size-h4').find('span');
                balanceElement.text(
                    Number(Math.abs(balance)).toLocaleString() +
                    (balance > 0 ? ' (Remaining)' : ' (Exceeded)')
                );
                balanceElement.removeClass('text-danger text-success')
                    .addClass(balance > 0 ? 'text-danger' : 'text-success');

                // Update progress bar
                var percentage = targetAmount > 0 ? Math.min((bongeSales / targetAmount) * 100, 100) : 0;
                $('.progress-bar').css('width', percentage + '%');
            },
            error: function (xhr, status, error) {
                console.error("An error occurred while fetching Bonge sales: " + error);
            }
        });
    });

    $("#supplier_target_id").change(function () {
        var target_id = $(this).val();
        var date = $("#input-date").val();

        $.ajax({
            url: '/get-target-details',
            type: 'POST',
            data: {
                target_id: target_id,
                date: date,
                _token: csrf_token
            },
            dataType: 'json',
            success: function (response) {
                // Update summary cards
                $(".text-primary").next('.font-size-h4').text(response.supplier_name);
                $(".text-info").next('.font-size-h4').text(
                    response.beneficiary_name +
                    ' (' + response.bank_name + ' - ' + response.account_number + ')'
                );
                $(".text-success").next('.font-size-h4').text(
                    Number(response.target_amount).toLocaleString()
                );

                // Update status
                var statusBadge = $(".badge");
                statusBadge.removeClass('badge-primary badge-warning')
                    .addClass(response.is_available ? 'badge-primary' : 'badge-warning')
                    .text(response.is_available ? 'Available' : 'Not Available');

                // Update amount input max value
                $("#input-amount").attr('max', response.remaining_balance);

                // Update balance and progress
                updateBalanceAndProgress(
                    response.target_amount,
                    response.used_amount,
                    response.remaining_balance
                );
            },
            error: function (xhr, status, error) {
                console.error("An error occurred: " + error);
            }
        });
    });

    // Helper function to update balance and progress
    function updateBalanceAndProgress(targetAmount, usedAmount, remainingBalance) {
        var balanceElement = $(".text-purple").next('.font-size-h4').find('span');
        balanceElement.text(
            Number(Math.abs(remainingBalance)).toLocaleString() +
            (remainingBalance > 0 ? ' (Remaining)' : ' (Exceeded)')
        );
        balanceElement.removeClass('text-danger text-success')
            .addClass(remainingBalance > 0 ? 'text-danger' : 'text-success');

        var percentage = targetAmount > 0 ? Math.min((usedAmount / targetAmount) * 100, 100) : 0;
        $('.progress-bar').css('width', percentage + '%');
    }

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

