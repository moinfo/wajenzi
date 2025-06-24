<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="activity_id">Activity <span class="text-danger">*</span></label>
                    <select name="activity_id" class="form-control" required>
                        <option value="">Select Activity</option>
                        @foreach($activities as $activity)
                            <option value="{{ $activity->id }}" {{ ($activity->id == ($object->activity_id ?? '')) ? 'selected' : '' }}>
                                {{ $activity->constructionStage->name }} - {{ $activity->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="Sub-Activity Name" required>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="2" placeholder="Brief description of this sub-activity">{{ $object->description ?? '' }}</textarea>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="estimated_duration_hours">Duration <span class="text-danger">*</span></label>
                    <input type="number" step="0.1" class="form-control" name="estimated_duration_hours" value="{{ $object->estimated_duration_hours ?? '' }}" placeholder="Duration" required>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="duration_unit">Unit</label>
                    <select name="duration_unit" class="form-control">
                        @foreach($duration_units as $unit)
                            <option value="{{ $unit['name'] }}" {{ ($unit['name'] == ($object->duration_unit ?? 'days')) ? 'selected' : '' }}>
                                {{ ucfirst($unit['name']) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="labor_requirement">Workers Required</label>
                    <input type="number" class="form-control" name="labor_requirement" value="{{ $object->labor_requirement ?? '' }}" placeholder="Number of workers">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="skill_level">Skill Level</label>
                    <select name="skill_level" class="form-control">
                        @foreach($skill_levels as $skill)
                            <option value="{{ $skill['name'] }}" {{ ($skill['name'] == ($object->skill_level ?? 'semi_skilled')) ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $skill['name'])) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}" placeholder="Sort order">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="can_run_parallel" value="1" {{ ($object->can_run_parallel ?? false) ? 'checked' : '' }}>
                        Can run in parallel with other activities
                    </label>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="weather_dependent" value="1" {{ ($object->weather_dependent ?? false) ? 'checked' : '' }}>
                        Weather dependent activity
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="SubActivity">Submit</button>
            @endif
        </div>
    </form>
</div>