@if ($items->isEmpty())
    <div class="text-center py-4 text-muted">
        <i class="fas fa-inbox fa-2x mb-2"></i><br>
        No data available yet.
    </div>
@else
<div class="table-responsive">
    <table class="table table-sm table-hover seat-importing-table">
        <thead class="thead-light">
            <tr>
                <th>#</th>
                <th>Item Name</th>
                <th class="text-right">Markup %</th>
                <th class="text-right">Local Sell</th>
                <th class="text-right">Jita Sell</th>
                <th class="text-right">Import Cost</th>
                <th class="text-right">Weekly Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $rank => $item)
            <tr>
                <td class="text-muted">{{ $rank + 1 }}</td>
                <td>
                    <a href="#" class="item-detail-link" data-type-id="{{ $item->type_id }}">
                        {{ $item->type_name ?? "Type #{$item->type_id}" }}
                    </a>
                </td>
                <td class="text-right">
                    <span class="font-weight-bold {{ $item->markup_pct >= 100 ? 'text-success' : ($item->markup_pct >= 50 ? 'text-warning' : '') }}">
                        {{ number_format($item->markup_pct, 2) }}%
                    </span>
                </td>
                <td class="text-right">{{ number_format($item->local_sell_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->jita_sell_price, 2) }}</td>
                <td class="text-right text-muted">{{ number_format($item->import_cost, 2) }}</td>
                <td class="text-right">{{ number_format($item->weekly_profit, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
