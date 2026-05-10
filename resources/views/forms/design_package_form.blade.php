<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Package Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="e.g. SILVER" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Building Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="rise_type" required>
                        <option value="low"  {{ ($object->rise_type ?? '') === 'low'  ? 'selected' : '' }}>Low-Rise (Single Storey)</option>
                        <option value="high" {{ ($object->rise_type ?? '') === 'high' ? 'selected' : '' }}>High-Rise (Multi Storey)</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Price (USD) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="price_usd" value="{{ $object->price_usd ?? '' }}" step="0.01" min="0" required>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            <label>Included Services <span class="text-danger">*</span></label>
            <small class="text-muted d-block mb-2">Check all services bundled in this package (base + add-ons)</small>
            @php
                $included = $object->included_services ?? [];
                $baseServices = ['Architectural design', 'Structural design'];
            @endphp
            <div class="row">
                @foreach($baseServices as $svc)
                <div class="col-md-6">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="included_services[]"
                            value="{{ $svc }}" {{ in_array($svc, $included) ? 'checked' : '' }}>
                        <label class="form-check-label">{{ $svc }} <span class="badge bg-info ms-1">Core</span></label>
                    </div>
                </div>
                @endforeach
                @foreach($design_service_addons ?? [] as $addon)
                <div class="col-md-6">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="included_services[]"
                            value="{{ $addon->name }}" {{ in_array($addon->name, $included) ? 'checked' : '' }}>
                        <label class="form-check-label">{{ $addon->name }} <span class="badge bg-secondary ms-1">Add-on</span></label>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Sort Order</label>
                    <input type="number" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}" min="0">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3 pt-4">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ ($object->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label">Active</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="DesignServicePackage">Submit</button>
            @endif
        </div>
    </form>
</div>
