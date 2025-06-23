{{-- Site Visit Form --}}
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
                    <label for="inspector_id" class="control-label required">Inspector</label>
                    <select name="inspector_id" id="input-inspector" class="form-control" required="required">
                        <option value="">Select Inspector</option>
                        @foreach ($inspectors as $inspector)
                            <option value="{{ $inspector->id }}" {{ ($inspector->id == $object->inspector_id) ? 'selected' : '' }}>{{ $inspector->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="visit_date" class="control-label required">Visit Date</label>
                    <input type="text" class="form-control datepicker" id="input-visit-date" name="visit_date" value="{{ $object->visit_date ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="status" class="control-label required">Status</label>
                    <select name="status" id="input-status" class="form-control" required="required">
                        <option value="">Select Status</option>
                        <option value="scheduled" {{ ($object->status == 'scheduled') ? 'selected' : '' }}>Scheduled</option>
                        <option value="completed" {{ ($object->status == 'completed') ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ ($object->status == 'cancelled') ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="findings">Findings</label>
                    <textarea class="form-control" id="input-findings" name="findings" rows="3">{{ $object->findings ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="recommendations">Recommendations</label>
                    <textarea class="form-control" id="input-recommendations" name="recommendations" rows="3">{{ $object->recommendations ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="SiteVisit">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
