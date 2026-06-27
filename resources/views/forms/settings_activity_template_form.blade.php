
<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf
        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label for="input-activity-code">Activity Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="input-activity-code" name="activity_code"
                        value="{{ $object->activity_code ?? '' }}" placeholder="e.g. A0, B1" required>
                </div>
            </div>
            <div class="col-md-8">
                <div class="form-group mb-3">
                    <label for="input-name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="input-name" name="name"
                        value="{{ $object->name ?? '' }}" placeholder="Activity name" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="input-phase">Phase <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="input-phase" name="phase"
                        value="{{ $object->phase ?? '' }}" placeholder="e.g. Survey Stage, 2D Design Stage" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3">
                    <label for="input-discipline">Discipline <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="input-discipline" name="discipline"
                        value="{{ $object->discipline ?? '' }}" placeholder="e.g. Architectural Drawing" required>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label for="input-duration-days">Duration (Days) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="input-duration-days" name="duration_days"
                        value="{{ $object->duration_days ?? '' }}" placeholder="Working days" min="1" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label for="input-predecessor-code">Predecessor</label>
                    <select class="form-select" id="input-predecessor-code" name="predecessor_code">
                        <option value="">-- None (Start) --</option>
                        @foreach($activity_templates ?? [] as $template)
                            <option value="{{ $template->activity_code }}"
                                {{ ($object->predecessor_code ?? '') == $template->activity_code ? 'selected' : '' }}>
                                {{ $template->activity_code }} - {{ $template->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group mb-3">
                    <label for="input-sort-order">Sort Order</label>
                    <input type="number" class="form-control" id="input-sort-order" name="sort_order"
                        value="{{ $object->sort_order ?? 0 }}" min="0">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group mb-3">
                    @php
                        $tplRoleIds = isset($object) && $object->id ? $object->responsibleRoleIds() : array_filter([$object->role_id ?? null]);
                        $allRoles = $roles ?? collect();
                        // Show currently-selected roles at the top so they're always visible.
                        $sortedRoles = $allRoles->sortByDesc(fn($r) => in_array($r->id, $tplRoleIds))->values();
                    @endphp
                    <label for="input-role-id">Responsible Roles <small class="text-muted">(hold Ctrl/Cmd to pick several)</small></label>
                    @if(count($tplRoleIds))
                        <div class="mb-1"><small class="text-success"><i class="fa fa-check-circle"></i> Current: {{ $allRoles->whereIn('id', $tplRoleIds)->pluck('name')->implode(', ') }}</small></div>
                    @endif
                    <select class="form-select" id="input-role-id" name="roles[]" multiple size="8">
                        @foreach($sortedRoles as $role)
                            <option value="{{ $role->id }}" {{ in_array($role->id, $tplRoleIds) ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group mb-3 pt-4">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="input-is-active" name="is_active" value="1"
                            {{ ($object->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="input-is-active">Active</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group mb-3">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem"><i class="si si-check"></i> Update</button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="ProjectActivityTemplate">Submit</button>
            @endif
        </div>
    </form>
</div>
