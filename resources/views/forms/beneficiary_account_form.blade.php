<div class="block-content">
    <form  method="post"  action="{{route('beneficiary_account')}}" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="example-nf-bank" class="control-label required">Beneficiary</label>
            <select name="beneficiary_id" id="input-beneficiary-id" class="form-control" required>
                <option value="">Select Bank</option>
                @foreach ($beneficiaries as $beneficiary)
                    <option value="{{ $beneficiary['id'] }}" {{ ( $beneficiary['id'] == $object->beneficiary_id) ? 'selected' : '' }}> {{ $beneficiary['name'] }} </option>
                @endforeach
            </select>
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
            <label for="example-nf-account" class="control-label required">Account Number</label>
            <input type="text" class="form-control" id="account" name="account" value="{{ $object->account ?? '' }}" placeholder="Account Number" required>
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

