<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-name" class="control-label required">Wakala Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ $object->name ?? '' }}" placeholder="Wakala Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-agent_id" class="control-label required">Agent ID</label>
            <input type="text" class="form-control" id="agent_id" name="agent_id" value="{{ $object->agent_id ?? '' }}" placeholder="Agent ID" required>
        </div>
        <div class="form-group">
            <label for="example-nf-phone_number" class="control-label required">Phone Number</label>
            <input type="number" class="form-control" id="phone_number" name="phone_number" value="{{ $object->phone_number ?? '' }}" placeholder="Phone Number" required>
        </div>
        <div class="form-group">
            <label for="example-nf-location" class="control-label required">Location</label>
            <input type="text" class="form-control" id="location" name="location" value="{{ $object->name ?? '' }}" placeholder="Location" required>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BankWithdraw">Submit</button>
            @endif
        </div>
    </form>
</div>

