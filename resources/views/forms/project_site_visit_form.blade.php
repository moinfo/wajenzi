<?php
$document_id = \App\Classes\Utility::getLastId('Project')+1;
?>
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="project_type_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project_id" class="form-control" required="required">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == $object->project_id) ? 'selected' : '' }}>{{ $project->project_name .' - '. $project->client->first_name.' '.$project->client->last_name  }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="location" class="control-label required">Location</label>
                    <input type="text" class="form-control" id="input-location" required="required" name="location" value="{{ $object->location ?? '' }}" placeholder="Site Visit location">
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
                    <input type="text" class="form-control datepicker" id="input-visit-date" name="visit_date" value="{{ $object->visit_date ?? date('Y-m-d') }}" required="required">
                </div>
            </div>
        </div>
        <input type="hidden" name="create_by_id" value="{{ Auth::user()->id }}">
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="11">
                <input type="hidden" name="link" value="project_site_visits/{{$document_id}}/11">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectSiteVisit">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>

