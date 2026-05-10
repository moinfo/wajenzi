<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-5">
                <div class="form-group mb-3">
                    <label>Structure Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="e.g. Swimming pool" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Rate (TZS per m²) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="rate_tzs_per_sqm" value="{{ $object->rate_tzs_per_sqm ?? '' }}" step="0.01" min="0" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label>Sort Order</label>
                    <input type="number" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}" min="0">
                </div>
            </div>
        </div>
        <div class="form-group mb-3">
            <div class="form-check">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ ($object->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label">Active</label>
            </div>
        </div>
        <div class="form-group mb-3">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="DesignSpecialStructure">Submit</button>
            @endif
        </div>
    </form>
</div>
