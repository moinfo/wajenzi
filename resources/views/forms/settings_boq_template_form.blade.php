<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    <label for="name">Template Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="BOQ Template Name" required>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <label for="building_type_id">Building Type</label>
                    <select name="building_type_id" class="form-control">
                        <option value="">Select Building Type</option>
                        @if(isset($building_types))
                            @foreach($building_types as $type)
                            <option value="{{ $type->id }}" {{ ($type->id == ($object->building_type_id ?? '')) ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of this BOQ template">{{ $object->description ?? '' }}</textarea>
        </div>
        
        <div class="form-group">
            <label for="is_active">Status</label>
            <select name="is_active" class="form-control" required>
                <option value="1" {{ ($object->is_active ?? true) ? 'selected' : '' }}>Active</option>
                <option value="0" {{ isset($object->is_active) && !$object->is_active ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        
        @if(!($object->id ?? null))
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i> 
            <strong>Note:</strong> After creating the template, you'll be able to configure stages, activities, and sub-activities through the template builder.
        </div>
        @endif
        
        @if($object->id ?? null)
        <div class="form-group">
            <label>Template Structure Configuration</label>
            <div class="alert alert-warning">
                <i class="fa fa-wrench"></i> 
                Use the <strong>Template Builder</strong> to configure stages, activities, and sub-activities for this template.
                <br>
                <a href="{{ route('hr_settings_boq_template_builder', ['templateId' => $object->id]) }}" class="btn btn-sm btn-primary mt-2">
                    <i class="fa fa-cogs"></i> Open Template Builder
                </a>
            </div>
        </div>
        @endif
        
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <input type="hidden" name="created_by" value="{{ $object->created_by }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <input type="hidden" name="created_by" value="{{ auth()->id() }}">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BoqTemplate">Create Template</button>
            @endif
        </div>
    </form>
</div>

