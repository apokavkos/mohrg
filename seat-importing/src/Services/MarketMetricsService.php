<?php

namespace Apokavkos\SeatImporting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Apokavkos\SeatImporting\Models\MarketHub;
use Apokavkos\SeatImporting\Models\MarketItemData;
use Apokavkos\SeatImporting\Models\MarketImportLog;

/**
 * Central service for all market metric calculations and cache management.
 *
 * Formulas used throughout (EVE market conventions):
 *   import_cost   = volume_m3 × isk_per_m3
 *   markup_pct    = ((local_sell − jita_sell − import_cost) / jita_sell) × 100
 *   weekly_profit = (local_sell − jita_sell − import_cost) × weekly_volume
 *   stock_pct     = (current_stock / weekly_volume) × 100
 */
class MarketMetricsService
{
    /**
     * Return all four metric sets for a hub, with caching.
     *
     * @return array{markup: Collection, low_stock: Collection, top_markup: Collection, top_total: Collection, last_import: MarketImportLog|null}
     */
    public function getHubMetrics(MarketHub $hub): array
    {
        $cacheKey = $this->hubCacheKey($hub->id);
        $ttl      = config('seat-importing.cache.metrics', 600);

        return Cache::remember($cacheKey, $ttl, function () use ($hub): array {
            $markupThreshold = (float) (\Apokavkos\SeatImporting\Models\MarketSetting::get(
                'markup_threshold_pct',
                null,
                config('seat-importing.markup_threshold_pct', 25.0)
            ));

            $stockThreshold = (float) (\Apokavkos\SeatImporting\Models\MarketSetting::get(
                'stock_low_threshold_pct',
                null,
                config('seat-importing.stock_low_threshold_pct', 50.0)
            ));

            return [
                'markup'      => $this->getMarkupItems($hub->id, $markupThreshold),
                'low_stock'   => $this->getLowStockItems($hub->id, $stockThreshold),
                'top_markup'  => $this->getTopMarkupItems($hub->id),
                'top_total'   => $this->getTopTotalItems($hub->id),
                'last_import' => $this->getLastImport($hub->id),
            ];
        });
    }

    /**
     * Items with markup_pct >= $minMarkupPct, ordered by weekly_profit descending.
     */
    public function getMarkupItems(int $hubId, float $minMarkupPct = 25.0): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->highMarkup($minMarkupPct)
            ->orderByDesc('weekly_profit')
            ->get();
    }

    /**
     * Items where stock is below $maxStockPct % of weekly volume — i.e. running low.
     * Ordered by stock_pct ascending so most critical items appear first.
     */
    public function getLowStockItems(int $hubId, float $maxStockPct = 50.0): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->lowStock($maxStockPct)
            ->orderByRaw('(current_stock / weekly_volume) ASC')
            ->get();
    }

    /**
     * Top $limit items by markup_pct descending.
     */
    public function getTopMarkupItems(int $hubId, int $limit = 20): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->topMarkup($limit)
            ->get();
    }

    /**
     * Top $limit items by weekly_profit descending.
     */
    public function getTopTotalItems(int $hubId, int $limit = 20): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->topTotal($limit)
            ->get();
    }

    /**
     * Compute per-row market metrics for a single item.
     *
     * @param  array{local_sell: float, jita_sell: float, volume_m3: float, weekly_volume: float, current_stock?: int} $row
     * @param  float $iskPerM3  Freight cost for the target hub
     * @return array{import_cost: float, markup_pct: float, weekly_profit: float}
     */
    public function calculateMetrics(array $row, float $iskPerM3): array
    {
        $localSell    = (float) ($row['local_sell'] ?? 0);
        $jitaSell     = (float) ($row['jita_sell'] ?? 0);
        $volumeM3     = (float) ($row['volume_m3'] ?? 0);
        $weeklyVolume = (float) ($row['weekly_volume'] ?? 0);

        $importCost = $volumeM3 * $iskPerM3;

        // Avoid division by zero — if Jita price is 0, markup is meaningless
        $markupPct = $jitaSell > 0
            ? (($localSell - $jitaSell - $importCost) / $jitaSell) * 100.0
            : 0.0;

        $weeklyProfit = ($localSell - $jitaSell - $importCost) * $weeklyVolume;

        return [
            'import_cost'   => round($importCost, 2),
            'markup_pct'    => round($markupPct, 4),
            'weekly_profit' => round($weeklyProfit, 2),
        ];
    }

    /**
     * Invalidate cached metrics for a specific hub.
     */
    public function flushHubCache(int $hubId): void
    {
        Cache::forget($this->hubCacheKey($hubId));
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function hubCacheKey(int $hubId): string
    {
        return config('seat-importing.cache.prefix', 'seat-importing') . ":hub_metrics:{$hubId}";
    }

    private function getLastImport(int $hubId): ?MarketImportLog
    {
        return MarketImportLog::where('hub_id', $hubId)
            ->whereIn('status', ['complete', 'failed'])
            ->latest('completed_at')
            ->first();
    }
}
