<?php
$projects = \App\Models\Project::all();
?>
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project" class="form-control" required="required">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == ($object->project_id ?? '')) ? 'selected' : '' }}>{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="version" class="control-label required">Version</label>
                    <input type="number" class="form-control" id="input-version" name="version" value="{{ $object->version ?? 1 }}" required="required" min="1">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="type" class="control-label required">Type</label>
                    <select name="type" id="input-type" class="form-control" required="required">
                        <option value="">Select Type</option>
                        <option value="client" {{ (($object->type ?? '') == 'client') ? 'selected' : '' }}>Client</option>
                        <option value="internal" {{ (($object->type ?? '') == 'internal') ? 'selected' : '' }}>Internal</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="status" class="control-label">Status</label>
                    <select name="status" id="input-status" class="form-control">
                        <option value="draft" {{ (($object->status ?? 'draft') == 'draft') ? 'selected' : '' }}>Draft</option>
                        <option value="submitted" {{ (($object->status ?? '') == 'submitted') ? 'selected' : '' }}>Submitted</option>
                        <option value="approved" {{ (($object->status ?? '') == 'approved') ? 'selected' : '' }}>Approved</option>
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="total_amount" class="control-label">Total Amount</label>
                    <input type="number" class="form-control" id="input-total-amount" name="total_amount" value="{{ $object->total_amount ?? '0.00' }}" step="0.01" min="0" readonly>
                </div>
            </div>
        </div>

        <hr>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update BOQ</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectBoq"><i class="si si-plus"></i> Create BOQ</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('#input-project').on('change', function() {
        var projectId = $(this).val();
        if (projectId) {
            $.get('/project_boqs/next-version', { project_id: projectId }, function(data) {
                $('#input-version').val(data.version);
            });
        }
    });
</script>
