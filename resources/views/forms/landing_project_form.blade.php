{{-- Landing Portfolio Project Form (loaded via loadFormModal) --}}
@php
    $isEdit = (bool)($object->id ?? null);
    $loc = function ($val) {
        return is_array($val) ? ($val['en'] ?? '') : '';
    };
    $action = $isEdit
        ? route('landing_portfolio.update', $object->id)
        : route('landing_portfolio.store');
@endphp

<div class="block-content">
    <form method="post" action="{{ $action }}" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="row">
            <div class="col-sm-8">
                <div class="form-group">
                    <label class="control-label required">Title (English)</label>
                    <input type="text" class="form-control" name="title" value="{{ $loc($object->title ?? null) }}" required>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label class="control-label">Badge / Category</label>
                    <input type="text" class="form-control" name="category" placeholder="e.g. 3D Design" value="{{ $loc($object->category ?? null) }}">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Description (English)</label>
                    <textarea class="form-control" name="description" rows="3">{{ $loc($object->description ?? null) }}</textarea>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group">
                    <label>Price (TZS)</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="price_tzs" value="{{ $object->price_tzs ?? '' }}">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Price (USD)</label>
                    <input type="number" step="0.01" min="0" class="form-control" name="price_usd" value="{{ $object->price_usd ?? '' }}">
                </div>
            </div>

            <div class="col-sm-6">
                <div class="form-group">
                    <label>Video link (YouTube)</label>
                    <input type="url" class="form-control" name="youtube_url" placeholder="https://youtu.be/..." value="{{ $object->youtube_url ?? '' }}">
                    <small class="text-muted">Upload the video to YouTube, then paste the link here.</small>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>3D tour link</label>
                    <input type="url" class="form-control" name="model_3d_url" placeholder="https://sketchfab.com/... or YouTube" value="{{ $object->model_3d_url ?? '' }}">
                    <small class="text-muted">Sketchfab / Matterport / YouTube link for the 3D walkthrough.</small>
                </div>
            </div>

            {{-- Amenity chips --}}
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Amenities / Feature chips</label>
                    <div id="amenities-wrap">
                        @php $amenities = $isEdit ? $object->amenities : collect(); @endphp
                        @forelse($amenities as $a)
                            <div class="input-group mb-5 amenity-row">
                                <input type="text" class="form-control" name="amenities[]" value="{{ $loc($a->label) }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-alt-danger remove-amenity"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        @empty
                            <div class="input-group mb-5 amenity-row">
                                <input type="text" class="form-control" name="amenities[]" placeholder="e.g. Bedrooms">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-alt-danger remove-amenity"><i class="fa fa-times"></i></button>
                                </div>
                            </div>
                        @endforelse
                    </div>
                    <button type="button" id="add-amenity" class="btn btn-sm btn-alt-secondary mt-5"><i class="fa fa-plus"></i> Add amenity</button>
                </div>
            </div>

            {{-- Gallery upload --}}
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Add images to gallery</label>
                    <input type="file" class="form-control" name="images[]" accept="image/*" multiple>
                    <small class="text-muted">First image becomes the cover if none is set. PNG/JPG/WEBP, up to 8MB each.</small>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    <label>Sort order</label>
                    <input type="number" min="0" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label class="d-block">Featured</label>
                    <label class="css-control css-control-primary css-switch">
                        <input type="checkbox" class="css-control-input" name="is_featured" value="1" {{ ($object->is_featured ?? false) ? 'checked' : '' }}>
                        <span class="css-control-indicator"></span> Show as featured
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label class="d-block">Published</label>
                    <label class="css-control css-control-primary css-switch">
                        <input type="checkbox" class="css-control-input" name="is_published" value="1" {{ ($object->is_published ?? true) ? 'checked' : '' }}>
                        <span class="css-control-indicator"></span> Visible in app
                    </label>
                </div>
            </div>
        </div>

        @if($isEdit && $object->images->count())
            <hr>
            <label>Current gallery</label>
            <div class="row">
                @foreach($object->images as $img)
                    <div class="col-4 col-md-3 mb-10 text-center">
                        <img src="{{ asset(ltrim($img->file, '/')) }}" class="img-fluid rounded mb-5" style="height:80px;object-fit:cover;width:100%;">
                        <div class="btn-group btn-group-sm d-block">
                            @if(!$img->is_primary)
                                <button type="button" class="btn btn-alt-secondary"
                                        onclick="submitImageAction('{{ route('landing_portfolio.image.primary', $img->id) }}')" title="Set as cover">
                                    <i class="fa fa-star-o"></i>
                                </button>
                            @else
                                <span class="badge badge-success">Cover</span>
                            @endif
                            <button type="button" class="btn btn-alt-danger"
                                    onclick="submitImageAction('{{ route('landing_portfolio.image.delete', $img->id) }}')" title="Remove">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="form-group text-right mt-10">
            <button type="submit" class="btn btn-alt-primary"><i class="si si-check"></i> {{ $isEdit ? 'Update' : 'Save' }} Project</button>
        </div>
    </form>
</div>

<script>
    (function () {
        var wrap = document.getElementById('amenities-wrap');
        document.getElementById('add-amenity').addEventListener('click', function () {
            var row = document.createElement('div');
            row.className = 'input-group mb-5 amenity-row';
            row.innerHTML = '<input type="text" class="form-control" name="amenities[]" placeholder="e.g. Parking">' +
                '<div class="input-group-append"><button type="button" class="btn btn-alt-danger remove-amenity"><i class="fa fa-times"></i></button></div>';
            wrap.appendChild(row);
        });
        wrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.remove-amenity');
            if (btn) { btn.closest('.amenity-row').remove(); }
        });
    })();

    function submitImageAction(url) {
        var token = (document.querySelector('meta[name="csrf-token"]') || {}).content
            || (document.querySelector('input[name="_token"]') || {}).value || '';
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = url;
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = '_token';
        input.value = token;
        f.appendChild(input);
        document.body.appendChild(f);
        f.submit();
    }
</script>
