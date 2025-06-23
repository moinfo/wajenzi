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
{{--                    <input type="number" class="form-control amount" id="input-amount" name="amount"--}}
{{--                           value="{{ $object->amount ?? '' }}" placeholder="Target Amount" required>--}}
                    <input type="text" class="form-control amount" value="{{ $object->amount ?? '' }}" id="input-amount" name="amount_formatted" placeholder="Target Amount" required>
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
    $(document).ready(function() {
        // Cache DOM elements
        const $efdSelect = $("#efd_id");
        const $targetSelect = $("#supplier_target_id");
        const $amountInput = $("#input-amount");
        const $dateInput = $("#input-date");
        const $descriptionInput = $("#input-description");
        const $form = $("form");

        // Form state management
        const formState = {
            lastEfdId: null,
            lastTargetId: null,
            isProcessing: false,
            originalBalance: 0,
            remainingTarget: 0
        };

        // Amount formatting functions
        function formatAmount(amount) {
            if (!amount) return '';
            // Remove any existing commas and non-numeric characters except decimal point
            amount = amount.toString().replace(/[^\d.]/g, '');

            // Split number into integer and decimal parts
            let [integerPart, decimalPart] = amount.split('.');

            // Add commas to integer part
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

            // Return formatted amount (with decimal if it exists)
            return decimalPart ? `${integerPart}.${decimalPart}` : integerPart;
        }

        function unformatAmount(amount) {
            if (!amount) return 0;
            // Remove all commas and return as number
            return parseFloat(amount.toString().replace(/,/g, '')) || 0;
        }

        // Function to get clean number from formatted string
        function getCleanNumber(text) {
            if (!text) return 0;
            return parseFloat(text.toString().replace(/[^0-9.-]+/g, '').replace(/[()Remaining|Exceeded]/g, '')) || 0;
        }

        // Function to format number with commas
        function formatNumber(number) {
            return Number(number || 0).toLocaleString("en");
        }

        // Function to show/hide form fields
        function toggleFormFields(show) {
            const fields = $(".form-group:not(:first-child)");
            show ? fields.fadeIn() : fields.fadeOut();
        }

        // Function to reset form fields
        function resetFormFields(excludeEfd = false) {
            if (!excludeEfd) {
                $efdSelect.val('').trigger('change');
            }

            $targetSelect.val('').trigger('change');
            $amountInput.val('');
            $descriptionInput.val('');

            // Reset displays
            updateDisplays({
                efd_name: '-',
                beneficiary_name: '-',
                bank_name: '-',
                account_number: '-',
                target_amount: 0,
                bonge_sales: 0,
                balance: 0
            });

            // Reset progress bar
            updateProgressBar(0);

            // Remove any messages
            $("#status-message, #amount-warning").remove();
        }

        // Function to update displays
        function updateDisplays(data) {
            $(".text-primary").next('.stat-value').text(data.efd_name || '-');
            $(".text-info").next('.stat-value').text(data.beneficiary_name || '-');
            $(".stat-subtitle").text(
                `${data.bank_name || '-'} - ${data.account_number || '-'}`
            );
            $(".text-success").next('.stat-value').text(formatAmount(data.target_amount));
            $(".text-warning").next('.stat-value').text(formatAmount(data.bonge_sales));


            const balanceElement = $(".text-purple").next('.stat-value');
            const balance = data.balance;
            balanceElement.text(
                formatAmount(Math.abs(balance)) +
                (balance > 0 ? ' (Remaining)' : ' (Exceeded)')
            );
            balanceElement.removeClass('text-danger text-success')
                .addClass(balance > 0 ? 'text-danger' : 'text-success');
        }

        // Function to update progress bar
        function updateProgressBar(percentage) {
            $('.progress-bar').css('width', `${Math.min(percentage, 100)}%`);
        }

        // Function to show status message
        function showStatusMessage(type, message) {
            $("#status-message").remove();
            const icon = type === 'success' ? 'check' : 'exclamation';
            $form.after(`
            <div id="status-message" class="alert alert-${type} mt-3">
                <i class="si si-${icon} mr-1"></i> ${message}
            </div>
        `);
        }

        // Function to update status badge
        function updateStatusBadge(status, description) {
            const statusBadge = $(".badge");
            const statusContainer = statusBadge.closest('.mt-2');

            statusBadge.removeClass('badge-primary badge-warning badge-success badge-secondary badge-danger')
                .addClass(`badge-${status.toLowerCase()}`)
                .text(status.toUpperCase());

            $('.status-description').remove();
            if (description) {
                statusContainer.after(`
                <div class="status-description mt-2">
                    <small class="text-${status.toLowerCase()} font-italic">${description}</small>
                </div>
            `);
            }
        }

        // Function to validate and update amount display
        function validateAndUpdateAmount(input) {
            const currentAmount = unformatAmount(input.value);
            $("#amount-warning").remove();

            const newBalance = formState.remainingTarget - currentAmount;

            // if((formState.remainingTarget > formState.originalBalance)){
            //     if (currentAmount > formState.originalBalance) {
            //         input.value = formatAmount(input.dataset.lastValidValue || '');
            //         $(input).after(`
            //         <div id="amount-warning" class="text-danger mt-1">
            //             <small><i class="si si-exclamation"></i> Cannot exceed available balance of Bonge Sales ${formatAmount(formState.originalBalance)}</small>
            //         </div>
            //     `);
            //     } else {
            //         input.dataset.lastValidValue = unformatAmount(input.value);
            //     }
            // }else {
            //     if (currentAmount > formState.remainingTarget) {
            //         input.value = formatAmount(input.dataset.lastValidValue || '');
            //         $(input).after(`
            //         <div id="amount-warning" class="text-danger mt-1">
            //             <small><i class="si si-exclamation"></i> Cannot exceed available balance of Supplier Target ${formatAmount(formState.remainingTarget)}</small>
            //         </div>
            //     `);
            //     } else {
            //         input.dataset.lastValidValue = unformatAmount(input.value);
            //     }
            // }

            // if ( (formState.remainingTarget > formState.originalBalance) ?  currentAmount > formState.originalBalance : currentAmount > formState.remainingTarget) {
            //     input.value = formatAmount(input.dataset.lastValidValue || '');
            //     $(input).after(`
            //     <div id="amount-warning" class="text-danger mt-1">
            //         <small><i class="si si-exclamation"></i> Cannot exceed available balance of ${formatAmount(formState.remainingTarget)}</small>
            //     </div>
            // `);
            // } else {
            //     input.dataset.lastValidValue = unformatAmount(input.value);
            // }

            // Update balance display
            const balanceElement = $(".text-purple").next('.stat-value');
            balanceElement.text(
                formatAmount(Math.abs(newBalance)) +
                (newBalance > 0 ? ' (Remaining)' : ' (Exceeded)')
            );
            balanceElement.removeClass('text-danger text-success')
                .addClass(newBalance > 0 ? 'text-danger' : 'text-success');

            // Update progress bar
            updateProgressBar((currentAmount / formState.remainingTarget) * 100);
        }

        // Initialize datepicker
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });

        // Initialize select2
        $(".select2").select2({
            theme: "bootstrap",
            placeholder: "Choose",
            width: 'auto',
            dropdownAutoWidth: true,
            allowClear: true
        });

        // Enhanced amount input handling
        $amountInput.on('input', function(e) {
            let cursorPosition = this.selectionStart;
            const originalLength = this.value.length;

            let value = unformatAmount(this.value);
            const formattedValue = formatAmount(value.toString());

            this.value = formattedValue;

            const lengthDiff = formattedValue.length - originalLength;
            cursorPosition += lengthDiff;
            this.setSelectionRange(cursorPosition, cursorPosition);

            validateAndUpdateAmount(this);
        });

        // EFD change handler
        $efdSelect.on('change', function() {
            const selectedEfdId = $(this).val();

            if (formState.isProcessing) return;

            if (selectedEfdId !== formState.lastEfdId) {
                resetFormFields(true);
                formState.lastEfdId = selectedEfdId;
            }

            if (!selectedEfdId) return;

            formState.isProcessing = true;
            $(this).prop('disabled', true);

            $.ajax({
                url: '/get-bonge-sales',
                type: 'POST',
                data: {
                    efd_id: selectedEfdId,
                    date: $dateInput.val(),
                    _token: csrf_token
                },
                dataType: 'json'
            })
                .done(function(response) {
                    if (!response || typeof response !== 'object') {
                        throw new Error('Invalid server response');
                    }

                    formState.originalBalance = response.balance;
                    updateDisplays(response);

                    // if (response.balance <= 0) {
                    //     toggleFormFields(false);
                    //     updateStatusBadge('danger', 'No balance available for preparation');
                    //     showStatusMessage('danger', 'Cannot proceed: No available balance for preparation. Please select another EFD.');
                    // } else {
                    //     toggleFormFields(true);
                    //     updateStatusBadge('primary', 'Ready to proceed');
                    // }

                    updateProgressBar((response.used_amount / response.bonge_sales) * 100);
                })
                .fail(function(xhr, status, error) {
                    console.error("Error fetching Bonge sales:", error);
                    showStatusMessage('danger', "Failed to fetch EFD data. Please try again.");
                })
                .always(function() {
                    formState.isProcessing = false;
                    $efdSelect.prop('disabled', false);
                });
        });

        // Target change handler
        $targetSelect.on('change', function() {
            const selectedTargetId = $(this).val();

            if (formState.isProcessing) return;

            if (selectedTargetId !== formState.lastTargetId) {
                $amountInput.val('');
                $descriptionInput.val('');
                formState.lastTargetId = selectedTargetId;
            }

            if (!selectedTargetId) {
                // Reset beneficiary info when no target is selected
                $(".text-info").next('.stat-value').text('-');
                $(".stat-subtitle").text('- - -');
                return;
            }


            formState.isProcessing = true;
            $(this).prop('disabled', true);

            $.ajax({
                url: '/get-target-details',
                type: 'POST',
                data: {
                    target_id: selectedTargetId,
                    date: $dateInput.val(),
                    _token: csrf_token
                },
                dataType: 'json'
            })
                .done(function(response) {
                    if (!response || typeof response !== 'object') {
                        throw new Error('Invalid server response');
                    }

                    $(".text-info").next('.stat-value').text(response.beneficiary_name || '-');
                    $(".stat-subtitle").text(
                        `${response.bank_name || '-'} - ${response.account_number || '-'}`
                    );

                    const bongeBalance = getCleanNumber($(".text-purple").next('.stat-value').text());
                    const remainingTarget = response.target_amount - response.used_amount;

                    formState.remainingTarget = remainingTarget;

                    // if (bongeBalance < remainingTarget) {
                    //     toggleFormFields(false);
                    //     updateStatusBadge('danger', 'Insufficient Balance');
                    //     showStatusMessage('danger', 'You cannot continue because amount not available to prepare. Please choose another supplier target list.');
                    // } else {
                        toggleFormFields(true);
                        updateStatusBadge('primary', 'Ready to proceed');
                        showStatusMessage('success', 'You can proceed with the target preparation.');

                        // Set max allowed amount
                        $amountInput.attr('max', Math.min(bongeBalance, remainingTarget));
                    // }
                })
                .fail(function(xhr, status, error) {
                    // Reset beneficiary info on error
                    $(".text-info").next('.stat-value').text('-');
                    $(".stat-subtitle").text('- - -');
                    console.error("Error fetching target details:", error);
                    showStatusMessage('danger', "Failed to fetch target details. Please try again.");
                })
                .always(function() {
                    formState.isProcessing = false;
                    $targetSelect.prop('disabled', false);
                });
        });

        // Date change handler
        $dateInput.on('change', function() {
            resetFormFields();
        });

        // Form submission handler
        $form.on('submit', function(e) {
            if (formState.isProcessing) {
                e.preventDefault();
                return;
            }

            const amount = unformatAmount($amountInput.val());

            if (!$('#unformatted_amount').length) {
                $('<input>').attr({
                    type: 'hidden',
                    id: 'unformatted_amount',
                    name: 'amount'
                }).appendTo($form);
            }

            $('#unformatted_amount').val(amount);

            if (!$efdSelect.val() || !$targetSelect.val() || !amount) {
                e.preventDefault();
                showStatusMessage('danger', "Please fill in all required fields.");
                return;
            }

            const balance = unformatAmount($(".text-purple").next('.stat-value').text());
            // if (amount > balance) {
            //     e.preventDefault();
            //     showStatusMessage('danger', "Amount exceeds available balance.");
            //     return;
            // }
        });

        // Initialize amount formatting if there's an existing value
        if ($amountInput.val()) {
            $amountInput.val(formatAmount($amountInput.val()));
        }

        // Initialize form
        resetFormFields();
    });
</script>

