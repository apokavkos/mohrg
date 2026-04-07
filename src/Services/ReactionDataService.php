<?php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReactionDataService
{
    protected $simpleReactions = [
        'Caesarium Cadmide', 'Carbon Polymers', 'Ceramic Powder', 'Crystallite Alloy', 'Dysporite', 
        'Fernite Alloy', 'Ferrofluid', 'Fluxed Condensates', 'Hexite', 'Hyperflurite', 'Neo Mercurite', 
        'Platinum Technite', 'Rolled Tungsten Alloy', 'Silicon Diborite', 'Solerium', 'Sulfuric Acid', 
        'Titanium Chromide', 'Vanadium Hafnite', 'Prometium', 'Thulium Hafnite', 'Promethium Mercurite', 
        'Carbon Fiber', 'Thermosetting Polymer', 'Oxy-Organic Solvents'
    ];

    protected $complexReactions = [
        'Titanium Carbide', 'Crystalline Carbonide', 'Fernite Carbide', 'Tungsten Carbide', 
        'Sylramic Fibers', 'Fullerides', 'Phenolic Composites', 'Nanotransistors', 'Hypersynaptic Fibers', 
        'Ferrogel', 'Fermionic Condensates', 'Plasmonic Metamaterials', 'Terahertz Metamaterials', 
        'Photonic Metamaterials', 'Nonlinear Metamaterials', 'Pressurized Oxidizers', 'Reinforced Carbon Fiber'
    ];

    public function getCategories()
    {
        return [
            'simple' => $this->simpleReactions,
            'complex' => $this->complexReactions,
        ];
    }

    public function getAllReactionNames()
    {
        return array_merge($this->simpleReactions, $this->complexReactions);
    }

    public function resolveTypeIds(array $names)
    {
        return DB::table('invTypes')
            ->whereIn('typeName', $names)
            ->pluck('typeID', 'typeName')
            ->toArray();
    }

    public function getAllInvolvedTypeIds()
    {
        return Cache::remember('eic.reaction.all_involved_ids', 86400, function() {
            $names = $this->getAllReactionNames();
            $reactionIds = array_values($this->resolveTypeIds($names));
            $involvedIds = $reactionIds;

            foreach ($reactionIds as $id) {
                $formula = $this->getReactionFormula($id);
                if ($formula) {
                    foreach ($formula['inputs'] as $input) {
                        $involvedIds[] = $input->type_id;
                    }
                }
            }

            return array_values(array_unique($involvedIds));
        });
    }

    public function getReactionFormula(int $productTypeId)
    {
        $cached = Cache::get('eic.reaction.formula.' . $productTypeId);
        if ($cached) return $cached;

        return $this->fetchAndCacheFormula($productTypeId);
    }

    public function fetchAndCacheFormula(int $productTypeId)
    {
        try {
            $response = Http::get("https://www.fuzzwork.co.uk/blueprint/api/blueprint.php?typeid=" . $productTypeId);
            
            if ($response->successful()) {
                $data = $response->json();
                
                $activityId = isset($data['activityMaterials'][11]) ? 11 : (isset($data['activityMaterials'][1]) ? 1 : null);
                if (!$activityId) return null;

                $inputs = [];
                foreach ($data['activityMaterials'][$activityId] as $mat) {
                    $inputs[] = (object)[
                        'type_id' => (int)$mat['typeid'],
                        'quantity' => (int)$mat['quantity'],
                        'name' => $mat['name']
                    ];
                }

                $formula = [
                    'inputs' => $inputs,
                    'output' => (object)[
                        'type_id' => $productTypeId,
                        'quantity' => $data['blueprintDetails']['productQuantity'] ?? 1,
                        'name' => $data['blueprintDetails']['productTypeName'] ?? ''
                    ],
                    'baseTime' => $data['blueprintDetails']['times'][$activityId] ?? 3600
                ];

                Cache::put('eic.reaction.formula.' . $productTypeId, $formula, 86400 * 7); // Cache for a week
                return $formula;
            }
        } catch (\Exception $e) {
            \Log::error("ReactionDataService Fetch Error: " . $e->getMessage());
        }
        return null;
    }

    public function warmup()
    {
        $names = $this->getAllReactionNames();
        $resolved = $this->resolveTypeIds($names);
        
        $results = ['success' => 0, 'failed' => 0];
        foreach ($resolved as $name => $id) {
            if (Cache::has('eic.reaction.formula.' . $id)) {
                $results['success']++;
                continue;
            }

            if ($this->fetchAndCacheFormula($id)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
            usleep(500000); // 0.5s delay to be nice to Fuzzwork
        }
        return $results;
    }
}
