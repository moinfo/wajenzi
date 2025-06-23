
{{-- Material Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="name" class="control-label required">Material Name</label>
                    <input type="text" class="form-control" id="input-name" name="name" value="{{ $object->name ?? '' }}" required="required" placeholder="Material Name">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="unit" class="control-label required">Unit</label>
                    <input type="text" class="form-control" id="input-unit" name="unit" value="{{ $object->unit ?? '' }}" required="required" placeholder="Unit (e.g., kg, pieces)">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="current_price">Current Price</label>
                    <input type="number" step="0.01" class="form-control" id="input-current-price" name="current_price" value="{{ $object->current_price ?? '' }}" placeholder="Current Price">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="input-description" name="description" rows="3" placeholder="Material Description">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Material">Submit</button>
            @endif
        </div>
    </form>
</div>
