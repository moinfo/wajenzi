<div class="block-content">
    <form method="post" autocomplete="off" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project_id" class="form-control" required="required">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == ($object->project_id ?? '')) ? 'selected' : '' }}>{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="construction_phase_id" class="control-label">Construction Phase</label>
                    <select name="construction_phase_id" id="input-construction_phase_id" class="form-control">
                        <option value="">Select Phase (Optional)</option>
                        @foreach ($construction_phases as $phase)
                            <option value="{{ $phase->id }}" {{ ($phase->id == ($object->construction_phase_id ?? '')) ? 'selected' : '' }}>{{ $phase->phase_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="title" class="control-label">Title</label>
                    <input type="text" class="form-control" id="input-title" name="title" value="{{ $object->title ?? '' }}" placeholder="e.g. Foundation pouring Day 3">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="taken_at" class="control-label">Date Taken</label>
                    <input type="text" class="form-control datepicker" id="input-taken_at" name="taken_at" value="{{ $object->taken_at ?? date('Y-m-d') }}">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="description" class="control-label">Description</label>
                    <textarea class="form-control" id="input-description" name="description" rows="2" placeholder="Brief description of the progress shown">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="file" class="control-label {{ !($object->id ?? null) ? 'required' : '' }}">Image</label>
                    <input type="file" class="form-control" id="input-file" name="file" accept="image/*" {{ !($object->id ?? null) ? 'required="required"' : '' }}>
                    @if($object->file ?? null)
                        <small class="text-muted">Current: {{ $object->file_name ?? basename($object->file) }}</small>
                    @endif
                </div>
            </div>
        </div>
        <input type="hidden" name="uploaded_by" value="{{ Auth::user()->id }}">
        <input type="hidden" name="file_name" id="input-file_name" value="{{ $object->file_name ?? '' }}">
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectProgressImage">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({ format: 'yyyy-mm-dd' });
    // Capture original filename into hidden field
    $('#input-file').on('change', function() {
        if (this.files && this.files[0]) {
            $('#input-file_name').val(this.files[0].name);
        }
    });
</script>
