<?php
    $templates = \App\Models\ProjectBoqTemplate::orderBy('name')->get();
?>
<div class="block-content">
    <form method="post" action="{{ route('project_boq.apply_template', $object->id ?? request('boq_id')) }}" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label for="template_id" class="control-label required">Select Template</label>
                    <select name="template_id" class="form-control" required>
                        <option value="">— Choose a template —</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}">
                                {{ $tpl->name }}
                                ({{ $tpl->items()->count() }} items, TZS {{ number_format($tpl->total_amount, 0) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <hr>
        <p class="text-muted" style="font-size: 11px;">
            This will copy all sections and items from the selected template into this BOQ.
            Existing items will NOT be removed.
        </p>
        <div class="form-group">
            <button type="submit" class="btn btn-alt-success col">
                <i class="si si-cloud-download"></i> Apply Template
            </button>
        </div>
    </form>
</div>
