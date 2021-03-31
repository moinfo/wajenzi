
<div class="block-content">
    <form  method="post" >
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Name</label>
            <input type="text" class="form-control" id="input-supplier-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Supplier Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-phone">Phone</label>
            <input type="text" class="form-control" id="input-phone" name="phone" value="{{ $object->name ?? '' }}" placeholder="Phone Number" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Email</label>
            <input type="email" class="form-control" id="input-email" name="email" value="{{ $object->name ?? '' }}" placeholder="Email Address">
        </div>
        <div class="form-group">
            <label for="example-nf-address">Address</label>
            <input type="text" class="form-control" id="input-address" name="address" value="{{ $object->name ?? '' }}" placeholder="Address">
        </div>
        <div class="form-group">
            <label for="example-nf-vrn">VRN</label>
            <input type="text" class="form-control" id="input-vrn" name="vrn" value="{{ $object->name ?? '' }}" placeholder="Supplier VRN">
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
