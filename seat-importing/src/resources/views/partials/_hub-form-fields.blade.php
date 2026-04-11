{{--
    Reusable hub form field set.
    Pass $hub = null for a new hub form, or an existing MarketHub for editing.
--}}
<div class="row">
    <div class="col-sm-8">
        <div class="form-group mb-2">
            <label>Hub Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control form-control-sm"
                value="{{ old('name', $hub?->name) }}" required maxlength="100"
                placeholder="e.g. 1DQ1-A Keepstar">
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group mb-2">
            <label>ISK / m³ <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="isk_per_m3"
                class="form-control form-control-sm"
                value="{{ old('isk_per_m3', $hub?->isk_per_m3 ?? config('seat-importing.default_isk_per_m3', 1000)) }}"
                required>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-4">
        <div class="form-group mb-2">
            <label>Solar System ID</label>
            <input type="number" min="1" name="solar_system_id"
                class="form-control form-control-sm"
                value="{{ old('solar_system_id', $hub?->solar_system_id) }}"
                placeholder="e.g. 30004759">
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group mb-2">
            <label>Region ID</label>
            <input type="number" min="1" name="region_id"
                class="form-control form-control-sm"
                value="{{ old('region_id', $hub?->region_id) }}"
                placeholder="e.g. 10000002">
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group mb-2">
            <label>Structure ID <small class="text-muted">(optional)</small></label>
            <input type="number" min="1" name="structure_id"
                class="form-control form-control-sm"
                value="{{ old('structure_id', $hub?->structure_id) }}"
                placeholder="e.g. 1035466617946">
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-8">
        <div class="form-group mb-2">
            <label>Notes</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2"
                maxlength="2000">{{ old('notes', $hub?->notes) }}</textarea>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="form-group mb-2">
            <label>Active</label>
            <div class="custom-control custom-switch mt-1">
                <input type="checkbox" class="custom-control-input" name="is_active"
                    id="is_active_{{ $hub?->id ?? 'new' }}" value="1"
                    {{ old('is_active', $hub ? $hub->is_active : true) ? 'checked' : '' }}>
                <label class="custom-control-label" for="is_active_{{ $hub?->id ?? 'new' }}">
                    Enabled
                </label>
            </div>
        </div>
    </div>
</div>
