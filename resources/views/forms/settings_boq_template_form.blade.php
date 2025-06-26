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
                            @php
                                // Group building types by parent/child hierarchy
                                $sortedBuildingTypes = collect();
                                
                                // First, get all parent building types (no parent_id) sorted by name
                                $parents = $building_types->where('parent_id', null)->sortBy('name');
                                
                                // For each parent, add it and then add its children
                                foreach($parents as $parent) {
                                    $sortedBuildingTypes->push($parent);
                                    
                                    // Get children of this parent and sort by name
                                    $children = $building_types->where('parent_id', $parent->id)->sortBy('name');
                                    
                                    foreach($children as $child) {
                                        $sortedBuildingTypes->push($child);
                                    }
                                }
                            @endphp
                            
                            @foreach($sortedBuildingTypes as $type)
                                <option value="{{ $type->id }}" {{ ($type->id == ($object->building_type_id ?? '')) ? 'selected' : '' }}>
                                    @if($type->parent_id)
                                        {{-- Child building type --}}
                                        &nbsp;&nbsp;&nbsp;&nbsp;└─ {{ $type->name }} 
                                        <span style="color: #6c757d;">({{ $type->parent->name ?? 'Unknown Parent' }})</span>
                                    @else
                                        {{-- Parent building type --}}
                                        🏢 {{ $type->name }}
                                    @endif
                                </option>
                            @endforeach
                        @endif
                    </select>
                    <small class="form-text text-muted">
                        🏢 = Parent building type &nbsp;&nbsp;|&nbsp;&nbsp; └─ = Child building type
                    </small>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="roof_type">Roof Type</label>
                    <select name="roof_type" class="form-control">
                        <option value="">Select Roof Type</option>
                        <option value="pitched_roof" {{ (($object->roof_type ?? '') == 'pitched_roof') ? 'selected' : '' }}>Pitched Roof</option>
                        <option value="hidden_roof" {{ (($object->roof_type ?? '') == 'hidden_roof') ? 'selected' : '' }}>Hidden Roof</option>
                        <option value="concrete_roof" {{ (($object->roof_type ?? '') == 'concrete_roof') ? 'selected' : '' }}>Concrete Roof</option>
                    </select>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label for="no_of_rooms">Number of Rooms</label>
                    <select name="no_of_rooms" class="form-control">
                        <option value="">Select No. of Rooms</option>
                        <option value="1" {{ (($object->no_of_rooms ?? '') == '1') ? 'selected' : '' }}>1 Room</option>
                        <option value="2" {{ (($object->no_of_rooms ?? '') == '2') ? 'selected' : '' }}>2 Rooms</option>
                        <option value="3" {{ (($object->no_of_rooms ?? '') == '3') ? 'selected' : '' }}>3 Rooms</option>
                        <option value="4" {{ (($object->no_of_rooms ?? '') == '4') ? 'selected' : '' }}>4 Rooms</option>
                        <option value="5+" {{ (($object->no_of_rooms ?? '') == '5+') ? 'selected' : '' }}>5+ Rooms</option>
                    </select>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label for="square_metre">Square Metre (SQM)</label>
                    <input type="number" step="0.01" class="form-control" name="square_metre" value="{{ $object->square_metre ?? '' }}" placeholder="0.00">
                    <small class="form-text text-muted">Total area in square metres</small>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="form-group">
                    <label for="run_metre">Run Metre</label>
                    <input type="number" step="0.01" class="form-control" name="run_metre" value="{{ $object->run_metre ?? '' }}" placeholder="0.00">
                    <small class="form-text text-muted">Linear measurement</small>
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

