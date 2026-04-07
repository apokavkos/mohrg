@extends('web::layouts.grids.12')

@section('title', 'Reactions Planner')
@section('page_header', 'Reactions Planner (C-J6MT Staging)')

@push('head')
<style>
    .reaction-table td, .reaction-table th {
        padding: 0.3rem 0.75rem !important;
        vertical-align: middle !important;
        font-size: 0.9rem;
    }
    th[title] {
        cursor: help;
        border-bottom: 1px dashed #999 !important;
    }
</style>
@endpush

@section('full')
<div class="row">
    <div class="col-md-12">
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-cog"></i> Global Settings</h3>
                <div class="card-tools">
                    <span id="save-status" class="mr-2 text-muted small" style="display: none;">Saving...</span>
                    <button class="btn btn-sm btn-primary" id="save-settings-btn"><i class="fas fa-save"></i> Save Settings</button>
                    <button class="btn btn-sm btn-success" id="sync-prices-btn"><i class="fas fa-dollar-sign"></i> Sync Prices</button>
                    <button class="btn btn-sm btn-info" id="warmup-btn"><i class="fas fa-sync"></i> Refresh Formulas</button>
                </div>
            </div>
            <div class="card-body">
                <form id="global-settings-form">
                    <input type="hidden" id="config_id" value="{{ $defaultConfig->id ?? '' }}">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Inputs (In)</label>
                                <select class="form-control config-input" id="input_method" name="input_method">
                                    <option value="buy" {{ ($defaultConfig->input_method ?? '') == 'buy' ? 'selected' : '' }}>Buy (Max)</option>
                                    <option value="sell" {{ ($defaultConfig->input_method ?? 'sell') == 'sell' ? 'selected' : '' }}>Sell (Min)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Outputs (Out)</label>
                                <select class="form-control config-input" id="output_method" name="output_method">
                                    <option value="sell" {{ ($defaultConfig->output_method ?? 'sell') == 'sell' ? 'selected' : '' }}>Sell (Min)</option>
                                    <option value="buy" {{ ($defaultConfig->output_method ?? '') == 'buy' ? 'selected' : '' }}>Buy (Max)</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Reaction Skill</label>
                                <select class="form-control config-input" id="skill_level" name="skill_level">
                                    @for($i=1; $i<=5; $i++)
                                        <option value="{{ $i }}" {{ ($defaultConfig->skill_level ?? 5) == $i ? 'selected' : '' }}>Level {{ $i }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Structure</label>
                                <select class="form-control config-input" id="structure_type" name="structure_type">
                                    <option value="Tatara" {{ ($defaultConfig->structure_type ?? 'Tatara') == 'Tatara' ? 'selected' : '' }}>Tatara</option>
                                    <option value="Athanor" {{ ($defaultConfig->structure_type ?? '') == 'Athanor' ? 'selected' : '' }}>Athanor</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Reaction Rig</label>
                                <select class="form-control config-input" id="rig_1" name="rig_1">
                                    <option value="none" {{ ($defaultConfig->rig_1 ?? 'none') == 'none' ? 'selected' : '' }}>None</option>
                                    <option value="t1_medium" {{ ($defaultConfig->rig_1 ?? '') == 't1_medium' ? 'selected' : '' }}>T1 Medium</option>
                                    <option value="t2_medium" {{ ($defaultConfig->rig_1 ?? '') == 't2_medium' ? 'selected' : '' }}>T2 Medium</option>
                                    <option value="t1_large" {{ ($defaultConfig->rig_1 ?? '') == 't1_large' ? 'selected' : '' }}>T1 Large</option>
                                    <option value="t2_large" {{ ($defaultConfig->rig_1 ?? '') == 't2_large' ? 'selected' : '' }}>T2 Large</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Space</label>
                                <select class="form-control config-input" id="space_type" name="space_type">
                                    <option value="nullsec" {{ ($defaultConfig->space_type ?? 'nullsec') == 'nullsec' ? 'selected' : '' }}>Nullsec / WH</option>
                                    <option value="lowsec" {{ ($defaultConfig->space_type ?? '') == 'lowsec' ? 'selected' : '' }}>Lowsec</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Market Hub</label>
                                <select class="form-control select2 config-input" id="hub_id" name="solar_system_id">
                                    <option value="10000002" {{ ($defaultConfig->solar_system_id ?? 10000002) == 10000002 ? 'selected' : '' }}>Jita</option>
                                    <option value="10000043" {{ ($defaultConfig->solar_system_id ?? 0) == 10000043 ? 'selected' : '' }}>Amarr</option>
                                    <option value="10000032" {{ ($defaultConfig->solar_system_id ?? 0) == 10000032 ? 'selected' : '' }}>Dodixie</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>System (for Cost Index)</label>
                                <select id="system_name" class="form-control select2 config-input" name="system_name">
                                    <option value="{{ $defaultConfig->system_name ?? 'Jita' }}" selected>{{ $defaultConfig->system_name ?? 'Jita' }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Facility Tax (%)</label>
                                <input type="number" step="0.1" id="facility_tax" name="facility_tax" class="form-control config-input" value="{{ $defaultConfig->facility_tax ?? '0.0' }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Runs</label>
                                <input type="number" id="runs" name="runs" class="form-control config-input" value="{{ $defaultConfig->runs ?? '1' }}">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@foreach(['complex' => 'Complex Reactions', 'simple' => 'Simple Reactions'] as $key => $title)
<div class="row">
    <div class="col-md-12">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">{{ $title }}</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 reaction-table" id="{{ $key }}-reactions-table">
                        <thead>
                            <tr>
                                <th>Reaction Name</th>
                                <th class="text-right" title="Estimated profit based on Jita market prices (Outputs - Inputs - Taxes)">Jita Profit</th>
                                <th class="text-right" title="Estimated profit based on local C-J6MT market prices (Outputs - Inputs - Taxes)">C-J Profit</th>
                                <th class="text-right" title="The extra profit earned by selling in C-J versus Jita (C-J Profit - Jita Profit)">Local Advantage</th>
                                <th class="text-right" title="Hourly profit per active reaction slot based on production time">ISK / Slot-Hour</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories[$key] as $name)
                                <tr data-name="{{ $name }}">
                                    <td>{{ $name }}</td>
                                    <td class="res-jita text-right"><i class="fas fa-spinner fa-spin text-muted"></i></td>
                                    <td class="res-local text-right">-</td>
                                    <td class="res-advantage text-right font-weight-bold">-</td>
                                    <td class="res-slot-hour text-right">-</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach
@stop

@push('javascript')
<script>
    $(function() {
        console.log('Reactions Planner Initialized');

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        $('#system_name').select2({
            ajax: {
                url: '{{ route("seat-assets::industry.systems") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { q: params.term }; },
                processResults: function (data) { return { results: data.results }; },
                cache: true
            },
            minimumInputLength: 1
        });

        function formatISK(val) {
            if (isNaN(val) || val === null) return '-';
            var formatted = Math.abs(val).toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 0});
            return (val < 0 ? '-' : '') + formatted;
        }

        async function calculateAll() {
            var rows = $('.reaction-table tbody tr').toArray();
            var chunkSize = 5;
            for (var i = 0; i < rows.length; i += chunkSize) {
                var chunk = rows.slice(i, i + chunkSize);
                await Promise.all(chunk.map(row => calculateRow($(row))));
            }
        }

        function calculateRow($row) {
            var name = $row.data('name');
            $row.find('.res-jita').html('<i class="fas fa-spinner fa-spin text-muted"></i>');

            return $.ajax({
                url: '{{ route("seat-assets::reactions.calculate") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    input_method: $('#input_method').val(),
                    output_method: $('#output_method').val(),
                    skill_level: $('#skill_level').val(),
                    structure_type: $('#structure_type').val(),
                    rig_1: $('#rig_1').val(),
                    space_type: $('#space_type').val(),
                    system_name: $('#system_name').val(),
                    facility_tax: $('#facility_tax').val(),
                    runs: $('#runs').val(),
                    hub_id: $('#hub_id').val()
                }
            }).done(function(res) {
                var $jita = $row.find('.res-jita');
                $jita.text(formatISK(res.jita.profit));
                $jita.removeClass('text-success text-danger').addClass(res.jita.profit > 0 ? 'text-success' : 'text-danger');

                var $local = $row.find('.res-local');
                $local.text(formatISK(res.local.profit));
                $local.removeClass('text-success text-danger').addClass(res.local.profit > 0 ? 'text-success' : 'text-danger');

                var $adv = $row.find('.res-advantage');
                $adv.text(formatISK(res.advantage));
                $adv.removeClass('text-success text-danger text-muted').addClass(res.advantage > 0 ? 'text-success' : 'text-danger');

                $row.find('.res-slot-hour').text(formatISK(res.slot_hour));
            }).fail(function(xhr) {
                $row.find('.res-jita').text('Error');
            });
        }

        function saveCurrentConfig() {
            $('#save-status').text('Saving...').fadeIn();
            
            var data = {
                _token: '{{ csrf_token() }}',
                id: $('#config_id').val(),
                name: 'Default Setup',
                solar_system_id: $('#hub_id').val(),
                input_method: $('#input_method').val(),
                output_method: $('#output_method').val(),
                skill_level: $('#skill_level').val(),
                structure_type: $('#structure_type').val(),
                rig_1: $('#rig_1').val(),
                space_type: $('#space_type').val(),
                system_name: $('#system_name').val(),
                facility_tax: $('#facility_tax').val(),
                runs: $('#runs').val(),
                is_default: 1
            };

            $.post('{{ route("seat-assets::reactions.config.save") }}', data, function(res) {
                $('#config_id').val(res.config.id);
                $('#save-status').text('Saved').delay(2000).fadeOut();
            });
        }

        $('#global-settings-form select, #global-settings-form input').on('change', debounce(function() {
            calculateAll();
        }, 500));

        $('#save-settings-btn').on('click', function() {
            saveCurrentConfig();
        });

        $('#warmup-btn').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-sync fa-spin"></i> Refreshing...');
            $.get('{{ route("seat-assets::industry.warmup") }}', function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync"></i> Refresh Formulas');
                calculateAll();
            });
        });

        $('#sync-prices-btn').on('click', function() {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-sync fa-spin"></i> Syncing Prices...');
            $.get('{{ route("seat-assets::reactions.warmup-prices") }}', function() {
                btn.prop('disabled', false).html('<i class="fas fa-dollar-sign"></i> Sync Prices');
                calculateAll();
            });
        });

        $('[title]').tooltip();
        setTimeout(calculateAll, 500);
    });
</script>
@endpush
