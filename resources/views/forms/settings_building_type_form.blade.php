<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="name">Building Type Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="Building Type Name" required>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent Building Type</label>
            <select name="parent_id" class="form-control">
                <option value="">-- No Parent (Top Level) --</option>
                @if(isset($parent_building_types))
                    @if(count($parent_building_types) > 0)
                        @foreach($parent_building_types as $parent)
                            @if(!isset($object->id) || $parent->id != $object->id)
                                <option value="{{ $parent->id }}" {{ ($parent->id == ($object->parent_id ?? '')) ? 'selected' : '' }}>
                                    {{ $parent->name }}
                                </option>
                            @endif
                        @endforeach
                    @else
                        <option value="" disabled>No parent building types available</option>
                    @endif
                @else
                    <option value="" disabled>Parent building types not loaded</option>
                @endif
            </select>
            @if(config('app.debug'))
                <small class="text-muted">
                    Debug: parent_building_types isset: {{ isset($parent_building_types) ? 'YES' : 'NO' }} | 
                    count: {{ isset($parent_building_types) ? count($parent_building_types) : 'N/A' }}
                </small>
            @endif
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of this building type">{{ $object->description ?? '' }}</textarea>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}" placeholder="Sort order">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="is_active">Status</label>
                    <select name="is_active" class="form-control" required>
                        <option value="1" {{ ($object->is_active ?? true) ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ isset($object->is_active) && !$object->is_active ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BuildingType">Submit</button>
            @endif
        </div>
    </form>
</div>