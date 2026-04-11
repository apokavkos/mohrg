{{--
    Item Detail Modal Content
    Rendered by renderItemDetail() JavaScript in dashboard.blade.php via AJAX.
    This partial is available as a standalone Blade render target if needed (e.g. for SSR fallback).
--}}
@if (isset($item))
<div class="row">
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <th>Type ID</th>
                <td>{{ $item['type_id'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Type Name</th>
                <td>{{ $item['type_name'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Group</th>
                <td>{{ $item['group_name'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Category</th>
                <td>{{ $item['category_name'] ?? '—' }}</td>
            </tr>
            <tr>
                <th>Volume</th>
                <td>{{ number_format($item['volume_m3'] ?? 0, 4) }} m³</td>
            </tr>
        </table>
        @if (! empty($item['description']))
            <p class="small text-muted">{{ \Illuminate\Support\Str::limit($item['description'], 400) }}</p>
        @endif
    </div>

    <div class="col-md-6">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th></th>
                    <th class="text-right">Sell</th>
                    <th class="text-right">Buy</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <th>Jita</th>
                    <td class="text-right">{{ number_format($item['jita_sell'] ?? 0, 2) }} ISK</td>
                    <td class="text-right">{{ number_format($item['jita_buy'] ?? 0, 2) }} ISK</td>
                </tr>
                <tr>
                    <th>Local</th>
                    <td class="text-right">{{ number_format($item['local_sell'] ?? 0, 2) }} ISK</td>
                    <td class="text-right">{{ number_format($item['local_buy'] ?? 0, 2) }} ISK</td>
                </tr>
            </tbody>
        </table>

        <table class="table table-sm">
            <tr>
                <th>Import Cost</th>
                <td class="text-right">{{ number_format($item['import_cost'] ?? 0, 2) }} ISK</td>
            </tr>
            <tr>
                <th>Markup</th>
                <td class="text-right">
                    <span class="font-weight-bold {{ ($item['markup_pct'] ?? 0) >= 25 ? 'text-success' : '' }}">
                        {{ number_format($item['markup_pct'] ?? 0, 2) }}%
                    </span>
                </td>
            </tr>
            <tr>
                <th>Weekly Profit</th>
                <td class="text-right font-weight-bold text-success">
                    {{ number_format($item['weekly_profit'] ?? 0, 0) }} ISK
                </td>
            </tr>
            <tr>
                <th>Weekly Volume</th>
                <td class="text-right">{{ number_format($item['weekly_volume'] ?? 0, 0) }} units</td>
            </tr>
            <tr>
                <th>Current Stock</th>
                <td class="text-right">{{ number_format($item['current_stock'] ?? 0) }} units</td>
            </tr>
            <tr>
                <th>Data Date</th>
                <td class="text-right">{{ $item['data_date'] ?? '—' }}</td>
            </tr>
        </table>
    </div>
</div>
@endif
