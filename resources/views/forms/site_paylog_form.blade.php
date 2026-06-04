{{-- Site Paylog — record several daily payments (material + labour) for one site --}}
@php
    $selected_site_id = request('site_id') ?? null;
    $today = request('date') ?? date('Y-m-d');
    $channels   = $payment_channels ?? collect();
    $sites      = $paylog_sites ?? collect();
    $categories = $paylog_categories ?? ['material' => 'Material', 'labour' => 'Labour'];
@endphp
<div class="block-content">
    <form method="post" action="{{ route('site_paylog.bulk', ['site_id' => $selected_site_id ?? 0]) }}" id="paylog-form">
        @csrf

        {{-- ── Header: site + date + optional project ──────────────────────── --}}
        <div class="row">
            <div class="col-sm-5">
                <div class="form-group">
                    <label class="control-label required">Site</label>
                    <select name="site_id" id="input-paylog-site" class="form-control select2" required>
                        <option value="">Select Site</option>
                        @foreach ($sites as $s)
                            <option value="{{ $s->id }}" {{ ($s->id == $selected_site_id) ? 'selected' : '' }}>
                                {{ $s->name }}@if($s->location) — {{ $s->location }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label class="control-label">Project (optional)</label>
                    <select name="project_id" class="form-control select2">
                        <option value="">— None —</option>
                        @foreach (($projects ?? []) as $p)
                            <option value="{{ $p->id }}">{{ $p->project_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label class="control-label required">Date</label>
                    <input type="date" class="form-control" name="payment_date" value="{{ old('payment_date', $today) }}" required>
                </div>
            </div>
        </div>

        {{-- ── Payment line items ──────────────────────────────────────────── --}}
        <div class="table-responsive" style="margin-top:8px;">
            <table class="table table-bordered table-sm align-middle" id="paylog-table">
                <thead class="thead-light">
                    <tr>
                        <th style="width:18%">Name (Payee)</th>
                        <th style="width:22%">Reason of Payment</th>
                        <th style="width:12%">Category</th>
                        <th style="width:14%">Bank/Mobile</th>
                        <th style="width:16%">Account Name</th>
                        <th style="width:14%">Amount (TZS)</th>
                        <th style="width:4%"></th>
                    </tr>
                </thead>
                <tbody id="paylog-tbody">
                    <tr class="pay-row" data-index="0">
                        <td><input type="text" class="form-control" name="payments[0][payee_name]" placeholder="Juma Mason" required></td>
                        <td><input type="text" class="form-control" name="payments[0][reason]" placeholder="Cement 50 bags / Labour 3 days" required></td>
                        <td>
                            <select name="payments[0][category]" class="form-control" required>
                                @foreach ($categories as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select name="payments[0][payment_channel_id]" class="form-control">
                                <option value="">—</option>
                                @foreach ($channels as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" class="form-control" name="payments[0][account_name]" placeholder="Account name"></td>
                        <td><input type="number" step="0.01" min="0.01" class="form-control amount-input" name="payments[0][amount]" placeholder="480000" required></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove" disabled><i class="fa fa-times"></i></button>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right"><strong>Total</strong></td>
                        <td colspan="2"><strong id="paylog-total">0</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mb-3">
            <button type="button" id="add-pay-row" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-plus"></i> Add Another Payment
            </button>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-alt-primary col">
                <i class="si si-check"></i> Save &amp; Submit
            </button>
        </div>
    </form>
</div>

{{-- Channel + category options reused when cloning rows --}}
<script id="paylog-channel-options" type="application/json">{!! json_encode($channels->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()) !!}</script>
<script id="paylog-category-options" type="application/json">{!! json_encode($categories) !!}</script>

<script>
(function () {
    var channels   = JSON.parse(document.getElementById('paylog-channel-options').textContent);
    var categories = JSON.parse(document.getElementById('paylog-category-options').textContent);
    var rowIndex = 1;

    function channelOptions() {
        var html = '<option value="">—</option>';
        channels.forEach(function (c) { html += '<option value="' + c.id + '">' + c.name + '</option>'; });
        return html;
    }
    function categoryOptions() {
        var html = '';
        Object.keys(categories).forEach(function (k) { html += '<option value="' + k + '">' + categories[k] + '</option>'; });
        return html;
    }

    function recalcTotal() {
        var total = 0;
        $('#paylog-tbody .amount-input').each(function () {
            var v = parseFloat($(this).val());
            if (!isNaN(v)) total += v;
        });
        $('#paylog-total').text(total.toLocaleString());
    }

    function updateRemoveButtons() {
        var rows = $('#paylog-tbody .pay-row');
        rows.find('.remove-row-btn').prop('disabled', rows.length === 1);
    }

    // Keep the bulk action URL pointed at the chosen site
    $('#input-paylog-site').on('change', function () {
        var siteId = $(this).val() || 0;
        $('#paylog-form').attr('action', '{{ url("site-paylog/bulk") }}/' + siteId);
    });

    $('#add-pay-row').on('click', function () {
        var idx = rowIndex++;
        var $row = $('<tr class="pay-row" data-index="' + idx + '">'
            + '<td><input type="text" class="form-control" name="payments[' + idx + '][payee_name]" placeholder="Juma Mason" required></td>'
            + '<td><input type="text" class="form-control" name="payments[' + idx + '][reason]" placeholder="Cement 50 bags / Labour 3 days" required></td>'
            + '<td><select name="payments[' + idx + '][category]" class="form-control" required>' + categoryOptions() + '</select></td>'
            + '<td><select name="payments[' + idx + '][payment_channel_id]" class="form-control">' + channelOptions() + '</select></td>'
            + '<td><input type="text" class="form-control" name="payments[' + idx + '][account_name]" placeholder="Account name"></td>'
            + '<td><input type="number" step="0.01" min="0.01" class="form-control amount-input" name="payments[' + idx + '][amount]" placeholder="480000" required></td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove"><i class="fa fa-times"></i></button></td>'
            + '</tr>');
        $('#paylog-tbody').append($row);
        updateRemoveButtons();
    });

    $('#paylog-tbody').on('click', '.remove-row-btn', function () {
        $(this).closest('.pay-row').remove();
        updateRemoveButtons();
        recalcTotal();
    });

    $('#paylog-tbody').on('input', '.amount-input', recalcTotal);

    // select2 inside the ajax modal
    if ($.fn.select2) {
        $('#input-paylog-site, [name="project_id"]').select2({
            theme: 'bootstrap', width: '100%', dropdownParent: $('#ajax-loader-modal')
        });
    }
})();
</script>
