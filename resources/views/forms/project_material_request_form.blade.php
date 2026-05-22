{{-- Project Material Request Form - Multi-item mode --}}
@php
    $selected_project_id = request('project_id') ?? ($object->project_id ?? null);
@endphp
<div class="block-content">
    <form method="post" action="{{ route('project_material_request.bulk', ['project_id' => $selected_project_id ?? 0]) }}" id="single-request-form">
        @csrf

        {{-- ── Header fields (apply to the whole request) ─────────────────── --}}
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label class="control-label required">Project</label>
                    <select name="project_id" id="input-project-mr" class="form-control select2" required>
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            @php
                                $projectLabel = $project->project_name ?: ('Project #' . $project->id);
                                $clientLabel  = $project->client?->full_name;
                            @endphp
                            <option value="{{ $project->id }}" {{ ($project->id == $selected_project_id) ? 'selected' : '' }}>
                                {{ $projectLabel }}@if($clientLabel && trim($clientLabel) !== trim($projectLabel)) — {{ $clientLabel }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label class="control-label required">Required Date</label>
                    <input type="date" class="form-control" name="required_date"
                        value="{{ old('required_date', date('Y-m-d', strtotime('+7 days'))) }}">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label class="control-label required">Priority</label>
                    <select name="priority" class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label class="control-label">Purpose / Justification</label>
                    <input type="text" class="form-control" name="purpose" placeholder="Brief reason for this request">
                </div>
            </div>
        </div>

        {{-- ── Line items ───────────────────────────────────────────────────── --}}
        <div class="table-responsive" style="margin-top:8px;">
            <table class="table table-bordered table-sm" id="items-table">
                <thead class="thead-light">
                    <tr>
                        <th style="width:46%">BOQ Item / Description</th>
                        <th style="width:18%">Qty Requested</th>
                        <th style="width:13%">Unit</th>
                        <th style="width:15%" class="text-center text-nowrap" title="Tick to enter item not in BOQ">
                            <small>Not in BOQ</small>
                        </th>
                        <th style="width:8%"></th>
                    </tr>
                </thead>
                <tbody id="items-tbody">
                    {{-- First row rendered by PHP so it can use Blade @foreach for options --}}
                    <tr class="item-row" data-index="0">
                        <td>
                            <div class="boq-field">
                                <select name="items[0][boq_item_id]" class="form-control select2 boq-item-select" style="width:100%">
                                    <option value="">Select BOQ Item</option>
                                    @foreach ($project_boq_items ?? [] as $boqItem)
                                        <option value="{{ $boqItem->id }}"
                                            data-unit="{{ $boqItem->unit }}"
                                            data-available="{{ max(0, ($boqItem->quantity ?? 0) - ($boqItem->quantity_requested ?? 0)) }}">
                                            {{ $boqItem->item_code ?? '' }} - {{ Str::limit($boqItem->description, 45) }} ({{ $boqItem->unit }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted avail-text"></small>
                            </div>
                            <div class="custom-field" style="display:none;">
                                <input type="text" class="form-control custom-desc-input" name="items[0][description]"
                                    placeholder="Item name / description">
                            </div>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0.01" class="form-control qty-input"
                                name="items[0][quantity_requested]" required>
                        </td>
                        <td>
                            <input type="text" class="form-control unit-input" name="items[0][unit]"
                                placeholder="unit" required>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" class="custom-toggle" title="Item not in BOQ" style="width:18px;height:18px;cursor:pointer;">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove" disabled>
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mb-3">
            <button type="button" id="add-item-btn" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-plus"></i> Add Another Item
            </button>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-alt-primary col">
                Submit Request
            </button>
        </div>
    </form>
</div>

{{-- All BOQ items as JSON for dynamic row population --}}
<script id="boq-items-data" type="application/json">
    {!! json_encode(
        collect($project_boq_items ?? [])->map(fn($item) => [
            'id'         => $item->id,
            'project_id' => $item->boq->project_id ?? null,
            'item_code'  => $item->item_code ?? '',
            'description'=> Str::limit($item->description, 45),
            'unit'       => $item->unit,
            'available'  => max(0, ($item->quantity ?? 0) - ($item->quantity_requested ?? 0)),
        ])
    ) !!}
</script>

<script>
(function () {
    var allBoqItems = JSON.parse(document.getElementById('boq-items-data').textContent);
    var rowIndex = 1; // next index to assign

    // ── Helpers ────────────────────────────────────────────────────────────
    function getSelectedProjectId() {
        return $('#input-project-mr').val();
    }

    function buildOptions(projectId) {
        var html = '<option value="">Select BOQ Item</option>';
        allBoqItems.forEach(function (item) {
            if (!projectId || String(item.project_id) === String(projectId)) {
                html += '<option value="' + item.id + '" '
                      + 'data-unit="' + item.unit + '" '
                      + 'data-available="' + item.available + '">'
                      + (item.item_code ? item.item_code + ' - ' : '')
                      + item.description + ' (' + item.unit + ')'
                      + '</option>';
            }
        });
        return html;
    }

    function initSelect2(el) {
        $(el).select2({
            theme: 'bootstrap',
            placeholder: 'Select BOQ Item',
            width: '100%',
            allowClear: true,
            dropdownParent: $('#ajax-loader-modal')
        });
    }

    function bindRowEvents(row) {
        var $row   = $(row);
        var $sel   = $row.find('.boq-item-select');
        var $qty   = $row.find('.qty-input');
        var $unit  = $row.find('.unit-input');
        var $avail = $row.find('.avail-text');
        var $toggle = $row.find('.custom-toggle');
        var $boqField    = $row.find('.boq-field');
        var $customField = $row.find('.custom-field');

        $sel.on('change', function () {
            var opt = $sel.find(':selected');
            var unit = opt.data('unit') || '';
            var avail = opt.data('available') !== undefined ? opt.data('available') : '';
            $unit.val(unit);
            if (opt.val()) {
                $avail.text('Available: ' + avail + ' ' + unit);
                $qty.attr('max', avail);
            } else {
                $avail.text('');
                $qty.removeAttr('max');
            }
        });

        $toggle.on('change', function () {
            if ($toggle.is(':checked')) {
                // Switch to free-text mode
                $sel.val('').trigger('change');
                $sel.select2('destroy');
                $boqField.hide();
                $customField.show();
                $customField.find('.custom-desc-input').prop('required', true);
                $avail.text('');
                $qty.removeAttr('max');
            } else {
                // Switch back to BOQ mode
                $customField.hide();
                $customField.find('.custom-desc-input').prop('required', false).val('');
                $boqField.show();
                initSelect2($sel[0]);
                bindBoqSelectEvent($sel, $qty, $unit, $avail);
            }
        });

        $row.find('.remove-row-btn').on('click', function () {
            if (!$toggle.is(':checked')) {
                $sel.select2('destroy');
            }
            $row.remove();
            updateRemoveButtons();
        });
    }

    function bindBoqSelectEvent($sel, $qty, $unit, $avail) {
        $sel.off('change').on('change', function () {
            var opt = $sel.find(':selected');
            var unit = opt.data('unit') || '';
            var avail = opt.data('available') !== undefined ? opt.data('available') : '';
            $unit.val(unit);
            if (opt.val()) {
                $avail.text('Available: ' + avail + ' ' + unit);
                $qty.attr('max', avail);
            } else {
                $avail.text('');
                $qty.removeAttr('max');
            }
        });
    }

    function updateRemoveButtons() {
        var rows = $('#items-tbody .item-row');
        rows.find('.remove-row-btn').prop('disabled', rows.length === 1);
    }

    function reloadAllRowOptions() {
        var projectId = getSelectedProjectId();
        var options   = buildOptions(projectId);
        $('#items-tbody .item-row').each(function () {
            var $row    = $(this);
            var $toggle = $row.find('.custom-toggle');
            if ($toggle.is(':checked')) return; // leave custom rows alone
            var $sel = $row.find('.boq-item-select');
            $sel.select2('destroy');
            $sel.html(options);
            initSelect2($sel[0]);
            bindRowEvents($row[0]);
            $row.find('.qty-input').val('').removeAttr('max');
            $row.find('.unit-input').val('');
            $row.find('.avail-text').text('');
        });
    }

    // ── Init first row ─────────────────────────────────────────────────────
    var $firstRow = $('#items-tbody .item-row').first();
    var $firstSel = $firstRow.find('.boq-item-select');

    // Filter options to the pre-selected project (if any)
    var preselectedProject = getSelectedProjectId();
    if (preselectedProject) {
        $firstSel.html(buildOptions(preselectedProject));
    }

    initSelect2($firstSel[0]);
    bindRowEvents($firstRow[0]);

    // ── Project change → reload all rows ──────────────────────────────────
    $('#input-project-mr').on('change', function () {
        var projectId = $(this).val() || 0;
        var form = $('#single-request-form');
        form.attr('action', '{{ url("project_material_request/bulk") }}/' + projectId);
        reloadAllRowOptions();
    });

    // ── Add row ────────────────────────────────────────────────────────────
    $('#add-item-btn').on('click', function () {
        var projectId = getSelectedProjectId();
        var idx       = rowIndex++;
        var options   = buildOptions(projectId);

        var $newRow = $('<tr class="item-row" data-index="' + idx + '">'
            + '<td>'
            +   '<div class="boq-field">'
            +     '<select name="items[' + idx + '][boq_item_id]" class="form-control select2 boq-item-select" style="width:100%">'
            +       options
            +     '</select>'
            +     '<small class="text-muted avail-text"></small>'
            +   '</div>'
            +   '<div class="custom-field" style="display:none;">'
            +     '<input type="text" class="form-control custom-desc-input" name="items[' + idx + '][description]" placeholder="Item name / description">'
            +   '</div>'
            + '</td>'
            + '<td><input type="number" step="0.01" min="0.01" class="form-control qty-input" name="items[' + idx + '][quantity_requested]" required></td>'
            + '<td><input type="text" class="form-control unit-input" name="items[' + idx + '][unit]" placeholder="unit" required></td>'
            + '<td class="text-center">'
            +   '<input type="checkbox" class="custom-toggle" title="Item not in BOQ" style="width:18px;height:18px;cursor:pointer;">'
            + '</td>'
            + '<td class="text-center">'
            +   '<button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" title="Remove"><i class="fa fa-times"></i></button>'
            + '</td>'
            + '</tr>');

        $('#items-tbody').append($newRow);
        initSelect2($newRow.find('.boq-item-select')[0]);
        bindRowEvents($newRow[0]);
        updateRemoveButtons();
    });

    // ── Project select2 ────────────────────────────────────────────────────
    $('#input-project-mr').select2({
        theme: 'bootstrap',
        placeholder: 'Choose',
        width: '100%',
        allowClear: true,
        dropdownParent: $('#ajax-loader-modal')
    });
})();
</script>
