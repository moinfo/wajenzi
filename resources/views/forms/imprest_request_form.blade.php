<?php
$document_id = \App\Classes\Utility::getLastId('ImprestRequest')+1;
?>

<div class="block-content">
    <form method="post" enctype="multipart/form-data" autocomplete="off">
        @csrf
        <div class="form-group">
            <label for="example-nf-email" class="control-label required">Expenses Sub Category</label>
            <select name="expenses_sub_category_id" id="expenses_sub_category_id" class="form-control" required>
                @foreach ($expenses_sub_categories as $expenses_sub_category)
                    <option value="{{ $expenses_sub_category->id }}" {{ ( $expenses_sub_category->id == $object->expenses_sub_category_id) ? 'selected' : '' }}> {{ $expenses_sub_category->name }} </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="project_id" class="control-label">Project</label>
            <select name="project_id" id="project_id" class="form-control">
                <option value="">-- Select Project (Optional) --</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" {{ ($project->id == $object->project_id) ? 'selected' : '' }}> {{ $project->project_name }} </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="example-nf-description" class="control-label required">Description</label>
            <textarea type="text" class="form-control" rows="4" id="description" name="description" required>{{ $object->description ?? '' }}</textarea>
        </div>

        <div class="form-group">
            <label for="example-nf-amount" class="control-label required">Amount</label>
            <input type="number" step=".01" class="form-control" id="amount" name="amount" value="{{ $object->amount ?? '' }}" placeholder="Imprest Amount" required>
        </div>

        <div class="form-group">
            <label for="example-nf-date" class="control-label required">Date</label>
            <input type="date" class="form-control" id="input-date" name="date" value="{{ old('date', $object->date ? \Carbon\Carbon::parse($object->date)->format('Y-m-d') : date('Y-m-d')) }}">
        </div>
        <div class="form-group">
            <label class="control-label" for="chooseFile">Choose file</label>
            <input type="file" name="file" class="form-control" id="chooseFile">
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <input type="hidden" name="document_id" value="{{$document_id}}">
                <input type="hidden" name="created_by_id" value="{{Auth::user()->id}}">
                <input type="hidden" name="document_number" value="IMPT/{{date('Y')}}/{{$document_id}}">
                <input type="hidden" name="document_type_id" value="13">
                <input type="hidden" name="link" value="finance/imprest_management/imprest_requests/{{$document_id}}/13">
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ImprestRequest">Submit</button>
            @endif
        </div>
    </form>
</div>
<script>
    $("input.amount").each((i, ele) => {
        let clone = $(ele).clone(false);
        clone.attr("type", "text");
        let ele1 = $(ele);
        clone.val(Number(ele1.val()).toLocaleString("en"));
        $(ele).after(clone);
        $(ele).hide();
        clone.mouseenter(() => {
            ele1.show();
            clone.hide();
        });
        setInterval(() => {
            let newv = Number(ele1.val()).toLocaleString("en");
            if (clone.val() != newv) {
                clone.val(newv);
            }
        }, 10);

        $(ele).mouseleave(() => {
            $(clone).show();
            $(ele1).hide();
        });
    });



</script>

