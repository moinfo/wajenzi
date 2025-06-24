<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="Building Type Name" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of this building type">{{ $object->description ?? '' }}</textarea>
        </div>
        
        <div class="form-group">
            <label for="is_active">Status</label>
            <select name="is_active" class="form-control" required>
                <option value="1" {{ ($object->is_active ?? true) ? 'selected' : '' }}>Active</option>
                <option value="0" {{ isset($object->is_active) && !$object->is_active ? 'selected' : '' }}>Inactive</option>
            </select>
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