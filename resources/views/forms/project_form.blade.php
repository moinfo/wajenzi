<?php
$document_id = \App\Classes\Utility::getLastId('Project')+1;
?>
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="project_name" class="control-label required">Project Name</label>
                    <input type="text" class="form-control" id="input-project-name" required="required" name="project_name" value="{{ $object->project_name ?? '' }}" placeholder="Project Name">
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group">
                    <label for="project_type_id" class="control-label required">Project Type</label>
                    <select name="project_type_id" id="input-project-type" class="form-control" required="required">
                        <option value="">Select Project Type</option>
                        @foreach ($projectTypes as $type)
                            <option value="{{ $type->id }}" {{ ($type->id == $object->project_type_id) ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="client_id" class="control-label required">Client</label>
                    <select name="client_id" id="input-client" class="form-control" required="required">
                        <option value="">Select Client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" {{ ($client->id == $object->client_id) ? 'selected' : '' }}>{{ $client->first_name .' '. $client->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="start_date" class="control-label required">Start Date</label>
                    <input type="text" class="form-control datepicker" id="input-start-date" name="start_date" value="{{ $object->start_date ?? date('Y-m-d') }}" required="required">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="expected_end_date" class="control-label required">Expected End Date</label>
                    <input type="text" class="form-control datepicker" id="input-expected-end-date" name="expected_end_date" value="{{ $object->expected_end_date ?? date('Y-m-d') }}" required="required">
                </div>
            </div>
        </div>
        <input type="hidden" name="create_by_id" value="{{ Auth::user()->id }}">
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <input type="hidden" name="document_number" value="PCT/{{$document_id}}/{{date('Y')}}">
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="10">
                <input type="hidden" name="link" value="projects/{{$document_id}}/10">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Project">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>

