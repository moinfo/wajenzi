<div class="modal-body">
    <form id="settings_chart_of_account_usage_form" method="POST">
        @csrf

        @if($object->id ?? null)
        <div class="form-group row">
            <div class="col-md-12">
                <label for="variable">Variable</label>
                <input type="text" class="form-control" id="variable" value="{{ $object->variable ?? '' }}" name="variable" placeholder="Enter Variable" readonly>
            </div>
        </div>
        @else

            <div class="form-group row">
                <div class="col-md-12">
                    <label for="variable">Variable</label>
                    <input type="text" class="form-control" id="variable" value="{{ $object->variable ?? '' }}" name="variable" placeholder="Enter Variable" required>
                </div>
            </div>
            @endif


        <div class="form-group row">
            <div class="col-md-12">
                <label for="value">Value</label>
                <input type="text" class="form-control" id="value" value="{{ $object->value ?? '' }}" name="value" placeholder="Enter Value" required>
            </div>
        </div>


        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ChartAccountVariable">Submit</button>
            @endif
        </div>
    </form>
</div>


<script>
    // For editing existing chart accounts



</script>
