<div class="form-group">
    <label>Campaign Name <span class="text-danger">*</span></label>
    <input type="text" name="name" class="form-control" required>
</div>
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Start Date <span class="text-danger">*</span></label>
            <input type="text" name="start_date" id="{{ $prefix }}-campaign-start"
                   class="form-control datepicker" required autocomplete="off">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>End Date</label>
            <input type="text" name="end_date" id="{{ $prefix }}-campaign-end"
                   class="form-control datepicker" autocomplete="off">
        </div>
    </div>
</div>
<div class="form-group">
    <label>Budget (TZS)</label>
    <input type="number" name="budget" class="form-control" min="0" step="0.01" placeholder="0.00">
</div>
<div class="form-group">
    <label>Notes</label>
    <textarea name="notes" class="form-control" rows="2"></textarea>
</div>
