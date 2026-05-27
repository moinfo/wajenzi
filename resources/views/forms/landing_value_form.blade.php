{{-- Landing Core Value Form (loaded via loadFormModal) --}}
@php
    $isEdit = (bool)($object->id ?? null);
    $loc = fn ($val) => is_array($val) ? ($val['en'] ?? '') : '';
    $action = $isEdit
        ? route('landing_values.update', $object->id)
        : route('landing_values.store');
@endphp

<div class="block-content">
    <form method="post" action="{{ $action }}" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label class="control-label required">Title (English)</label>
                    <input type="text" class="form-control" name="title" value="{{ $loc($object->title ?? null) }}" placeholder="e.g. INTEGRITY" required>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label>Description (English)</label>
                    <textarea class="form-control" name="description" rows="4">{{ $loc($object->description ?? null) }}</textarea>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>Order</label>
                    <input type="number" min="0" class="form-control" name="sort_order" value="{{ $object->sort_order ?? 0 }}">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label class="d-block">Published</label>
                    <label class="css-control css-control-primary css-switch">
                        <input type="checkbox" class="css-control-input" name="is_published" value="1" {{ ($object->is_published ?? true) ? 'checked' : '' }}>
                        <span class="css-control-indicator"></span>
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group text-right mt-10">
            <button type="submit" class="btn btn-alt-primary"><i class="si si-check"></i> {{ $isEdit ? 'Update' : 'Save' }} Core Value</button>
        </div>
    </form>
</div>
