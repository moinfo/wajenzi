<style>
    .status-description {
        font-size: 0.875rem;
        margin-top: 0.5rem;
    }

    .status-description small {
        display: block;
        line-height: 1.4;
    }

    .font-italic {
        font-style: italic;
    }
    /* Card Base Styles */
    .block-rounded {
        border-radius: 0.75rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        background-color: #fff;
        margin-bottom: 1rem;
        transition: all 0.3s ease;
    }

    .block-rounded:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    /* Header Styles */
    .block-header {
        padding: 1.25rem;
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
        background: linear-gradient(to right, #34d399, #10b981);
    }

    .block-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.25rem;
    }

    /* Content Styles */
    .block-content {
        padding: 1.5rem;
    }

    /* Card Border Styles */
    .border-left {
        border-left-width: 4px;
        border-left-style: solid;
    }

    .border-primary { border-left-color: #3b82f6 !important; }
    .border-info { border-left-color: #06b6d4 !important; }
    .border-success { border-left-color: #10b981 !important; }
    .border-warning { border-left-color: #f97316 !important; }
    .border-purple { border-left-color: #8b5cf6 !important; }

    /* Text Colors */
    .text-primary { color: #3b82f6 !important; }
    .text-info { color: #06b6d4 !important; }
    .text-success { color: #10b981 !important; }
    .text-warning { color: #f97316 !important; }
    .text-purple { color: #8b5cf6 !important; }
    .text-danger { color: #ef4444 !important; }

    /* Badge Styles */
    .badge {
        padding: 0.5rem 1rem;
        font-weight: 600;
        border-radius: 9999px;
    }

    /* Progress Bar */
    .progress {
        background-color: #f3f4f6;
        border-radius: 9999px;
        height: 0.5rem;
        overflow: hidden;
    }

    .progress-bar {
        border-radius: 9999px;
    }

    /* Card Content Styles */
    .stat-card {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
    }

    .stat-icon {
        width: 2rem;
        height: 2rem;
        opacity: 0.8;
    }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }

    .stat-value {
        font-size: 0.95rem;
        font-weight: 300;
        color: #1f2937;
    }

    .stat-subtitle {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
</style>
<div class="block">
    <div class="block-header">
        <h3 class="block-title text-white">
            <i class="si si-target me-2"></i>
            Supplier Target Summary
        </h3>
    </div>
    <div class="block-content pb-2">
        <div class="row">
            <!-- Supplier Card -->
            <div class="col-md-3">
                <div class="block block-rounded border-left border-primary">
                    <div class="block-content stat-card">
                        <div class="stat-content">
                            <div class="stat-label text-primary">Supplier Name</div>
                            <div class="stat-value">{{ $efd->name ?? null }}</div>
                        </div>
                        <i class="si si-users stat-icon text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Beneficiary Card -->
            <div class="col-md-3">
                <div class="block block-rounded border-left border-info">
                    <div class="block-content stat-card">
                        <div class="stat-content">
                            <div class="stat-label text-info">Beneficiary</div>
                            <div class="stat-value">{{ $beneficiary->name ?? null }}</div>
                            <div class="stat-subtitle">{{ $beneficiary->bank_name ?? '' }} - {{ $beneficiary->account_number ?? '' }}</div>
                        </div>
                        <i class="si si-graph stat-icon text-info"></i>
                    </div>
                </div>
            </div>

            <!-- Target Amount Card -->
            <div class="col-md-3">
                <div class="block block-rounded border-left border-success">
                    <div class="block-content stat-card">
                        <div class="stat-content">
                            <div class="stat-label text-success">Target Amount</div>
                            <div class="stat-value">{{ number_format($object->amount ?? 0) }}</div>
                        </div>
                        <i class="si si-target stat-icon text-success"></i>
                    </div>
                </div>
            </div>

            <!-- Bonge Sales Card -->
            <div class="col-md-3">
                <div class="block block-rounded border-left border-warning">
                    <div class="block-content stat-card">
                        <div class="stat-content">
                            <div class="stat-label text-warning">Bonge Sales</div>
                            <div class="stat-value">{{ number_format($bonge_sales ?? 0) }}</div>
                        </div>
                        <i class="si si-bag stat-icon text-warning"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Balance and Status Row -->
        <div class="row mt-4">
            <!-- Balance Card -->
            <div class="col-md-6">
                <div class="block block-rounded border-left border-purple">
                    <div class="block-content">
                        <div class="stat-card mb-3">
                            <div class="stat-content">
                                <div class="stat-label text-purple">Balance</div>
                                @php
                                    // Calculate balance
                                    $balance = ($object->amount ?? 0) - ($bonge_sales ?? 0);
                                    // Determine balance class
                                    $balanceClass = $balance > 0 ? 'text-danger' : 'text-success';
                                    // Calculate percentage for progress bar
                                    $percentage = 0;
                                    if (isset($object->amount) && $object->amount > 0) {
                                        $percentage = min(($bonge_sales ?? 0) / $object->amount * 100, 100);
                                    }
                                    // Determine status
                                    $statusText = $object->status ?? 'Pending';
                                    $statusClass = '';
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
                                <div class="stat-value {{ $balanceClass }}">
                                    {{ number_format(abs($balance)) }}
                                    {{ $balance > 0 ? '(Remaining)' : '(Exceeded)' }}
                                </div>
                            </div>
                            <i class="si si-calculator stat-icon text-purple"></i>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: {{ $percentage }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Card -->
            <div class="col-md-6">
                <div class="block block-rounded border-left border-gray">
                    <div class="block-content stat-card">
                        <div class="stat-content">
                            <div class="stat-label text-muted">Status</div>
                            <div class="mt-2">
                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                            </div>
                        </div>
                        <i class="si si-chart stat-icon text-muted"></i>
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
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="example-nf-amount">Description</label>
                    <textarea type="text" class="form-control description" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="SupplierTargetPreparation">Submit
                </button>
            @endif
        </div>
    </form>
</div>
<script>
    // Function to hide/show form fields
    function toggleFormFields(show) {
        if (show) {
            $(".form-group:not(:first-child)").fadeIn();
        } else {
            $(".form-group:not(:first-child)").fadeOut();
        }
    }

    let debounceTimer;
    let originalBalance = 0;

    // Function to clear amount input and reset summary
    // Updated clearAmountAndSummary function
    function clearAmountAndSummary() {
        $("#input-amount").val('');
        $("#input-amount").next('input').val(''); // Clear formatted display input
        recalculateBalance(0); // Reset balance with 0
    }

    // Optimized balance calculation function
    // Updated balance calculation function
    function recalculateBalance(currentAmount) {
        // Get bonge sales and current used amount from the summary
        const bongeSales = parseFloat($(".text-warning").next('.stat-value').text().replace(/,/g, '')) || 0;
        const alreadyUsedAmount = parseFloat($("#efd_id option:selected").data('used-amount')) || 0;

        // Add current input amount to already used amount
        const totalUsedAmount = alreadyUsedAmount + (currentAmount || 0);

        // Calculate remaining balance
        const remainingBalance = bongeSales - totalUsedAmount;

        // Update Balance Display
        const balanceElement = $(".text-purple").next('.stat-value');
        balanceElement.text(
            Number(Math.abs(remainingBalance)).toLocaleString() +
            (remainingBalance > 0 ? ' (Remaining)' : ' (Exceeded)')
        );

        // Update balance color
        balanceElement.removeClass('text-danger text-success')
            .addClass(remainingBalance > 0 ? 'text-danger' : 'text-success');

        // Update Progress Bar based on bonge sales
        const percentage = bongeSales > 0 ? Math.min((totalUsedAmount / bongeSales) * 100, 100) : 0;
        $('.progress-bar').css('width', percentage + '%');
    }

    // Optimized amount input handler
    $("#input-amount").on('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const amount = parseFloat(this.value) || 0;
            recalculateBalance(amount);
        }, 100); // Debounce delay of 100ms
    });

    // Update EFD change handler
    $("#efd_id").change(function () {
        clearAmountAndSummary();
        var efd_id = $(this).val();
        var date = $("#input-date").val();

        if (!efd_id) return;

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
                // Update EFD Name
                $(".text-primary").next('.stat-value').text(response.efd_name);

                // Update Bonge Sales
                $(".text-warning").next('.stat-value').text(
                    Number(response.bonge_sales).toLocaleString()
                );

                // Update Balance
                const balanceElement = $(".text-purple").next('.stat-value');
                balanceElement.text(
                    Number(Math.abs(response.balance)).toLocaleString() +
                    (response.balance > 0 ? ' (Remaining)' : ' (Exceeded)')
                );
                balanceElement.removeClass('text-danger text-success')
                    .addClass(response.balance > 0 ? 'text-danger' : 'text-success');

                // Store original balance
                originalBalance = response.balance;

                // Check Balance condition
                if (response.balance <= 0) {
                    // Hide all fields except EFD
                    toggleFormFields(false);

                    // Update status to Not Available with red color
                    var statusBadge = $(".badge");
                    statusBadge.removeClass('badge-primary badge-warning badge-success badge-secondary')
                        .addClass('badge-danger')
                        .text('NOT AVAILABLE');

                    // Add status description
                    var statusContainer = statusBadge.closest('.mt-2');
                    if (!statusContainer.next('.status-description').length) {
                        statusContainer.after(
                            '<div class="status-description mt-2">' +
                            '<small class="text-danger font-italic">No balance available for preparation</small>' +
                            '</div>'
                        );
                    }

                    // Add warning message below the form
                    $("#status-message").remove();
                    $('form').after(
                        '<div id="status-message" class="alert alert-danger mt-3">' +
                        '<i class="si si-exclamation mr-1"></i> ' +
                        'Cannot proceed: No available balance for preparation. Please select another EFD.' +
                        '</div>'
                    );
                } else {
                    // Show fields and update status
                    toggleFormFields(true);
                    var statusBadge = $(".badge");
                    statusBadge.removeClass('badge-primary badge-warning badge-success badge-secondary badge-danger')
                        .addClass('badge-primary')
                        .text('AVAILABLE');

                    // Remove any existing status description and message
                    $('.status-description, #status-message').remove();
                }

                // Update Progress Bar
                const percentage = response.bonge_sales > 0 ?
                    Math.min((response.used_amount / response.bonge_sales) * 100, 100) : 0;
                $('.progress-bar').css('width', percentage + '%');
            },
            error: function (xhr, status, error) {
                console.error("An error occurred while fetching Bonge sales: " + error);
            }
        });
    });


    // Updated supplier target change handler
    $("#supplier_target_id").change(function () {
        var target_id = $(this).val();
        var date = $("#input-date").val();

        if (!target_id) return;

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
                // Update beneficiary info and target amount
                $(".text-info").next('.stat-value').text(response.beneficiary_name);
                $(".stat-subtitle").text(response.bank_name + ' - ' + response.account_number);
                $(".text-success").next('.stat-value').text(
                    Number(response.target_amount).toLocaleString()
                );

                // Get current bonge sales balance and target remaining
                const bongeBalance = parseFloat($(".text-purple").next('.stat-value').text().replace(/,/g, '')) || 0;
                const remainingTarget = response.target_amount - response.used_amount;

                // Remove any existing status message
                $("#status-message").remove();

                // Update status and form visibility based on balance vs target
                var statusBadge = $(".badge");
                var statusContainer = statusBadge.closest('.mt-2');

                if (bongeBalance > remainingTarget) {
                    toggleFormFields(false);
                    statusBadge.removeClass('badge-primary badge-warning badge-success badge-secondary')
                        .addClass('badge-danger')
                        .text('UNAVAILABLE');

                    // Add status description
                    if (!statusContainer.next('.status-description').length) {
                        statusContainer.after(
                            '<div class="status-description mt-2">' +
                            '<small class="text-danger font-italic">Insufficient Balance</small>' +
                            '</div>'
                        );
                    }

                    // Add warning message below the form
                    $('form').after(
                        '<div id="status-message" class="alert alert-danger mt-3">' +
                        '<i class="si si-exclamation mr-1"></i> ' +
                        'You cannot continue because amount not available to prepare. Please choose another supplier target list.' +
                        '</div>'
                    );
                } else {
                    toggleFormFields(true);
                    statusBadge.removeClass('badge-primary badge-warning badge-success badge-secondary badge-danger')
                        .addClass('badge-primary')
                        .text('AVAILABLE');

                    // Add status description
                    if (!statusContainer.next('.status-description').length) {
                        statusContainer.after(
                            '<div class="status-description mt-2">' +
                            '<small class="text-success font-italic">Ready to proceed</small>' +
                            '</div>'
                        );
                    }

                    // Add success message below the form
                    $('form').after(
                        '<div id="status-message" class="alert alert-success mt-3">' +
                        '<i class="si si-check mr-1"></i> ' +
                        'You can proceed with the target preparation.' +
                        '</div>'
                    );
                }

                // Set max allowed amount
                $("#input-amount").attr('max', Math.min(bongeBalance, remainingTarget));
            },
            error: function (xhr, status, error) {
                console.error("An error occurred: " + error);
            }
        });
    });

    // Update the amount formatting code
    // Add this function to validate amount
    function validateAmount(input) {
        const bongeBalance = parseFloat($(".text-purple").next('.stat-value').text().replace(/,/g, '').replace(/[()Remaining|Exceeded]/g, '')) || 0;
        const enteredAmount = parseFloat(input.value) || 0;

        if (enteredAmount > bongeBalance) {
            input.value = bongeBalance; // Set to max allowed
            // Show validation message
            if (!$("#amount-warning").length) {
                $(input).after(
                    '<div id="amount-warning" class="text-danger mt-1">' +
                    '<small><i class="si si-exclamation"></i> Amount cannot exceed available balance of ' +
                    Number(bongeBalance).toLocaleString() + '</small>' +
                    '</div>'
                );
            }
        } else {
            $("#amount-warning").remove();
        }
        return input.value;
    }

    function getCleanNumber(text) {
        return parseFloat(text.replace(/[^0-9.-]+/g, '').replace(/[()Remaining|Exceeded]/g, '')) || 0;
    }

    function validateAndUpdateAmount(input) {
        const currentAmount = parseFloat(input.value) || 0;

        $("#amount-warning").remove();

        // Calculate new balance using original balance
        const newBalance = originalBalance - currentAmount;

        // If amount exceeds original balance
        if (currentAmount > originalBalance) {
            input.value = input.dataset.lastValidValue || '';
            $(input).after(
                '<div id="amount-warning" class="text-danger mt-1">' +
                '<small><i class="si si-exclamation"></i> Cannot exceed available balance of ' +
                Number(originalBalance).toLocaleString() + '</small>' +
                '</div>'
            );
        } else {
            input.dataset.lastValidValue = input.value;
        }

        // Update balance display
        const balanceElement = $(".text-purple").next('.stat-value');
        balanceElement.text(
            Number(Math.abs(newBalance)).toLocaleString() +
            (newBalance > 0 ? ' (Remaining)' : ' (Exceeded)')
        );
        balanceElement.removeClass('text-danger text-success')
            .addClass(newBalance > 0 ? 'text-danger' : 'text-success');

        // Update progress bar using original balance
        const percentage = originalBalance > 0 ? Math.min((currentAmount / originalBalance) * 100, 100) : 0;
        $('.progress-bar').css('width', percentage + '%');

        return input.value;
    }

    $("#input-amount, input.amount").on('input keyup', function(e) {
        clearTimeout(debounceTimer);

        const currentValue = parseFloat(this.value) || 0;

        if (currentValue > originalBalance) {
            e.preventDefault();
            this.value = this.dataset.lastValidValue || '';
            return false;
        }

        debounceTimer = setTimeout(() => {
            validateAndUpdateAmount(this);

            // Update formatted display if exists
            const formattedInput = $(this).next('input[type="text"]');
            if (formattedInput.length) {
                formattedInput.val(Number(this.value || 0).toLocaleString("en"));
            }
        }, 100);
    });

    // Update amount formatting
    $("input.amount").each((i, ele) => {
        let clone = $(ele).clone(false);
        clone.attr("type", "text");
        let ele1 = $(ele);

        // Initialize with formatted value
        clone.val(Number(ele1.val()).toLocaleString("en"));
        $(ele).after(clone);
        $(ele).hide();

        clone.mouseenter(() => {
            ele1.show();
            clone.hide();
        });

        ele1.mouseleave(() => {
            if (document.activeElement !== ele1[0]) {  // Only hide if not focused
                clone.show();
                ele1.hide();
            }
        });
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
    $(document).ready(function() {
        const amountInput = $("#input-amount");

        // Single function to format number
        function formatNumber(value) {
            if (!value) return '';
            // Remove any non-digits/decimal
            value = value.toString().replace(/[^\d.]/g, '');
            // Ensure only one decimal point
            const parts = value.split('.');
            value = parts[0] + (parts.length > 1 ? '.' + parts[1] : '');
            // Add commas to whole number part
            return value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Single function to clean number
        function cleanNumber(value) {
            return value.toString().replace(/,/g, '');
        }

        // Debounced version of validateAndUpdateAmount
        const debouncedValidate = _.debounce((input) => {
            const currentAmount = parseFloat(cleanNumber(input.value)) || 0;
            const newBalance = originalBalance - currentAmount;

            // Update balance display without changing input
            const balanceElement = $(".text-purple").next('.stat-value');
            balanceElement.text(
                Number(Math.abs(newBalance)).toLocaleString() +
                (newBalance > 0 ? ' (Remaining)' : ' (Exceeded)')
            );
            balanceElement.removeClass('text-danger text-success')
                .addClass(newBalance > 0 ? 'text-danger' : 'text-success');

            // Update progress bar
            const percentage = originalBalance > 0 ?
                Math.min((currentAmount / originalBalance) * 100, 100) : 0;
            $('.progress-bar').css('width', percentage + '%');
        }, 100);

        // Single input handler
        amountInput.on('input', function(e) {
            const cursorPos = this.selectionStart;
            const originalLength = this.value.length;

            // Get clean number and format
            let value = cleanNumber(this.value);
            const formatted = formatNumber(value);

            // Only update if actually changed to prevent cursor jumping
            if (this.value !== formatted) {
                this.value = formatted;

                // Adjust cursor position
                const newCursorPos = cursorPos + (formatted.length - originalLength);
                this.setSelectionRange(newCursorPos, newCursorPos);
            }

            // Validate and update balance
            debouncedValidate(this);
        });

        // Remove any other input/keyup handlers
        amountInput.off('keyup');

        // Format initial value if exists
        if (amountInput.val()) {
            amountInput.val(formatNumber(amountInput.val()));
        }
    });

    // Add styles for right-aligned input
    $('<style>')
        .text(`
        #input-amount {
            text-align: right;
            direction: ltr;
        }
    `)
        .appendTo('head');
</script>

