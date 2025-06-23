
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-supplier" class="control-label required">Category</label>
            <select name="category_id" id="input-category-id" class="form-control" required>

                <option value="">Select Category</option>

                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ ( $category->id == $object->category_id) ? 'selected' : '' }}> {{ $category->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Sub Category</label>
            <input type="text" class="form-control" id="input-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-supplier" class="control-label required">Billing Cycle</label>
            <select name="billing_cycle" id="input-billing_cycle" class="form-control" required>

                <option value="">Select Billing Cycle</option>

                @foreach ($billing_cycles as $billing_cycle)
                    <option value="{{ $billing_cycle['id'] }}" {{ ( $billing_cycle['id'] == $object->billing_cycle) ? 'selected' : '' }}> {{ $billing_cycle['name'] }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Amount</label>
            <input type="text" class="form-control amount" id="input-price" name="price" value="{{ $object->price ?? '' }}" placeholder="Amount" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Description</label>
            <textarea type="text" class="form-control" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Bank">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
