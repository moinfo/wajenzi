<div class="block-content">
    <form method="post" action="{{ route('project_boq.save_template', $object->id ?? request('boq_id')) }}" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="template_name" class="control-label required">Template Name</label>
                    <input type="text" class="form-control" name="template_name" required
                        placeholder="e.g., 3-Bedroom House BOQ, Office Block Standard">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="template_description" class="control-label">Description</label>
                    <textarea class="form-control" name="template_description" rows="2"
                        placeholder="Optional notes about this template"></textarea>
                </div>
            </div>
        </div>
        <hr>
        <p class="text-muted" style="font-size: 11px;">
            This will save a copy of all sections and items from this BOQ as a reusable template.
            You can then apply this template to any new BOQ.
        </p>
        <div class="form-group">
            <button type="submit" class="btn btn-alt-primary col">
                <i class="si si-layers"></i> Save as Template
            </button>
        </div>
    </form>
</div>
