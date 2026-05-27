{{-- Landing Poster Form (loaded via loadFormModal) --}}
@php
    $isEdit = (bool)($object->id ?? null);
    $loc = fn ($val) => is_array($val) ? ($val['en'] ?? '') : '';
    $action = $isEdit
        ? route('landing_posters.update', $object->id)
        : route('landing_posters.store');
@endphp

<div class="block-content">
    <form method="post" action="{{ $action }}" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Title (optional)</label>
                    <input type="text" class="form-control" name="title" value="{{ $loc($object->title ?? null) }}">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Subtitle (optional)</label>
                    <input type="text" class="form-control" name="subtitle" value="{{ $loc($object->subtitle ?? null) }}">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Tap link (optional)</label>
                    <input type="url" class="form-control" name="link_url" placeholder="https://..." value="{{ $object->link_url ?? '' }}">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Video link (YouTube, optional)</label>
                    <input type="url" class="form-control" name="youtube_url" placeholder="https://youtu.be/..." value="{{ $object->youtube_url ?? '' }}">
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    <label class="control-label {{ $isEdit ? '' : 'required' }}">Banner image</label>
                    <input type="file" class="form-control" name="image" accept="image/*" {{ $isEdit ? '' : 'required' }}>
                    <small class="text-muted">Wide banner works best. PNG/JPG/WEBP, up to 8MB.</small>
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
                    <img src="{{ asset(ltrim($object->image, '/')) }}" style="height:90px;object-fit:cover;width:100%;max-width:360px;" class="rounded">
                </div>
            @endif
        </div>
        <div class="form-group text-right mt-10">
            <button type="submit" class="btn btn-alt-primary"><i class="si si-check"></i> {{ $isEdit ? 'Update' : 'Save' }} Poster</button>
        </div>
    </form>
</div>
