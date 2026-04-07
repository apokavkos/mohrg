@extends('web::layouts.grids.12')

@section('title', 'Industry Calculator')
@section('page_header', 'Industry Calculator')

@section('full')
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Calculation Parameters</h3>
            </div>
            <div class="card-body">
                <div id="imported-bp-banner" class="alert alert-info d-none mb-3"></div>

                <div class="form-group">
                    <label>Select Blueprint</label>
                    <div class="mb-2">
                        <select id="item-search" class="form-control select2" style="width: 100%;">
                            <option value="">Search blueprint (e.g. Ishtar)...</option>
                        </select>
                    </div>
                    <div id="recent-blueprints-container" class="mb-2 d-none">
                        <small class="text-muted">Recent: </small>
                        <span id="recent-blueprints-list"></span>
                    </div>
                    <button class="btn btn-outline-secondary btn-block" type="button" id="import-blueprint-btn">
                        <i class="fas fa-download"></i> Import My Blueprint
                    </button>
                    <input type="hidden" id="blueprint-type-id">
                </div>

                <div class="form-group">
                    <label>Solar System</label>
                    <div class="input-group">
                        <select id="system-search" class="form-control select2" style="width: 100%;">
                            <option value="Jita" selected>Jita</option>
                        </select>
                        <div class="input-group-append">
                            <span class="input-group-text" id="system-cost-index-badge">
                                <i class="fas fa-spinner fa-spin d-none" id="system-index-loading"></i>
                                <span id="system-index-value">-%</span>
                            </span>
                        </div>
                    </div>
                    @if(!empty($currentSystems))
                        <div class="mt-2">
                            <small class="text-muted">Current Locations: </small>
                            @foreach($currentSystems as $system)
                                <a href="javascript:void(0)" class="badge badge-info current-system-link" data-system="{{ $system }}">{{ $system }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Runs</label>
                            <input type="number" id="runs" class="form-control" value="1" min="1">
                            <small id="bp-runs-note" class="text-danger d-none"></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>ME Level</label>
                            <select id="me-level" class="form-control">
                                @for($i=0; $i<=10; $i++)
                                    <option value="{{ $i }}" {{ $i == 10 ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>TE Level</label>
                            <select id="te-level" class="form-control">
                                @for($i=0; $i<=20; $i+=2)
                                    <option value="{{ $i }}" {{ $i == 20 ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Facility Type</label>
                    <select id="facility-type" class="form-control">
                        <option value="npc">NPC Station</option>
                        <option value="engineering_complex">Engineering Complex</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Structure Rig</label>
                            <select id="rig-type" class="form-control">
                                <option value="none">None</option>
                                <option value="t1_me">T1 ME Rig</option>
                                <option value="t2_me">T2 ME Rig</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>System Security</label>
                            <select id="system-security" class="form-control">
                                <option value="high">Highsec</option>
                                <option value="low">Lowsec</option>
                                <option value="null">Nullsec/WH</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Facility Tax Rate (%)</label>
                    <input type="number" step="0.1" id="tax-rate" class="form-control" value="1">
                </div>

                <div class="form-check mb-1">
                    <input type="checkbox" class="form-check-input" id="build-components">
                    <label class="form-check-label" for="build-components">Build sub-components</label>
                </div>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="build-reactions">
                    <label class="form-check-label" for="build-reactions">Include reactions (Moongoo)</label>
                </div>

                <button type="button" class="btn btn-primary btn-block" id="calculate-btn">Calculate</button>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div id="calc-error" class="alert alert-danger d-none mb-3"></div>
        <div id="calc-loading" class="text-center my-5 d-none">
            <i class="fas fa-sync fa-spin fa-3x"></i>
            <p class="mt-2">Calculating...</p>
        </div>

        <div class="card" id="results-card" style="display: none;">
            <div class="card-header">
                <h3 class="card-title">Results: <span id="res-product-name"></span></h3>
            </div>
            <div class="card-body">
                <div class="row mb-4 text-center">
                    <div class="col-md-4 border-right">
                        <strong>Production Time</strong> <br><span id="res-time"></span>
                    </div>
                    <div class="col-md-4 border-right">
                        <strong>Install Cost</strong> <br><span id="res-install-cost"></span>
                    </div>
                    <div class="col-md-4">
                        <strong>Material Cost</strong> <br><span id="res-mat-cost"></span>
                    </div>
                </div>
                <div class="row mb-4 text-center">
                    <div class="col-md-4 border-right border-top pt-2">
                        <strong>Total Cost</strong> <br><span id="res-total-cost"></span>
                    </div>
                    <div class="col-md-4 border-right border-top pt-2">
                        <strong>Total Revenue</strong> <br><span id="res-revenue"></span>
                    </div>
                    <div class="col-md-4 border-top pt-2">
                        <strong>Profit</strong> <br><span id="res-profit"></span> (<span id="res-margin"></span>%)
                    </div>
                </div>

                <div id="materials-container">
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <h5>Required Materials (Buy)</h5>
                        </div>
                        <div class="col-md-6 text-right">
                            <button class="btn btn-sm btn-outline-info" id="copy-materials-btn">
                                <i class="fas fa-shopping-basket"></i> Copy Materials
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive mb-4">
                        <table class="table table-hover table-sm mb-0" id="res-materials-table">
                            <thead>
                                <tr>
                                    <th>Group</th>
                                    <th>Material</th>
                                    <th class="text-right">Quantity</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">Total Price</th>
                                </tr>
                            </thead>
                            <tbody id="res-materials-body">
                            </tbody>
                        </table>
                    </div>

                    <div id="components-section" class="d-none">
                        <div class="row mb-2 border-top pt-3">
                            <div class="col-md-6">
                                <h5>Required Components (Build)</h5>
                            </div>
                            <div class="col-md-6 text-right">
                                <button class="btn btn-sm btn-outline-primary" id="copy-components-btn">
                                    <i class="fas fa-tools"></i> Copy Components
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0" id="res-components-table">
                                <thead>
                                    <tr>
                                        <th>Group</th>
                                        <th>Component (Click to Expand)</th>
                                        <th class="text-right">Total Needed</th>
                                    </tr>
                                </thead>
                                <tbody id="res-components-body">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('seat-assets::industry.partials.blueprint-picker-modal')

@stop

@push('javascript')
<script>
    $(function() {
        console.log('Industry Calculator initialized with Select2');

        // Recent Blueprints logic
        var RECENT_BP_KEY = 'eic_recent_blueprints';
        var recentBlueprints = JSON.parse(localStorage.getItem(RECENT_BP_KEY) || '[]');
        updateRecentBlueprintsUI();

        function addRecentBlueprint(id, name) {
            // Remove if already exists
            recentBlueprints = recentBlueprints.filter(bp => bp.id != id);
            // Prepend
            recentBlueprints.unshift({ id: id, name: name });
            // Limit to 5
            recentBlueprints = recentBlueprints.slice(0, 5);
            localStorage.setItem(RECENT_BP_KEY, JSON.stringify(recentBlueprints));
            updateRecentBlueprintsUI();
        }

        function updateRecentBlueprintsUI() {
            var $list = $('#recent-blueprints-list');
            $list.empty();
            if (recentBlueprints.length > 0) {
                $('#recent-blueprints-container').removeClass('d-none');
                recentBlueprints.forEach(function(bp) {
                    $list.append(`
                        <a href="javascript:void(0)" class="badge badge-secondary recent-bp-link mr-1" 
                           data-id="${bp.id}" data-name="${bp.name}">${bp.name}</a>
                    `);
                });
            } else {
                $('#recent-blueprints-container').addClass('d-none');
            }
        }

        $(document).on('click', '.recent-bp-link', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var option = new Option(name, id, true, true);
            $('#item-search').append(option).trigger('change');
            $('#blueprint-type-id').val(id);
            $('#imported-bp-banner').addClass('d-none');
            addRecentBlueprint(id, name); // Bump it to the front
        });

        // Initial System Index
        updateSystemIndex('Jita');

        // Fix for Select2 search field focus
        $(document).on('select2:open', function() {
            setTimeout(function() {
                var searchField = document.querySelector('.select2-search__field');
                if (searchField) {
                    searchField.focus();
                }
            }, 10);
        });

        // Settings Persistence Logic
        var SETTINGS_KEY = 'eic_industry_settings';
        
        function saveSettings() {
            var settings = {
                system: $('#system-search').val(),
                runs: $('#runs').val(),
                meLevel: $('#me-level').val(),
                teLevel: $('#te-level').val(),
                facilityType: $('#facility-type').val(),
                rigType: $('#rig-type').val(),
                systemSecurity: $('#system-security').val(),
                taxRate: $('#tax-rate').val(),
                buildComponents: $('#build-components').is(':checked'),
                buildReactions: $('#build-reactions').is(':checked')
            };
            localStorage.setItem(SETTINGS_KEY, JSON.stringify(settings));
        }

        function loadSettings() {
            var saved = localStorage.getItem(SETTINGS_KEY);
            if (!saved) return;
            
            var settings = JSON.parse(saved);
            
            if (settings.system) {
                var option = new Option(settings.system, settings.system, true, true);
                $('#system-search').append(option).trigger('change');
                updateSystemIndex(settings.system);
            }
            
            if (settings.runs) $('#runs').val(settings.runs);
            if (settings.meLevel) $('#me-level').val(settings.meLevel);
            if (settings.teLevel) $('#te-level').val(settings.teLevel);
            if (settings.facilityType) $('#facility-type').val(settings.facilityType);
            if (settings.rigType) $('#rig-type').val(settings.rigType);
            if (settings.systemSecurity) $('#system-security').val(settings.systemSecurity);
            if (settings.taxRate) $('#tax-rate').val(settings.taxRate);
            if (settings.hasOwnProperty('buildComponents')) $('#build-components').prop('checked', settings.buildComponents);
            if (settings.hasOwnProperty('buildReactions')) $('#build-reactions').prop('checked', settings.buildReactions);
        }

        // Load settings on init
        loadSettings();

        // Listen for changes to save
        $('input, select').on('change', function() {
            if ($(this).closest('#blueprint-picker-modal').length === 0 && $(this).attr('id') !== 'item-search') {
                saveSettings();
            }
        });

        // Item Search (Select2)
        $('#item-search').select2({
            ajax: {
                url: '{{ route("seat-assets::industry.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            placeholder: 'Search blueprint...',
            minimumInputLength: 3
        }).on('select2:select', function(e) {
            var data = e.params.data;
            $('#blueprint-type-id').val(data.id);
            $('#imported-bp-banner').addClass('d-none');
            addRecentBlueprint(data.id, data.text);
        });

        // System Search (Select2)
        $('#system-search').select2({
            ajax: {
                url: '{{ route("seat-assets::industry.systems") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data.results };
                },
                cache: true
            },
            placeholder: 'Search system...',
            minimumInputLength: 1
        }).on('select2:select', function(e) {
            var data = e.params.data;
            updateSystemIndex(data.id);
        });

        function updateSystemIndex(systemName) {
            if (!systemName) return;

            $('#system-index-loading').removeClass('d-none');
            $('#system-index-value').text('-%');

            $.get('{{ route("seat-assets::industry.system-index", "") }}/' + encodeURIComponent(systemName))
                .done(function(data) {
                    $('#system-index-value').text(data.formatted);
                })
                .fail(function() {
                    $('#system-index-value').text('N/A');
                })
                .always(function() {
                    $('#system-index-loading').addClass('d-none');
                });
        }

        $('.current-system-link').on('click', function() {
            var systemName = $(this).data('system');
            // Update Select2 display
            var option = new Option(systemName, systemName, true, true);
            $('#system-search').append(option).trigger('change');
            updateSystemIndex(systemName);
        });

        var allBlueprints = [];
        var blueprintsLoaded = false;
        var blueprintLoadError = false;
        var blueprintLoadErrorMessage = '';

        // Pre-cache blueprints on page load
        function preloadBlueprints() {
            blueprintLoadError = false;
            blueprintLoadErrorMessage = '';
            $.get('{{ route("seat-assets::industry.blueprints") }}')
                .done(function(data) {
                    allBlueprints = data;
                    blueprintsLoaded = true;
                    console.log('Blueprints pre-cached: ' + allBlueprints.length);
                    // If modal is open and waiting, trigger a search refresh
                    var currentSearch = $('#bp-modal-search').val();
                    if (currentSearch && currentSearch.length >= 2) {
                        loadOwnedBlueprints(currentSearch);
                    }
                })
                .fail(function(xhr) {
                    blueprintLoadError = true;
                    blueprintLoadErrorMessage = xhr.responseJSON?.error || xhr.statusText || 'Unknown error';
                    console.error('Failed to pre-cache blueprints', xhr);
                    
                    if ($('#blueprint-picker-modal').hasClass('show')) {
                        var tbody = $('#bp-modal-table-body');
                        tbody.empty().append('<tr><td colspan="6" class="text-center text-danger">Failed to load blueprints: ' + blueprintLoadErrorMessage + '. <button class="btn btn-sm btn-outline-danger ml-2" onclick="preloadBlueprints()">Retry</button></td></tr>');
                    }
                });
        }
        preloadBlueprints();

        $('#import-blueprint-btn').on('click', function() {
            $('#blueprint-picker-modal').modal('show');
            $('#bp-modal-search').val('');
            $('#bp-modal-table-body').empty();
            
            if (blueprintLoadError) {
                $('#bp-modal-table-body').append('<tr><td colspan="6" class="text-center text-danger">Failed to load blueprints: ' + blueprintLoadErrorMessage + '. <button class="btn btn-sm btn-outline-danger ml-2" onclick="preloadBlueprints()">Retry</button></td></tr>');
            } else if (!blueprintsLoaded) {
                $('#bp-modal-table-body').append('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading blueprint data in background...</td></tr>');
            } else {
                $('#bp-modal-table-body').append('<tr><td colspan="6" class="text-center text-muted">Start typing to search your blueprints...</td></tr>');
            }
        });

        $('#blueprint-picker-modal').on('shown.bs.modal', function() {
            $('#bp-modal-search').focus();
        });

        function loadOwnedBlueprints(search) {
            var tbody = $('#bp-modal-table-body');
            tbody.empty();

            if (!search || search.length < 2) {
                tbody.append('<tr><td colspan="6" class="text-center text-muted">Start typing to search your blueprints...</td></tr>');
                return;
            }

            if (blueprintLoadError) {
                tbody.append('<tr><td colspan="6" class="text-center text-danger">Failed to load blueprints. <button class="btn btn-sm btn-outline-danger ml-2" onclick="preloadBlueprints()">Retry</button></td></tr>');
                return;
            }

            if (!blueprintsLoaded) {
                tbody.append('<tr><td colspan="6" class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading data...</td></tr>');
                return;
            }

            var filtered = allBlueprints.filter(function(bp) {
                return bp.typeName.toLowerCase().includes(search.toLowerCase());
            });

            if (filtered.length === 0) {
                tbody.append('<tr><td colspan="6" class="text-center text-muted">No matching blueprints found.</td></tr>');
            } else {
                renderBlueprintTable(filtered);
            }
        }

        function renderBlueprintTable(rows) {
            var tbody = $('#bp-modal-table-body');
            tbody.empty();
            rows.forEach(function(bp) {
                var typeBadge = bp.quantity === -2 ? '<span class="badge badge-warning">BPC</span>' : '<span class="badge badge-primary">BPO</span>';
                var runs = bp.quantity === -2 ? bp.runs : '∞';
                tbody.append(`
                    <tr>
                        <td>${bp.typeName.replace(' Blueprint', '')}</td>
                        <td>${typeBadge}</td>
                        <td>${bp.material_efficiency} / ${bp.time_efficiency}</td>
                        <td>${runs}</td>
                        <td>${bp.ownerName}</td>
                        <td><button class="btn btn-sm btn-primary bp-use-btn" data-item-id="${bp.item_id}">Use</button></td>
                    </tr>
                `);
            });
        }

        $(document).on('click', '.bp-use-btn', function() {
            var itemId = $(this).data('item-id');
            $.get('{{ route("seat-assets::industry.blueprint.detail", "") }}/' + itemId, function(bp) {
                // Manually set Select2 for item
                var option = new Option(bp.productName, bp.productTypeID, true, true);
                $('#item-search').append(option).trigger('change');
                
                $('#blueprint-type-id').val(bp.productTypeID);
                $('#me-level').val(bp.materialEfficiency);
                $('#te-level').val(bp.timeEfficiency);
                
                // Add to recent
                addRecentBlueprint(bp.productTypeID, bp.productName);

                if (bp.isCopy) {
                    $('#runs').attr('max', bp.runs);
                    $('#runs').val(Math.min($('#runs').val() || 1, bp.runs));
                    $('#bp-runs-note').text('BPC — max ' + bp.runs + ' runs').removeClass('d-none');
                } else {
                    $('#runs').removeAttr('max');
                    $('#bp-runs-note').addClass('d-none');
                }

                var typeLabel = bp.isCopy ? 'BPC (' + bp.runs + ' runs)' : 'BPO';
                $('#imported-bp-banner').html(
                    '📋 Using: <strong>' + bp.productName + ' Blueprint</strong> (' + typeLabel +
                    ') — ME ' + bp.materialEfficiency + ' / TE ' + bp.timeEfficiency +
                    ' — Owner: ' + bp.ownerName +
                    ' <button class="btn btn-sm btn-outline-secondary ml-2" id="clear-import-btn">Clear</button>'
                ).removeClass('d-none');

                $('#blueprint-picker-modal').modal('hide');
            });
        });

        $(document).on('click', '#clear-import-btn', function() {
            $('#imported-bp-banner').addClass('d-none');
            $('#item-search').val(null).trigger('change');
            $('#blueprint-type-id').val('');
            $('#me-level').val(10);
            $('#te-level').val(20);
            $('#runs').val(1).removeAttr('max');
            $('#bp-runs-note').addClass('d-none');
        });

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        $('#bp-modal-search').on('input', debounce(function() {
            loadOwnedBlueprints($(this).val());
        }, 300));

        $('#calculate-btn').on('click', function() {
            var bpId = $('#blueprint-type-id').val();
            if (!bpId) {
                alert('Please select a blueprint from the search results first.');
                return;
            }

            $('#calc-error').addClass('d-none');
            $('#results-card').hide();
            $('#calc-loading').removeClass('d-none');

            $.ajax({
                url: '{{ route("seat-assets::industry.calculate") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    blueprintTypeID: bpId,
                    systemName: $('#system-search').val(),
                    runs: $('#runs').val(),
                    meLevel: $('#me-level').val(),
                    teLevel: $('#te-level').val(),
                    facilityType: $('#facility-type').val(),
                    rigType: $('#rig-type').val(),
                    systemSecurity: $('#system-security').val(),
                    taxRate: $('#tax-rate').val(),
                    buildComponents: $('#build-components').is(':checked') ? 1 : 0,
                    buildReactions: $('#build-reactions').is(':checked') ? 1 : 0,
                },
                success: function(result) {
                    renderResults(result);
                },
                error: function(xhr) {
                    $('#calc-error').text('Error: ' + (xhr.responseJSON?.error || 'An unexpected error occurred.')).removeClass('d-none');
                },
                complete: function() {
                    $('#calc-loading').addClass('d-none');
                }
            });
        });

        var currentMaterials = [];
        var currentComponents = [];

        function renderResults(res) {
            $('#results-card').show();
            $('#res-product-name').text(res.product.typeName + ' x' + res.product.outputQuantity);
            $('#res-time').text(res.productionTime.formatted);
            $('#res-install-cost').text(res.costs.jobInstallationCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ISK');
            $('#res-mat-cost').text(res.costs.totalMaterialCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ISK');
            $('#res-total-cost').text(res.costs.totalCost.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ISK');
            $('#res-revenue').text(res.costs.revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ISK');
            
            var profitEl = $('#res-profit');
            profitEl.text(res.costs.profit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ISK');
            if (res.costs.profit > 0) {
                profitEl.removeClass('text-danger').addClass('text-success');
            } else {
                profitEl.removeClass('text-success').addClass('text-danger');
            }
            
            $('#res-margin').text(res.costs.profitMargin.toFixed(2));

            // Separate Materials (Only non-components if buildComponents is on, or everything if off)
            currentMaterials = res.materials.filter(m => !m.isComponent && !m.isReaction);
            currentComponents = res.materials.filter(m => m.isComponent || m.isReaction);

            if ($.fn.DataTable.isDataTable('#res-materials-table')) {
                $('#res-materials-table').DataTable().destroy();
            }

            var matTbody = $('#res-materials-body');
            matTbody.empty();
            currentMaterials.forEach(function(mat) {
                matTbody.append(`
                    <tr>
                        <td><span class="badge badge-secondary">${mat.groupName}</span></td>
                        <td><img src="${mat.iconUrl}" style="width: 24px; margin-right: 5px;"> ${mat.typeName}</td>
                        <td class="text-right" data-order="${mat.adjustedQuantity}">${mat.adjustedQuantity.toLocaleString()}</td>
                        <td class="text-right" data-order="${mat.unitPrice}">${mat.unitPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td class="text-right" data-order="${mat.totalPrice}">${mat.totalPrice.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>
                `);
            });

            $('#res-materials-table').DataTable({
                "paging": false, "searching": false, "ordering": true, "info": false, "order": [[4, "desc"]]
            });

            // Handle Components
            var compTbody = $('#res-components-body');
            compTbody.empty();
            if (currentComponents.length > 0 && res.materialTree) {
                $('#components-section').removeClass('d-none');
                
                // Root level of the tree are components for the main product
                res.materialTree.forEach(function(node) {
                    if (node.isBuildable || node.isReactable) {
                        renderComponentRow(node, compTbody, 0);
                    }
                });
            } else {
                $('#components-section').addClass('d-none');
            }
        }

        function renderComponentRow(node, tbody, depth) {
            var padding = depth * 20;
            var toggle = node.subMaterials.length > 0 ? `<i class="fas fa-chevron-right mr-2 transition-icon"></i>` : '';
            var rowId = 'comp-' + Math.random().toString(36).substr(2, 9);
            var badgeClass = node.isReactable ? 'badge-info' : 'badge-primary';
            var badgeText = node.isReactable ? 'React' : 'Build';
            
            tbody.append(`
                <tr class="component-row" style="cursor: pointer;" data-target=".${rowId}">
                    <td><span class="badge ${badgeClass}">${badgeText}</span></td>
                    <td style="padding-left: ${padding + 10}px;">
                        ${toggle}
                        <img src="https://images.evetech.net/types/${node.typeID}/icon?size=32" style="width: 20px;" class="mr-1">
                        ${node.typeName}
                    </td>
                    <td class="text-right font-weight-bold">${node.adjustedQuantity.toLocaleString()}</td>
                </tr>
            `);

            if (node.subMaterials.length > 0) {
                node.subMaterials.forEach(function(sub) {
                    var subRow = $(`
                        <tr class="${rowId} d-none bg-light">
                            <td><small class="text-muted ml-4">${sub.isBuildable ? 'Sub-Component' : (sub.isReactable ? 'Reaction' : 'Material')}</small></td>
                            <td style="padding-left: ${padding + 40}px;">
                                <img src="https://images.evetech.net/types/${sub.typeID}/icon?size=32" style="width: 16px;" class="mr-1">
                                ${sub.typeName}
                            </td>
                            <td class="text-right text-muted">${sub.adjustedQuantity.toLocaleString()}</td>
                        </tr>
                    `);
                    tbody.append(subRow);
                    if (sub.isBuildable || sub.isReactable) {
                        renderComponentRow(sub, tbody, depth + 1); // Allow nested expansion
                    }
                });
            }
        }

        $(document).on('click', '.component-row', function() {
            var target = $(this).data('target');
            var $targetRows = $(target);
            var $icon = $(this).find('.transition-icon');
            if ($targetRows.hasClass('d-none')) {
                $targetRows.removeClass('d-none');
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                $targetRows.addClass('d-none');
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        });

        function copyToClipboard(text, $btn) {
            var $temp = $("<textarea>");
            $("body").append($temp);
            $temp.val(text).select();
            document.execCommand("copy");
            $temp.remove();
            
            var originalHtml = $btn.html();
            $btn.html('<i class="fas fa-check"></i> Copied!').addClass('btn-success');
            setTimeout(() => {
                $btn.html(originalHtml).removeClass('btn-success');
            }, 2000);
        }

        $('#copy-materials-btn').on('click', function() {
            var text = currentMaterials.map(m => m.typeName + " " + m.adjustedQuantity).join("\n");
            copyToClipboard(text, $(this));
        });

        $('#copy-components-btn').on('click', function() {
            var text = currentComponents.map(c => c.typeName + " " + c.adjustedQuantity).join("\n");
            copyToClipboard(text, $(this));
        });
    });
</script>
@endpush
