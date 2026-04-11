@extends('web::layouts.app')

@section('title', 'Market Importing — Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3 mb-0">
                <i class="fas fa-chart-line text-primary"></i> Market Importing
                @if ($selectedHub) <small class="text-muted">— {{ $selectedHub->name }}</small> @endif
            </h1>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row mb-3">
        <div class="col-12">
            @include('seat-importing::partials.hub-selector', ['hubs' => $hubs, 'selectedHub' => $selectedHub])
        </div>
    </div>

    @if (! $selectedHub)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No market hubs configured</h4>
                        <p class="text-muted">Select <strong>+ Add New Hub</strong> above to get started.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        @if (isset($lastImport) && $lastImport)
            <div class="row mb-2">
                <div class="col-12">
                    <small class="text-muted">
                        <i class="fas fa-sync-alt"></i> Last import: <strong>{{ $lastImport->completed_at ? $lastImport->completed_at->diffForHumans() : 'unknown' }}</strong>
                        &mdash; {{ number_format($lastImport->rows_processed) }} rows
                        @if ($lastImport->status === 'failed') <span class="badge badge-danger">FAILED</span> @endif
                    </small>
                </div>
            </div>
        @endif

        <ul class="nav nav-tabs mb-3" id="metricTabs" role="tablist">
            <li class="nav-item"><a class="nav-link active" id="tab-markup" data-toggle="tab" href="#pane-markup" role="tab"><i class="fas fa-percentage text-success"></i> &ge; Markup <span class="badge badge-success">{{ $markupItems->count() }}</span></a></li>
            <li class="nav-item"><a class="nav-link" id="tab-stock" data-toggle="tab" href="#pane-stock" role="tab"><i class="fas fa-exclamation-triangle text-warning"></i> Low Stock <span class="badge badge-warning">{{ $lowStockItems->count() }}</span></a></li>
            <li class="nav-item"><a class="nav-link" id="tab-top-markup" data-toggle="tab" href="#pane-top-markup" role="tab"><i class="fas fa-trophy text-info"></i> Top Markup</a></li>
            <li class="nav-item"><a class="nav-link" id="tab-top-total" data-toggle="tab" href="#pane-top-total" role="tab"><i class="fas fa-coins text-primary"></i> Top Weekly ISK</a></li>
        </ul>

        <div class="tab-content" id="metricTabContent">
            <div class="tab-pane fade show active" id="pane-markup" role="tabpanel"><div class="card"><div class="card-body p-0">@include('seat-importing::partials.table-markup', ['items' => $markupItems])</div></div></div>
            <div class="tab-pane fade" id="pane-stock" role="tabpanel"><div class="card"><div class="card-body p-0">@include('seat-importing::partials.table-stock', ['items' => $lowStockItems])</div></div></div>
            <div class="tab-pane fade" id="pane-top-markup" role="tabpanel"><div class="card"><div class="card-body p-0">@include('seat-importing::partials.table-top-markup', ['items' => $topMarkupItems])</div></div></div>
            <div class="tab-pane fade" id="pane-top-total" role="tabpanel"><div class="card"><div class="card-body p-0">@include('seat-importing::partials.table-top-total', ['items' => $topTotalItems])</div></div></div>
        </div>
    @endif
</div>

{{-- Add Hub Modal --}}
<div class="modal fade" id="add-hub-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('seat-importing.hub.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Add New Market Hub</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    @include('seat-importing::partials._hub-form-fields', ['hub' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Hub</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Item detail modal --}}
<div class="modal fade" id="itemDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemDetailModalLabel"><i class="fas fa-box-open"></i> Item Detail</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="itemDetailBody"><div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i></div></div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script>
$(function () {
    // Select2 Hub Fields
    $('.select2-system').select2({
        ajax: { url: '{{ route("seat-importing.search.systems") }}', dataType: 'json', delay: 250, data: function(p){ return {q:p.term}; }, processResults: function(d){ return {results:d.results}; }, cache: true },
        placeholder: 'Search system...', minimumInputLength: 3, dropdownParent: $('#add-hub-modal')
    });
    $('.select2-region').select2({
        ajax: { url: '{{ route("seat-importing.search.regions") }}', dataType: 'json', delay: 250, data: function(p){ return {q:p.term}; }, processResults: function(d){ return {results:d.results}; }, cache: true },
        placeholder: 'Search region...', minimumInputLength: 3, dropdownParent: $('#add-hub-modal')
    });
    $('.select2-structure').select2({
        ajax: { url: '{{ route("seat-importing.search.structures") }}', dataType: 'json', delay: 250, data: function(p){ return {q:p.term}; }, processResults: function(d){ return {results:d.results}; }, cache: true },
        placeholder: 'Search structure...', minimumInputLength: 3, dropdownParent: $('#add-hub-modal')
    });

    // Item Details
    $(document).on('click', '.item-detail-link', function (e) {
        e.preventDefault();
        var typeId = $(this).data('type-id');
        $('#itemDetailModalLabel').text($(this).text());
        $('#itemDetailModal').modal('show');
        $.get('{{ url('seat-importing/item') }}/' + typeId).done(function(d){ $('#itemDetailBody').html(renderItemDetail(d)); });
    });

    function renderItemDetail(d) {
        var isk = v => parseFloat(v).toLocaleString() + ' ISK';
        return `<div class="row"><div class="col-md-6"><table class="table table-sm"><tr><th>Volume</th><td>${d.volume_m3} m³</td></tr></table><p class="small text-muted">${d.description.substring(0,300)}</p></div><div class="col-md-6"><table class="table table-sm"><tr><th>Jita Sell</th><td>${isk(d.jita_sell)}</td></tr><tr><th>Local Sell</th><td>${isk(d.local_sell)}</td></tr><tr><th>Profit</th><td>${isk(d.weekly_profit)}</td></tr></table></div></div>`;
    }

    if ($.fn.DataTable) { $('.seat-importing-table').DataTable({ order: [], pageLength: 50 }); }
});
</script>
@endpush
