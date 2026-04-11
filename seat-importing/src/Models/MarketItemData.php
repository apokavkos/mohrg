<?php

namespace Apokavkos\SeatImporting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-hub, per-item market snapshot.
 * Prices are in ISK; volumes are units; volume_m3 is the EVE item packaged volume.
 *
 * @property int    $id
 * @property int    $hub_id
 * @property int    $type_id          EVE typeID from invTypes
 * @property string $type_name
 * @property float  $local_sell_price Lowest sell order at the hub
 * @property float  $local_buy_price  Highest buy order at the hub
 * @property float  $jita_sell_price  Jita sell (source price)
 * @property float  $jita_buy_price   Jita buy
 * @property int    $current_stock    Units currently in sell orders at hub
 * @property float  $weekly_volume    Average weekly units sold at hub
 * @property float  $volume_m3        Packaged volume per unit (m³) from SDE
 * @property float  $import_cost      volume_m3 × isk_per_m3
 * @property float  $markup_pct       ((local_sell - jita_sell - import_cost) / jita_sell) × 100
 * @property float  $weekly_profit    (local_sell - jita_sell - import_cost) × weekly_volume
 * @property string $data_date        Date of this snapshot (YYYY-MM-DD)
 */
class MarketItemData extends Model
{
    protected $table = 'market_item_data';

    protected $fillable = [
        'hub_id',
        'type_id',
        'type_name',
        'local_sell_price',
        'local_buy_price',
        'jita_sell_price',
        'jita_buy_price',
        'current_stock',
        'weekly_volume',
        'volume_m3',
        'import_cost',
        'markup_pct',
        'weekly_profit',
        'data_date',
    ];

    protected $casts = [
        'hub_id'           => 'integer',
        'type_id'          => 'integer',
        'local_sell_price' => 'float',
        'local_buy_price'  => 'float',
        'jita_sell_price'  => 'float',
        'jita_buy_price'   => 'float',
        'current_stock'    => 'integer',
        'weekly_volume'    => 'float',
        'volume_m3'        => 'float',
        'import_cost'      => 'float',
        'markup_pct'       => 'float',
        'weekly_profit'    => 'float',
        'data_date'        => 'date',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function hub(): BelongsTo
    {
        return $this->belongsTo(MarketHub::class, 'hub_id');
    }

    // -------------------------------------------------------------------------
    // Query Scopes
    // -------------------------------------------------------------------------

    /**
     * Items with markup at or above the given threshold.
     */
    public function scopeHighMarkup(Builder $query, float $minPct = 25.0): Builder
    {
        return $query->where('markup_pct', '>=', $minPct);
    }

    /**
     * Items where current stock is below the given percentage of weekly volume.
     * A stock_pct < 50 means less than half a week's supply is on the market.
     */
    public function scopeLowStock(Builder $query, float $maxStockPct = 50.0): Builder
    {
        // Avoid divide-by-zero; only consider items with a meaningful weekly volume
        return $query->where('weekly_volume', '>', 0)
            ->whereRaw('(current_stock / weekly_volume) * 100 < ?', [$maxStockPct]);
    }

    /**
     * Top items by markup percentage, descending.
     */
    public function scopeTopMarkup(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('markup_pct')->limit($limit);
    }

    /**
     * Top items by absolute weekly profit, descending.
     */
    public function scopeTopTotal(Builder $query, int $limit = 20): Builder
    {
        return $query->orderByDesc('weekly_profit')->limit($limit);
    }

    /**
     * Restrict the query to rows from the most recent data_date for the given hub.
     * This ensures we only show the latest snapshot and not historical data.
     */
    public function scopeLatestDateSubquery(Builder $query, int $hubId): Builder
    {
        $latestDate = static::where('hub_id', $hubId)
            ->max('data_date');

        if ($latestDate) {
            $query->where('data_date', $latestDate);
        }

        return $query;
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Human-readable markup percentage (e.g. "42.50%").
     */
    public function markupFormatted(): string
    {
        return number_format($this->markup_pct, 2) . '%';
    }

    /**
     * Human-readable weekly profit (e.g. "1,234,567,890 ISK").
     */
    public function profitFormatted(): string
    {
        return number_format($this->weekly_profit, 0) . ' ISK';
    }

    /**
     * Computed stock percentage (current stock as % of weekly volume).
     * Returns null if weekly_volume is zero.
     */
    public function stockPct(): ?float
    {
        if ($this->weekly_volume <= 0) {
            return null;
        }

        return ($this->current_stock / $this->weekly_volume) * 100.0;
    }

    /**
     * Days of supply remaining at current sell rate.
     */
    public function daysSupply(): ?float
    {
        if ($this->weekly_volume <= 0) {
            return null;
        }

        // weekly_volume / 7 gives daily volume; current_stock / daily_volume = days
        return $this->current_stock / ($this->weekly_volume / 7.0);
    }
}
