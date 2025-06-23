
{{-- Project Invoice Form --}}
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
                    <label for="invoice_number" class="control-label required">Invoice Number</label>
                    <input type="text" class="form-control" id="input-invoice-number" name="invoice_number" value="{{ $object->invoice_number ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="amount" class="control-label required">Amount</label>
                    <input type="number" step="0.01" class="form-control" id="input-amount" name="amount" value="{{ $object->amount ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="due_date" class="control-label required">Due Date</label>
                    <input type="text" class="form-control datepicker" id="input-due-date" name="due_date" value="{{ $object->due_date ?? '' }}" required="required">
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="status" class="control-label required">Status</label>
                    <select name="status" id="input-status" class="form-control" required="required">
                        <option value="">Select Status</option>
                        <option value="pending" {{ ($object->status == 'pending') ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ ($object->status == 'paid') ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ ($object->status == 'overdue') ? 'selected' : '' }}>Overdue</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="form-group">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{$object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Invoice">Submit</button>
            @endif
        </div>
    </form>
</div>
