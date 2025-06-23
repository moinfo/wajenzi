
{{-- Daily Report Form --}}
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
                    <label for="report_date" class="control-label required">Report Date</label>
                    <input type="text" class="form-control datepicker" id="input-report-date" name="report_date" value="{{ $object->report_date ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="supervisor_id" class="control-label required">Supervisor</label>
                    <select name="supervisor_id" id="input-supervisor" class="form-control" required="required">
                        <option value="">Select Supervisor</option>
                        @foreach ($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}" {{ ($supervisor->id == $object->supervisor_id) ? 'selected' : '' }}>{{ $supervisor->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="weather_conditions">Weather Conditions</label>
                    <input type="text" class="form-control" id="input-weather" name="weather_conditions" value="{{ $object->weather_conditions ?? '' }}" placeholder="Weather Conditions">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="labor_hours" class="control-label required">Labor Hours</label>
                    <input type="number" class="form-control" id="input-labor-hours" name="labor_hours" value="{{ $object->labor_hours ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="work_completed" class="control-label required">Work Completed</label>
                    <textarea class="form-control" id="input-work-completed" name="work_completed" rows="3" required="required">{{ $object->work_completed ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="materials_used" class="control-label required">Materials Used</label>
                    <textarea class="form-control" id="input-materials-used" name="materials_used" rows="3" required="required">{{ $object->materials_used ?? '' }}</textarea>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="issues_faced">Issues Faced</label>
                    <textarea class="form-control" id="input-issues" name="issues_faced" rows="3">{{ $object->issues_faced ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="DailyReport">Submit</button>
            @endif
        </div>
    </form>
</div>
