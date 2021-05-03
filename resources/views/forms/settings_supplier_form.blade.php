
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Name</label>
            <input type="text" class="form-control" id="input-supplier-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Supplier Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-phone" class="control-label required">Phone</label>
            <input type="text" class="form-control" id="input-phone" name="phone" value="{{ $object->phone ?? '' }}" placeholder="Phone Number" required>
        </div>
        <div class="form-group" class="control-label required">
            <label for="example-nf-email">Email</label>
            <input type="email" class="form-control" id="input-email" name="email" value="{{ $object->email ?? '' }}" placeholder="Email Address" required>
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
                <option value="">Select System</option>
                @foreach ($systems as $system)
                    <option value="{{ $system['id'] }}" {{ ( $system['id'] == $object->system_id) ? 'selected' : '' }}> {{ $system['name'] }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
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
