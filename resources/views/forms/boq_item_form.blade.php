{{-- BOQ Item Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="boq_id" class="control-label required">BOQ</label>
                    <select name="boq_id" id="input-boq" class="form-control" required="required">
                        <option value="">Select BOQ</option>
                        @foreach ($boqs as $boq)
                            <option value="{{ $boq->id }}" {{ ($boq->id == $object->boq_id) ? 'selected' : '' }}>BOQ-{{ $boq->id }} ({{ $boq->project->project_name }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    <label for="description" class="control-label required">Description</label>
                    <input type="text" class="form-control" id="input-description" name="description" value="{{ $object->description ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="quantity" class="control-label required">Quantity</label>
                    <input type="number" step="0.01" class="form-control" id="input-quantity" name="quantity" value="{{ $object->quantity ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="unit" class="control-label required">Unit</label>
                    <input type="text" class="form-control" id="input-unit" name="unit" value="{{ $object->unit ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="unit_price" class="control-label required">Unit Price</label>
                    <input type="number" step="0.01" class="form-control" id="input-unit-price" name="unit_price" value="{{ $object->unit_price ?? '' }}" required="required">
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="BOQItem">Submit</button>
            @endif
        </div>
    </form>
</div>
