<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        
        <div class="form-group">
            <label for="name">Stage Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="Construction Stage Name" required>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent Construction Stage</label>
            <select name="parent_id" class="form-control">
                <option value="">-- No Parent (Top Level) --</option>
                @if(isset($parent_construction_stages))
                    @if(count($parent_construction_stages) > 0)
                        @foreach($parent_construction_stages as $parent)
                            @if(!isset($object->id) || $parent->id != $object->id)
                                <option value="{{ $parent->id }}" {{ ($parent->id == ($object->parent_id ?? '')) ? 'selected' : '' }}>
                                    {{ $parent->name }}
                                </option>
                            @endif
                        @endforeach
                    @else
                        <option value="" disabled>No parent construction stages available</option>
                    @endif
                @else
                    <option value="" disabled>Parent construction stages not loaded</option>
                @endif
            </select>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of this construction stage">{{ $object->description ?? '' }}</textarea>
        </div>
        
        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}" placeholder="Sort order (lower numbers appear first)">
        </div>
        
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ConstructionStage">Submit</button>
            @endif
        </div>
    </form>
</div>