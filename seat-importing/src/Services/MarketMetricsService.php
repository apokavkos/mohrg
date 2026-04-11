<?php

namespace Apokavkos\SeatImporting\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Apokavkos\SeatImporting\Models\MarketHub;
use Apokavkos\SeatImporting\Models\MarketItemData;
use Apokavkos\SeatImporting\Models\MarketImportLog;
use Apokavkos\SeatImporting\Models\MarketSetting;

class MarketMetricsService
{
    public function getHubMetrics(MarketHub $hub): array
    {
        $cacheKey = $this->hubCacheKey($hub->id);
        
        // If cache is empty, we warm it on the fly, but ideally the background job does this
        if (!Cache::has($cacheKey)) {
            $this->warmHubCache($hub->id);
        }

        return Cache::get($cacheKey, [
            'markup'      => collect(),
            'low_stock'   => collect(),
            'top_markup'  => collect(),
            'top_total'   => collect(),
            'last_import' => $this->getLastImport($hub->id),
        ]);
    }

    public function warmHubCache(int $hubId): void
    {
        $markupThreshold = (float) MarketSetting::get('markup_threshold_pct', null, config('seat-importing.markup_threshold_pct', 25.0));
        $stockThreshold  = (float) MarketSetting::get('stock_low_threshold_pct', null, config('seat-importing.stock_low_threshold_pct', 50.0));

        $metrics = [
            'markup'      => $this->fetchMarkupItems($hubId, $markupThreshold),
            'low_stock'   => $this->fetchLowStockItems($hubId, $stockThreshold),
            'top_markup'  => $this->fetchTopMarkupItems($hubId),
            'top_total'   => $this->fetchTopTotalItems($hubId),
            'last_import' => $this->getLastImport($hubId),
        ];

        Cache::put($this->hubCacheKey($hubId), $metrics, config('seat-importing.cache.metrics', 3600 * 24));
    }

    private function fetchMarkupItems(int $hubId, float $minMarkupPct): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->highMarkup($minMarkupPct)
            ->orderByDesc('weekly_profit')
            ->limit(1000)
            ->get();
    }

    private function fetchLowStockItems(int $hubId, float $maxStockPct): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->lowStock($maxStockPct)
            ->orderByRaw('(current_stock / NULLIF(weekly_volume, 0)) ASC')
            ->limit(1000)
            ->get();
    }

    private function fetchTopMarkupItems(int $hubId): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->topMarkup(50)
            ->get();
    }

    private function fetchTopTotalItems(int $hubId): Collection
    {
        return MarketItemData::where('hub_id', $hubId)
            ->latestDateSubquery($hubId)
            ->topTotal(50)
            ->get();
    }

    public function getLastImport(int $hubId): ?MarketImportLog
    {
        return MarketImportLog::where('hub_id', $hubId)->where('status', 'complete')->latest('completed_at')->first();
    }

    public function flushHubCache(int $hubId): void
    {
        Cache::forget($this->hubCacheKey($hubId));
    }

    private function hubCacheKey(int $hubId): string
    {
        return config('seat-importing.cache.prefix', 'seat-importing') . ":hub_metrics:{$hubId}";
    }

    public function calculateMetrics(array $row, float $iskPerM3): array
    {
        $importCost = $row['volume_m3'] * $iskPerM3;
        $profitPerUnit = $row['local_sell'] - $row['jita_sell'] - $importCost;
        $markupPct = $row['jita_sell'] > 0 ? ($profitPerUnit / $row['jita_sell']) * 100 : 0;
        $weeklyProfit = $profitPerUnit * $row['weekly_volume'];

        return [
            'import_cost'   => $importCost,
            'markup_pct'    => $markupPct,
            'weekly_profit' => $weeklyProfit,
        ];
    }
}
