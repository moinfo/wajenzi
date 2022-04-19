
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Name</label>
            <input type="text" class="form-control" id="input-supplier-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Supplier Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-phone">Phone</label>
            <input type="text" class="form-control" id="input-phone" name="phone" value="{{ $object->phone ?? '' }}" placeholder="Phone Number" >
        </div>
{{--        <div class="form-group" >--}}
{{--            <label for="example-nf-account_name">Account Name</label>--}}
{{--            <input type="text" class="form-control" id="input-account_name" name="account_name" value="{{ $object->account_name ?? '' }}" placeholder="Account Name" >--}}
{{--        </div>--}}
{{--        <div class="form-group" >--}}
{{--            <label for="example-nf-crdb_account">CRDB Account</label>--}}
{{--            <input type="text" class="form-control" id="input-crdb_account" name="crdb_account" value="{{ $object->crdb_account ?? '' }}" placeholder="CRDB Account" >--}}
{{--        </div>--}}
{{--        <div class="form-group" >--}}
{{--            <label for="example-nf-nmb_account">NMB Account</label>--}}
{{--            <input type="text" class="form-control" id="input-nmb_account" name="nmb_account" value="{{ $object->nmb_account ?? '' }}" placeholder="NMB Account" >--}}
{{--        </div>--}}
{{--        <div class="form-group" >--}}
{{--            <label for="example-nf-nbc_account">NBC Account</label>--}}
{{--            <input type="text" class="form-control" id="input-nbc_account" name="nbc_account" value="{{ $object->nbc_account ?? '' }}" placeholder="NBC Account" >--}}
{{--        </div>--}}
        <div class="form-group">
            <label for="example-nf-address">Address</label>
            <input type="text" class="form-control" id="input-address" name="address" value="{{ $object->address ?? '' }}" placeholder="Address">
        </div>
        <div class="form-group">
            <label for="example-nf-vrn" class="control-label required">VRN</label>
            <input type="text" class="form-control" id="input-vrn" name="vrn" value="{{ $object->vrn ?? '' }}" placeholder="Supplier VRN" required>
        </div>
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">is Transferred</label>
            <select name="is_transferred" id="is_transferred" class="form-control" required>
                @foreach ($transfers as $transfer)
                    <option value="{{ $transfer['name'] }}" {{ ( $transfer['name'] == $object->is_transferred) ? 'selected' : '' }}> {{ $transfer['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">Whitestar Supplier</label>
            <select name="whitestar_supplier_id" id="input-whitestar_supplier-id" class="form-control" required>
                <option value="">Select Whitestar Supplier</option>
                @foreach ($whitestar_suppliers as $whitestar_supplier)
                    <option value="{{ $whitestar_supplier->local_supplier_id }}" {{ ( $whitestar_supplier->local_supplier_id == $object->whitestar_supplier_id) ? 'selected' : '' }}> {{ $whitestar_supplier->first_name . ' '. $whitestar_supplier->last_name }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">System</label>
            <select name="system_id" id="input-system-id" class="form-control" required>
                @foreach ($systems as $system)
                    <option value="{{ $system['id'] }}" {{ ( $system['id'] == $object->system_id) ? 'selected' : '' }}> {{ $system['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-system" class="control-label required">Supplier Type</label>
            <select name="supplier_type" id="input-supplier_type" class="form-control" required>
                @foreach ($supplier_types as $supplier_type)
                    <option value="{{ $supplier_type['name'] }}" {{ ( $supplier_type['name'] == $object->supplier_type) ? 'selected' : '' }}> {{ $supplier_type['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem" value="Supplier"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Supplier">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
