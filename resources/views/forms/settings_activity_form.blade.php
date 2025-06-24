<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        
        <div class="form-group">
            <label for="construction_stage_id">Construction Stage <span class="text-danger">*</span></label>
            <select name="construction_stage_id" class="form-control" required>
                <option value="">Select Construction Stage</option>
                @foreach($construction_stages as $stage)
                    <option value="{{ $stage->id }}" {{ ($stage->id == ($object->construction_stage_id ?? '')) ? 'selected' : '' }}>
                        {{ $stage->name }}
                    </option>
                @endforeach
            </select>
        </div>
        
        <div class="form-group">
            <label for="name">Activity Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="Activity Name" required>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of this activity">{{ $object->description ?? '' }}</textarea>
        </div>
        
        <div class="form-group">
            <label for="sort_order">Sort Order</label>
            <input type="number" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}" placeholder="Sort order within the stage">
        </div>
        
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Activity">Submit</button>
            @endif
        </div>
    </form>
</div>