<?php
$isEdit = (bool) ($object->id ?? null);
$selectedType = ($object->client_id ?? null) && !($object->project_id ?? null) ? 'client' : 'project';
$formAction = $isEdit
    ? route('project_site_visit.update', $object->id)
    : route('project_site_visit.store');
?>
<div class="block-content">
    <form method="post" action="{{ $formAction }}" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label class="control-label required d-block">Linked To</label>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" id="psv-type-project" name="psv_link_type" value="project" {{ $selectedType === 'project' ? 'checked' : '' }}>
                        <label class="custom-control-label" for="psv-type-project">Project</label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline">
                        <input type="radio" class="custom-control-input" id="psv-type-client" name="psv_link_type" value="client" {{ $selectedType === 'client' ? 'checked' : '' }}>
                        <label class="custom-control-label" for="psv-type-client">Client (no project yet)</label>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 psv-project-field">
                <div class="form-group">
                    <label for="input-project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project_id" class="form-control">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == ($object->project_id ?? null)) ? 'selected' : '' }}>{{ $project->project_name .' - '. $project->client->first_name.' '.$project->client->last_name  }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12 psv-client-field" style="display:none;">
                <div class="form-group">
                    <label for="input-client_id" class="control-label required">Client</label>
                    <select name="client_id" id="input-client_id" class="form-control">
                        <option value="">Select Client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" {{ ($client->id == ($object->client_id ?? null)) ? 'selected' : '' }}>{{ $client->first_name.' '.$client->last_name }}{{ $client->phone_number ? ' - '.$client->phone_number : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="phone_number" class="control-label">Phone Number</label>
                    <input type="text" class="form-control" id="input-phone_number" name="phone_number" value="{{ $object->phone_number ?? '' }}" placeholder="Client phone number">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="location" class="control-label required">Location</label>
                    <input type="text" class="form-control" id="input-location" required="required" name="location" value="{{ $object->location ?? '' }}" placeholder="Site Visit location">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    <label for="site_visit_location_id" class="control-label">Calculator Location <small class="text-muted">(drives the billing estimate)</small></label>
                    <select name="site_visit_location_id" id="input-site_visit_location_id" class="form-control">
                        <option value="">— None —</option>
                        @foreach (($site_visit_locations ?? []) as $loc)
                            <option value="{{ $loc->id }}" {{ ($loc->id == ($object->site_visit_location_id ?? null)) ? 'selected' : '' }}>
                                {{ $loc->name }} ({{ number_format((float) $loc->base_cost_tzs) }} TZS base)
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="visit_days" class="control-label">Days</label>
                    <input type="number" min="1" max="365" class="form-control" id="input-visit_days" name="visit_days" value="{{ $object->visit_days ?? 1 }}">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="description" class="control-label required">Description</label>
                    <input type="text" class="form-control" id="input-description" required="required" name="description" value="{{ $object->description ?? '' }}" placeholder="Site visit description">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="visit_date" class="control-label required">Visit Date</label>
                    <input type="date" class="form-control" id="input-visit-date" name="visit_date" value="{{ old('visit_date', $object->visit_date ? \Carbon\Carbon::parse($object->visit_date)->format('Y-m-d') : date('Y-m-d')) }}">
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($isEdit)
                <button type="submit" class="btn btn-alt-primary"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col">Submit Request</button>
            @endif
        </div>
    </form>
</div>

<script>
(function() {
    var modal = document.getElementById('ajax-loader-modal') || document;
    var projectField = modal.querySelector('.psv-project-field');
    var clientField  = modal.querySelector('.psv-client-field');
    var projectSelect = modal.querySelector('#input-project_id');
    var clientSelect  = modal.querySelector('#input-client_id');
    var radios = modal.querySelectorAll('input[name="psv_link_type"]');

    function apply(type) {
        var isProject = type === 'project';
        projectField.style.display = isProject ? '' : 'none';
        clientField.style.display  = isProject ? 'none' : '';
        if (projectSelect) {
            projectSelect.required = isProject;
            projectSelect.disabled = !isProject;
            if (!isProject) projectSelect.value = '';
        }
        if (clientSelect) {
            clientSelect.required = !isProject;
            clientSelect.disabled = isProject;
            if (isProject) clientSelect.value = '';
        }
    }

    radios.forEach(function(r) {
        r.addEventListener('change', function() { apply(this.value); });
    });

    var checked = modal.querySelector('input[name="psv_link_type"]:checked');
    apply(checked ? checked.value : 'project');
})();
</script>
