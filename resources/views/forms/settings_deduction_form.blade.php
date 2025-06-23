<div class="block-content">
    <form method="post"  enctype="multipart/form-data"  autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="example-nf-nature" class="control-label required">Nature</label>
            <select name="nature" id="input-nature-id" class="form-control" required>
                <option value="">Select Nature</option>
                @foreach ($natures as $nature)
                    <option value="{{ $nature['name'] }}" {{ ( $nature['name'] == $object->nature) ? 'selected' : '' }}> {{ $nature['name'] }} </option>
                @endforeach

            </select>
        </div>
        <div class="form-group">
            <label for="example-nf-name" class="control-label required">Deduction Name</label>
            <input type="text" class="form-control" id="input-name" name="name"
                   value="{{ $object->name ?? '' }}" placeholder="Name" required>
        </div>
        <div class="form-group">
            <label for="example-nf-abbreviation" class="control-label required">Abbreviation</label>
            <input type="text" class="form-control" id="input-abbreviation" name="abbreviation"
                   value="{{ $object->abbreviation ?? '' }}" placeholder="NSSF" required>
        </div>
        <div class="form-group">
            <label for="example-nf-description">Description</label>
            <textarea type="text" class="form-control" id="input-description" name="description"
            >{{ $object->description ?? '' }}</textarea>
        </div>
        <div class="form-group" >
            <label for="example-nf-registration_number" class="control-label required">Registration Number</label>
            <input type="text" class="form-control" id="input-registration_number" name="registration_number"
                   value="{{ $object->registration_number ?? '' }}" placeholder="Registration Number" >
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Expense">Submit</button>
            @endif
        </div>
    </form>
</div>

<script>
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd'
    });
</script>


