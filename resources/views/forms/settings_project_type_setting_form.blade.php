
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="input-project-type-name">Name</label>
            <input type="text" class="form-control" id="input-project-type-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Project Type Name" required>
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
