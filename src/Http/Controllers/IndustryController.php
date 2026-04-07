<?php

namespace Apokavkos\SeatAssets\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Apokavkos\SeatAssets\Services\BlueprintService;
use Apokavkos\SeatAssets\Services\BlueprintImportService;
use Apokavkos\SeatAssets\Services\CostCalculatorService;
use Apokavkos\SeatAssets\Services\EveIndustryApiService;
use Apokavkos\SeatAssets\Services\DataTables\AllAssetsDataTable;
use Illuminate\Support\Facades\DB;

class IndustryController extends Controller
{
    protected $blueprintService;
    protected $importService;
    protected $calculatorService;
    protected $apiService;

    public function __construct(
        BlueprintService $blueprintService, 
        BlueprintImportService $importService, 
        CostCalculatorService $calculatorService, 
        EveIndustryApiService $apiService
    ) {
        $this->blueprintService = $blueprintService;
        $this->importService = $importService;
        $this->calculatorService = $calculatorService;
        $this->apiService = $apiService;
    }

    public function index()
    {
        $characterIds = auth()->user()->associatedCharacterIds();
        
        $currentSystems = DB::table('character_locations')
            ->whereIn('character_id', $characterIds)
            ->join('solar_systems', 'character_locations.solar_system_id', '=', 'solar_systems.system_id')
            ->select('solar_systems.name')
            ->distinct()
            ->pluck('name')
            ->toArray();

        return view('seat-assets::industry.calculator', compact('currentSystems'));
    }

    public function guide()
    {
        return view('seat-assets::industry.guide');
    }

    public function assets(Request $request, AllAssetsDataTable $dataTable)
    {
        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        return $dataTable->render('seat-assets::assets.index');
    }

    public function searchItems(Request $request)
    {
        $q = $request->get('q');
        if (empty($q)) return response()->json(['results' => []]);

        $items = DB::table('invTypes')
            ->where('published', 1)
            ->where('typeName', 'like', '%' . $q . '%')
            ->limit(20)
            ->select('typeID', 'typeName')
            ->get();

        $results = [];
        foreach ($items as $item) {
            $results[] = [
                'id' => $item->typeID,
                'text' => $item->typeName
            ];
        }

        return response()->json(['results' => $results]);
    }

    public function searchSystems(Request $request)
    {
        $q = $request->get('q');
        if (empty($q)) return response()->json(['results' => []]);

        $systems = DB::table('solar_systems')
            ->where('name', 'like', '%' . $q . '%')
            ->limit(20)
            ->select('name')
            ->get();

        $results = [];
        foreach ($systems as $sys) {
            $results[] = ['id' => $sys->name, 'text' => $sys->name];
        }

        return response()->json(['results' => $results]);
    }

    public function getSystemIndex($systemName)
    {
        $indices = $this->apiService->getSystemCostIndex($systemName);
        $mfgIndex = $indices[1] ?? null;
        
        if ($mfgIndex === null) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json([
            'manufacturing' => $mfgIndex,
            'formatted' => number_format($mfgIndex * 100, 2) . '%'
        ]);
    }

    public function listOwnedBlueprints(Request $request)
    {
        try {
            if (!auth()->check()) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }
            $userId = auth()->user()->id;
            $search = $request->get('search');
            $data = $this->importService->getAllBlueprintsForUser($userId, $search);
            
            $flat = [];
            if (isset($data['characters'])) {
                foreach ($data['characters'] as $char) {
                    if (isset($char['blueprints'])) {
                        foreach ($char['blueprints'] as $bp) {
                            $bp->ownerType = 'character';
                            $bp->ownerName = $char['character_name'] ?? 'Unknown';
                            $flat[] = $bp;
                        }
                    }
                }
            }
            if (isset($data['corporations'])) {
                foreach ($data['corporations'] as $corp) {
                    if (isset($corp['blueprints'])) {
                        foreach ($corp['blueprints'] as $bp) {
                            $bp->ownerType = 'corporation';
                            $bp->ownerName = $corp['corporation_name'] ?? 'Unknown';
                            $flat[] = $bp;
                        }
                    }
                }
            }

            return response()->json($flat);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
        }
    }

    public function getOwnedBlueprint($itemId)
    {
        $userId = auth()->user()->id;
        $bp = $this->importService->getBlueprintByItemId($itemId, $userId);
        
        if (!$bp) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $product = $this->blueprintService->findProductByBlueprint($bp->type_id);

        return response()->json([
            'blueprintTypeID' => $bp->type_id,
            'productTypeID' => $product ? $product->typeID : 0,
            'productName' => $product ? $product->typeName : 'Unknown Item',
            'materialEfficiency' => $bp->material_efficiency,
            'timeEfficiency' => $bp->time_efficiency,
            'isCopy' => $bp->quantity === -2,
            'runs' => $bp->runs,
            'maxRuns' => $bp->quantity === -2 ? $bp->runs : null,
            'ownerType' => $bp->ownerType,
            'ownerName' => $bp->ownerName,
            'locationFlag' => $bp->location_flag
        ]);
    }

    public function warmup()
    {
        $ishtarId = 12005;
        $this->blueprintService->getMaterials($ishtarId);
        sleep(1);
        $this->blueprintService->getBlueprintData($ishtarId);
        sleep(1);
        $this->apiService->getMarketPrice($ishtarId);
        return "Warmup complete for Ishtar.";
    }

    public function calculate(Request $request)
    {
        $productTypeId = $request->input('blueprintTypeID');
        $systemName = $request->input('systemName', 'Jita');
        $runs = (int) $request->input('runs', 1);
        $meLevel = (int) $request->input('meLevel', 10);
        $teLevel = (int) $request->input('teLevel', 20);
        $facilityType = $request->input('facilityType', 'npc');
        $rigType = $request->input('rigType', 'none');
        $systemSecurity = $request->input('systemSecurity', 'high');
        $taxRate = (float) $request->input('taxRate', 1.0);
        $buildComponents = (int) $request->input('buildComponents', 0);
        $buildReactions = (int) $request->input('buildReactions', 0);

        if (!$productTypeId) {
            return response()->json(['error' => 'Item not specified'], 400);
        }

        $bpData = $this->blueprintService->getBlueprintData($productTypeId);
        if (!$bpData) {
            return response()->json(['error' => 'Industry data not available for this item.'], 404);
        }

        list($facilityMod, $facilityTimeMod, $rigMod) = $this->calculatorService->getFacilityModifiers($facilityType, $rigType, $systemSecurity);

        if ($buildComponents) {
            $meModifier = (1.0 - $meLevel / 100);
            $tree = $this->blueprintService->getMaterialTree($productTypeId, $runs, $meModifier, $facilityMod, $rigMod, (bool)$buildReactions);
            $calculatedMaterials = $this->flattenMaterials($tree);
        } else {
            $baseMaterials = $this->blueprintService->getMaterials($productTypeId);
            if (empty($baseMaterials)) {
                return response()->json(['error' => 'Could not retrieve material requirements.'], 404);
            }
            $calculatedMaterials = $this->calculatorService->calculateMaterials($baseMaterials, $runs, $meLevel, $facilityMod, $rigMod);
        }

        $totalMaterialCost = 0;
        foreach ($calculatedMaterials as &$mat) {
            $price = $this->apiService->getMarketPrice($mat->typeID);
            $mat->unitPrice = $price;
            $mat->totalPrice = $price * $mat->adjustedQuantity;
            $mat->iconUrl = "https://images.evetech.net/types/{$mat->typeID}/icon?size=32";
            
            if (!isset($mat->groupName)) {
                $mat->groupName = DB::table('invTypes')
                    ->join('invGroups', 'invTypes.groupID', '=', 'invGroups.groupID')
                    ->where('invTypes.typeID', $mat->typeID)
                    ->value('groupName') ?? 'Unknown';
            }
            if (!isset($mat->isComponent)) {
                $mat->isComponent = false;
            }
            if (!isset($mat->isReaction)) {
                $mat->isReaction = false;
            }

            $totalMaterialCost += $mat->totalPrice;
        }

        $productionTimeSec = $this->calculatorService->calculateTime($bpData['baseTime'], $runs, $teLevel, [], $facilityTimeMod);
        
        $d = floor($productionTimeSec / 86400);
        $h = floor(($productionTimeSec % 86400) / 3600);
        $m = floor(($productionTimeSec % 3600) / 60);
        $s = floor($productionTimeSec % 60);
        $formattedTime = "{$d}d {$h}h {$m}m {$s}s";

        $baseJobCost = $this->apiService->getBaseJobCost($bpData['blueprintTypeID']);
        $indices = $this->apiService->getSystemCostIndex($systemName);
        $mfgIndex = $indices[1] ?? 0.05;
        
        $jobCostData = $this->calculatorService->calculateJobCost($baseJobCost, $mfgIndex, $runs, $taxRate);

        $sellPrice = $this->apiService->getMarketPrice($productTypeId);
        $profitData = $this->calculatorService->calculateProfit($sellPrice, $totalMaterialCost, $jobCostData['totalInstallationCost'], $bpData['productionQuantity'] * $runs);

        return response()->json([
            'product' => [
                'typeID' => $productTypeId,
                'typeName' => $bpData['productName'],
                'iconUrl' => "https://images.evetech.net/types/{$productTypeId}/icon?size=64",
                'outputQuantity' => $bpData['productionQuantity'] * $runs,
                'isReaction' => ($bpData['activityID'] ?? 1) == 11
            ],
            'materials' => array_values($calculatedMaterials),
            'materialTree' => $buildComponents ? $tree : [],
            'productionTime' => [
                'seconds' => $productionTimeSec,
                'formatted' => $formattedTime
            ],
            'costs' => [
                'jobInstallationCost' => $jobCostData['totalInstallationCost'],
                'totalMaterialCost' => $totalMaterialCost,
                'totalCost' => $profitData['totalCost'],
                'revenue' => $profitData['revenue'],
                'profit' => $profitData['profit'],
                'profitMargin' => $profitData['profitMargin']
            ],
            'systemCostIndex' => $mfgIndex,
            'baseJobCost' => $baseJobCost
        ]);
    }

    protected function flattenMaterials(array $tree, &$flat = [])
    {
        foreach ($tree as $node) {
            $hasSub = !empty($node['subMaterials']);
            
            if (!$hasSub) {
                if (!isset($flat[$node['typeID']])) {
                    $groupName = DB::table('invTypes')
                        ->join('invGroups', 'invTypes.groupID', '=', 'invGroups.groupID')
                        ->where('invTypes.typeID', $node['typeID'])
                        ->value('groupName');

                    $flat[$node['typeID']] = (object)[
                        'typeID' => $node['typeID'],
                        'typeName' => $node['typeName'],
                        'adjustedQuantity' => 0,
                        'groupName' => $groupName ?? 'Unknown',
                        'isComponent' => false,
                        'isReaction' => false
                    ];
                }
                $flat[$node['typeID']]->adjustedQuantity += $node['adjustedQuantity'];
            } else {
                // It's a component or reaction we are building/reacting
                if (!isset($flat[$node['typeID']])) {
                    $groupName = DB::table('invTypes')
                        ->join('invGroups', 'invTypes.groupID', '=', 'invGroups.groupID')
                        ->where('invTypes.typeID', $node['typeID'])
                        ->value('groupName');

                    $flat[$node['typeID']] = (object)[
                        'typeID' => $node['typeID'],
                        'typeName' => $node['typeName'],
                        'adjustedQuantity' => 0,
                        'groupName' => $groupName ?? 'Unknown',
                        'isComponent' => !$node['isReactable'],
                        'isReaction' => $node['isReactable']
                    ];
                }
                $flat[$node['typeID']]->adjustedQuantity += $node['adjustedQuantity'];
                
                $this->flattenMaterials($node['subMaterials'], $flat);
            }
        }
        return $flat;
    }
}
