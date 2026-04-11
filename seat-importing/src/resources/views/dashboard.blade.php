@extends('web::layouts.app')

@section('title', 'Market Importing — Dashboard')

@section('content')
<div class="container-fluid">

    {{-- Page header --}}
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-line text-primary"></i>
                Market Importing
                @if ($selectedHub)
                    <small class="text-muted">— {{ $selectedHub->name }}</small>
                @endif
            </h1>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Hub selector row --}}
    <div class="row mb-3">
        <div class="col-12">
            @include('seat-importing::partials.hub-selector', ['hubs' => $hubs, 'selectedHub' => $selectedHub])
        </div>
    </div>

    @if (! $selectedHub)
        {{-- No hubs configured yet --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No market hubs configured</h4>
                        <p class="text-muted">Create a hub in
                            @can('seat-importing.manage')
                                <a href="{{ route('seat-importing.settings') }}">Settings</a>
                            @else
                                Settings (requires manage permission)
                            @endcan
                            then import a CSV to see metrics here.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Last import info bar --}}
        @if (isset($lastImport) && $lastImport)
            <div class="row mb-2">
                <div class="col-12">
                    <small class="text-muted">
                        <i class="fas fa-sync-alt"></i>
                        Last import:
                        <strong>{{ $lastImport->completed_at ? $lastImport->completed_at->diffForHumans() : 'unknown' }}</strong>
                        &mdash; {{ number_format($lastImport->rows_processed) }} rows
                        @if ($lastImport->status === 'failed')
                            <span class="badge badge-danger">FAILED</span>
                        @elseif ($lastImport->status === 'complete')
                            <span class="badge badge-success">OK</span>
                        @endif
                    </small>
                </div>
            </div>
        @endif

        {{-- Four metric tabs --}}
        <ul class="nav nav-tabs mb-3" id="metricTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-markup" data-toggle="tab" href="#pane-markup" role="tab">
                    <i class="fas fa-percentage text-success"></i>
                    &ge; Markup Threshold
                    <span class="badge badge-success">{{ $markupItems->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-stock" data-toggle="tab" href="#pane-stock" role="tab">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    Low Stock
                    <span class="badge badge-warning">{{ $lowStockItems->count() }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-top-markup" data-toggle="tab" href="#pane-top-markup" role="tab">
                    <i class="fas fa-trophy text-info"></i>
                    Top Markup
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-top-total" data-toggle="tab" href="#pane-top-total" role="tab">
                    <i class="fas fa-coins text-primary"></i>
                    Top Weekly ISK
                </a>
            </li>
        </ul>

        <div class="tab-content" id="metricTabContent">

            {{-- Tab 1: Markup items --}}
            <div class="tab-pane fade show active" id="pane-markup" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-percentage text-success"></i>
                            Items &ge; {{ number_format(config('seat-importing.markup_threshold_pct', 25)) }}% Markup
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @include('seat-importing::partials.table-markup', ['items' => $markupItems])
                    </div>
                </div>
            </div>

            {{-- Tab 2: Low stock --}}
            <div class="tab-pane fade" id="pane-stock" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            Low Stock Items
                            <small class="text-muted">(stock &lt; {{ number_format(config('seat-importing.stock_low_threshold_pct', 50)) }}% of weekly volume)</small>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @include('seat-importing::partials.table-stock', ['items' => $lowStockItems])
                    </div>
                </div>
            </div>

            {{-- Tab 3: Top markup % --}}
            <div class="tab-pane fade" id="pane-top-markup" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-trophy text-info"></i>
                            Top 20 by Markup %
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @include('seat-importing::partials.table-top-markup', ['items' => $topMarkupItems])
                    </div>
                </div>
            </div>

            {{-- Tab 4: Top weekly profit --}}
            <div class="tab-pane fade" id="pane-top-total" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-coins text-primary"></i>
                            Top 20 by Weekly ISK Profit
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @include('seat-importing::partials.table-top-total', ['items' => $topTotalItems])
                    </div>
                </div>
            </div>

        </div>{{-- /.tab-content --}}
    @endif

</div>{{-- /.container-fluid --}}

{{-- Item detail modal (populated via AJAX) --}}
<div class="modal fade" id="itemDetailModal" tabindex="-1" role="dialog" aria-labelledby="itemDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemDetailModalLabel">
                    <i class="fas fa-box-open"></i> Item Detail
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="itemDetailBody">
                <div class="text-center py-4">
                    <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script>
(function ($) {
    'use strict';

    // -------------------------------------------------------------------------
    // Hub selector — redirect to selected hub's dashboard
    // -------------------------------------------------------------------------
    $('#hub-selector').on('change', function () {
        var hubId = $(this).val();
        if (hubId) {
            window.location.href = '{{ url('seat-importing/hub') }}/' + hubId;
        }
    });

    // -------------------------------------------------------------------------
    // Item detail modal — AJAX fetch on item name click
    // -------------------------------------------------------------------------
    $(document).on('click', '.item-detail-link', function (e) {
        e.preventDefault();
        var typeId   = $(this).data('type-id');
        var typeName = $(this).text();

        $('#itemDetailModalLabel').html('<i class="fas fa-box-open"></i> ' + typeName);
        $('#itemDetailBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div>');
        $('#itemDetailModal').modal('show');

        $.get('{{ url('seat-importing/item') }}/' + typeId)
            .done(function (data) {
                $('#itemDetailBody').html(renderItemDetail(data));
            })
            .fail(function () {
                $('#itemDetailBody').html('<div class="alert alert-danger">Failed to load item details.</div>');
            });
    });

    function renderItemDetail(d) {
        var isk = function(v) {
            return parseFloat(v).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ISK';
        };
        var pct = function(v) {
            return parseFloat(v).toFixed(2) + '%';
        };

        return '<div class="row">'
            + '<div class="col-md-6">'
            + '<table class="table table-sm table-borderless">'
            + '<tr><th>Type ID</th><td>' + d.type_id + '</td></tr>'
            + '<tr><th>Group</th><td>' + d.group_name + '</td></tr>'
            + '<tr><th>Category</th><td>' + d.category_name + '</td></tr>'
            + '<tr><th>Volume</th><td>' + parseFloat(d.volume_m3).toFixed(4) + ' m³</td></tr>'
            + '</table>'
            + (d.description ? '<p class="small text-muted">' + d.description.substring(0, 300) + '</p>' : '')
            + '</div>'
            + '<div class="col-md-6">'
            + '<table class="table table-sm">'
            + '<thead><tr><th></th><th>Sell</th><th>Buy</th></tr></thead>'
            + '<tbody>'
            + '<tr><th>Jita</th><td>' + isk(d.jita_sell) + '</td><td>' + isk(d.jita_buy) + '</td></tr>'
            + '<tr><th>Local</th><td>' + isk(d.local_sell) + '</td><td>' + isk(d.local_buy) + '</td></tr>'
            + '</tbody></table>'
            + '<table class="table table-sm">'
            + '<tr><th>Import Cost</th><td>' + isk(d.import_cost) + '</td></tr>'
            + '<tr><th>Markup</th><td><span class="' + (parseFloat(d.markup_pct) >= 25 ? 'text-success font-weight-bold' : '') + '">' + pct(d.markup_pct) + '</span></td></tr>'
            + '<tr><th>Weekly Profit</th><td>' + isk(d.weekly_profit) + '</td></tr>'
            + '<tr><th>Weekly Volume</th><td>' + parseFloat(d.weekly_volume).toFixed(0) + ' units</td></tr>'
            + '<tr><th>Current Stock</th><td>' + parseInt(d.current_stock).toLocaleString() + ' units</td></tr>'
            + '<tr><th>Data Date</th><td>' + (d.data_date || '—') + '</td></tr>'
            + '</table>'
            + '</div>'
            + '</div>';
    }

    // -------------------------------------------------------------------------
    // DataTables initialisation (if DataTables is available in SeAT)
    // -------------------------------------------------------------------------
    if ($.fn.DataTable) {
        $('.seat-importing-table').DataTable({
            order: [],
            pageLength: 50,
            lengthMenu: [25, 50, 100, 250],
            language: { search: 'Filter:' },
        });
    }

}(jQuery));
</script>
@endpush
