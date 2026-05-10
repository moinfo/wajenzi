<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Currency Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="e.g. Euro" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="code" value="{{ $object->code ?? '' }}" placeholder="e.g. EUR" maxlength="10" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label>Symbol <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="symbol" value="{{ $object->symbol ?? '' }}" placeholder="e.g. €" required>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label>Rate to USD <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="rate_to_usd" value="{{ $object->rate_to_usd ?? 1 }}" step="0.000001" min="0.000001" required>
                    <small class="text-muted">Units of this currency that equal 1 USD. (TZS = 2640, USD = 1.0, EUR ≈ 0.92)</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group mb-3">
                    <label>Base Currency?</label>
                    <select class="form-select" name="is_base">
                        <option value="NO" {{ ($object->is_base ?? 'NO') === 'NO' ? 'selected' : '' }}>No</option>
                        <option value="YES" {{ ($object->is_base ?? 'NO') === 'YES' ? 'selected' : '' }}>Yes</option>
                    </select>
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
        <div class="form-group mb-3">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Currency">Submit</button>
            @endif
        </div>
    </form>
</div>
