{{-- Enhanced Project Material Request Form with BOQ linking --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project" class="form-control select2" required>
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == ($object->project_id ?? '')) ? 'selected' : '' }}>
                                {{ $project->project_name ?? $project->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="boq_item_id" class="control-label">BOQ Item</label>
                    <select name="boq_item_id" id="input-boq-item" class="form-control select2">
                        <option value="">Select BOQ Item (Optional)</option>
                        @foreach ($project_boq_items ?? [] as $boqItem)
                            <option value="{{ $boqItem->id }}"
                                data-unit="{{ $boqItem->unit }}"
                                data-quantity="{{ $boqItem->quantity }}"
                                data-available="{{ $boqItem->quantity_remaining }}"
                                {{ ($boqItem->id == ($object->boq_item_id ?? '')) ? 'selected' : '' }}>
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
                        name="quantity_requested" value="{{ $object->quantity_requested ?? '' }}" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="unit" class="control-label required">Unit</label>
                    <input type="text" class="form-control" id="input-unit" name="unit"
                        value="{{ $object->unit ?? '' }}" placeholder="e.g., pcs, kg, m" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="required_date" class="control-label required">Required Date</label>
                    <input type="text" class="form-control datepicker" id="input-required-date"
                        name="required_date" value="{{ $object->required_date ?? date('Y-m-d', strtotime('+7 days')) }}" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="priority" class="control-label required">Priority</label>
                    <select name="priority" id="input-priority" class="form-control" required>
                        @foreach ($priorities ?? [['id'=>'low','name'=>'Low'],['id'=>'medium','name'=>'Medium'],['id'=>'high','name'=>'High'],['id'=>'urgent','name'=>'Urgent']] as $priority)
                            <option value="{{ $priority['id'] }}" {{ (($object->priority ?? 'medium') == $priority['id']) ? 'selected' : '' }}>
                                {{ $priority['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="construction_phase_id" class="control-label">Construction Phase</label>
                    <select name="construction_phase_id" id="input-phase" class="form-control select2">
                        <option value="">Select Phase (Optional)</option>
                        @foreach ($construction_phases ?? [] as $phase)
                            <option value="{{ $phase->id }}" {{ ($phase->id == ($object->construction_phase_id ?? '')) ? 'selected' : '' }}>
                                {{ $phase->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="purpose" class="control-label">Purpose / Justification</label>
                    <textarea class="form-control" id="input-purpose" name="purpose" rows="3"
                        placeholder="Describe why these materials are needed">{{ $object->purpose ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <input type="hidden" name="requester_id" value="{{ auth()->id() }}">
        <input type="hidden" name="status" value="pending">

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem">
                    <i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectMaterialRequest">
                    Submit Request
                </button>
            @endif
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

        // When BOQ item is selected, update unit and show available quantity
        $('#input-boq-item').change(function() {
            var selected = $(this).find(':selected');
            if (selected.val()) {
                var unit = selected.data('unit');
                var available = selected.data('available');
                $('#input-unit').val(unit);
                $('#boq-available-text').text('Available: ' + available + ' ' + unit);
            } else {
                $('#boq-available-text').text('');
            }
        });

        // Trigger change on load if BOQ item is pre-selected
        if ($('#input-boq-item').val()) {
            $('#input-boq-item').trigger('change');
        }
    });
</script>
