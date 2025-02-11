
{{-- Project Construction Phase Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project" class="form-control" required="required">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == $object->project_id) ? 'selected' : '' }}>{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="phase_name" class="control-label required">Phase Name</label>
                    <input type="text" class="form-control" id="input-phase-name" name="phase_name" value="{{ $object->phase_name ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="status" class="control-label required">Status</label>
                    <select name="status" id="input-status" class="form-control" required="required">
                        <option value="">Select Status</option>
                        <option value="pending" {{ ($object->status == 'pending') ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ ($object->status == 'in_progress') ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ ($object->status == 'completed') ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="start_date" class="control-label required">Start Date</label>
                    <input type="text" class="form-control datepicker" id="input-start-date" name="start_date" value="{{ $object->start_date ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="text" class="form-control datepicker" id="input-end-date" name="end_date" value="{{ $object->end_date ?? '' }}">
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ConstructionPhase">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });

    // Calculate total price for BOQ Item
    $('#input-quantity, #input-unit-price').on('change', function() {
        let quantity = parseFloat($('#input-quantity').val()) || 0;
        let unitPrice = parseFloat($('#input-unit-price').val()) || 0;
        let totalPrice = quantity * unitPrice;

        // If you have a total price field, uncomment this
        // $('#input-total-price').val(totalPrice.toFixed(2));
    });
</script>
