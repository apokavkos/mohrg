@extends('web::layouts.grids.12')

@section('title', 'Market Markup Report')
@section('page_header', 'Market Markup Report')

@section('full')
    <div class="card card-default">
        <div class="card-body">
            <form action="{{ route('seat-assets::market.markup') }}" method="GET" class="form-inline">
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
            <h3 class="card-title">Price Comparison vs Jita</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="markup-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>In Stock</th>
                            <th class="text-right">Jita Price</th>
                            <th class="text-right">Local Price</th>
                            <th class="text-right">Markup %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report as $row)
                            <tr class="{{ $row->markup >= 25 ? 'table-warning' : '' }}">
                                <td>
                                    <img src="https://images.evetech.net/types/{{ $row->type_id }}/icon?size=32" style="width: 24px;" class="img-circle mr-2">
                                    {{ $row->name }}
                                </td>
                                <td>{{ number_format($row->qty) }}</td>
                                <td class="text-right">{{ number_format($row->jita_price, 2) }}</td>
                                <td class="text-right">{{ number_format($row->local_price, 2) }}</td>
                                <td class="text-right font-weight-bold {{ $row->markup >= 25 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($row->markup, 1) }}%
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
        $('#markup-table').DataTable({
            "paging": true,
            "lengthChange": true,
            "searching": true,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "order": [[4, "desc"]]
        });
    });
</script>
@endpush
