{{-- Project Material Request Form - context-aware (simplified when from BOQ) --}}
@php
    $selected_project_id = request('project_id') ?? ($object->project_id ?? null);
    $selected_boq_item_id = request('boq_item_id') ?? ($object->boq_item_id ?? null);
    $isFromBoq = $selected_boq_item_id && !($object->id ?? null);
    $preselectedItem = $isFromBoq ? ($project_boq_items ?? collect())->firstWhere('id', $selected_boq_item_id) : null;
@endphp
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf

        @if($isFromBoq && $preselectedItem)
            {{-- ===== SIMPLIFIED MODE: opened from BOQ items page ===== --}}
            <input type="hidden" name="project_id" value="{{ $selected_project_id }}">
            <input type="hidden" name="boq_item_id" value="{{ $selected_boq_item_id }}">
            <input type="hidden" name="unit" value="{{ $preselectedItem->unit }}">

            <div style="background: #f0f7ff; border: 1px solid #d0e3f7; border-radius: 6px; padding: 12px 16px; margin-bottom: 16px;">
                <div style="font-weight: 600; color: #333; margin-bottom: 4px;">
                    {{ $preselectedItem->item_code }} — {{ $preselectedItem->description }}
                </div>
                <div style="font-size: 12px; color: #666;">
                    @if($preselectedItem->section)
                        <span style="color: #0066cc; font-weight: 500;">{{ $preselectedItem->section->name }}</span>
                        &nbsp;|&nbsp;
                    @endif
                    BOQ: {{ number_format($preselectedItem->quantity, 2) }} {{ $preselectedItem->unit }}
                    &nbsp;|&nbsp;
                    Requested: {{ number_format($preselectedItem->quantity_requested ?? 0, 2) }}
                    &nbsp;|&nbsp;
                    <strong style="color: #28a745;">Available: {{ number_format($preselectedItem->quantity_remaining, 2) }} {{ $preselectedItem->unit }}</strong>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="quantity_requested" class="control-label required">Quantity to Request</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01"
                                max="{{ $preselectedItem->quantity_remaining }}"
                                class="form-control" id="input-quantity-requested"
                                name="quantity_requested" placeholder="Enter quantity" required autofocus>
                            <div class="input-group-append">
                                <span class="input-group-text">{{ $preselectedItem->unit }}</span>
                            </div>
                        </div>
                        <small class="text-muted">Max: {{ number_format($preselectedItem->quantity_remaining, 2) }} {{ $preselectedItem->unit }}</small>
                    </div>
                </div>
                <div class="col-sm-6">
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
                        <label for="purpose" class="control-label">Purpose <small class="text-muted">(optional)</small></label>
                        <input type="text" class="form-control" name="purpose" placeholder="Brief reason for this request">
                    </div>
                </div>
            </div>

        @else
            {{-- ===== FULL MODE: opened from Material Requests page or editing ===== --}}
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        <label for="project_id" class="control-label required">Project</label>
                        <select name="project_id" id="input-project" class="form-control select2" required>
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
                        <label for="boq_item_id" class="control-label">BOQ Item</label>
                        <select name="boq_item_id" id="input-boq-item" class="form-control select2">
                            <option value="">Select BOQ Item (Optional)</option>
                            @foreach ($project_boq_items ?? [] as $boqItem)
                                <option value="{{ $boqItem->id }}"
                                    data-unit="{{ $boqItem->unit }}"
                                    data-quantity="{{ $boqItem->quantity }}"
                                    data-available="{{ $boqItem->quantity_remaining }}"
                                    data-section="{{ $boqItem->section->name ?? '' }}"
                                    {{ ($boqItem->id == $selected_boq_item_id) ? 'selected' : '' }}>
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
                        <label class="control-label">Construction Phase</label>
                        <input type="text" class="form-control" id="input-phase-display" readonly
                            style="background: #f8f9fa; color: #555;"
                            placeholder="Auto-detected from BOQ item"
                            value="{{ ($object->boqItem->section->name ?? '') }}">
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
        @endif

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

        // When BOQ item is selected (full mode), update unit, phase, and available qty
        $('#input-boq-item').change(function() {
            var selected = $(this).find(':selected');
            if (selected.val()) {
                var unit = selected.data('unit');
                var available = selected.data('available');
                var section = selected.data('section');
                $('#input-unit').val(unit);
                $('#boq-available-text').text('Available: ' + available + ' ' + unit);
                $('#input-phase-display').val(section || 'No section');
            } else {
                $('#boq-available-text').text('');
                $('#input-phase-display').val('');
            }
        });

        // Trigger change on load if BOQ item is pre-selected (full mode)
        if ($('#input-boq-item').val()) {
            $('#input-boq-item').trigger('change');
        }
    });
</script>
