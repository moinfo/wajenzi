
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-supplier" class="control-label required">Supplier</label>
            <select name="supplier_id" id="input-supplier-id" class="form-control" required>
                <option value=""></option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier['id'] }}" {{ ( $supplier['id'] == $object->supplier_id) ? 'selected' : '' }}> {{ $supplier['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-bank" class="control-label required">Bank</label>
            <select name="bank_id" id="input-bank-id" class="form-control" required>
                <option value=""></option>
            @foreach ($banks as $bank)
                    <option value="{{ $bank['id'] }}" {{ ( $bank['id'] == $object->bank_id) ? 'selected' : '' }}> {{ $bank['name'] }} </option>
                @endforeach
            </select>
        </div>

        <div class="form-group" >
            <label for="example-nf-account_name">Account Name</label>
            <input type="text" class="form-control" id="input-account_name" name="account_name" value="{{ $object->account_name ?? '' }}" placeholder="Account Name" required>
        </div>
        <div class="form-group" >
            <label for="example-nf-account_number">Account Number</label>
            <input type="text" class="form-control" id="input-account_number" name="account_number" value="{{ $object->account_number ?? '' }}" placeholder="Account Number" required>
        </div>


        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem" value="SupplierContact"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary" name="addItem" value="SupplierContact">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
