
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Expenses Sub Category</label>
            <input type="text" class="form-control" id="input-item-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Expense Sub Category Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Expense Category</label>
            <select name="expenses_category_id" id="input-ifd-id" class="form-control" required>

                <option value="">Select Expense Category</option>

                @foreach ($expenses_categories as $expenses_category)
                    <option value="{{ $expenses_category->id }}" {{ ( $expenses_category->id == $object->expenses_category_id) ? 'selected' : '' }}> {{ $expenses_category->name }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">is Deducted</label>
            <select name="is_financial" id="is_financial" class="form-control" required>
                @foreach ($transfers as $transfer)
                    @if($transfer['name'] != 'CAN BE BOTH')
                    <option value="{{ $transfer['name'] }}" {{ ( $transfer['name'] == $object->is_financial) ? 'selected' : '' }}> {{ $transfer['name'] }} </option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ExpenseCategory">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
