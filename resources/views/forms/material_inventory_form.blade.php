{{-- Material Inventory Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project" class="form-control" required="required">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == $object->project_id) ? 'selected' : '' }}>{{ $project->project_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="material_id" class="control-label required">Material</label>
                    <select name="material_id" id="input-material" class="form-control" required="required">
                        <option value="">Select Material</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}" {{ ($material->id == $object->material_id) ? 'selected' : '' }}>{{ $material->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="quantity" class="control-label required">Quantity</label>
                    <input type="number" step="0.01" class="form-control" id="input-quantity" name="quantity" value="{{ $object->quantity ?? '' }}" required="required">
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="MaterialInventory">Submit</button>
            @endif
        </div>
    </form>
</div>
