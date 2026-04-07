<?php

namespace Apokavkos\SeatAssets\Services;

class CostCalculatorService
{
    public function calculateMaterials(array $baseMaterials, int $runs, int $meLevel, float $facilityMod, float $rigMod)
    {
        $meModifier = (1.0 - $meLevel / 100);
        $modifier = $meModifier * $facilityMod * $rigMod;
        
        $calculated = [];
        foreach ($baseMaterials as $mat) {
            $required = max($runs, ceil(round($runs * $mat->quantity * $modifier, 2)));
            $calculated[] = (object) [
                'typeID' => $mat->materialTypeID ?? $mat->typeID,
                'typeName' => $mat->typeName,
                'baseQuantity' => $mat->quantity,
                'adjustedQuantity' => $required,
            ];
        }
        return $calculated;
    }

    public function calculateTime(int $baseTime, int $runs, int $teLevel, array $skills, float $facilityTimeMod)
    {
        $teModifier = (1.0 - $teLevel / 100);
        $skillMod = 1.0;
        foreach ($skills as $skill) {
            $skillMod *= (1 - 0.01 * $skill->level);
        }
        
        return $baseTime * $teModifier * $facilityTimeMod * $skillMod * $runs;
    }

    public function calculateJobCost(float $baseJobCost, float $systemCostIndex, int $runs, float $taxRate)
    {
        $jobFee = $baseJobCost * $systemCostIndex * $runs;
        $facilityTax = $jobFee * ($taxRate / 100);
        return [
            'jobFee' => $jobFee,
            'facilityTax' => $facilityTax,
            'totalInstallationCost' => $jobFee + $facilityTax
        ];
    }
    
    public function getFacilityModifiers(string $facilityType, string $rigType, string $security)
    {
        $facilityMod = 1.0;
        $facilityTimeMod = 1.0;
        $rigMod = 1.0;

        if ($facilityType === 'engineering_complex') {
            $facilityMod = 1.0;
            if ($rigType === 't1_te') {
                $facilityTimeMod = 0.80; 
            }
        }
        
        if ($rigType === 't1_me') {
            if ($security === 'high') $rigMod = 0.98;
            elseif ($security === 'low') $rigMod = 0.976;
            else $rigMod = 0.952;
        } elseif ($rigType === 't2_me') {
            if ($security === 'high') $rigMod = 0.976;
            elseif ($security === 'low') $rigMod = 0.9524;
            else $rigMod = 0.904;
        }

        return [(float)$facilityMod, (float)$facilityTimeMod, (float)$rigMod];
    }

    public function calculateInventionChance(float $baseChance, int $encLevel, int $dc1Level, int $dc2Level, float $decryptorMod)
    {
        $skillModifier = 1 + ($encLevel / 40) + ($dc1Level + $dc2Level) / 30;
        return $baseChance * $skillModifier * $decryptorMod;
    }

    public function calculateProfit(float $sellPrice, float $materialCost, float $installCost, int $outputQuantity)
    {
        $revenue = $sellPrice * $outputQuantity;
        $totalCost = $materialCost + $installCost;
        $profit = $revenue - $totalCost;
        $margin = $totalCost > 0 ? ($profit / $totalCost) * 100 : 0;

        return [
            'totalCost' => $totalCost,
            'revenue' => $revenue,
            'profit' => $profit,
            'profitMargin' => $margin
        ];
    }
}
