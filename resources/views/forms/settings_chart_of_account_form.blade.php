<!-- settings_chart_of_account_form.blade.php -->
<div class="modal-body">
    <form id="settings_chart_of_account_form" method="POST">
        @csrf

        <div class="form-group row">
            <div class="col-md-12">
                <label for="account_type">Account Type</label>
                <select class="form-control" id="account_type" name="account_type" required>
                    <option value="">Select Account Type</option>
                    @foreach($account_types as $account_type)
                    <option value="{{ $account_type->id }}" {{ ( $account_type->id == $object->account_type) ? 'selected' : '' }}>{{ $account_type->type }} ({{ $account_type->code }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-12">
                <label for="parent">Parent Account</label>
                <select class="form-control" id="parent" name="parent">
                    <option value="">None (Top Level)</option>
                    @foreach($chart_of_accounts as $chart_account)
                    <option value="{{ $chart_account->id }}" {{ ( $chart_account->id == $object->parent ) ? 'selected' : '' }}>{{ $chart_account->code }} - {{ $chart_account->account_name }} </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6">
                <label for="code">Account Code</label>
                <input type="text" class="form-control" id="code" name="code" value="{{ $object->code ?? '' }}" placeholder="Enter account code" required>
            </div>
            <div class="col-md-6">
                <label for="currency">Currency</label>
                <select class="form-control" id="currency" name="currency">
                    @foreach($currencies as $currency)
                        <option value="{{ $currency->id }}" {{ ( $currency->id == $object->parent ) ? 'selected' : '' }}>{{ $currency->symbol }} - {{ $currency->name }} </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-12">
                <label for="account_name">Account Name</label>
                <input type="text" class="form-control" id="account_name" value="{{ $object->account_name ?? '' }}" name="account_name" placeholder="Enter account name" required>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-6">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status">
                    <option value="ACTIVE">Active</option>
                    <option value="INACTIVE">Inactive</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ChartAccount">Submit</button>
            @endif
        </div>
    </form>
</div>


<script>
    // For editing existing chart accounts



</script>
