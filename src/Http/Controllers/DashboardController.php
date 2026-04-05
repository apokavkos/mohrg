<?php

namespace Apokavkos\SeatAssets\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Seat\Eveapi\Models\Assets\CharacterAsset;
use Seat\Eveapi\Models\Industry\CharacterIndustryJob;
use Seat\Eveapi\Models\Wallet\CorporationWalletBalance;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $character_ids = auth()->user()->associatedCharacterIds();

        $asset_counts = CharacterAsset::whereIn('character_id', $character_ids)
            ->select('character_id', DB::raw('count(*) as count'))
            ->groupBy('character_id')
            ->with('character')
            ->get();

        $industry_jobs = CharacterIndustryJob::whereIn('installer_id', $character_ids)
            ->where('status', 'active')
            ->select('activity_id', DB::raw('count(*) as count'))
            ->groupBy('activity_id')
            ->get();

        $activity_mapping = [
            1 => 'Manufacturing',
            3 => 'Time Efficiency',
            4 => 'Material Efficiency',
            5 => 'Copying',
            8 => 'Invention',
            9 => 'Reactions',
        ];

        $wallet_balances = CorporationWalletBalance::whereIn('corporation_id', function($query) use ($character_ids) {
            $query->select('corporation_id')
                ->from('character_infos')
                ->whereIn('character_id', $character_ids);
        })->get();

        return view('seat-assets::dashboard.index', compact('asset_counts', 'industry_jobs', 'activity_mapping', 'wallet_balances'));
    }
}
