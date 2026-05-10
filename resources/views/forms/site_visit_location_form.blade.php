<div class="block-content">
    <form method="post" action="{{ route('hr_settings_site_visit_locations') }}" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label>Location Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="e.g. Arusha" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label>Sort Order</label>
                    <input type="number" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}" min="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3 pt-4">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ ($object->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
        </div>
        <hr>
        <p class="text-muted fs-sm mb-3">Preset amounts are suggested values that pre-fill the calculator fields for this location.</p>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label>Base Cost (TZS) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="base_cost_tzs" value="{{ $object->base_cost_tzs ?? 150000 }}" step="0.01" min="0" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label>Preset Travel (TZS)</label>
                    <input type="number" class="form-control" name="preset_travel_tzs" value="{{ $object->preset_travel_tzs ?? 0 }}" step="0.01" min="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label>Preset Local Transport (TZS)</label>
                    <input type="number" class="form-control" name="preset_local_tzs" value="{{ $object->preset_local_tzs ?? 0 }}" step="0.01" min="0">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label>Preset Allowance (TZS)</label>
                    <input type="number" class="form-control" name="preset_allowance_tzs" value="{{ $object->preset_allowance_tzs ?? 0 }}" step="0.01" min="0">
                </div>
            </div>
        </div>
        <div class="form-group mb-3">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="SiteVisitLocation">Submit</button>
            @endif
        </div>
    </form>
</div>
