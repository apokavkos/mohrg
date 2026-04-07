@extends('web::layouts.grids.12')

@section('title', 'Stockpiles')
@section('page_header', 'Stockpiles')

@section('full')
    <!-- 1. Stockpiles Table (Moved to Top) -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">Stockpiles</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Name (Click to Expand)</th>
                                    <th>Check Location</th>
                                    <th>Items</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockpiles as $stockpile)
                                    <tr class="stockpile-row" style="cursor: pointer;" data-target="#items-{{ $stockpile->id }}">
                                        <td>
                                            <i class="fas fa-chevron-right mr-2 transition-icon"></i>
                                            <strong>{{ $stockpile->name }}</strong>
                                        </td>
                                        <td>
                                            @if($stockpile->location_id)
                                                {{ $locations[$stockpile->location_id] ?? 'Unknown Location' }}
                                            @else
                                                <span class="text-muted">All Locations</span>
                                            @endif
                                        </td>
                                        <td>{{ $stockpile->items_count }}</td>
                                        <td>{{ $stockpile->created_at->diffForHumans() }}</td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('seat-assets::stockpiles.industry', $stockpile->id) }}" class="btn btn-xs btn-info">
                                                    <i class="fas fa-industry"></i> Industry
                                                </a>
                                                <form action="{{ route('seat-assets::stockpiles.delete', $stockpile->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this stockpile?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-xs btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr id="items-{{ $stockpile->id }}" class="item-details-row d-none bg-gray-lightest">
                                        <td colspan="5" class="p-0">
                                            <div class="p-3">
                                                <table class="table table-sm table-bordered mb-0 bg-white">
                                                    <thead>
                                                        <tr>
                                                            <th>Item Name</th>
                                                            <th class="text-right">Target Quantity</th>
                                                            <th class="text-right">Current Stock</th>
                                                            <th>Housing Structure</th>
                                                            <th class="text-right">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($stockpile->items as $item)
                                                            <tr class="{{ $item->local_stock >= $item->quantity ? 'table-success' : 'table-danger' }}">
                                                                <td>{{ $item->typeName }}</td>
                                                                <td class="text-right">{{ number_format($item->quantity) }}</td>
                                                                <td class="text-right">{{ number_format($item->local_stock) }}</td>
                                                                <td>
                                                                    <select class="form-control form-control-sm item-location-select location-search" data-item-id="{{ $item->id }}">
                                                                        <option value="" {{ !$item->location_id ? 'selected' : '' }}>
                                                                            {{ $stockpile->location_id ? '(Inherit: ' . ($locations[$stockpile->location_id] ?? 'Unknown') . ')' : 'All Locations' }}
                                                                        </option>
                                                                        @foreach($locations as $id => $name)
                                                                            <option value="{{ $id }}" {{ $item->location_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td class="text-right">
                                                                    @if($item->local_stock >= $item->quantity)
                                                                        <span class="badge badge-success">OK</span>
                                                                    @else
                                                                        <span class="badge badge-danger">MISSING {{ number_format($item->quantity - $item->local_stock) }}</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No stockpiles found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Workflow Banner (Moved to Middle) -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card card-outline card-success shadow-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="text-success"><i class="fas fa-sync-alt mr-2"></i> Optimize with the Stockpile Churn Workflow</h4>
                            <p class="mb-0 text-muted">Stop building for one-off batches. Build continuous pipelines of intermediate components and raw materials to maximize your industrial throughput.</p>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="btn-group">
                                <a href="{{ route('seat-assets::stockpiles.workflow') }}" class="btn btn-outline-success">
                                    <i class="fas fa-book-open mr-1"></i> Full Guide
                                </a>
                                <a href="{{ route('seat-assets::stockpiles.workflow', ['mode' => 'wizard']) }}" class="btn btn-success">
                                    <i class="fas fa-magic mr-1"></i> Start Interactive Guide
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Add New Stockpile (Moved to Bottom) -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h3 class="card-title">Add New Stockpile</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('seat-assets::stockpiles.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Stockpile Name</label>
                                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. My Abyssal Gila" required>
                                </div>
                                <div class="form-group">
                                    <label for="location_id">Check Location (Optional)</label>
                                    <select name="location_id" id="location_id" class="form-control location-search">
                                        <option value="">Check All Locations</option>
                                        @foreach($locations as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Type a solar system name to find structures in that system.</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block mt-4">Save Stockpile</button>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="multibuy">EVE Multi-Buy Text</label>
                                    <textarea name="multibuy" id="multibuy" rows="7" class="form-control" placeholder="Paste Multi-Buy text here..." required></textarea>
                                    <small class="form-text text-muted">Paste the list of items from the EVE Online Multi-Buy window.</small>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@push('javascript')
<script>
    $(function () {
        $('.location-search').select2({
            placeholder: 'Search for system or structure...',
            minimumInputLength: 0,
            allowClear: true,
            ajax: {
                url: '{{ route("seat-assets::stockpiles.search.locations") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: $.map(data.results, function (obj) {
                            return { id: obj.id, text: obj.text };
                        })
                    };
                },
                cache: true
            }
        });

        $('.item-location-select').on('change', function() {
            var itemId = $(this).data('item-id');
            var locationId = $(this).val();
            var $row = $(this).closest('tr');

            $.post('{{ route("seat-assets::stockpiles.item.location", ["itemId" => "REPLACE_ID"]) }}'.replace('REPLACE_ID', itemId), {
                _token: '{{ csrf_token() }}',
                location_id: locationId
            }, function(data) {
                // Refresh the page to update inventory counts
                location.reload();
            });
        });

        $('.stockpile-row').on('click', function() {
            var target = $(this).data('target');
            var $targetRow = $(target);
            var $icon = $(this).find('.transition-icon');

            if ($targetRow.hasClass('d-none')) {
                $targetRow.removeClass('d-none');
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
            } else {
                $targetRow.addClass('d-none');
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
            }
        });
    });
</script>
@endpush
