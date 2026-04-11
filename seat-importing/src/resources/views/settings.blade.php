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
            <a href="{{ route('seat-importing.dashboard') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
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

        {{-- ----------------------------------------------------------------- --}}
        {{-- Global Settings                                                    --}}
        {{-- ----------------------------------------------------------------- --}}
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
                            <input type="number" step="0.01" min="0"
                                class="form-control @error('isk_per_m3') is-invalid @enderror"
                                id="isk_per_m3" name="isk_per_m3"
                                value="{{ old('isk_per_m3', $globalSettings['isk_per_m3']) }}">
                            <small class="form-text text-muted">
                                Used to calculate import cost per item: <em>volume_m³ × ISK/m³</em>.
                                Can be overridden per hub below.
                            </small>
                            @error('isk_per_m3')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="markup_threshold_pct">Markup Threshold (%)</label>
                            <input type="number" step="0.1" min="0" max="10000"
                                class="form-control @error('markup_threshold_pct') is-invalid @enderror"
                                id="markup_threshold_pct" name="markup_threshold_pct"
                                value="{{ old('markup_threshold_pct', $globalSettings['markup_threshold_pct']) }}">
                            <small class="form-text text-muted">
                                Items with markup &ge; this value appear in the "Markup" tab.
                            </small>
                            @error('markup_threshold_pct')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="stock_low_threshold">Low Stock Threshold (%)</label>
                            <input type="number" step="0.1" min="0" max="10000"
                                class="form-control @error('stock_low_threshold') is-invalid @enderror"
                                id="stock_low_threshold" name="stock_low_threshold"
                                value="{{ old('stock_low_threshold', $globalSettings['stock_low_threshold']) }}">
                            <small class="form-text text-muted">
                                Items with stock &lt; this % of weekly volume appear in the "Low Stock" tab.
                            </small>
                            @error('stock_low_threshold')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Global Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ----------------------------------------------------------------- --}}
        {{-- Hubs management                                                    --}}
        {{-- ----------------------------------------------------------------- --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Market Hubs</h5>
                    <button class="btn btn-sm btn-success" data-toggle="collapse" data-target="#new-hub-form">
                        <i class="fas fa-plus"></i> Add Hub
                    </button>
                </div>

                {{-- New hub form (collapsed by default) --}}
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
                    @if ($hubs->isEmpty())
                        <p class="p-3 text-muted mb-0">No hubs yet. Click "Add Hub" above.</p>
                    @else
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light">
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
                                    <td>
                                        <strong>{{ $hub->name }}</strong>
                                        @if ($hub->notes)
                                            <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($hub->notes, 60) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($hub->isk_per_m3, 0) }}</td>
                                    <td class="text-center">
                                        @if ($hub->is_active)
                                            <span class="badge badge-success">Yes</span>
                                        @else
                                            <span class="badge badge-secondary">No</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        <button class="btn btn-xs btn-outline-primary" data-toggle="collapse"
                                            data-target="#edit-hub-{{ $hub->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('seat-importing.hub.destroy', $hub) }}"
                                            class="d-inline"
                                            onsubmit="return confirm('Delete hub {{ addslashes($hub->name) }} and all its data?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                {{-- Inline edit row --}}
                                <tr class="collapse" id="edit-hub-{{ $hub->id }}">
                                    <td colspan="4" class="bg-light p-3">
                                        <form method="POST" action="{{ route('seat-importing.hub.update', $hub) }}">
                                            @csrf
                                            @method('PUT')
                                            @include('seat-importing::partials._hub-form-fields', ['hub' => $hub])
                                            <button type="submit" class="btn btn-primary btn-sm mt-2">
                                                <i class="fas fa-save"></i> Update Hub
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

    </div>{{-- /.row --}}

    {{-- ----------------------------------------------------------------- --}}
    {{-- Recent Import Logs                                                  --}}
    {{-- ----------------------------------------------------------------- --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Import Logs</h5>
                </div>
                <div class="card-body p-0">
                    @if ($recentLogs->isEmpty())
                        <p class="p-3 text-muted mb-0">No import logs yet. Run <code>php artisan seat:importing:import</code> to start.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Hub</th>
                                        <th>Source</th>
                                        <th>File</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-right">Processed</th>
                                        <th class="text-right">Failed</th>
                                        <th>Started</th>
                                        <th>Elapsed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentLogs as $log)
                                    <tr>
                                        <td>{{ $log->id }}</td>
                                        <td>{{ $log->hub?->name ?? '—' }}</td>
                                        <td><code>{{ $log->source }}</code></td>
                                        <td class="text-truncate" style="max-width:200px;">
                                            <small title="{{ $log->filename }}">{{ basename($log->filename ?? '') }}</small>
                                        </td>
                                        <td class="text-center">
                                            @if ($log->status === 'complete')
                                                <span class="badge badge-success">complete</span>
                                            @elseif ($log->status === 'failed')
                                                <span class="badge badge-danger" title="{{ $log->error_message }}">failed</span>
                                            @elseif ($log->status === 'running')
                                                <span class="badge badge-info">running</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $log->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format($log->rows_processed) }}</td>
                                        <td class="text-right {{ $log->rows_failed > 0 ? 'text-danger' : '' }}">
                                            {{ number_format($log->rows_failed) }}
                                        </td>
                                        <td>
                                            <small>{{ $log->started_at ? $log->started_at->format('Y-m-d H:i') : '—' }}</small>
                                        </td>
                                        <td>
                                            <small>{{ $log->elapsedSeconds() !== null ? number_format($log->elapsedSeconds(), 1) . 's' : '—' }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>{{-- /.container-fluid --}}
@endsection
