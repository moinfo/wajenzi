<div class="block-content">
    <form method="post" action="{{ route('imprest_request.retire', ['id' => $object->id]) }}" enctype="multipart/form-data" autocomplete="off">
        @csrf

        <div class="alert alert-info" style="margin-bottom: 15px;">
            <strong>Document:</strong> {{ $object->document_number }}<br>
            <strong>Amount:</strong> {{ number_format($object->amount, 2) }}<br>
            <strong>Description:</strong> {{ $object->description }}
        </div>

        <div class="form-group">
            <label for="retirement_file" class="control-label required">Retirement Document</label>
            <input type="file" name="retirement_file" id="retirement_file" class="form-control" required
                   accept=".png,.jpg,.jpeg,.pdf,.doc,.docx,.xls,.xlsx">
            <small class="text-muted">Upload receipt(s) or supporting document for this imprest. PDF, image, or office file up to 8MB.</small>
        </div>

        <div class="form-group">
            <label for="retirement_notes" class="control-label">Notes <small class="text-muted">(optional)</small></label>
            <textarea name="retirement_notes" id="retirement_notes" class="form-control" rows="3"
                      placeholder="Briefly describe how the imprest was used"></textarea>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-success">
                <i class="fa fa-check mr-1"></i> Submit Retirement &amp; Close
            </button>
        </div>
    </form>
</div>
