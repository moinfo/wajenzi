{{-- Project Material Request Form - Full mode (from Material Requests listing page) --}}
@php
    $selected_project_id = request('project_id') ?? ($object->project_id ?? null);
@endphp
<div class="block-content">
    <form method="post" action="{{ route('project_material_request.bulk', ['project_id' => $selected_project_id ?? 0]) }}" id="single-request-form">
        @csrf

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project-mr" class="form-control select2" required>
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == $selected_project_id) ? 'selected' : '' }}>
                                {{ $project->project_name ?? $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="boq_item_id" class="control-label required">BOQ Item</label>
                    <select name="items[0][boq_item_id]" id="input-boq-item" class="form-control select2" required>
                        <option value="">Select BOQ Item</option>
                        @foreach ($project_boq_items ?? [] as $boqItem)
                            <option value="{{ $boqItem->id }}"
                                data-unit="{{ $boqItem->unit }}"
                                data-quantity="{{ $boqItem->quantity }}"
                                data-available="{{ $boqItem->quantity_remaining }}"
                                data-section="{{ $boqItem->section->name ?? '' }}">
                                {{ $boqItem->item_code ?? '' }} - {{ Str::limit($boqItem->description, 50) }} ({{ $boqItem->unit }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted" id="boq-available-text"></small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="quantity_requested" class="control-label required">Quantity Requested</label>
                    <input type="number" step="0.01" class="form-control" id="input-quantity-requested"
                        name="items[0][quantity_requested]" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="unit" class="control-label required">Unit</label>
                    <input type="text" class="form-control" id="input-unit" name="items[0][unit]"
                        placeholder="e.g., pcs, kg, m" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="required_date" class="control-label required">Required Date</label>
                    <input type="text" class="form-control datepicker" id="input-required-date"
                        name="required_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="priority" class="control-label required">Priority</label>
                    <select name="priority" id="input-priority" class="form-control" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="purpose" class="control-label">Purpose / Justification</label>
                    <input type="text" class="form-control" name="purpose" placeholder="Brief reason for this request">
                </div>
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-alt-primary col">
                Submit Request
            </button>
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true
        });

        $(".select2").select2({
            theme: "bootstrap",
            placeholder: "Choose",
            width: '100%',
            dropdownAutoWidth: true,
            allowClear: true,
            dropdownParent: $('#ajax-loader-modal')
        });

        // Update form action when project changes
        $('#input-project-mr').change(function() {
            var projectId = $(this).val() || 0;
            var form = $('#single-request-form');
            var baseUrl = '{{ url("project_material_request/bulk") }}/' + projectId;
            form.attr('action', baseUrl);
        });

        // When BOQ item is selected, update unit and available qty
        $('#input-boq-item').change(function() {
            var selected = $(this).find(':selected');
            if (selected.val()) {
                var unit = selected.data('unit');
                var available = selected.data('available');
                $('#input-unit').val(unit);
                $('#boq-available-text').text('Available: ' + available + ' ' + unit);
                $('#input-quantity-requested').attr('max', available);
            } else {
                $('#boq-available-text').text('');
                $('#input-unit').val('');
            }
        });
    });
</script>
