{{-- Landing Award Form (loaded via loadFormModal) --}}
@php
    $isEdit = (bool)($object->id ?? null);
    $loc = fn ($val) => is_array($val) ? ($val['en'] ?? '') : '';
    $action = $isEdit
        ? route('landing_awards.update', $object->id)
        : route('landing_awards.store');
@endphp

<div class="block-content">
    <form method="post" action="{{ $action }}" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-9">
                <div class="form-group">
                    <label class="control-label required">Title (English)</label>
                    <input type="text" class="form-control" name="title" value="{{ $loc($object->title ?? null) }}" required>
                </div>
            </div>
            <div class="col-sm-3">
                <div class="form-group">
                    <label>Year</label>
                    <input type="text" class="form-control" name="year" placeholder="2024" value="{{ $object->year ?? '' }}">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Subtitle</label>
                    <input type="text" class="form-control" name="subtitle" value="{{ $loc($object->subtitle ?? null) }}">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Awarding organization</label>
                    <input type="text" class="form-control" name="organization" value="{{ $loc($object->organization ?? null) }}">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Description (English)</label>
                    <textarea class="form-control" name="description" rows="3">{{ $loc($object->description ?? null) }}</textarea>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    <label>Award image</label>
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
            <button type="submit" class="btn btn-alt-primary"><i class="si si-check"></i> {{ $isEdit ? 'Update' : 'Save' }} Award</button>
        </div>
    </form>
</div>
