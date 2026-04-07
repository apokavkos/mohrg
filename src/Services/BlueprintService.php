<?php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BlueprintService
{
    /**
     * Fuzzwork API requires the PRODUCT typeID, not the Blueprint typeID.
     */
    public function getMaterials(int $productTypeID)
    {
        return Cache::remember('eic.blueprint.mats.' . $productTypeID, 86400, function () use ($productTypeID) {
            try {
                // Fuzzwork Blueprint API
                $response = Http::get("https://www.fuzzwork.co.uk/blueprint/api/blueprint.php?typeid=" . $productTypeID);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Activity 1 is Manufacturing, Activity 11 is Reactions
                    $activityId = isset($data['activityMaterials'][1]) ? 1 : (isset($data['activityMaterials'][11]) ? 11 : null);

                    if (!$activityId || !isset($data['activityMaterials'][$activityId])) {
                        return [];
                    }

                    $materials = [];
                    foreach ($data['activityMaterials'][$activityId] as $mat) {
                        $materials[] = (object)[
                            'materialTypeID' => (int)$mat['typeid'],
                            'quantity' => (int)$mat['quantity'],
                            'typeName' => $mat['name'],
                            'activityID' => $activityId
                        ];
                    }
                    return $materials;
                }
            } catch (\Exception $e) {
                \Log::error("Fuzzwork API Failure: " . $e->getMessage());
            }
            return [];
        });
    }

    public function getBlueprintData(int $productTypeID)
    {
        return Cache::remember('eic.blueprint.data.' . $productTypeID, 86400, function () use ($productTypeID) {
            try {
                $response = Http::get("https://www.fuzzwork.co.uk/blueprint/api/blueprint.php?typeid=" . $productTypeID);
                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (!isset($data['blueprintDetails'])) {
                        return null;
                    }

                    $details = $data['blueprintDetails'];
                    
                    // Detect activity ID for time lookup
                    $activityId = isset($data['activityMaterials'][1]) ? 1 : (isset($data['activityMaterials'][11]) ? 11 : 1);

                    return [
                        'baseTime' => $details['times'][$activityId] ?? 0,
                        'blueprintTypeID' => $data['requestedid'] ?? 0,
                        'productTypeID' => $productTypeID,
                        'productName' => $details['productTypeName'] ?? DB::table('invTypes')->where('typeID', $productTypeID)->value('typeName'),
                        'productionQuantity' => $details['productQuantity'] ?? 1,
                        'activityID' => $activityId
                    ];
                }
            } catch (\Exception $e) { }
            return null;
        });
    }

    public function findProductByBlueprint(int $blueprintTypeID)
    {
        try {
            $product = DB::table('invTypes')
                ->join('industryActivityProducts', 'invTypes.typeID', '=', 'industryActivityProducts.productTypeID')
                ->where('industryActivityProducts.typeID', $blueprintTypeID)
                ->where('industryActivityProducts.activityID', 1)
                ->select('invTypes.typeID', 'invTypes.typeName')
                ->first();

            if ($product) return $product;
        } catch (\Exception $e) { }

        $bpName = DB::table('invTypes')->where('typeID', $blueprintTypeID)->value('typeName');
        $prodName = str_replace(' Blueprint', '', $bpName);
        return DB::table('invTypes')->where('typeName', $prodName)->first();
    }

    public function isComponentBuildable(int $typeID)
    {
        return Cache::remember('eic.type.buildable.' . $typeID, 86400, function() use ($typeID) {
            $typeName = DB::table('invTypes')->where('typeID', $typeID)->value('typeName');
            if (!$typeName) return false;

            return DB::table('invTypes')
                ->where('typeName', $typeName . ' Blueprint')
                ->exists();
        });
    }

    public function isComponentReactable(int $typeID)
    {
        return Cache::remember('eic.type.reactable.' . $typeID, 86400, function() use ($typeID) {
            $typeName = DB::table('invTypes')->where('typeID', $typeID)->value('typeName');
            if (!$typeName) return false;

            return DB::table('invTypes')
                ->where('typeName', 'Reaction Formula: ' . $typeName)
                ->exists();
        });
    }

    public function getMaterialTree(int $productTypeID, int $runs, float $meModifier, float $facilityModifier, float $rigModifier, bool $buildReactions = true, int $depth = 0)
    {
        if ($depth > 8) return [];

        $baseMaterials = $this->getMaterials($productTypeID);
        $tree = [];

        foreach ($baseMaterials as $mat) {
            $isReaction = ($mat->activityID ?? 1) == 11;
            $currentModifier = $isReaction ? 1.0 : ($meModifier * $facilityModifier * $rigModifier);
            
            $required = (int) max($runs, ceil(round($runs * $mat->quantity * $currentModifier, 2)));
            
            $node = [
                'typeID' => $mat->materialTypeID,
                'typeName' => $mat->typeName,
                'baseQuantity' => $mat->quantity,
                'adjustedQuantity' => $required,
                'isBuildable' => $this->isComponentBuildable($mat->materialTypeID),
                'isReactable' => $this->isComponentReactable($mat->materialTypeID),
                'activityID' => $mat->activityID ?? 1,
                'subMaterials' => []
            ];

            // Decide whether to cascade down
            $shouldCascade = false;
            if ($node['isBuildable']) {
                $shouldCascade = true;
            } elseif ($node['isReactable'] && $buildReactions) {
                $shouldCascade = true;
            }

            if ($shouldCascade && $depth < 8) {
                $subMeMod = $node['isReactable'] ? 1.0 : 0.90;
                
                $subBpData = $this->getBlueprintData($mat->materialTypeID);
                $outQty = $subBpData['productionQuantity'] ?? 1;
                $subRuns = (int) ceil($required / $outQty);

                $node['subMaterials'] = $this->getMaterialTree($mat->materialTypeID, $subRuns, $subMeMod, 1.0, 1.0, $buildReactions, $depth + 1);
            }

            $tree[] = $node;
        }

        return $tree;
    }
}
