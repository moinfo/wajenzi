<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="name">Category Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="Category Name" required>
        </div>

        <div class="form-group">
            <label for="parent_id">Parent Category</label>
            <select name="parent_id" class="form-control">
                <option value="">-- No Parent (Top Level) --</option>
                @if(isset($parent_boq_item_categories))
                    @foreach($parent_boq_item_categories as $parent)
                        <option value="{{ $parent->id }}" {{ ($parent->id == ($object->parent_id ?? '')) ? 'selected' : '' }}>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Brief description of this category">{{ $object->description ?? '' }}</textarea>
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
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BoqItemCategory">Submit</button>
            @endif
        </div>
    </form>
</div>
