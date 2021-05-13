
<div class="block-content">
    <form  method="post"  autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email">Name</label>
            <input type="text" class="form-control" id="input-name" name="name" value="{{ $object->name ?? '' }}" placeholder="Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Description</label>
            <textarea type="text" class="form-control" id="input-description" name="description">{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group">
            <label for="example-nf-email">Keyword</label>
            <input type="text" class="form-control" id="input-keyword" name="keyword" value="{{ $object->keyword ?? '' }}" placeholder="Keyword" required>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Bank">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>
