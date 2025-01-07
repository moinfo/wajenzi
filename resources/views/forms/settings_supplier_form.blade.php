<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Name</label>
            <input type="text" class="form-control" id="input-supplier-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Supplier Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-phone">Phone</label>
            <input type="text" class="form-control" id="input-phone" name="phone" value="{{ $object->phone ?? '' }}" placeholder="Phone Number">
        </div>
        <div class="form-group">
            <label for="example-nf-address">Address</label>
            <input type="text" class="form-control" id="input-address" name="address" value="{{ $object->address ?? '' }}" placeholder="Address">
        </div>
        <div class="form-group">
            <label for="example-nf-vrn" class="control-label required">VRN</label>
            <input type="text" class="form-control" id="input-vrn" name="vrn" value="{{ $object->vrn ?? '' }}" placeholder="Supplier VRN" required>
        </div>
        <div class="form-group">
            <label for="example-nf-vrn" class="control-label required">Default Debit</label>
            <input type="number" class="form-control" id="input-debit" name="debit" value="{{ $object->debit ?? '' }}" required>
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
            <label for="example-nf-system" class="control-label required">Supplier Depend On System</label>
            <select name="supplier_depend_on_system" id="supplier_depend_on_system" class="form-control" required>
                <option value="">Select</option>
                @foreach ($supplier_depend_on_systems as $supplier_depend_on_system)
                    <option value="{{ $supplier_depend_on_system['name'] }}" {{ ( $supplier_depend_on_system['name'] == $object->supplier_depend_on_system) ? 'selected' : '' }}> {{ $supplier_depend_on_system['name'] }} </option>
                @endforeach
            </select>
        </div>

        @if($object->supplier_depend_on_system)
            @if($object->supplier_depend_on_system == 'WHITESTAR')
                <div class="form-group">
                    <label for="example-nf-system" class="control-label required">Whitestar Supplier</label>
                    <select name="whitestar_supplier_id" id="input-whitestar_supplier-id" class="form-control">
                        <option value="">Select Whitestar Supplier</option>
                        @foreach ($whitestar_suppliers as $whitestar_supplier)
                            <option value="{{ $whitestar_supplier->local_supplier_id }}" {{ ( $whitestar_supplier->local_supplier_id == $object->whitestar_supplier_id) ? 'selected' : '' }}>
                                {{ $whitestar_supplier->first_name . ' '. $whitestar_supplier->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="form-group">
                    <label for="example-nf-system" class="control-label required">Bonge Supplier</label>
                    <select name="bonge_supplier_id" id="input-bonge_supplier-id" class="form-control">
                        <option value="">Select Bonge Supplier</option>
                        @foreach ($bonge_suppliers as $bonge_supplier)
                            <option value="{{ $bonge_supplier->local_supplier_id }}" {{ ( $bonge_supplier->local_supplier_id == $object->bonge_supplier_id) ? 'selected' : '' }}>
                                {{ $bonge_supplier->first_name . ' '. $bonge_supplier->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        @else
            <div class="form-group" id="whitestar" style='display:none;'>
                <label for="example-nf-system" class="control-label required">Whitestar Supplier</label>
                <select name="whitestar_supplier_id" id="input-whitestar_supplier-id" class="form-control">
                    <option value="">Select Whitestar Supplier</option>
                    @foreach ($whitestar_suppliers as $whitestar_supplier)
                        <option value="{{ $whitestar_supplier->local_supplier_id }}" {{ ( $whitestar_supplier->local_supplier_id == $object->whitestar_supplier_id) ? 'selected' : '' }}>
                            {{ $whitestar_supplier->first_name . ' '. $whitestar_supplier->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group" id="bonge" style='display:none;'>
                <label for="example-nf-system" class="control-label required">Bonge Supplier</label>
                <select name="bonge_supplier_id" id="input-bonge_supplier-id" class="form-control">
                    <option value="">Select Bonge Supplier</option>
                    @foreach ($bonge_suppliers as $bonge_supplier)
                        <option value="{{ $bonge_supplier->local_supplier_id }}" {{ ( $bonge_supplier->local_supplier_id == $object->bonge_supplier_id) ? 'selected' : '' }}>
                            {{ $bonge_supplier->first_name . ' '. $bonge_supplier->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

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
            <label for="example-nf-system" class="control-label required">Is Withdraw?</label>
            <select name="is_withdraw" id="input-is_withdraw" class="form-control" required>
                @foreach ($is_withdraws as $is_withdraw)
                    <option value="{{ $is_withdraw['name'] }}" {{ ( $is_withdraw['name'] == $object->is_withdraw) ? 'selected' : '' }}> {{ $is_withdraw['name'] }} </option>
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

    // Initialize form based on initial value
    $(document).ready(function() {
        const initialSystem = $('#supplier_depend_on_system').val();
        if (initialSystem == 'WHITESTAR') {
            $("#whitestar").show();
            $("#bonge").hide();
            $("#input-bonge_supplier-id").prop('disabled', true);
            $("#input-whitestar_supplier-id").prop('disabled', false);
        } else if (initialSystem == 'BONGE') {
            $("#bonge").show();
            $("#whitestar").hide();
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', false);
        } else {
            $("#whitestar").hide();
            $("#bonge").hide();
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', true);
        }
    });

    // Handle system change
    $('#supplier_depend_on_system').on('change', function() {
        // Reset both select values when changing system
        $('#input-whitestar_supplier-id').val('');
        $('#input-bonge_supplier-id').val('');

        if (this.value == 'WHITESTAR') {
            $("#whitestar").show();
            $("#bonge").hide();
            // Disable Bonge supplier field when Whitestar is selected
            $("#input-bonge_supplier-id").prop('disabled', true);
            $("#input-whitestar_supplier-id").prop('disabled', false);
        } else if (this.value == 'BONGE') {
            $("#bonge").show();
            $("#whitestar").hide();
            // Disable Whitestar supplier field when Bonge is selected
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', false);
        } else {
            $("#whitestar").hide();
            $("#bonge").hide();
            // Disable both when nothing is selected
            $("#input-whitestar_supplier-id").prop('disabled', true);
            $("#input-bonge_supplier-id").prop('disabled', true);
        }
    });
</script>
