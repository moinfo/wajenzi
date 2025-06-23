{{-- BOQ Form --}}
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
                    <label for="version" class="control-label required">Version</label>
                    <input type="number" class="form-control" id="input-version" name="version" value="{{ $object->version ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="type" class="control-label required">Type</label>
                    <select name="type" id="input-type" class="form-control" required="required">
                        <option value="">Select Type</option>
                        <option value="client" {{ ($object->type == 'client') ? 'selected' : '' }}>Client</option>
                        <option value="internal" {{ ($object->type == 'internal') ? 'selected' : '' }}>Internal</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="total_amount" class="control-label required">Total Amount</label>
                    <input type="number" step="0.01" class="form-control" id="input-total-amount" name="total_amount" value="{{ $object->total_amount ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="status" class="control-label required">Status</label>
                    <select name="status" id="input-status" class="form-control" required="required">
                        <option value="">Select Status</option>
                        <option value="draft" {{ ($object->status == 'draft') ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ ($object->status == 'submitted') ? 'selected' : '' }}>Submitted</option>
                        <option value="approved" {{ ($object->status == 'approved') ? 'selected' : '' }}>Approved</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BOQ">Submit</button>
            @endif
        </div>
    </form>
</div>
