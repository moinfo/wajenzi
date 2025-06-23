<div class="modal-body">
    <form id="settings_chart_of_account_usage_form" method="POST">
        @csrf


        <div class="form-group row">
            <div class="col-md-12">
                <label for="name">Name</label>
                <input type="text" class="form-control" id="name" value="{{ $object->name ?? '' }}" name="name" placeholder="Enter name" required>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-12">
                <label for="charts_account">Chart of Account</label>
                <select class="form-control" id="charts_account_id" name="charts_account_id" required>
                    <option value="">Select Chart of Account</option>
                    @foreach($charts_accounts as $charts_account)
                    <option value="{{ $charts_account->id }}" {{ ( $charts_account->id == $object->charts_account_id) ? 'selected' : '' }}>{{ $charts_account->id }} ({{ $charts_account->account_name }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-md-12">
                <label for="description">Description</label>
                <textarea type="text" class="form-control" id="description" name="description" rows="4" placeholder="Enter description" >{{ $object->description ?? '' }}</textarea>
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
