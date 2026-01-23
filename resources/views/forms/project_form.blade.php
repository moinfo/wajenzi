<?php
$document_id = \App\Classes\Utility::getLastId('Project')+1;
$serviceTypes = \App\Models\ServiceType::all();
$users = \App\Models\User::all();
?>
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <!-- Basic Information -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="project_name" class="control-label required">Project Name</label>
                    <input type="text" class="form-control" id="input-project-name" required="required" name="project_name" value="{{ $object->project_name ?? '' }}" placeholder="Project Name">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="client_id" class="control-label required">Client</label>
                    <select name="client_id" id="input-client" class="form-control" required="required">
                        <option value="">Select Client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" {{ ($client->id == ($object->client_id ?? '')) ? 'selected' : '' }}>{{ $client->first_name .' '. $client->last_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="control-label">Description</label>
                    <textarea class="form-control" id="input-description" name="description" rows="2" placeholder="Brief project description">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>

            <!-- Classification -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="project_type_id" class="control-label required">Project Category</label>
                    <select name="project_type_id" id="input-project-type" class="form-control" required="required">
                        <option value="">Select Category</option>
                        @foreach ($projectTypes as $type)
                            <option value="{{ $type->id }}" {{ ($type->id == ($object->project_type_id ?? '')) ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="service_type_id" class="control-label">Service Type</label>
                    <select name="service_type_id" id="input-service-type" class="form-control">
                        <option value="">Select Service Type</option>
                        @foreach ($serviceTypes as $type)
                            <option value="{{ $type->id }}" {{ ($type->id == ($object->service_type_id ?? '')) ? 'selected' : '' }}>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Dates -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="start_date" class="control-label required">Start Date</label>
                    <input type="text" class="form-control datepicker" id="input-start-date" name="start_date" value="{{ $object->start_date ?? date('Y-m-d') }}" required="required">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="expected_end_date" class="control-label required">Expected End Date</label>
                    <input type="text" class="form-control datepicker" id="input-expected-end-date" name="expected_end_date" value="{{ $object->expected_end_date ?? date('Y-m-d') }}" required="required">
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <label for="actual_end_date" class="control-label">Actual End Date</label>
                    <input type="text" class="form-control datepicker" id="input-actual-end-date" name="actual_end_date" value="{{ $object->actual_end_date ?? '' }}" placeholder="Set when completed">
                </div>
            </div>

            <!-- Financial & Priority -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="contract_value" class="control-label">Contract Value (TZS)</label>
                    <input type="number" class="form-control" id="input-contract-value" name="contract_value" value="{{ $object->contract_value ?? '' }}" placeholder="0.00" step="0.01" min="0">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="priority" class="control-label">Priority</label>
                    <select name="priority" id="input-priority" class="form-control">
                        <option value="low" {{ (($object->priority ?? '') == 'low') ? 'selected' : '' }}>Low</option>
                        <option value="normal" {{ (($object->priority ?? 'normal') == 'normal') ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ (($object->priority ?? '') == 'high') ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ (($object->priority ?? '') == 'urgent') ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
            </div>

            <!-- Team Assignment -->
            <div class="col-md-6">
                <div class="form-group">
                    <label for="salesperson_id" class="control-label">Salesperson</label>
                    <select name="salesperson_id" id="input-salesperson" class="form-control">
                        <option value="">Select Salesperson</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ ($user->id == ($object->salesperson_id ?? '')) ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="project_manager_id" class="control-label">Project Manager</label>
                    <select name="project_manager_id" id="input-project-manager" class="form-control">
                        <option value="">Select Project Manager</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ ($user->id == ($object->project_manager_id ?? '')) ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <input type="hidden" name="create_by_id" value="{{ Auth::user()->id }}">
        <hr>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update Project</button>
            @else
                <input type="hidden" name="document_number" value="PCT/{{$document_id}}/{{date('Y')}}">
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="document_type_id" value="10">
                <input type="hidden" name="link" value="projects/{{$document_id}}/10">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Project"><i class="si si-plus"></i> Create Project</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
