{{-- Project Type Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="name" class="control-label required">Type Name</label>
                    <input type="text" class="form-control" id="input-name" name="name" value="{{ $object->name ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea class="form-control" id="input-description" name="description" rows="3">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectType">Submit</button>
            @endif
        </div>
    </form>
</div>
