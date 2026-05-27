{{-- Landing Service Form (loaded via loadFormModal) --}}
@php
    $isEdit = (bool)($object->id ?? null);
    $loc = fn ($val) => is_array($val) ? ($val['en'] ?? '') : '';
    $action = $isEdit
        ? route('landing_services.update', $object->id)
        : route('landing_services.store');
    $features = $isEdit ? ($object->features ?? []) : [];
@endphp

<div class="block-content">
    <form method="post" action="{{ $action }}" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label class="control-label required">Title (English)</label>
                    <input type="text" class="form-control" name="title" value="{{ $loc($object->title ?? null) }}" required>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Short description</label>
                    <textarea class="form-control" name="short_description" rows="2">{{ $loc($object->short_description ?? null) }}</textarea>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Full description</label>
                    <textarea class="form-control" name="full_description" rows="3">{{ $loc($object->full_description ?? null) }}</textarea>
                </div>
            </div>

            <div class="col-sm-12">
                <div class="form-group">
                    <label>Features</label>
                    <div id="features-wrap">
                        @forelse($features as $f)
                            <div class="input-group mb-5 feature-row">
                                <input type="text" class="form-control" name="features[]" value="{{ is_array($f) ? ($f['en'] ?? '') : $f }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-alt-danger remove-feature"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        @empty
                            <div class="input-group mb-5 feature-row">
                                <input type="text" class="form-control" name="features[]" placeholder="e.g. Cost Estimation">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-alt-danger remove-feature"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" id="add-feature" class="btn btn-sm btn-alt-secondary mt-5"><i class="fa fa-plus"></i> Add feature</button>
                </div>
            </div>

            <div class="col-sm-8">
                <div class="form-group">
                    <label>Service image</label>
                    <input type="file" class="form-control" name="image" accept="image/*">
                    <small class="text-muted">PNG/JPG/WEBP, up to 8MB. Leave empty to keep the current image.</small>
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label>Order</label>
                    <input type="number" min="0" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}">
                </div>
            </div>
            <div class="col-sm-2">
                <div class="form-group">
                    <label class="d-block">Published</label>
                    <label class="css-control css-control-primary css-switch">
                        <input type="checkbox" class="css-control-input" name="is_published" value="1" {{ ($object->is_published ?? true) ? 'checked' : '' }}>
                        <span class="css-control-indicator"></span>
                    </label>
                </div>
            </div>
            @if($isEdit && $object->image)
                <div class="col-sm-12 mb-10">
                    <img src="{{ asset(ltrim($object->image, '/')) }}" style="height:90px;object-fit:cover;" class="rounded">
                </div>
            @endif
        </div>
        <div class="form-group text-right mt-10">
            <button type="submit" class="btn btn-alt-primary"><i class="si si-check"></i> {{ $isEdit ? 'Update' : 'Save' }} Service</button>
        </div>
    </form>
</div>

<script>
    (function () {
        var wrap = document.getElementById('features-wrap');
        document.getElementById('add-feature').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'input-group mb-5 feature-row';
            row.innerHTML = '<input type="text" class="form-control" name="features[]" placeholder="e.g. Project Management">' +
                '<div class="input-group-append"><button type="button" class="btn btn-alt-danger remove-feature"><i class="fa fa-times"></i></button></div>';
            wrap.appendChild(row);
        });
        wrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-feature');
            if (btn) { btn.closest('.feature-row').remove(); }
        });
    })();
</script>
