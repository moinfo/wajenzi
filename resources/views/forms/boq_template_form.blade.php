<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="name" class="control-label required">Template Name</label>
            <input type="text" class="form-control" name="name"
                value="{{ $object->name ?? '' }}" required
                placeholder="e.g., Residential House Foundation">
        </div>

        <div class="form-group">
            <label for="description" class="control-label">Description</label>
            <textarea class="form-control" name="description" rows="3"
                placeholder="Brief description of what this template covers">{{ $object->description ?? '' }}</textarea>
        </div>

        <div class="form-group">
            <label for="type" class="control-label required">Type</label>
            <select name="type" class="form-control" required>
                <option value="combined" {{ (($object->type ?? 'combined') == 'combined') ? 'selected' : '' }}>Combined</option>
                <option value="material" {{ (($object->type ?? '') == 'material') ? 'selected' : '' }}>Material Only</option>
                <option value="labour" {{ (($object->type ?? '') == 'labour') ? 'selected' : '' }}>Labour Only</option>
            </select>
        </div>

        <hr>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem" value="ProjectBoqTemplate">
                    <i class="si si-check"></i> Update Template
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectBoqTemplate">
                    <i class="si si-plus"></i> Create Template
                </button>
            @endif
        </div>
    </form>
</div>
