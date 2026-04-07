<?php

namespace Apokavkos\SeatAssets\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Apokavkos\SeatAssets\Models\ReactionConfig;
use Apokavkos\SeatAssets\Services\ReactionDataService;
use Apokavkos\SeatAssets\Services\ReactionCalculatorService;
use Apokavkos\SeatAssets\Services\MarketPriceService;
use Apokavkos\SeatAssets\Services\EveIndustryApiService;

class ReactionController extends Controller
{
    protected $dataService;
    protected $calculatorService;
    protected $priceService;
    protected $apiService;
    protected $localHubId = 1049588174021; // C-J6MT Keepstar

    public function __construct(
        ReactionDataService $dataService,
        ReactionCalculatorService $calculatorService,
        MarketPriceService $priceService,
        EveIndustryApiService $apiService
    ) {
        $this->dataService = $dataService;
        $this->calculatorService = $calculatorService;
        $this->priceService = $priceService;
        $this->apiService = $apiService;
    }

    public function index()
    {
        $user_id = auth()->user()->id;
        $configs = ReactionConfig::where('user_id', $user_id)->get();
        $defaultConfig = $configs->where('is_default', true)->first() ?: $configs->first();

        // If no config exists, create a default one to store user preferences
        if (!$defaultConfig) {
            $defaultConfig = ReactionConfig::create([
                'user_id' => $user_id,
                'name' => 'Default Settings',
                'is_default' => true
            ]);
        }

        $categories = $this->dataService->getCategories();
        
        return view('seat-assets::reactions.planner', compact('configs', 'defaultConfig', 'categories'));
    }

    public function warmup()
    {
        return response()->json($this->dataService->warmup());
    }

    public function warmupPrices()
    {
        $typeIds = $this->dataService->getAllInvolvedTypeIds();
        $this->priceService->getPrices($typeIds, 10000002);
        $this->priceService->getPrices($typeIds, $this->localHubId);
        return response()->json(['success' => true, 'count' => count($typeIds)]);
    }

    public function calculate(Request $request)
    {
        $productTypeId = $request->type_id;
        if (!$productTypeId && $request->has('name')) {
            $productTypeId = DB::table('invTypes')->where('typeName', $request->name)->value('typeID');
        }

        if (!$productTypeId) return response()->json(['error' => 'Product not found'], 404);

        $formula = $this->dataService->getReactionFormula($productTypeId);
        if (!$formula) return response()->json(['error' => 'Formula not found'], 404);

        $runs = $request->get('runs', 1);
        $skillLevel = $request->get('skill_level', 5);
        $inputMethod = $request->get('input_method', 'sell');
        $outputMethod = $request->get('output_method', 'sell');
        $facilityTax = (float)$request->get('facility_tax', 0.0);

        list($rigMatBonus, $rigTimeBonus) = $this->calculatorService->getRigBonuses($request->rig_1, $request->space_type);
        list($structMatBonus, $structTimeBonus) = $this->calculatorService->getStructureBonuses($request->structure_type);

        $allTypeIds = array_merge([$productTypeId], array_column($formula['inputs'], 'type_id'));
        
        $jitaPrices = $this->priceService->getPrices($allTypeIds, 10000002);
        $resJita = $this->runProfitCalc($formula, $jitaPrices, $runs, $rigMatBonus, $structMatBonus, $inputMethod, $outputMethod, $request->system_name, $facilityTax, $skillLevel, $rigTimeBonus, $structTimeBonus);

        $localPrices = $this->priceService->getPrices($allTypeIds, $this->localHubId);
        $resLocal = $this->runProfitCalc($formula, $localPrices, $runs, $rigMatBonus, $structMatBonus, $inputMethod, $outputMethod, $request->system_name, $facilityTax, $skillLevel, $rigTimeBonus, $structTimeBonus);

        return response()->json([
            'name' => $formula['output']->name,
            'jita' => $resJita,
            'local' => $resLocal,
            'advantage' => $resLocal['profit'] - $resJita['profit'],
            'slot_hour' => $resJita['profit'] / max(1, ($resJita['time'] / 3600))
        ]);
    }

    protected function runProfitCalc($formula, $prices, $runs, $rigMatBonus, $structMatBonus, $inputMethod, $outputMethod, $systemName, $facilityTax, $skillLevel, $rigTimeBonus, $structTimeBonus)
    {
        $totalInputCost = 0;
        $totalEiv = 0;
        $adjustedPrices = $this->priceService->getAdjustedPrices();

        foreach ($formula['inputs'] as $input) {
            $qty = $this->calculatorService->calculateRequiredMaterials($input->quantity, $runs, $rigMatBonus, $structMatBonus);
            $p = $prices->get($input->type_id);
            $cost = $qty * ($inputMethod == 'buy' ? ($p->buy_price ?? 0) : ($p->sell_price ?? 0));
            $totalInputCost += $cost;
            
            $adj = $adjustedPrices->get($input->type_id)->adjusted_price ?? 0;
            $totalEiv += ($adj * $qty);
        }

        $outP = $prices->get($formula['output']->type_id);
        $outputValue = ($formula['output']->quantity * $runs) * ($outputMethod == 'sell' ? ($outP->sell_price ?? 0) : ($outP->buy_price ?? 0));

        $indices = $this->apiService->getSystemCostIndex($systemName);
        $mfgIndex = $indices[11] ?? 0.01;
        $tax = $this->calculatorService->calculateInstallationCost($totalEiv, $mfgIndex, 0.04, $facilityTax);

        $profit = $outputValue - $totalInputCost - $tax;
        $time = $this->calculatorService->calculateProductionTime($formula['baseTime'], $runs, $skillLevel, $rigTimeBonus, $structTimeBonus);

        return [
            'profit' => $profit,
            'percent' => $outputValue > 0 ? ($profit / $outputValue) * 100 : 0,
            'output_value' => $outputValue,
            'tax' => $tax,
            'inputs_cost' => $totalInputCost,
            'time' => $time
        ];
    }

    public function saveConfig(Request $request)
    {
        $user_id = auth()->user()->id;
        
        if ($request->has('is_default') && $request->is_default) {
            ReactionConfig::where('user_id', $user_id)->update(['is_default' => false]);
        }

        $config = ReactionConfig::updateOrCreate(
            ['id' => $request->id, 'user_id' => $user_id],
            $request->only([
                'name', 'structure_type', 'reactor_type', 'rig_1', 'rig_2', 'rig_3', 
                'space_type', 'solar_system_id', 'input_method', 'output_method', 
                'skill_level', 'system_name', 'runs', 'fuel_block_type_id', 
                'facility_tax', 'is_default'
            ])
        );

        return response()->json(['success' => true, 'config' => $config]);
    }
}
