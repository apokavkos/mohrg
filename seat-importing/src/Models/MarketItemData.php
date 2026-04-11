<?php

namespace Apokavkos\SeatImporting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketItemData extends Model
{
    protected $table = 'market_item_data';

    protected $fillable = [
        'hub_id', 'type_id', 'type_name', 'local_sell_price', 'local_buy_price',
        'jita_sell_price', 'jita_buy_price', 'current_stock', 'weekly_volume',
        'volume_m3', 'import_cost', 'markup_pct', 'weekly_profit', 'data_date',
    ];

    protected $casts = [
        'hub_id' => 'integer', 'type_id' => 'integer', 'local_sell_price' => 'float',
        'local_buy_price' => 'float', 'jita_sell_price' => 'float', 'jita_buy_price' => 'float',
        'current_stock' => 'integer', 'weekly_volume' => 'float', 'volume_m3' => 'float',
        'import_cost' => 'float', 'markup_pct' => 'float', 'weekly_profit' => 'float',
        'data_date' => 'date',
    ];

    public function hub(): BelongsTo { return $this->belongsTo(MarketHub::class, 'hub_id'); }

    public function scopeHighMarkup(Builder $query, float $minPct = 25.0): Builder
    {
        return $query->where('markup_pct', '>=', $minPct);
    }

    public function scopeLowStock(Builder $query, float $maxStockPct = 50.0): Builder
    {
        return $query->where('weekly_volume', '>', 0)
            ->whereRaw('(current_stock / weekly_volume) * 100 < ?', [$maxStockPct]);
    }

    public function scopeTopMarkup(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('markup_pct')->limit($limit);
    }

    public function scopeTopTotal(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('weekly_profit')->limit($limit);
    }

    public function scopeLatestDateSubquery(Builder $query, int $hubId): Builder
    {
        return $query->where('data_date', function($q) use ($hubId) {
            $q->selectRaw('max(data_date)')
              ->from('market_item_data')
              ->where('hub_id', $hubId);
        });
    }

    public function markupFormatted(): string { return number_format($this->markup_pct, 2) . '%'; }
    public function profitFormatted(): string { return number_format($this->weekly_profit, 0) . ' ISK'; }
    
    public function stockPct(): ?float
    {
        if ($this->weekly_volume <= 0) return null;
        return ($this->current_stock / $this->weekly_volume) * 100.0;
    }

    public function daysSupply(): ?float
    {
        if ($this->weekly_volume <= 0) return null;
        return $this->current_stock / ($this->weekly_volume / 7.0);
    }
}
