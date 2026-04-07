@extends('web::layouts.grids.12')

@section('title', 'Market Stock Health')
@section('page_header', 'Market Stock Health')

@section('full')
    <div class="card card-default">
        <div class="card-body">
            <form action="{{ route('seat-assets::market.stock') }}" method="GET" class="form-inline">
                <div class="form-group">
                    <label for="hub_id" class="mr-2">Select Market Hub:</label>
                    <select name="hub_id" id="hub_id" class="form-control select2" onchange="this.form.submit()">
                        @foreach($hubs as $hub)
                            <option value="{{ $hub->hub_id }}" {{ $selectedHubId == $hub->hub_id ? 'selected' : '' }}>
                                {{ $hub->name }} ({{ ucfirst($hub->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Stock vs Weekly Volume</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="stock-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-right">In Stock</th>
                            <th class="text-right">Avg Weekly Vol</th>
                            <th class="text-right">Health %</th>
                            <th class="text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report as $row)
                            <tr>
                                <td>
                                    <img src="https://images.evetech.net/types/{{ $row->type_id }}/icon?size=32" style="width: 24px;" class="img-circle mr-2">
                                    {{ $row->typeName }}
                                </td>
                                <td class="text-right">{{ number_format($row->quantity) }}</td>
                                <td class="text-right">{{ number_format($row->weekly_volume ?: 0) }}</td>
                                <td class="text-right">{{ number_format($row->stock_health, 1) }}%</td>
                                <td class="text-right">
                                    @if($row->quantity == 0)
                                        <span class="badge badge-danger">OUT OF STOCK</span>
                                    @elseif($row->stock_health < 50)
                                        <span class="badge badge-warning">UNDERSEEDED</span>
                                    @else
                                        <span class="badge badge-success">HEALTHY</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@push('javascript')
<script>
    $(function () {
        $('#stock-table').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[3, "asc"]]
        });
    });
</script>
@endpush
