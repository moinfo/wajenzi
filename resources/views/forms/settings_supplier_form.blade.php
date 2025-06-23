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
            <label for="example-nf-system" class="control-label required">System</label>
            <select name="system_id" id="input-system-id" class="form-control" required>
                @foreach ($systems as $system)
                    <option value="{{ $system['id'] }}" {{ ( $system['id'] == $object->system_id) ? 'selected' : '' }}> {{ $system['name'] }} </option>
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
