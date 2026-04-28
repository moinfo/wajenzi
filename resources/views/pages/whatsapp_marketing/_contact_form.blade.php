<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Phone <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control" placeholder="+255..." required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Stage <span class="text-danger">*</span></label>
            <select name="stage" class="form-control" required>
                @foreach(\App\Models\WhatsAppContact::STAGES as $key => $meta)
                    <option value="{{ $key }}">{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Source <span class="text-danger">*</span></label>
            <select name="source" class="form-control" required>
                @foreach(\App\Models\WhatsAppContact::SOURCES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Ad Campaign</label>
            <select name="campaign_id" class="form-control">
                <option value="">— None —</option>
                @foreach($formCampaigns ?? $campaigns as $camp)
                    <option value="{{ $camp->id }}"
                            @if(isset($camp->status) && $camp->status === 'closed') class="text-muted" @endif>
                        {{ $camp->name }}{{ isset($camp->status) && $camp->status === 'closed' ? ' [Closed]' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Next Follow-up Date</label>
            <input type="text" name="next_followup_date" id="{{ $prefix }}-followup-date"
                   class="form-control datepicker" autocomplete="off">
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label class="d-block mb-1">Services Interested In
                <a href="{{ route('field_marketing.index', ['tab' => 'services']) }}" target="_blank" class="small ml-2 text-muted">
                    <i class="fa fa-cog"></i> Manage services
                </a>
            </label>
            @if($services->isEmpty())
                <p class="text-muted small">No services configured yet.</p>
            @else
            <div class="btn-group flex-wrap" data-toggle="buttons">
                @foreach($services as $svc)
                <label class="btn btn-sm btn-outline-success m-1" style="border-radius:20px">
                    <input type="checkbox" name="service_ids[]" value="{{ $svc->id }}"
                           class="svc-cb" data-id="{{ $svc->id }}" autocomplete="off">
                    {{ $svc->name }}
                </label>
                @endforeach
            </div>
            @endif
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Assigned To</label>
            <select name="assigned_to" class="form-control">
                <option value="">— Unassigned —</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Link to Client</label>
            <select name="client_id" class="form-control">
                <option value="">— None —</option>
                @foreach(\App\Models\ProjectClient::orderBy('first_name')->get() as $cl)
                    <option value="{{ $cl->id }}">{{ $cl->first_name }} {{ $cl->last_name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label class="d-block mb-2">Labels</label>
            <div class="d-flex flex-wrap" style="gap:8px">
                @foreach(\App\Models\WhatsAppContact::LABELS as $key => $meta)
                <label class="d-flex align-items-center" style="cursor:pointer; user-select:none; gap:6px; font-weight:normal; margin:0">
                    <input type="checkbox" name="label_ids[]" value="{{ $key }}"
                           class="lbl-cb {{ $prefix }}-lbl-cb" data-key="{{ $key }}" style="display:none">
                    <span class="lbl-dot" style="width:14px;height:14px;border-radius:50%;display:inline-block;
                          background:{{ $meta['hex'] }};flex-shrink:0;transition:transform .15s;
                          box-shadow:0 0 0 2px #fff,0 0 0 3px {{ $meta['hex'] }}88"></span>
                    <span style="font-size:13px">{{ $meta['label'] }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Notes</label>
            <textarea name="notes" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>Deal Value (TZS)</label>
            <input type="number" name="deal_value" class="form-control" min="0" step="0.01" placeholder="0.00">
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group mb-0">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="{{ $prefix }}-important" name="is_important" value="1">
                <label class="custom-control-label" for="{{ $prefix }}-important">Mark as Important</label>
            </div>
        </div>
    </div>
</div>
