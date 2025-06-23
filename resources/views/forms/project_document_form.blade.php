{{-- Project Document Form --}}
<div class="block-content">
    <form method="post" autocomplete="off" enctype="multipart/form-data">
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
                    <label for="document_type" class="control-label required">Document Type</label>
                    <select name="document_type" id="input-document-type" class="form-control" required="required">
                        <option value="">Select Type</option>
                        <option value="contract" {{ ($object->document_type == 'contract') ? 'selected' : '' }}>Contract</option>
                        <option value="drawing" {{ ($object->document_type == 'drawing') ? 'selected' : '' }}>Drawing</option>
                        <option value="report" {{ ($object->document_type == 'report') ? 'selected' : '' }}>Report</option>
                        <option value="other" {{ ($object->document_type == 'other') ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="file" class="control-label required">Document File</label>
                    <input type="file" class="form-control" id="input-file" name="file" {{ !$object->id ? 'required="required"' : '' }}>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="input-description" name="description" rows="3">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Document">Submit</button>
            @endif
        </div>
    </form>
</div>
