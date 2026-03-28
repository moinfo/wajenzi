<div class="block-content">
    <form method="post" autocomplete="off">
        @csrf

        <div class="form-group">
            <label for="input-position-name">Position Name</label>
            <input
                type="text"
                class="form-control"
                id="input-position-name"
                name="name"
                value="{{ $object->name ?? '' }}"
                placeholder="e.g. Site Engineer"
                required
            >
        </div>

        <div class="form-group">
            <label for="input-position-abbreviation">Abbreviation</label>
            <input
                type="text"
                class="form-control"
                id="input-position-abbreviation"
                name="abbreviation"
                value="{{ $object->abbreviation ?? '' }}"
                placeholder="e.g. SE"
                maxlength="20"
                required
            >
        </div>

        <div class="form-group">
            <label for="input-position-report-to">Reports To</label>
            <select class="form-control" id="input-position-report-to" name="report_to_id">
                <option value="">No reporting line</option>
                @foreach(($positions ?? collect()) as $position)
                    @if(($object->id ?? null) !== $position->id)
                        <option
                            value="{{ $position->id }}"
                            {{ (string) ($object->report_to_id ?? '') === (string) $position->id ? 'selected' : '' }}
                        >
                            {{ $position->name }}
                        </option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="input-position-status">Status</label>
            <select class="form-control" id="input-position-status" name="status" required>
                <option value="ACTIVE" {{ ($object->status ?? 'ACTIVE') === 'ACTIVE' ? 'selected' : '' }}>ACTIVE</option>
                <option value="INACTIVE" {{ ($object->status ?? '') === 'INACTIVE' ? 'selected' : '' }}>INACTIVE</option>
            </select>
        </div>

        <div class="form-group">
            <label for="input-position-description">Description</label>
            <textarea
                class="form-control"
                id="input-position-description"
                name="description"
                rows="3"
                placeholder="Short description of this role"
            >{{ $object->description ?? '' }}</textarea>
        </div>

        <div class="form-group mb-0">
            @if($object->id ?? null)
                <input type="hidden" name="id" value="{{ $object->id }}">
                <button type="submit" class="btn btn-alt-primary" name="updateItem">
                    <i class="si si-check"></i> Update
                </button>
            @else
                <button type="submit" class="btn btn-alt-primary col" name="addItem" value="Position">
                    Submit
                </button>
            @endif
        </div>
    </form>
</div>
