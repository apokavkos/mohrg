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
                <th class="text-right">Weekly Vol</th>
                <th class="text-right">Local Sell</th>
                <th class="text-right">Weekly ISK Volume</th>
                <th class="text-right">Markup %</th>
                <th class="text-right">Weekly Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $rank => $item)
            @php
                // Weekly ISK volume = total ISK traded at this hub for the item per week
                $weeklyIskVolume = $item->local_sell_price * $item->weekly_volume;
            @endphp
            <tr>
                <td class="text-muted">{{ $rank + 1 }}</td>
                <td>
                    <a href="#" class="item-detail-link" data-type-id="{{ $item->type_id }}">
                        {{ $item->type_name ?? "Type #{$item->type_id}" }}
                    </a>
                </td>
                <td class="text-right">{{ number_format($item->weekly_volume, 0) }}</td>
                <td class="text-right">{{ number_format($item->local_sell_price, 2) }}</td>
                <td class="text-right text-info">{{ number_format($weeklyIskVolume, 0) }}</td>
                <td class="text-right">{{ number_format($item->markup_pct, 2) }}%</td>
                <td class="text-right font-weight-bold text-success">
                    {{ number_format($item->weekly_profit, 0) }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
