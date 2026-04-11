@if ($items->isEmpty())
    <div class="text-center py-4 text-muted">
        <i class="fas fa-warehouse fa-2x mb-2"></i><br>
        All items are above the stock threshold.
    </div>
@else
<div class="table-responsive">
    <table class="table table-sm table-hover seat-importing-table">
        <thead class="thead-light">
            <tr>
                <th>Item Name</th>
                <th class="text-right">Weekly Vol</th>
                <th class="text-right">Current Stock</th>
                <th class="text-right">Stock %</th>
                <th class="text-right">Days Supply</th>
                <th class="text-right">Jita Sell</th>
                <th class="text-right">Local Sell</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
            @php
                $stockPct    = $item->stockPct();
                $daysSupply  = $item->daysSupply();
                $stockClass  = $stockPct !== null && $stockPct < 10
                    ? 'table-danger'
                    : ($stockPct !== null && $stockPct < 25 ? 'table-warning' : '');
            @endphp
            <tr class="{{ $stockClass }}">
                <td>
                    <a href="#" class="item-detail-link" data-type-id="{{ $item->type_id }}">
                        {{ $item->type_name ?? "Type #{$item->type_id}" }}
                    </a>
                </td>
                <td class="text-right">{{ number_format($item->weekly_volume, 0) }}</td>
                <td class="text-right">{{ number_format($item->current_stock) }}</td>
                <td class="text-right font-weight-bold">
                    @if ($stockPct !== null)
                        {{ number_format($stockPct, 1) }}%
                    @else
                        —
                    @endif
                </td>
                <td class="text-right">
                    @if ($daysSupply !== null)
                        {{ number_format($daysSupply, 1) }}d
                    @else
                        —
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->jita_sell_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->local_sell_price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
