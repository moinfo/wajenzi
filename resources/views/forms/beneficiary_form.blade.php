<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-account_name" class="control-label required">Account Name</label>
            <input type="text" class="form-control" id="account_name" name="account_name" value="{{ $object->account_name ?? '' }}" placeholder="Account Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-account_number" class="control-label required">Account Number</label>
            <input type="text" class="form-control" id="account_number" name="account_number" value="{{ $object->account_number ?? '' }}" placeholder="Account Number" required>
        </div>

        <div class="form-group">
            <label for="example-nf-bank" class="control-label required">Bank</label>
            <select name="bank_id" id="input-bank-id" class="form-control" required>

                <option value="">Select Bank</option>
                @foreach ($banks as $bank)
                    <option value="{{ $bank['id'] }}" {{ ( $bank['id'] == $object->bank_id) ? 'selected' : '' }}> {{ $bank['name'] }} </option>
                @endforeach
            </select>
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

