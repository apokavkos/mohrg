<?php

namespace Apokavkos\SeatAssets\Services;

use Apokavkos\SeatAssets\Models\MarketHub;
use Apokavkos\SeatAssets\Models\MarketSnapshot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Seat\Eveapi\Models\RefreshToken;
use Seat\Eveapi\Services\EseyeClient;
use Carbon\Carbon;

class MarketSyncService
{
    public function syncAllHubs()
    {
        $hubs = MarketHub::where('is_enabled', true)->get();
        
        foreach ($hubs as $hub) {
            $this->syncHub($hub);
        }
    }

    public function syncHub(MarketHub $hub)
    {
        if ($hub->type === 'region') {
            $this->syncRegion($hub);
        } else {
            $this->syncStructure($hub);
        }
    }

    public function syncVolumeData(array $typeIds)
    {
        $regionId = 10000002; // Jita
        
        foreach ($typeIds as $typeId) {
            if (Cache::has('eic.volume.sync.' . $typeId)) continue;

            try {
                $response = Http::get("https://esi.evetech.net/latest/markets/{$regionId}/history/?type_id={$typeId}");
                if ($response->successful()) {
                    $history = collect($response->json())->take(-7); // Last 7 days
                    $weeklyVolume = $history->sum('volume');
                    $avgPrice = $history->avg('average');

                    DB::table('eic_market_volume')->updateOrInsert(
                        ['type_id' => $typeId],
                        ['weekly_volume' => $weeklyVolume, 'avg_price' => $avgPrice, 'updated_at' => Carbon::now()]
                    );
                    
                    Cache::put('eic.volume.sync.' . $typeId, true, 86400);
                }
                usleep(100000); // 0.1s delay
            } catch (\Exception $e) { }
        }
    }

    protected function syncRegion(MarketHub $hub)
    {
        if ($hub->hub_id == 10000002) {
            $typeIds = app(ReactionDataService::class)->getAllInvolvedTypeIds();
            app(MarketPriceService::class)->getPrices($typeIds, $hub->hub_id);
            
            $prices = DB::table('eic_market_price_cache')->where('region_id', $hub->hub_id)->get();
            
            DB::transaction(function() use ($hub, $prices) {
                MarketSnapshot::where('hub_id', $hub->hub_id)->update(['quantity' => 0]);
                
                foreach ($prices as $p) {
                    MarketSnapshot::updateOrCreate(
                        ['hub_id' => $hub->hub_id, 'type_id' => $p->type_id],
                        ['quantity' => 0, 'lowest_sell' => $p->sell_price, 'updated_at' => Carbon::now()]
                    );
                }
            });
        }
    }

    protected function syncStructure(MarketHub $hub)
    {
        $token = RefreshToken::where('scopes', 'like', '%esi-markets.structure_markets.v1%')->first();
        if (!$token) return;

        try {
            $esi = new EseyeClient();
            $esi->setAuthentication($token);
            
            $allOrders = [];
            $page = 1;

            do {
                $response = $esi->invoke('get', "/markets/structures/{$hub->hub_id}/", [
                    'query' => ['page' => $page]
                ]);
                if ($response->isFailed()) break;
                $batch = $response->getBody();
                if (is_array($batch)) $allOrders = array_merge($allOrders, $batch);
                $page++;
            } while ($page <= $response->getPagesCount());

            $items = [];
            foreach ($allOrders as $order) {
                $order = (object)$order;
                if (!isset($items[$order->type_id])) {
                    $items[$order->type_id] = ['qty' => 0, 'low' => PHP_INT_MAX];
                }
                
                if (!$order->is_buy_order) {
                    $items[$order->type_id]['qty'] += $order->volume_remain;
                    $items[$order->type_id]['low'] = min($items[$order->type_id]['low'], $order->price);
                }
            }

            DB::transaction(function() use ($hub, $items) {
                // Reset ALL existing snapshots for this hub to 0 qty
                // This ensures items no longer on market are cleared
                MarketSnapshot::where('hub_id', $hub->hub_id)->update(['quantity' => 0]);

                foreach ($items as $typeId => $data) {
                    MarketSnapshot::updateOrCreate(
                        ['hub_id' => $hub->hub_id, 'type_id' => $typeId],
                        [
                            'quantity' => $data['qty'],
                            'lowest_sell' => $data['low'] == PHP_INT_MAX ? 0 : $data['low'],
                            'updated_at' => Carbon::now()
                        ]
                    );
                }
            });

        } catch (\Exception $e) {
            \Log::error("MarketSyncService Structure Error: " . $e->getMessage());
        }
    }
}
