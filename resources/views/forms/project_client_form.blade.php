{{-- Project Client Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="first_name" class="control-label required">First Name</label>
                    <input type="text" class="form-control" id="input-first-name" required="required" name="first_name" value="{{ $object->first_name ?? '' }}" placeholder="First Name">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="last_name" class="control-label required">Last Name</label>
                    <input type="text" class="form-control" id="input-last-name" required="required" name="last_name" value="{{ $object->last_name ?? '' }}" placeholder="Last Name">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="email" class="control-label required">Email</label>
                    <input type="email" class="form-control" id="input-email" name="email" value="{{ $object->email ?? '' }}" placeholder="Email" required="required">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="text" class="form-control" id="input-phone" name="phone_number" value="{{ $object->phone_number ?? '' }}" placeholder="Phone Number">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" class="form-control" id="input-address" name="address" value="{{ $object->address ?? '' }}" placeholder="Address">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="identification_number">Identification Number</label>
                    <input type="text" class="form-control" id="input-identification" name="identification_number" value="{{ $object->identification_number ?? '' }}" placeholder="Identification Number">
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectClient">Submit</button>
            @endif
        </div>
    </form>
</div>
