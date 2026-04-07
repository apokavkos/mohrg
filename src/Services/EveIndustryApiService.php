<?php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class EveIndustryApiService
{
    public function getSystemCostIndex(string $systemName)
    {
        return Cache::remember('seat-assets.industry.costindex.' . $systemName, 3600, function () use ($systemName) {
            try {
                $response = Http::get('http://api.eve-industry.org/system-cost-index.xml?name=' . urlencode($systemName));
                if ($response->successful()) {
                    $xml = simplexml_load_string($response->body());
                    if ($xml && isset($xml->solarsystem->activity)) {
                        $indices = [];
                        foreach ($xml->solarsystem->activity as $activity) {
                            $indices[(int)$activity['id']] = (float)$activity;
                        }
                        return $indices;
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }
            return [];
        });
    }

    public function getBaseJobCost(int $typeId)
    {
        return Cache::remember('seat-assets.industry.basecost.' . $typeId, 3600, function () use ($typeId) {
            try {
                $response = Http::get('http://api.eve-industry.org/job-base-cost.xml?ids=' . $typeId);
                if ($response->successful()) {
                    $xml = simplexml_load_string($response->body());
                    if ($xml && isset($xml->{'job-base-cost'})) {
                        return (float)$xml->{'job-base-cost'};
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }
            return 0.0;
        });
    }
    
    public function getMarketPrice(int $typeId)
    {
        return Cache::remember('seat-assets.industry.market.' . $typeId, 600, function () use ($typeId) {
            try {
                $response = Http::get('https://market.fuzzwork.co.uk/aggregates/?region=10000002&types=' . $typeId);
                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data[$typeId]['sell']['min'])) {
                        return (float)$data[$typeId]['sell']['min'];
                    }
                }
            } catch (Exception $e) {
                // Ignore
            }
            return 0.0;
        });
    }
}
