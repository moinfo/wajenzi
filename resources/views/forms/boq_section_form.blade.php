<?php
    $boq_id = request('boq_id') ?? ($object->boq_id ?? null);
    $boq_sections = $boq_id
        ? \App\Models\ProjectBoqSection::where('boq_id', $boq_id)->orderBy('sort_order')->get()
        : collect();

    // Build indented list for parent dropdown
    if (!function_exists('buildSectionOptions')) {
        function buildSectionOptions($sections, $parentId = null, $depth = 0, $excludeId = null) {
            $options = [];
            foreach ($sections->where('parent_id', $parentId) as $section) {
                if ($section->id == $excludeId) continue;
                $options[] = ['id' => $section->id, 'name' => str_repeat('— ', $depth) . $section->name];
                $options = array_merge($options, buildSectionOptions($sections, $section->id, $depth + 1, $excludeId));
            }
            return $options;
        }
    }

    $parentOptions = buildSectionOptions($boq_sections, null, 0, $object->id ?? null);
?>
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <input type="hidden" name="boq_id" value="{{ $boq_id }}">

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="name" class="control-label required">Section Name</label>
                    <input type="text" class="form-control" id="input-section-name" name="name"
                        value="{{ $object->name ?? '' }}" required
                        placeholder="e.g., Substructure, Foundation Setting, Excavation">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="parent_id" class="control-label">Parent Section</label>
                    <select name="parent_id" id="input-parent-section" class="form-control">
                        <option value="">— Root Level (No Parent) —</option>
                        @foreach ($parentOptions as $opt)
                            <option value="{{ $opt['id'] }}" {{ ($opt['id'] == ($object->parent_id ?? '')) ? 'selected' : '' }}>
                                {{ $opt['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="sort_order" class="control-label">Sort Order</label>
                    <input type="number" class="form-control" id="input-sort-order" name="sort_order"
                        value="{{ $object->sort_order ?? 0 }}" min="0">
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="control-label">Description</label>
                    <textarea class="form-control" id="input-section-description" name="description" rows="2"
                        placeholder="Optional description for this section">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <hr>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem" value="ProjectBoqSection">
                    <i class="si si-check"></i> Update Section
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectBoqSection">
                    <i class="si si-plus"></i> Add Section
                </button>
            @endif
        </div>
    </form>
</div>
