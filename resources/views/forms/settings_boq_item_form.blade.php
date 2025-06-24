<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        
        <div class="form-group">
            <label for="name">Item Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ $object->name ?? '' }}" placeholder="Item/Material Name" required>
        </div>
        
        <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" class="form-control">
                <option value="">Select Category</option>
                @if(isset($boq_item_categories))
                    @foreach($boq_item_categories as $category)
                        <option value="{{ $category->id }}" {{ ($category->id == ($object->category_id ?? '')) ? 'selected' : '' }}>
                            @if($category->parent){{ $category->parent->name }} > @endif{{ $category->name }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="unit">Unit of Measurement</label>
                    <input type="text" class="form-control" name="unit" value="{{ $object->unit ?? '' }}" placeholder="e.g., kg, m², pieces, bags">
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="form-group">
                    <label for="base_price">Base Price</label>
                    <input type="number" step="0.01" class="form-control" name="base_price" value="{{ $object->base_price ?? '' }}" placeholder="Base unit price">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">Description</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Detailed description of the item/material">{{ $object->description ?? '' }}</textarea>
        </div>
        
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BoqTemplateItem">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $(document).ready(function() {
        // Format number inputs with thousand separators for base_price
        $("input[name='base_price']").each((i, ele) => {
            let clone = $(ele).clone(false);
            clone.attr("type", "text");
            let ele1 = $(ele);
            clone.val(Number(ele1.val()).toLocaleString("en"));
            $(ele).after(clone);
            $(ele).hide();

            clone.mouseenter(() => {
                ele1.show();
                clone.hide();
            });

            $(ele).mouseleave(() => {
                clone.show();
                ele1.hide();
            });
        });
    });
</script>