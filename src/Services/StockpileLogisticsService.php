<?php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\DB;
use Seat\Eveapi\Models\Assets\CharacterAsset;
use Seat\Eveapi\Models\Assets\CorporationAsset;
use Seat\Eveapi\Models\Industry\CharacterIndustryJob;
use Seat\Eveapi\Models\Industry\CorporationIndustryJob;
use Seat\Eveapi\Models\Sde\InvType;
use Apokavkos\SeatAssets\Models\Stockpile;
use Apokavkos\SeatAssets\Services\BlueprintService;

class StockpileLogisticsService
{
    protected $blueprintService;

    public function __construct(BlueprintService $blueprintService)
    {
        $this->blueprintService = $blueprintService;
    }

    public function getInventoryBreakdown($typeId, $characterIds, $corporationIds, $locationId = null)
    {
        // 1. Current Assets
        $charAssetsQuery = CharacterAsset::whereIn('character_id', $characterIds)
            ->where('type_id', $typeId);
        
        $corpAssetsQuery = CorporationAsset::whereIn('corporation_id', $corporationIds)
            ->where('type_id', $typeId);

        if ($locationId) {
            $charAssetsQuery->where('location_id', $locationId);
            $corpAssetsQuery->where('location_id', $locationId);
        }

        $charAssets = $charAssetsQuery->sum('quantity');
        $corpAssets = $corpAssetsQuery->sum('quantity');

        // 2. In-flight Jobs (Jobs don't really have a 'location' for products until delivered, 
        // but typically you build where your stockpile is. For now, in-flight is global 
        // as they are 'incoming' to the total effective inventory)
        $bpData = $this->blueprintService->getBlueprintData($typeId);
        $outputQty = $bpData['productionQuantity'] ?? 1;

        $charJobs = CharacterIndustryJob::whereIn('installer_id', $characterIds)
            ->where('product_type_id', $typeId)
            ->whereIn('status', ['active', 'ready'])
            ->sum('runs') * $outputQty;

        $corpJobs = CorporationIndustryJob::whereIn('corporation_id', $corporationIds)
            ->where('product_type_id', $typeId)
            ->whereIn('status', ['active', 'ready'])
            ->sum('runs') * $outputQty;

        $totalAssets = $charAssets + $corpAssets;
        $totalInFlight = $charJobs + $corpJobs;

        return [
            'assets' => $totalAssets,
            'in_flight' => $totalInFlight,
            'effective' => $totalAssets + $totalInFlight
        ];
    }

    public function getLogisticsReport(Stockpile $stockpile)
    {
        $user = auth()->user();
        $characterIds = $user->associatedCharacterIds();
        $corporationIds = DB::table('character_affiliations')
            ->whereIn('character_id', $characterIds)
            ->pluck('corporation_id')
            ->unique()
            ->toArray();

        $locationId = $stockpile->location_id;

        $report = [
            'items' => [],
            'buy_list' => [],
            'build_list' => [],
            'bottlenecks' => [],
            'health' => 0,
            'location_name' => null
        ];

        if ($locationId) {
            $name = DB::table('universe_stations')->where('station_id', $locationId)->value('name');
            if (!$name) {
                $name = DB::table('universe_structures')->where('structure_id', $locationId)->value('name');
            }
            $report['location_name'] = $name;
        }

        $targetItems = $stockpile->items;
        $totalItems = count($targetItems);
        $greenItems = 0;

        $requirements = []; // itemID => total needed quantity

        foreach ($targetItems as $item) {
            $inventory = $this->getInventoryBreakdown($item->type_id, $characterIds, $corporationIds, $locationId);
            $deficit = max(0, $item->quantity - $inventory['effective']);
            
            $status = $deficit <= 0 ? 'GREEN' : 'RED';
            if ($status === 'GREEN') $greenItems++;

            $report['items'][] = array_merge([
                'type_id' => $item->type_id,
                'name' => $item->typeName,
                'target' => $item->quantity,
                'deficit' => $deficit,
                'status' => $status
            ], $inventory);

            if ($deficit > 0) {
                $this->cascadeRequirements($item->type_id, $deficit, $requirements, $characterIds, $corporationIds, $locationId);
            }
        }

        foreach ($requirements as $typeId => $qty) {
            $inventory = $this->getInventoryBreakdown($typeId, $characterIds, $corporationIds, $locationId);
            $needed = max(0, $qty - $inventory['effective']);

            if ($needed <= 0) continue;

            $typeName = DB::table('invTypes')->where('typeID', $typeId)->value('typeName');
            
            if ($this->blueprintService->isComponentBuildable($typeId)) {
                $report['build_list'][] = [
                    'type_id' => $typeId,
                    'name' => $typeName,
                    'quantity' => $needed
                ];
            } else {
                $report['buy_list'][] = [
                    'type_id' => $typeId,
                    'name' => $typeName,
                    'quantity' => $needed
                ];
            }
        }

        $report['health'] = $totalItems > 0 ? ($greenItems / $totalItems) * 100 : 100;

        return $report;
    }

    protected function cascadeRequirements($typeId, $quantity, &$requirements, $characterIds, $corporationIds, $locationId)
    {
        $materials = $this->blueprintService->getMaterials($typeId);
        if (empty($materials)) return;

        foreach ($materials as $mat) {
            $needed = $mat->quantity * $quantity;
            if (!isset($requirements[$mat->materialTypeID])) {
                $requirements[$mat->materialTypeID] = 0;
            }
            $requirements[$mat->materialTypeID] += $needed;

            if ($this->blueprintService->isComponentBuildable($mat->materialTypeID)) {
                $this->cascadeRequirements($mat->materialTypeID, $needed, $requirements, $characterIds, $corporationIds, $locationId);
            }
        }
    }
}
