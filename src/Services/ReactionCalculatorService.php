<?php

namespace Apokavkos\SeatAssets\Services;

class ReactionCalculatorService
{
    public function calculateRequiredMaterials(int $baseQuantity, int $runs, float $rigBonus, float $structureBonus)
    {
        // Formula: Required = max(runs, ceil(base_quantity × runs × (1 - rig_material_bonus) × (1 - structure_material_bonus)))
        return (int) max($runs, ceil($baseQuantity * $runs * (1 - $rigBonus) * (1 - $structureBonus)));
    }

    public function calculateProductionTime(int $baseTime, int $runs, int $skillLevel, float $rigBonus, float $structureBonus)
    {
        // Formula: Time after skills = Base Time × (1 - 0.04 × Reactions_Skill_Level)
        $time = $baseTime * (1 - 0.04 * $skillLevel);
        
        // Time after rig = Time × (1 - rig_time_bonus)
        $time = $time * (1 - $rigBonus);
        
        // Time after structure = Time × (1 - structure_time_bonus)
        $time = $time * (1 - $structureBonus);
        
        return $time * $runs;
    }

    public function calculateInstallationCost(float $eiv, float $systemCostIndex, float $sccSurcharge, float $facilityTax)
    {
        // Formula: Job Cost = EIV × System Cost Index × (1 + SCC Surcharge) × (1 + Facility Tax)
        // SCC Surcharge is usually fixed (e.g. 0.04 for 4%)
        return $eiv * $systemCostIndex * (1 + $sccSurcharge) * (1 + ($facilityTax / 100));
    }

    public function getRigBonuses(string $rigType, string $spaceType)
    {
        $materialBonus = 0.0;
        $timeBonus = 0.0;

        switch ($rigType) {
            case 't1_medium':
                $materialBonus = ($spaceType === 'lowsec') ? 0.024 : 0.020;
                $timeBonus = 0.20;
                break;
            case 't2_medium':
                $materialBonus = ($spaceType === 'lowsec') ? 0.0312 : 0.024;
                $timeBonus = 0.24;
                break;
            case 't1_large':
                $materialBonus = ($spaceType === 'lowsec') ? 0.0288 : 0.024;
                $timeBonus = 0.24;
                break;
            case 't2_large':
                $materialBonus = ($spaceType === 'lowsec') ? 0.036 : 0.030;
                $timeBonus = 0.288;
                break;
        }

        return [$materialBonus, $timeBonus];
    }

    public function getStructureBonuses(string $structureType)
    {
        $materialBonus = 0.0;
        $timeBonus = 0.0;

        if ($structureType === 'Tatara') {
            $materialBonus = 0.01; // 1%
            $timeBonus = 0.25;     // 25%
        }

        return [$materialBonus, $timeBonus];
    }
}
