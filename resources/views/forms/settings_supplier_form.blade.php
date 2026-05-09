<div class="block-content">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Name</label>
            <input type="text" class="form-control" id="input-supplier-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Supplier Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-phone">Phone</label>
            <input type="text" class="form-control" id="input-phone" name="phone" value="{{ $object->phone ?? '' }}" placeholder="Phone Number">
        </div>
        <div class="form-group">
            <label for="example-nf-address">Address</label>
            <input type="text" class="form-control" id="input-address" name="address" value="{{ $object->address ?? '' }}" placeholder="Address">
        </div>
        <div class="form-group">
            <label for="example-nf-vrn" class="control-label required">VRN</label>
            <input type="text" class="form-control" id="input-vrn" name="vrn" value="{{ $object->vrn ?? '' }}" placeholder="Supplier VRN" required>
        </div>
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">System</label>
            <select name="system_id" id="input-system-id" class="form-control" required>
                @foreach ($systems as $system)
                    <option value="{{ $system['id'] }}" {{ ( $system['id'] == $object->system_id) ? 'selected' : '' }}> {{ $system['name'] }} </option>
                @endforeach
            </select>
        </div>

        {{-- ── Payment Details ─────────────────────────────────────────── --}}
        @php
            $pm = old('payment_method', $object->payment_method ?? '');
        @endphp
        <fieldset class="form-group" style="border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;margin-top:8px;">
            <legend class="control-label" style="font-size:13px;font-weight:700;width:auto;padding:0 8px;margin-bottom:8px;">
                Payment Details
            </legend>

            <div class="form-group mb-3">
                <label class="control-label d-block" style="font-size:12px;color:#475569;">Means of Payment</label>
                <div class="btn-group btn-group-toggle d-flex" data-toggle="buttons" id="supplier-payment-method-group">
                    @foreach([
                        'BANK'   => ['label' => 'Bank Acc',     'icon' => 'fa-university'],
                        'MOBILE' => ['label' => 'Mobile',       'icon' => 'fa-mobile-alt'],
                        'CASH'   => ['label' => 'Cash in Hand', 'icon' => 'fa-money-bill-wave'],
                    ] as $val => $meta)
                        <label class="btn btn-outline-primary {{ $pm === $val ? 'active' : '' }}" style="flex:1;">
                            <input type="radio" name="payment_method" value="{{ $val }}" autocomplete="off"
                                   {{ $pm === $val ? 'checked' : '' }}>
                            <i class="fa {{ $meta['icon'] }} mr-1"></i> {{ $meta['label'] }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Bank fields --}}
            <div id="supplier-payment-bank" class="supplier-payment-block" style="display:{{ $pm === 'BANK' ? 'block' : 'none' }};">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:12px;">Bank Name</label>
                        <input type="text" name="bank_name" class="form-control" list="supplier-bank-list"
                               value="{{ old('bank_name', $object->bank_name ?? '') }}" placeholder="e.g. NMB, CRDB, NBC…">
                        <datalist id="supplier-bank-list">
                            <option value="NMB">
                            <option value="CRDB">
                            <option value="NBC">
                            <option value="Stanbic">
                            <option value="Exim Bank">
                            <option value="KCB">
                            <option value="DTB">
                            <option value="Equity Bank">
                            <option value="Akiba Commercial Bank">
                            <option value="Absa Bank">
                            <option value="Standard Chartered">
                        </datalist>
                    </div>
                    <div class="form-group col-md-6">
                        <label style="font-size:12px;">Account Number</label>
                        <input type="text" name="bank_account_number" class="form-control"
                               value="{{ old('bank_account_number', $object->bank_account_number ?? '') }}" placeholder="Account Number">
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;">Account Name</label>
                    <input type="text" name="account_name" class="form-control"
                           value="{{ old('account_name', $object->account_name ?? '') }}" placeholder="Name as it appears on the account">
                </div>
            </div>

            {{-- Mobile money fields --}}
            <div id="supplier-payment-mobile" class="supplier-payment-block" style="display:{{ $pm === 'MOBILE' ? 'block' : 'none' }};">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label style="font-size:12px;">Provider</label>
                        <select name="mobile_provider" class="form-control">
                            <option value="">— Select provider —</option>
                            @foreach(['M-Pesa','Mixx by Yas','Tigo Pesa','Airtel Money','Halopesa','T-Pesa','Other'] as $prov)
                                <option value="{{ $prov }}" {{ old('mobile_provider', $object->mobile_provider ?? '') === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-6">
                        <label style="font-size:12px;">Mobile Number</label>
                        <input type="text" name="mobile_number" class="form-control"
                               value="{{ old('mobile_number', $object->mobile_number ?? '') }}" placeholder="e.g. 0712345678">
                    </div>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;">Account Name</label>
                    <input type="text" name="account_name" class="form-control"
                           value="{{ old('account_name', $object->account_name ?? '') }}" placeholder="Registered name on the mobile money line">
                </div>
            </div>

            {{-- Cash --}}
            <div id="supplier-payment-cash" class="supplier-payment-block" style="display:{{ $pm === 'CASH' ? 'block' : 'none' }};">
                <div class="alert alert-info mb-0" style="font-size:12.5px;">
                    <i class="fa fa-info-circle mr-1"></i>
                    Supplier will be paid in cash. No bank or mobile money details are required.
                </div>
            </div>
        </fieldset>

        {{-- ── Attachments ─────────────────────────────────────────────── --}}
        <fieldset class="form-group" style="border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;margin-top:8px;">
            <legend class="control-label" style="font-size:13px;font-weight:700;width:auto;padding:0 8px;margin-bottom:8px;">
                Attachments
            </legend>
            <p class="text-muted" style="font-size:11.5px;margin-top:-4px;">
                Optional. Allowed: PDF, JPG, PNG, DOC, XLS — max 4 MB each.
            </p>

            @foreach([
                'proforma'  => ['label' => 'Pro-forma',  'icon' => 'fa-file-invoice'],
                'quotation' => ['label' => 'Quotation',  'icon' => 'fa-file-alt'],
                'document'  => ['label' => 'Document',   'icon' => 'fa-file'],
            ] as $field => $meta)
                @php $existing = $object->{$field} ?? null; @endphp
                <div class="form-group mb-3">
                    <label style="font-size:12px;">
                        <i class="fa {{ $meta['icon'] }} mr-1" style="color:#64748b;"></i>
                        {{ $meta['label'] }}
                    </label>
                    <input type="file" name="{{ $field }}" class="form-control-file"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx">
                    @if($existing)
                        <div class="mt-1" style="font-size:11.5px;">
                            <a href="{{ asset(ltrim($existing, '/')) }}" target="_blank" class="text-primary">
                                <i class="fa fa-paperclip mr-1"></i>View current {{ strtolower($meta['label']) }}
                            </a>
                            <span class="text-muted ml-2">— upload a new file to replace</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </fieldset>

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem" value="Supplier"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Supplier">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });

    // ── Payment method toggle: show only the relevant block ──────────
    (function () {
        const map = { BANK: 'supplier-payment-bank', MOBILE: 'supplier-payment-mobile', CASH: 'supplier-payment-cash' };
        function applyPaymentMethod(val) {
            Object.values(map).forEach(id => {
                const el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });
            if (val && map[val]) {
                const el = document.getElementById(map[val]);
                if (el) el.style.display = 'block';
            }
        }
        document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            input.addEventListener('change', e => applyPaymentMethod(e.target.value));
        });
        const checked = document.querySelector('input[name="payment_method"]:checked');
        if (checked) applyPaymentMethod(checked.value);
    })();

    // Initialize form based on initial value
    $(document).ready(function() {
        const initialSystem = $('#supplier_depend_on_system').val();
        if (initialSystem == 'WHITESTAR') {
            $("#whitestar").show();
            $("#bonge").hide();
            $("#input-bonge_supplier-id").prop('disabled', true);
            $("#input-whitestar_supplier-id").prop('disabled', false);
        } else if (initialSystem == 'BONGE') {
            $("#bonge").show();
            $("#whitestar").hide();
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', false);
        } else {
            $("#whitestar").hide();
            $("#bonge").hide();
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', true);
        }
    });

    // Handle system change
    $('#supplier_depend_on_system').on('change', function() {
        // Reset both select values when changing system
        $('#input-whitestar_supplier-id').val('');
        $('#input-bonge_supplier-id').val('');

        if (this.value == 'WHITESTAR') {
            $("#whitestar").show();
            $("#bonge").hide();
            // Disable Bonge supplier field when Whitestar is selected
            $("#input-bonge_supplier-id").prop('disabled', true);
            $("#input-whitestar_supplier-id").prop('disabled', false);
        } else if (this.value == 'BONGE') {
            $("#bonge").show();
            $("#whitestar").hide();
            // Disable Whitestar supplier field when Bonge is selected
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', false);
        } else {
            $("#whitestar").hide();
            $("#bonge").hide();
            // Disable both when nothing is selected
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', true);
        }
    });
</script>
