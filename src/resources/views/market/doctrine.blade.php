@extends('web::layouts.grids.12')

@section('title', 'Doctrine Fits & Stock Dashboard')
@section('page_header', 'Doctrine Fits & Stock Dashboard')

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
</style>
@endpush

@section('full')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary card-outline mb-3">
                <div class="card-body">
                    <form id="hub-selection-form" action="{{ route('seat-assets::market.doctrine') }}" method="GET">
                        <div class="form-group mb-0">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="hub_id" class="mb-0">Target Market Hub:</label>
                                <a href="#" class="btn btn-xs btn-outline-info" data-toggle="modal" data-target="#addHubModal">
                                    <i class="fas fa-plus"></i> Add Structure
                                </a>
                            </div>
                            <select name="hub_id" id="hub_id" class="form-control select2" onchange="$('#hub-selection-form').submit()">
                                @foreach($hubs as $hub)
                                    <option value="{{ $hub->hub_id }}" {{ $selectedHubId == $hub->hub_id ? 'selected' : '' }}>
                                        {{ $hub->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Saved Fittings (Clone of the Importing page table) -->
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-shield-alt"></i> Managed Doctrine Fits</h3>
                    <div class="card-tools d-flex align-items-center">
                        @if($lastSync)
                            <span class="text-muted small mr-3" id="last-sync-time">
                                Last Updated: {{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}
                            </span>
                        @endif
                        <button class="btn btn-xs btn-outline-info mr-3" onclick="manualSyncHub({{ $selectedHubId }})" id="manual-sync-btn">
                            <i class="fas fa-sync-alt"></i> Sync Hub
                        </button>
                        <input type="text" id="search-fittings" class="form-control form-control-sm mr-2" placeholder="Search fits..." style="width: 150px;">
                        <button class="btn btn-sm btn-outline-secondary mr-2" onclick="toggleAllFits(true)" title="Expand All">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary mr-2" onclick="toggleAllFits(false)" title="Collapse All">
                            <i class="fas fa-compress-arrows-alt"></i>
                        </button>
                        <div class="btn-group mr-2" data-toggle="buttons">
                            <label class="btn btn-xs btn-outline-secondary active"><input type="radio" name="stock_pct" value="100" checked> 100%</label>
                            <label class="btn btn-xs btn-outline-secondary"><input type="radio" name="stock_pct" value="50"> 50%</label>
                            <label class="btn btn-xs btn-outline-secondary"><input type="radio" name="stock_pct" value="25"> 25%</label>
                            <label class="btn btn-xs btn-outline-secondary"><input type="radio" name="stock_pct" value="10"> 10%</label>
                        </div>
                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    </div>
                </div>
                <div class="card-body p-0 text-sm">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"></th>
                                    <th>Name</th>
                                    <th>Reference</th>
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

    <!-- Edit Fit Modal (for pen icon) -->
    <div class="modal fade" id="saveFitModal" tabindex="-1" role="dialog">
        ... (rest of modal content) ...
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

        // Search Fittings
        $('#search-fittings').on('keyup', function() {
            var val = $(this).val().toLowerCase();
            
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
                    
                    if (name.indexOf(val) > -1 || label.indexOf(val) > -1) {
                        $wrapper.show();
                        groupMatches = true;
                    } else {
                        $wrapper.hide();
                    }
                });

                if (groupMatches || $header.text().toLowerCase().indexOf(val) > -1) {
                    $header.show();
                    if ($header.text().toLowerCase().indexOf(val) > -1 && val !== '') {
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
                    location.reload();
                }
            }).fail(function() {
                alert('Failed to add hub or sync prices. Check logs.');
                $btn.prop('disabled', false).text('Add & Sync Prices');
            });
        });
    });

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

    function editSavedFit(fit) {
        $('#fit_id').val(fit.id);
        $('#fit_text_hidden').val(fit.fit_text);
        $('#fit_name').val(fit.name);
        $('#fit_label').val(fit.label);
        $('#fit_group_id').val(fit.group_id);
        $('#fit_url').val(fit.reference_url);
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
        $.ajax({ url: '/seat-assets/market/groups/' + id, method: 'DELETE', data: { _token: '{{ csrf_token() }}' }, success: function() { location.reload(); } });
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

    function manualSyncHub(hubId) {
        var $btn = $('#manual-sync-btn');
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Syncing...');

        $.post('{{ route("seat-assets::market.hubs.sync", ["hubId" => "REPLACEME"]) }}'.replace('REPLACEME', hubId), {
            _token: '{{ csrf_token() }}'
        }, function(res) {
            if (res.success) {
                location.reload();
            }
        }).fail(function() {
            alert('Sync failed. Check logs.');
            $btn.prop('disabled', false).html(originalHtml);
        });
    }
</script>
@endpush
