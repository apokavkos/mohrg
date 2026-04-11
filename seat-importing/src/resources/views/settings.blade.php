@extends('web::layouts.app')

@section('title', 'Market Importing — Settings')

@section('content')
<div class="container-fluid">

    <div class="row mb-3">
        <div class="col-12 d-flex align-items-center justify-content-between">
            <h1 class="h3 mb-0">
                <i class="fas fa-cog text-secondary"></i>
                Market Importing — Settings
            </h1>
            <div>
                <button type="button" class="btn btn-sm btn-primary run-import-btn">
                    <i class="fas fa-sync"></i> Run Import Now
                </button>
                <a href="{{ route('seat-importing.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Global Defaults</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('seat-importing.settings.save') }}">
                        @csrf
                        <div class="form-group">
                            <label for="isk_per_m3">Default ISK per m³ (freight cost)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="isk_per_m3" value="{{ $globalSettings['isk_per_m3'] }}">
                        </div>
                        <div class="form-group">
                            <label for="markup_threshold_pct">Markup Threshold (%)</label>
                            <input type="number" step="0.1" min="0" max="10000" class="form-control" name="markup_threshold_pct" value="{{ $globalSettings['markup_threshold_pct'] }}">
                        </div>
                        <div class="form-group">
                            <label for="stock_low_threshold">Low Stock Threshold (%)</label>
                            <input type="number" step="0.1" min="0" max="10000" class="form-control" name="stock_low_threshold" value="{{ $globalSettings['stock_low_threshold'] }}">
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Global Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Market Hubs</h5>
                    <button class="btn btn-sm btn-success" data-toggle="collapse" data-target="#new-hub-form">
                        <i class="fas fa-plus"></i> Add Hub
                    </button>
                </div>
                <div class="collapse" id="new-hub-form">
                    <div class="card-body border-bottom bg-light">
                        <h6>New Hub</h6>
                        <form method="POST" action="{{ route('seat-importing.hub.store') }}">
                            @csrf
                            @include('seat-importing::partials._hub-form-fields', ['hub' => null])
                            <button type="submit" class="btn btn-success btn-sm mt-2">
                                <i class="fas fa-plus"></i> Create Hub
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="text-right">ISK/m³</th>
                                <th class="text-center">Active</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hubs as $hub)
                            <tr>
                                <td><strong>{{ $hub->name }}</strong></td>
                                <td class="text-right">{{ number_format($hub->isk_per_m3, 0) }}</td>
                                <td class="text-center">{!! $hub->is_active ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-secondary">No</span>' !!}</td>
                                <td class="text-right">
                                    <button class="btn btn-xs btn-outline-primary" data-toggle="collapse" data-target="#edit-hub-{{ $hub->id }}"><i class="fas fa-edit"></i></button>
                                    <form method="POST" action="{{ route('seat-importing.hub.destroy', $hub) }}" class="d-inline" onsubmit="return confirm('Delete hub?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse" id="edit-hub-{{ $hub->id }}">
                                <td colspan="4" class="bg-light p-3">
                                    <form method="POST" action="{{ route('seat-importing.hub.update', $hub) }}">
                                        @csrf @method('PUT')
                                        @include('seat-importing::partials._hub-form-fields', ['hub' => $hub])
                                        <button type="submit" class="btn btn-primary btn-sm mt-2"><i class="fas fa-save"></i> Update Hub</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-history"></i> Recent Import Logs</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>ID</th><th>Hub</th><th>Status</th><th>Processed</th><th>Failed</th><th>Started</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->id }}</td>
                                    <td>{{ $log->hub?->name ?? '—' }}</td>
                                    <td><span class="badge badge-{{ $log->status === 'complete' ? 'success' : ($log->status === 'failed' ? 'danger' : 'info') }}">{{ $log->status }}</span></td>
                                    <td>{{ number_format($log->rows_processed) }}</td>
                                    <td>{{ number_format($log->rows_failed) }}</td>
                                    <td>{{ $log->started_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('javascript')
<script>
$(function() {
    $('.select2-system').select2({
        ajax: {
            url: '{{ route("seat-importing.search.systems") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        placeholder: 'Search solar system...',
        minimumInputLength: 3
    });

    $('.select2-region').select2({
        ajax: {
            url: '{{ route("seat-importing.search.regions") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        placeholder: 'Search region...',
        minimumInputLength: 3
    });

    $('.select2-structure').select2({
        ajax: {
            url: '{{ route("seat-importing.search.structures") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) { return { q: params.term }; },
            processResults: function (data) { return { results: data.results }; },
            cache: true
        },
        placeholder: 'Search structure (if synced)...',
        minimumInputLength: 3
    });

    $('.run-import-btn').click(function() {
        if(!confirm('Dispatch background import job for all hubs?')) return;
        $.post('{{ route("seat-importing.import.run") }}', { _token: '{{ csrf_token() }}' })
            .done(function(data) { alert(data.message); location.reload(); })
            .fail(function() { alert('Error dispatching job.'); });
    });
});
</script>
@endpush
