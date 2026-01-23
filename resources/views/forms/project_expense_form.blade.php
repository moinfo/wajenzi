{{-- Project Cost Form --}}
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="project_id" class="control-label required">Project</label>
                    <select name="project_id" id="input-project" class="form-control select2" required="required">
                        <option value="">Select Project</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ ($project->id == ($object->project_id ?? '')) ? 'selected' : '' }}>
                                {{ $project->document_number }} - {{ $project->project_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="cost_category_id" class="control-label required">Cost Category</label>
                    <select name="cost_category_id" id="input-cost-category" class="form-control select2" required="required">
                        <option value="">Select Cost Category</option>
                        @foreach ($cost_categories as $category)
                            <option value="{{ $category->id }}" {{ ($category->id == ($object->cost_category_id ?? '')) ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="expense_date" class="control-label required">Cost Date</label>
                    <input type="text" class="form-control datepicker" id="input-expense-date" name="expense_date" value="{{ isset($object->expense_date) ? $object->expense_date->format('Y-m-d') : date('Y-m-d') }}" required="required">
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label for="amount" class="control-label required">Cost Amount (TZS)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="input-amount" name="amount" value="{{ $object->amount ?? '' }}" placeholder="0.00" required="required">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="description" class="control-label required">Cost Description</label>
                    <textarea class="form-control" id="input-description" name="description" rows="2" placeholder="Enter cost description" required="required">{{ $object->description ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="form-group">
                    <label for="remarks" class="control-label">Remarks</label>
                    <textarea class="form-control" id="input-remarks" name="remarks" rows="2" placeholder="Additional remarks (optional)">{{ $object->remarks ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="form-group">
            <input type="hidden" name="created_by" value="{{ auth()->id() }}">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update Cost</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectExpense">Submit Cost</button>
            @endif
        </div>
    </form>
</div>
