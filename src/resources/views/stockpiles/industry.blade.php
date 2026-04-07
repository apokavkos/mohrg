@extends('web::layouts.grids.12')

@section('title', 'Logistics Engine: ' . $stockpile->name)
@section('page_header', 'Logistics Engine: ' . $stockpile->name . ($report['location_name'] ? ' (' . $report['location_name'] . ')' : ''))

@section('full')
<div class="row">
    <!-- Pipeline Health & Summary -->
    <div class="col-md-12 mb-4">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-heartbeat mr-1"></i> PIPELINE HEALTH</h3>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="progress progress-xxs mb-2" style="height: 25px;">
                            <div class="progress-bar {{ $report['health'] >= 100 ? 'bg-success' : ($report['health'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                 role="progressbar" style="width: {{ $report['health'] }}%;" aria-valuenow="{{ $report['health'] }}" aria-valuemin="0" aria-valuemax="100">
                                <span class="font-weight-bold">{{ number_format($report['health'], 1) }}% Healthy</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <h4 class="mb-0">{{ count(array_filter($report['items'], fn($i) => $i['status'] === 'GREEN')) }} / {{ count($report['items']) }} Green Stockpiles</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Action Required: BUILD -->
    <div class="col-md-6">
        <div class="card card-primary card-outline shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-tools mr-1"></i> ACTION REQUIRED: BUILD</h3>
                <div class="card-tools">
                    @if(count($report['build_list']) > 0)
                        <form action="{{ route('seat-assets::stockpiles.from-requirements') }}" method="POST" style="display:inline;">
                            @csrf
                            @if(request()->get('mode') === 'wizard' || request()->header('referer') && str_contains(request()->header('referer'), 'mode=wizard'))
                                <input type="hidden" name="return_to_wizard" value="1">
                            @endif
                            <input type="hidden" name="name" value="Build: {{ $stockpile->name }}">
                            <input type="hidden" name="location_id" value="{{ $stockpile->location_id }}">
                            @foreach($report['build_list'] as $index => $item)
                                <input type="hidden" name="items[{{ $index }}][type_id]" value="{{ $item['type_id'] }}">
                                <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                            @endforeach
                            <button type="submit" class="btn btn-xs btn-primary">
                                <i class="fas fa-plus-circle mr-1"></i> Create Build Stockpile
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">Needed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['build_list'] as $build)
                            <tr>
                                <td>
                                    <img src="https://images.evetech.net/types/{{ $build['type_id'] }}/icon?size=32" style="width: 24px;" class="mr-2">
                                    {{ $build['name'] }}
                                </td>
                                <td class="text-right font-weight-bold text-primary">{{ number_format($build['quantity']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">No intermediate building required.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer py-2 small text-muted">
                <i class="fas fa-info-circle mr-1"></i> <strong>Build List:</strong> These are buildable components required to replenish your primary stockpiles.
            </div>
        </div>
    </div>

    <!-- Action Required: BUY -->
    <div class="col-md-6">
        <div class="card card-warning card-outline shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-shopping-cart mr-1"></i> ACTION REQUIRED: BUY</h3>
                <div class="card-tools">
                    @if(count($report['buy_list']) > 0)
                        <form action="{{ route('seat-assets::stockpiles.from-requirements') }}" method="POST" style="display:inline;">
                            @csrf
                            @if(request()->get('mode') === 'wizard' || request()->header('referer') && str_contains(request()->header('referer'), 'mode=wizard'))
                                <input type="hidden" name="return_to_wizard" value="1">
                            @endif
                            <input type="hidden" name="name" value="Buy: {{ $stockpile->name }}">
                            <input type="hidden" name="location_id" value="{{ $stockpile->location_id }}">
                            @foreach($report['buy_list'] as $index => $item)
                                <input type="hidden" name="items[{{ $index }}][type_id]" value="{{ $item['type_id'] }}">
                                <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
                            @endforeach
                            <button type="submit" class="btn btn-xs btn-warning">
                                <i class="fas fa-plus-circle mr-1"></i> Create Buy Stockpile
                            </button>
                        </form>
                    @endif
                    <button class="btn btn-xs btn-default" onclick="copyToClipboard('buy-list-text')">Copy Multi-buy</button>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Raw Material / PI</th>
                            <th class="text-right">Deficit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $buyText = ""; @endphp
                        @forelse($report['buy_list'] as $buy)
                            @php $buyText .= $buy['name'] . " " . $buy['quantity'] . "\n"; @endphp
                            <tr>
                                <td>
                                    <img src="https://images.evetech.net/types/{{ $buy['type_id'] }}/icon?size=32" style="width: 24px;" class="mr-2">
                                    {{ $buy['name'] }}
                                </td>
                                <td class="text-right font-weight-bold text-orange">{{ number_format($buy['quantity']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted py-3">All raw materials available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <textarea id="buy-list-text" style="display:none;">{{ $buyText }}</textarea>
            </div>
            <div class="card-footer py-2 small text-muted">
                <i class="fas fa-info-circle mr-1"></i> <strong>Buy List:</strong> These are raw materials or components that cannot be built and must be acquired.
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-12">
        <div class="card bg-light border-left-success">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-1 text-center">
                        <i class="fas fa-sync-alt fa-2x text-success"></i>
                    </div>
                    <div class="col-md-9">
                        <h5 class="mb-1 text-success font-weight-bold">Applying the "Red/Green" Loop</h5>
                        <p class="mb-0 small text-muted">
                            Your goal is to keep all stockpiles <span class="badge badge-success">GREEN</span>. 
                            <strong>RED</strong> status indicates a deficit in <strong>Effective Inventory</strong> (Current Assets + In-Flight Jobs). 
                            Always be buying or building to resolve Red items immediately.
                        </p>
                    </div>
                    <div class="col-md-2 text-right">
                        <a href="{{ route('seat-assets::stockpiles.workflow') }}" class="btn btn-sm btn-outline-success">Full Guide</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-left-success { border-left: 5px solid #28a745 !important; }
</style>

<div class="row">
    <!-- Detailed Status -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i> DETAILED STOCKPILE STATUS</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" id="stockpile-status-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Target Item</th>
                            <th class="text-right">Assets</th>
                            <th class="text-right">In-Flight</th>
                            <th class="text-right">Effective</th>
                            <th class="text-right">Target</th>
                            <th class="text-right">Deficit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['items'] as $item)
                            <tr class="{{ $item['status'] === 'GREEN' ? 'table-success-light' : 'table-danger-light' }}">
                                <td>
                                    @if($item['status'] === 'GREEN')
                                        <span class="badge badge-success">GREEN</span>
                                    @else
                                        <span class="badge badge-danger">RED</span>
                                    @endif
                                </td>
                                <td>
                                    <img src="https://images.evetech.net/types/{{ $item['type_id'] }}/icon?size=32" style="width: 24px;" class="mr-2">
                                    {{ $item['name'] }}
                                </td>
                                <td class="text-right">{{ number_format($item['assets']) }}</td>
                                <td class="text-right">{{ number_format($item['in_flight']) }}</td>
                                <td class="text-right font-weight-bold">{{ number_format($item['effective']) }}</td>
                                <td class="text-right">{{ number_format($item['target']) }}</td>
                                <td class="text-right font-weight-bold {{ $item['deficit'] > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $item['deficit'] > 0 ? number_format($item['deficit']) : 'OK' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .table-success-light { background-color: rgba(40, 167, 69, 0.05); }
    .table-danger-light { background-color: rgba(220, 53, 69, 0.05); }
    .progress-xxs { border-radius: 4px; border: 1px solid #ddd; }
</style>
@stop

@push('javascript')
<script>
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.style.display = 'block';
        copyText.select();
        document.execCommand("copy");
        copyText.style.display = 'none';
        alert("Copied to clipboard!");
    }

    $(function () {
        $('#stockpile-status-table').DataTable({
            "paging": false,
            "searching": true,
            "ordering": true,
            "info": false,
            "order": [[0, "asc"]]
        });
    });
</script>
@endpush
