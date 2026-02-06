<?php
    $boq_id = request('boq_id') ?? ($object->boq_id ?? null);
    $boq_sections = $boq_id
        ? \App\Models\ProjectBoqSection::where('boq_id', $boq_id)->orderBy('sort_order')->get()
        : collect();

    // Build indented list for section dropdown
    if (!function_exists('buildItemSectionOptions')) {
        function buildItemSectionOptions($sections, $parentId = null, $depth = 0) {
            $options = [];
            foreach ($sections->where('parent_id', $parentId) as $section) {
                $options[] = ['id' => $section->id, 'name' => str_repeat('— ', $depth) . $section->name];
                $options = array_merge($options, buildItemSectionOptions($sections, $section->id, $depth + 1));
            }
            return $options;
        }
    }

    $sectionOptions = buildItemSectionOptions($boq_sections);
?>
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <input type="hidden" name="boq_id" value="{{ $boq_id }}">

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="section_id" class="control-label">Section</label>
                    <select name="section_id" id="input-item-section" class="form-control">
                        <option value="">— No Section —</option>
                        @foreach ($sectionOptions as $opt)
                            <option value="{{ $opt['id'] }}" {{ ($opt['id'] == ($object->section_id ?? '')) ? 'selected' : '' }}>
                                {{ $opt['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label for="item_type" class="control-label required">Item Type</label>
                    <select name="item_type" id="input-item-type" class="form-control" required>
                        <option value="material" {{ (($object->item_type ?? 'material') == 'material') ? 'selected' : '' }}>Material</option>
                        <option value="labour" {{ (($object->item_type ?? '') == 'labour') ? 'selected' : '' }}>Labour</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="description" class="control-label required">Description</label>
                    <input type="text" class="form-control" id="input-description" name="description"
                        value="{{ $object->description ?? '' }}" required
                        placeholder="e.g., Portland Cement 42.5N, Mason Labour">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="specification" class="control-label">Specification</label>
                    <input type="text" class="form-control" id="input-specification" name="specification"
                        value="{{ $object->specification ?? '' }}"
                        placeholder="e.g., 50kg bag, Per day rate">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label for="quantity" class="control-label required">Quantity</label>
                    <input type="number" step="0.01" class="form-control" id="input-quantity" name="quantity"
                        value="{{ $object->quantity ?? '' }}" required min="0">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="unit" class="control-label required">Unit</label>
                    <input type="text" class="form-control" id="input-unit" name="unit"
                        value="{{ $object->unit ?? '' }}" required
                        placeholder="e.g., bags, m3, days">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="unit_price" class="control-label required">Unit Price</label>
                    <input type="number" step="0.01" class="form-control" id="input-unit-price" name="unit_price"
                        value="{{ $object->unit_price ?? '' }}" required min="0">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-group">
                    <label for="total_price" class="control-label">Total Price</label>
                    <input type="number" step="0.01" class="form-control" id="input-total-price" name="total_price"
                        value="{{ $object->total_price ?? '' }}" readonly
                        style="background-color: #f0f0f0; font-weight: bold;">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="sort_order" class="control-label">Sort Order</label>
                    <input type="number" class="form-control" id="input-item-sort-order" name="sort_order"
                        value="{{ $object->sort_order ?? 0 }}" min="0">
                </div>
            </div>
        </div>

        <hr>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem" value="ProjectBoqItem">
                    <i class="si si-check"></i> Update Item
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectBoqItem">
                    <i class="si si-plus"></i> Add Item
                </button>
            @endif
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        function calculateTotal() {
            var qty = parseFloat($('#input-quantity').val()) || 0;
            var price = parseFloat($('#input-unit-price').val()) || 0;
            $('#input-total-price').val((qty * price).toFixed(2));
        }

        $('#input-quantity, #input-unit-price').on('input change', calculateTotal);

        // Calculate on load if editing
        if ($('#input-quantity').val() && $('#input-unit-price').val()) {
            calculateTotal();
        }
    });
</script>
