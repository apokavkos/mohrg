@extends('web::layouts.grids.12')

@section('title', 'Doctrine Importing')
@section('page_header', 'Doctrine Importing')

@push('head')
<style>
    .group-header {
        background-color: #f4f6f9;
        font-weight: bold;
        padding: 2px 10px !important;
    }
    .edit-name-icon {
        cursor: pointer;
        font-size: 0.8rem;
        margin-left: 5px;
        color: #007bff;
    }
    .edit-name-icon:hover { color: #0056b3; }
    .table-sm td, .table-sm th {
        padding: 0.2rem 0.5rem !important;
        vertical-align: middle !important;
    }
    .compact-pre {
        margin: 2px !important;
        padding: 5px !important;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('full')
    <div class="row">
        <div class="col-md-4">
            <!-- Paste Form -->
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title">Paste EFT/Pyfa Fit</h3>
                </div>
                <div class="card-body">
                    <form id="fit-paste-form" action="{{ route('seat-assets::market.fittings') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="hub_id" class="mb-0">Target Market Hub:</label>
                                <a href="#" class="btn btn-xs btn-outline-info" data-toggle="modal" data-target="#addHubModal">
                                    <i class="fas fa-plus"></i> Add Structure
                                </a>
                            </div>
                            <select name="hub_id" id="hub_id" class="form-control select2">
                                @foreach($hubs as $hub)
                                    <option value="{{ $hub->hub_id }}" {{ $selectedHubId == $hub->hub_id ? 'selected' : '' }}>
                                        {{ $hub->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="fit_text">EFT/Pyfa Text:</label>
                            <textarea name="fit_text" id="fit_text" rows="15" class="form-control" placeholder="Paste fit here...">{{ request('fit_text') }}</textarea>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <button type="submit" class="btn btn-primary btn-block">Check Availability</button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#saveFitModal" onclick="prepareNewFit()" {{ request('fit_text') ? '' : 'disabled' }}>
                                    Save Fit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Export Preview -->
            @if($results)
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Current Export Block</h3>
                    </div>
                    <div class="card-body">
                        <textarea id="export-text" rows="10" class="form-control mb-3" placeholder="Select items..."></textarea>
                        <div class="row">
                            <div class="col-6">
                                <button class="btn btn-default btn-block" onclick="copyExportText()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-primary btn-block" onclick="saveExportBlock()">
                                    <i class="fas fa-save"></i> Save List
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-8">
            <!-- Saved Shopping Lists -->
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title">Saved Shopping Lists</h3>
                    <div class="card-tools d-flex align-items-center">
                        <input type="text" id="search-exports" class="form-control form-control-sm mr-2" placeholder="Search lists..." style="width: 150px;">
                        <button class="btn btn-sm btn-outline-secondary mr-2" onclick="toggleAllExports(true)" title="Expand All">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary mr-2" onclick="toggleAllExports(false)" title="Collapse All">
                            <i class="fas fa-compress-arrows-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-primary" onclick="mergeSelectedExports()">
                            <i class="fas fa-compress-alt"></i> Merge & Dedupe Selected
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body p-0 text-sm">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="exports-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Label (Click to Expand)</th>
                                    <th>Created At</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($savedExports as $export)
                                    <tr class="export-row" data-label="{{ strtolower($export->label) }}">
                                        <td><input type="checkbox" class="merge-check" value="{{ $export->id }}"></td>
                                        <td class="expand-row" style="cursor: pointer;" data-target="#export-content-{{ $export->id }}">
                                            <i class="fas fa-chevron-right mr-2 transition-icon"></i>
                                            <strong>{{ $export->label }}</strong>
                                        </td>
                                        <td>{{ $export->created_at->diffForHumans() }}</td>
                                        <td class="text-right">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-xs btn-default" onclick="copyTextToClipboard(@json($export->export_text))" title="Copy to Clipboard">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button type="button" class="btn btn-xs btn-primary" onclick='editSavedExport(@json($export))'>
                                                    Edit
                                                </button>
                                                <form action="{{ route('seat-assets::market.exports.delete', $export->id) }}" method="POST" onsubmit="return confirm('Delete list?')" style="display:inline;">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="export-content-{{ $export->id }}" class="d-none bg-light export-content">
                                        <td colspan="4">
                                            <pre class="compact-pre border bg-white" style="max-height: 150px; overflow-y: auto;">{{ $export->export_text }}</pre>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center">No saved lists.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Saved Fittings -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title">Saved Fittings</h3>
                    <div class="card-tools d-flex align-items-center">
                        <select id="filter-label" class="form-control form-control-sm mr-2" style="width: 120px;">
                            <option value="">All Labels</option>
                            @foreach($savedFits->pluck('label')->filter()->unique()->sort() as $label)
                                <option value="{{ strtolower($label) }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="text" id="search-fittings" class="form-control form-control-sm mr-2" placeholder="Search fits..." style="width: 150px;">
                        <button class="btn btn-sm btn-outline-secondary mr-2" onclick="toggleAllFits(true)" title="Expand All">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary mr-2" onclick="toggleAllFits(false)" title="Collapse All">
                            <i class="fas fa-compress-arrows-alt"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body p-0 text-sm">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0" id="fittings-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Name</th>
                                    <th>Label</th>
                                    <th class="text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php 
                                    $fitsByGroup = $savedFits->groupBy('group_id');
                                    $ungrouped = $fitsByGroup->get(null, collect());
                                @endphp

                                @foreach($savedGroups as $group)
                                    @php $groupFits = $fitsByGroup->get($group->id, collect()); @endphp
                                    <tr class="group-header toggle-group" style="cursor: pointer;" data-group-id="{{ $group->id }}">
                                        <td colspan="4">
                                            <i class="fas fa-chevron-down mr-2 group-icon"></i>
                                            <i class="fas fa-layer-group text-muted mr-2"></i>
                                            {{ $group->name }} ({{ $groupFits->count() }})
                                            <button type="button" class="btn btn-link btn-xs p-0 ml-2" onclick="event.stopPropagation(); editGroupLabel(@json($group))" title="Rename Group">
                                                <i class="fas fa-pen text-primary"></i>
                                            </button>
                                            <button type="button" class="btn btn-link btn-xs p-0 ml-2" onclick="event.stopPropagation(); deleteGroup({{ $group->id }})" title="Dissolve Group">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @foreach($groupFits as $fit)
                                        <tr class="group-{{ $group->id }}-content-wrapper">
                                            <td colspan="4" class="p-0 border-0">
                                                <table class="table table-sm mb-0 w-100" style="table-layout: fixed;">
                                                    @include('seat-assets::market.partials.fit_row', ['fit' => $fit])
                                                </table>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach

                                @if($ungrouped->count() > 0)
                                    <tr class="group-header toggle-group" style="cursor: pointer;" data-group-id="ungrouped">
                                        <td colspan="4">
                                            <i class="fas fa-chevron-down mr-2 group-icon"></i>
                                            <i class="fas fa-question-circle text-muted mr-2"></i> Ungrouped ({{ $ungrouped->count() }})
                                        </td>
                                    </tr>
                                    @foreach($ungrouped as $fit)
                                        <tr class="group-ungrouped-content-wrapper">
                                            <td colspan="4" class="p-0 border-0">
                                                <table class="table table-sm mb-0 w-100" style="table-layout: fixed;">
                                                    @include('seat-assets::market.partials.fit_row', ['fit' => $fit])
                                                </table>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <td colspan="4" class="p-2">
                                        <div class="d-flex justify-content-between">
                                            <button class="btn btn-sm btn-success" onclick="openBatchRestockModal()">
                                                <i class="fas fa-truck-loading"></i> Restock Selected
                                            </button>
                                            <button class="btn btn-sm btn-primary" onclick="createGroupFromSelected()">
                                                <i class="fas fa-folder-plus"></i> Create Group
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Analysis Results -->
            @if($results)
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Analysis Results</h3>
                        <div class="card-tools d-flex align-items-center">
                            @if($lastSync)
                                <span class="text-muted small mr-3" id="last-sync-time">
                                    Last Updated: {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}
                                </span>
                            @endif
                            <button class="btn btn-xs btn-outline-info mr-3" onclick="manualSyncHub({{ $selectedHubId }})" id="manual-sync-btn">
                                <i class="fas fa-sync-alt"></i> Sync Hub
                            </button>
                            <div class="input-group input-group-sm mr-2" style="width: 150px;">
                                <div class="input-group-prepend"><span class="input-group-text">Ships:</span></div>
                                <input type="number" id="export_multiplier" class="form-control" value="1" min="1" onchange="applyMultiplier()">
                            </div>
                            <button class="btn btn-xs btn-outline-primary" onclick="selectAllForExport()">Select All Missing</button>
                        </div>
                    </div>
                    <div class="card-body p-0 text-sm">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0" id="fit-results-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;"><i class="fas fa-check-square"></i></th>
                                        <th>Item</th>
                                        <th class="text-right">Per Ship</th>
                                        <th class="text-right">Export Qty</th>
                                        <th class="text-right">Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($results as $row)
                                        @php $isMissing = $row->available < $row->required; @endphp
                                        <tr class="{{ $isMissing ? 'table-danger' : '' }}" 
                                            data-type-name="{{ $row->name }}" 
                                            data-required-per-ship="{{ $row->required }}"
                                            data-available="{{ $row->available }}">
                                            <td><input type="checkbox" class="export-check" {{ $isMissing ? 'checked' : '' }}></td>
                                            <td>
                                                <img src="https://images.evetech.net/types/{{ $row->type_id }}/icon?size=32" style="width: 20px;" class="img-circle mr-2">
                                                {{ $row->name }}
                                            </td>
                                            <td class="text-right">{{ $row->required }}</td>
                                            <td class="text-right" style="width: 80px;">
                                                <input type="number" class="form-control form-control-xs export-qty" value="{{ max(0, $row->required - $row->available) }}" min="0" style="padding: 0 5px; height: 22px;">
                                            </td>
                                            <td class="text-right available-val {{ $row->available < $row->required ? 'text-bold text-danger' : 'text-success' }}">{{ number_format($row->available) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Save/Edit Fit Modal -->
    <div class="modal fade" id="saveFitModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="save-fit-modal-form" action="{{ route('seat-assets::market.fittings.save') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="fit_id">
                    <input type="hidden" name="fit_text" id="fit_text_hidden" value="{{ request('fit_text') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="saveFitModalTitle">Save Fitting</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group"><label>Fit Name</label><input type="text" name="name" id="fit_name" class="form-control" required></div>
                        <div class="form-group"><label>Label / Sub-Category</label><input type="text" name="label" id="fit_label" class="form-control" placeholder="Optional identifier"></div>
                        <div class="form-group">
                            <label>Group</label>
                            <select name="group_id" id="fit_group_id" class="form-control">
                                <option value="">-- No Group --</option>
                                @foreach($savedGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group"><label>Reference URL</label><input type="url" name="reference_url" id="fit_url" class="form-control"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Fit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Batch Restock Review Modal -->
    <div class="modal fade" id="restockReviewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Restock Requirements</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Stocking Percentage</label>
                            <div class="d-flex align-items-center">
                                <div class="btn-group btn-group-toggle mr-2" data-toggle="buttons">
                                    <label class="btn btn-sm btn-outline-secondary active"><input type="radio" name="modal_pct" value="100" checked onchange="refreshBatchRestock()"> 100%</label>
                                    <label class="btn btn-sm btn-outline-secondary"><input type="radio" name="modal_pct" value="50" onchange="refreshBatchRestock()"> 50%</label>
                                    <label class="btn btn-sm btn-outline-secondary"><input type="radio" name="modal_pct" value="25" onchange="refreshBatchRestock()"> 25%</label>
                                    <label class="btn btn-sm btn-outline-secondary"><input type="radio" name="modal_pct" value="10" onchange="refreshBatchRestock()"> 10%</label>
                                </div>
                                <input type="number" id="custom_pct" class="form-control form-control-sm" placeholder="Custom %" style="width: 80px;" onkeyup="refreshBatchRestock()">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 border-right">
                            <label>Items to Buy</label>
                            <div class="table-responsive" style="max-height: 350px;">
                                <table class="table table-sm table-striped">
                                    <thead><tr><th>Item</th><th class="text-right">Qty</th></tr></thead>
                                    <tbody id="restock-review-body"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Save Options</label>
                                <select id="restock_replace_id" class="form-control form-control-sm mb-2">
                                    <option value="">-- Create New List --</option>
                                    @foreach($savedExports as $export)
                                        <option value="{{ $export->id }}">{{ $export->label }}</option>
                                    @endforeach
                                </select>
                                <input type="text" id="restock_label" class="form-control form-control-sm" placeholder="List Label">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="confirmBatchRestock()">Finalize Shopping List</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Market Hub Modal -->
    <div class="modal fade" id="addHubModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Target Market Hub</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Search Structure or Region</label>
                        <select id="hub-search-select" class="form-control" style="width: 100%"></select>
                    </div>
                    <div id="hub-search-status" class="text-muted small"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-add-hub" disabled>Add & Sync Prices</button>
                </div>
            </div>
        </div>
    </div>
@stop

@push('javascript')
<script>
    var currentBatchText = "";

    $(function () {
        $('#fit-results-table').DataTable({
            "paging": false, "searching": false, "ordering": true, "info": false, "autoWidth": false, "responsive": true,
            "columnDefs": [{ "orderable": false, "targets": 0 }]
        });

        $('.export-check, .export-qty').on('change keyup', updateExportText);
        updateExportText();

        $('.expand-row').on('click', function() {
            var target = $(this).data('target');
            $(target).toggleClass('d-none');
            $(this).find('.transition-icon').toggleClass('fa-chevron-right fa-chevron-down');
        });

        $('.toggle-group').on('click', function() {
            var groupId = $(this).data('group-id');
            var $content = $('.group-' + groupId + '-content-wrapper');
            var $icon = $(this).find('.group-icon');

            if ($content.hasClass('d-none')) {
                $content.removeClass('d-none');
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                $content.addClass('d-none');
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        });

        // Search Exports
        $('#search-exports').on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $('.export-row').each(function() {
                var label = $(this).data('label') || '';
                var show = label.indexOf(val) > -1;
                $(this).toggle(show);
                if (!show) $('#' + $(this).find('.expand-row').data('target').substring(1)).addClass('d-none');
            });
        });

        // Search Fittings
        $('#search-fittings, #filter-label').on('keyup change', function() {
            var val = $('#search-fittings').val().toLowerCase();
            var labelVal = $('#filter-label').val().toLowerCase();
            
            $('.group-header').each(function() {
                var groupId = $(this).data('group-id');
                var $header = $(this);
                var $wrappers = $('.group-' + groupId + '-content-wrapper');
                var groupMatches = false;

                $wrappers.each(function() {
                    var $wrapper = $(this);
                    var $fitRow = $wrapper.find('.fit-row');
                    var name = $fitRow.data('name') || '';
                    var label = $fitRow.data('label') || '';
                    
                    var searchMatch = name.indexOf(val) > -1 || label.indexOf(val) > -1;
                    var labelMatch = labelVal === '' || label === labelVal;

                    if (searchMatch && labelMatch) {
                        $wrapper.show();
                        groupMatches = true;
                    } else {
                        $wrapper.hide();
                    }
                });

                if (groupMatches || ($header.text().toLowerCase().indexOf(val) > -1 && labelVal === '')) {
                    $header.show();
                    if ($header.text().toLowerCase().indexOf(val) > -1 && val !== '' && labelVal === '') {
                        $wrappers.show();
                    }
                } else {
                    $header.hide();
                }
            });
        });

        // Hub Search Select2
        $('#hub-search-select').select2({
            ajax: {
                url: '{{ route("seat-assets::market.hubs.search") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) { return { q: params.term }; },
                processResults: function(data) { return { results: data.results }; },
                cache: true
            },
            placeholder: 'Type to search...',
            minimumInputLength: 1,
            dropdownParent: $('#addHubModal')
        });

        $('#hub-search-select').on('select2:select', function(e) {
            $('#confirm-add-hub').prop('disabled', false);
        });

        $('#confirm-add-hub').on('click', function() {
            var data = $('#hub-search-select').select2('data')[0];
            if (!data) return;

            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');
            $('#hub-search-status').text('Adding hub and fetching latest prices from ESI... this may take a moment.');

            $.post('{{ route("seat-assets::market.hubs.add") }}', {
                _token: '{{ csrf_token() }}',
                hub_id: data.id,
                name: data.text,
                type: data.type
            }, function(res) {
                if (res.success) {
                    if ($('#hub_id option[value="' + res.hub.hub_id + '"]').length === 0) {
                        $('#hub_id').append(new Option(res.hub.name, res.hub.hub_id));
                    }
                    $('#hub_id').val(res.hub.hub_id).trigger('change');
                    $('#addHubModal').modal('hide');
                    if ($('#fit_text').val()) {
                        $('#fit-paste-form').submit();
                    } else {
                        location.reload();
                    }
                }
            }).fail(function() {
                alert('Failed to add hub or sync prices. Check logs.');
                $btn.prop('disabled', false).text('Add & Sync Prices');
            });
        });
    });

    function toggleAllExports(expand) {
        $('.export-row').each(function() {
            if ($(this).is(':visible')) {
                var target = $(this).find('.expand-row').data('target');
                var $target = $(target);
                var $icon = $(this).find('.transition-icon');
                if (expand) {
                    $target.removeClass('d-none');
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                } else {
                    $target.addClass('d-none');
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                }
            }
        });
    }

    function toggleAllFits(expand) {
        $('.fit-row').each(function() {
            var $fitRow = $(this);
            if ($fitRow.closest('tr[class*="-content-wrapper"]').is(':visible')) {
                var target = $fitRow.find('.expand-row').data('target');
                var $target = $(target);
                var $icon = $fitRow.find('.transition-icon');
                if (expand) {
                    $target.removeClass('d-none');
                    $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                } else {
                    $target.addClass('d-none');
                    $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                }
            }
        });
    }

    function applyMultiplier() {
        var mult = parseInt($('#export_multiplier').val()) || 1;
        $('#fit-results-table tbody tr').each(function() {
            var $row = $(this);
            var reqPerShip = parseInt($row.data('required-per-ship')) || 0;
            var avail = parseInt($row.data('available')) || 0;
            var totalNeeded = reqPerShip * mult;
            var toBuy = Math.max(0, totalNeeded - avail);
            $row.find('.export-qty').val(toBuy);
            if (toBuy > 0) {
                $row.addClass('table-danger'); $row.find('.export-check').prop('checked', true);
            } else { $row.removeClass('table-danger'); }
        });
        updateExportText();
    }

    function editSavedFit(fit) {
        $('#fit_id').val(fit.id);
        $('#fit_text_hidden').val(fit.fit_text);
        $('#fit_name').val(fit.name);
        $('#fit_label').val(fit.label);
        $('#fit_group_id').val(fit.group_id);
        $('#fit_url').val(fit.reference_url);
        $('#saveFitModalTitle').text('Edit Fitting');
        $('#saveFitModal').modal('show');
    }

    function createGroupFromSelected() {
        var ids = []; $('.restock-check:checked').each(function() { ids.push($(this).val()); });
        if (ids.length == 0) return alert('Select fits first');
        var name = prompt('Enter group name:');
        if (!name) return;
        $.post('{{ route("seat-assets::market.groups.save") }}', { _token: '{{ csrf_token() }}', name: name, fit_ids: ids }, function() { location.reload(); });
    }

    function editGroupLabel(group) {
        var newName = prompt('Rename group:', group.name);
        if (!newName || newName === group.name) return;
        $.post('{{ route("seat-assets::market.groups.save") }}', { _token: '{{ csrf_token() }}', id: group.id, name: newName }, function() { location.reload(); });
    }

    function deleteGroup(id) {
        if (!confirm('Dissolve this group? Fits will not be deleted.')) return;
        $.ajax({
            url: '/seat-assets/market/groups/' + id,
            method: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() { location.reload(); }
        });
    }

    function updateExportText() {
        var lines = [];
        $('.export-check:checked').each(function() {
            var $row = $(this).closest('tr');
            var name = $row.data('type-name');
            var qty = $row.find('.export-qty').val();
            if (qty > 0) lines.push(name + ' ' + qty);
        });
        $('#export-text').val(lines.join('\n'));
    }

    function openBatchRestockModal() {
        var ids = []; $('.restock-check:checked').each(function() { ids.push($(this).val()); });
        if (ids.length == 0) return alert('Select fits first');
        refreshBatchRestock();
        $('#restockReviewModal').modal('show');
    }

    function refreshBatchRestock() {
        var ids = []; $('.restock-check:checked').each(function() { ids.push($(this).val()); });
        var custom = $('#custom_pct').val();
        var pct = custom ? custom : $('input[name="modal_pct"]:checked').val();
        $.post('{{ route("seat-assets::market.fittings.batch-restock") }}', { _token: '{{ csrf_token() }}', fit_ids: ids, percent: pct }, function(res) {
            currentBatchText = res.text;
            var lines = res.text.split('\n');
            var tbody = $('#restock-review-body');
            tbody.empty();
            lines.forEach(function(line) {
                if (line.trim()) {
                    var lastSpace = line.lastIndexOf(' ');
                    var name = line.substring(0, lastSpace);
                    var qty = line.substring(lastSpace + 1);
                    tbody.append('<tr><td>' + name + '</td><td class="text-right">' + qty + '</td></tr>');
                }
            });
        });
    }

    function confirmBatchRestock() {
        var replaceId = $('#restock_replace_id').val();
        var label = replaceId ? $('#restock_replace_id option:selected').text() : $('#restock_label').val();
        if (!label) return alert('Enter a label');
        $.post('{{ route("seat-assets::market.exports.save") }}', { _token: '{{ csrf_token() }}', replace_id: replaceId, label: label, export_text: currentBatchText }, function() { location.reload(); });
    }

    function editSavedExport(exportObj) { $('#edit_export_id').val(exportObj.id); $('#edit_export_label').val(exportObj.label); $('#edit_export_text').val(exportObj.export_text); $('#editExportModal').modal('show'); }
    function confirmEditExport() { $.post('{{ route("seat-assets::market.exports.save") }}', { _token: '{{ csrf_token() }}', replace_id: $('#edit_export_id').val(), label: $('#edit_export_label').val(), export_text: $('#edit_export_text').val() }, function() { location.reload(); }); }
    function prepareNewFit() { $('#fit_id').val(''); $('#fit_text_hidden').val($('#fit_text').val()); $('#fit_name').val(''); $('#fit_label').val(''); $('#fit_url').val(''); $('#saveFitModalTitle').text('Save Fitting'); }
    function saveExportBlock() { if (!$('#export-text').val()) return alert('List is empty'); $('#saveExportModal').modal('show'); }
    function confirmSaveExport() { $.post('{{ route("seat-assets::market.exports.save") }}', { _token: '{{ csrf_token() }}', replace_id: $('#replace_export_id').val(), label: $('#new_export_label').val(), export_text: $('#export-text').val() }, function() { location.reload(); }); }
    function mergeSelectedExports() { var ids = []; $('.merge-check:checked').each(function() { ids.push($(this).val()); }); if (ids.length == 0) return alert('Select lists first'); $.post('{{ route("seat-assets::market.exports.dedupe") }}', { _token: '{{ csrf_token() }}', ids: ids }, function(res) { $('#fit-text').val(res.text); $('#fit-paste-form').submit(); }); }
    function selectAllForExport() { $('.export-check').prop('checked', true); updateExportText(); }
    function copyExportText() { copyTextToClipboard($('#export-text').val()); }
    function copyTextToClipboard(text) { var $temp = $("<textarea>"); $("body").append($temp); $temp.val(text).select(); document.execCommand("copy"); $temp.remove(); alert('Copied!'); }

    function manualSyncHub(hubId) {
        var $btn = $('#manual-sync-btn');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

        $.post('{{ route("seat-assets::market.hubs.sync", ["hubId" => "REPLACEME"]) }}'.replace('REPLACEME', hubId), {
            _token: '{{ csrf_token() }}'
        }, function(res) {
            if (res.success) {
                if ($('#fit_text').val()) {
                    $('#fit-paste-form').submit();
                } else {
                    location.reload();
                }
            }
        }).fail(function() {
            alert('Sync failed. Check logs.');
            $btn.prop('disabled', false).html(originalHtml);
        });
    }
</script>
@endpush
