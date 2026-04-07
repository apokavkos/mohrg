<?php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Apokavkos\SeatAssets\Models\MarketPriceCache;
use Seat\Eveapi\Models\RefreshToken;
use Carbon\Carbon;
use Seat\Eveapi\Services\EseyeClient;

class MarketPriceService
{
    protected $jitaRegionId = 10000002;

    public function getPrices(array $typeIds, int $locationId = null)
    {
        if (!$locationId || $locationId < 100000000) {
            return $this->getRegionPrices($typeIds, $locationId ?: $this->jitaRegionId);
        }

        return $this->getStructurePrices($typeIds, $locationId);
    }

    protected function getRegionPrices(array $typeIds, int $regionId)
    {
        $now = Carbon::now();
        $cached = MarketPriceCache::whereIn('type_id', $typeIds)
            ->where('region_id', $regionId)
            ->where('updated_at', '>', $now->copy()->subMinutes(15))
            ->get()
            ->keyBy('type_id');

        $missingIds = array_diff($typeIds, $cached->keys()->toArray());

        if (!empty($missingIds)) {
            $chunks = array_chunk($missingIds, 100);
            foreach ($chunks as $chunk) {
                try {
                    $url = "https://market.fuzzwork.co.uk/aggregates/?region={$regionId}&types=" . implode(',', $chunk);
                    $response = Http::get($url);
                    
                    if ($response->successful()) {
                        $data = $response->json();
                        foreach ($data as $typeId => $prices) {
                            MarketPriceCache::updateOrCreate(
                                ['type_id' => $typeId, 'region_id' => $regionId],
                                [
                                    'buy_price' => (float)($prices['buy']['max'] ?? 0),
                                    'sell_price' => (float)($prices['sell']['min'] ?? 0),
                                    'adjusted_price' => (float)($prices['sell']['min'] ?? 0),
                                    'updated_at' => Carbon::now()
                                ]
                            );
                        }
                    }
                } catch (\Exception $e) {
                    \Log::error("MarketPriceService Region Error: " . $e->getMessage());
                }
            }
            $cached = MarketPriceCache::whereIn('type_id', $typeIds)
                ->where('region_id', $regionId)
                ->get()
                ->keyBy('type_id');
        }

        return $cached;
    }

    protected function getStructurePrices(array $typeIds, int $structureId)
    {
        $now = Carbon::now();
        
        $cached = MarketPriceCache::whereIn('type_id', $typeIds)
            ->where('region_id', $structureId)
            ->where('updated_at', '>', $now->copy()->subMinutes(15))
            ->get()
            ->keyBy('type_id');

        $missingIds = array_diff($typeIds, $cached->keys()->toArray());

        if (!empty($missingIds)) {
            $lockKey = 'eic.market.fetch.' . $structureId;
            
            Cache::lock($lockKey, 60)->get(function () use ($structureId) {
                $token = RefreshToken::where('scopes', 'like', '%esi-markets.structure_markets.v1%')->first();
                if (!$token) return;

                try {
                    $esi = new EseyeClient();
                    $esi->setAuthentication($token);
                    
                    $allOrders = [];
                    $page = 1;

                    do {
                        $response = $esi->invoke('get', "/markets/structures/{$structureId}/", [
                            'query' => ['page' => $page]
                        ]);
                        
                        if ($response->isFailed()) break;

                        $batch = $response->getBody();
                        if (is_array($batch)) {
                            $allOrders = array_merge($allOrders, $batch);
                        }
                        
                        $page++;
                    } while ($page <= $response->getPagesCount());

                    $prices = [];
                    foreach ($allOrders as $order) {
                        $order = (object)$order;
                        $tId = $order->type_id;
                        if (!isset($prices[$tId])) {
                            $prices[$tId] = ['buy' => 0, 'sell' => PHP_INT_MAX];
                        }
                        if ($order->is_buy_order) {
                            $prices[$tId]['buy'] = max($prices[$tId]['buy'], $order->price);
                        } else {
                            $prices[$tId]['sell'] = min($prices[$tId]['sell'], $order->price);
                        }
                    }

                    foreach ($prices as $typeId => $p) {
                        MarketPriceCache::updateOrCreate(
                            ['type_id' => $typeId, 'region_id' => $structureId],
                            [
                                'buy_price' => $p['buy'],
                                'sell_price' => $p['sell'] == PHP_INT_MAX ? 0 : $p['sell'],
                                'updated_at' => Carbon::now()
                            ]
                        );
                    }
                } catch (\Exception $e) {
                    \Log::error("MarketPriceService Structure ESI Error: " . $e->getMessage());
                }
            });

            $cached = MarketPriceCache::whereIn('type_id', $typeIds)
                ->where('region_id', $structureId)
                ->get()
                ->keyBy('type_id');
        }

        return $cached;
    }

    public function getAdjustedPrices()
    {
        return Cache::remember('eic.adjusted_prices', 3600, function() {
            try {
                $response = Http::get("https://esi.evetech.net/latest/markets/prices/");
                if ($response->successful()) {
                    return collect($response->json())->keyBy('type_id');
                }
            } catch (\Exception $e) { }
            return collect();
        });
    }
}
