<?php

namespace Apokavkos\SeatAssets\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Seat\Eveapi\Models\Assets\CharacterAsset;
use Seat\Eveapi\Models\Industry\CharacterIndustryJob;
use Seat\Eveapi\Models\Industry\CorporationIndustryJob;
use Seat\Eveapi\Models\Wallet\CorporationWalletBalance;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Illuminate\Support\Facades\DB;

use Seat\Eveapi\Models\Wallet\CharacterWalletBalance;
use Seat\Eveapi\Models\Corporation\CorporationInfo;
use Illuminate\Http\Request;
use Seat\Services\Models\UserSetting;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user_id = auth()->user()->id;
        $character_ids = auth()->user()->associatedCharacterIds();

        // 1. Persistent Settings (Manual update because user_id is not fillable)
        if ($request->has('corporation_id')) {
            $val = $request->get('corporation_id');
            $store_val = ($val === "") ? null : $val;
            
            $setting = UserSetting::where('user_id', $user_id)->where('name', 'seat_assets_selected_corp')->first();
            if (!$setting) {
                $setting = new UserSetting();
                $setting->user_id = $user_id;
                $setting->name = 'seat_assets_selected_corp';
            }
            $setting->value = $store_val;
            $setting->save();
        }
        if ($request->has('wallet_division')) {
            $val = $request->get('wallet_division');
            if ($val !== null) {
                $setting = UserSetting::where('user_id', $user_id)->where('name', 'seat_assets_wallet_division')->first();
                if (!$setting) {
                    $setting = new UserSetting();
                    $setting->user_id = $user_id;
                    $setting->name = 'seat_assets_wallet_division';
                }
                $setting->value = $val;
                $setting->save();
            }
        }

        $selected_corp_id = UserSetting::where('user_id', $user_id)->where('name', 'seat_assets_selected_corp')->value('value');
        $wallet_division = UserSetting::where('user_id', $user_id)->where('name', 'seat_assets_wallet_division')->value('value') ?: 1;

        // 2. Get corporations - Player corporations (ID > 2,000,000) with at least one character having a token
        $corporations = CorporationInfo::where('corporation_infos.corporation_id', '>', 2000000)
            ->whereIn('corporation_infos.corporation_id', function($query) use ($character_ids) {
                $query->select('character_affiliations.corporation_id')
                    ->from('character_affiliations')
                    ->join('refresh_tokens', 'character_affiliations.character_id', '=', 'refresh_tokens.character_id')
                    ->whereIn('character_affiliations.character_id', $character_ids);
            })->get();

        // Filter queries by selected corp if applicable
        $query_character_ids = $character_ids;
        if ($selected_corp_id) {
            $query_character_ids = DB::table('character_affiliations')
                ->where('corporation_id', $selected_corp_id)
                ->whereIn('character_id', $character_ids)
                ->pluck('character_id')
                ->toArray();
        }

        // Get Division Names for the dropdown
        $division_labels = [];
        for ($i = 1; $i <= 7; $i++) {
            $division_labels[$i] = ($i == 1) ? 'Master' : 'Division ' . $i;
        }

        if ($selected_corp_id) {
            $db_labels = DB::table('corporation_divisions')
                ->where('corporation_id', $selected_corp_id)
                ->where('type', 'wallet')
                ->pluck('name', 'division')
                ->toArray();
            
            foreach ($db_labels as $div => $name) {
                if (!empty($name)) {
                    $division_labels[$div] = $name;
                }
            }
        }

        // Combined Industry Jobs (Character + Corporation where character is installer)
        $char_jobs = CharacterIndustryJob::whereIn('installer_id', $query_character_ids)
            ->where('status', 'active')
            ->select('installer_id', 'activity_id')
            ->get();

        $corp_jobs = CorporationIndustryJob::whereIn('installer_id', $query_character_ids)
            ->where('status', 'active')
            ->select('installer_id', 'activity_id')
            ->get();

        $all_active_jobs = $char_jobs->concat($corp_jobs);
        $grouped_jobs = $all_active_jobs->groupBy('installer_id');

        // Overall totals for the bottom card
        $industry_jobs_totals = $all_active_jobs->groupBy('activity_id')->map(function($jobs) {
            return (object) ['activity_id' => $jobs->first()->activity_id, 'count' => $jobs->count()];
        });

        $activity_mapping = [
            1 => 'Manufacturing',
            3 => 'Time Efficiency',
            4 => 'Material Efficiency',
            5 => 'Copying',
            8 => 'Invention',
            9 => 'Reactions',
        ];

        // Corp Wallets filtered by division with labels
        $wallet_balances = CorporationWalletBalance::where('corporation_wallet_balances.division', $wallet_division)
            ->when($selected_corp_id, function($query) use ($selected_corp_id) {
                return $query->where('corporation_wallet_balances.corporation_id', $selected_corp_id);
            }, function($query) use ($character_ids) {
                return $query->whereIn('corporation_wallet_balances.corporation_id', function($q) use ($character_ids) {
                    $q->select('character_affiliations.corporation_id')
                      ->from('character_affiliations')
                      ->where('corporation_id', '>', 2000000)
                      ->whereIn('character_affiliations.character_id', $character_ids);
                });
            })
            ->join('corporation_infos', 'corporation_wallet_balances.corporation_id', '=', 'corporation_infos.corporation_id')
            ->leftJoin('corporation_divisions', function($join) {
                $join->on('corporation_wallet_balances.corporation_id', '=', 'corporation_divisions.corporation_id')
                     ->on('corporation_wallet_balances.division', '=', 'corporation_divisions.division')
                     ->where('corporation_divisions.type', '=', 'wallet');
            })
            ->select(
                'corporation_wallet_balances.corporation_id',
                'corporation_wallet_balances.division',
                'corporation_wallet_balances.balance',
                'corporation_infos.name as corp_name',
                'corporation_divisions.name as division_name'
            )
            ->get();

        // Character ISK Summary (Wallet Balances)
        $character_wallets = CharacterWalletBalance::whereIn('character_wallet_balances.character_id', $query_character_ids)
            ->join('character_infos', 'character_wallet_balances.character_id', '=', 'character_infos.character_id')
            ->select(
                'character_wallet_balances.character_id',
                'character_wallet_balances.balance',
                'character_infos.name as character_name'
            )
            ->get();

        // Character Industry Slots
        $char_skills = DB::table('character_skills')
            ->whereIn('character_id', $query_character_ids)
            ->whereIn('skill_id', [3387, 24625, 3406, 24624, 45746, 45748])
            ->get()
            ->groupBy('character_id');

        $total_char_isk = 0;
        $summary = [
            'manu_used' => 0, 'manu_total' => 0,
            'science_used' => 0, 'science_total' => 0,
            'reactions_used' => 0, 'reactions_total' => 0,
        ];

        // Attach industry data and character object for partial
        foreach ($character_wallets as $wallet) {
            $total_char_isk += $wallet->balance;

            $wallet->character = (object) [
                'character_id' => $wallet->character_id,
                'name' => $wallet->character_name
            ];

            // Industry calculations
            $c_skills = $char_skills->get($wallet->character_id, collect());
            $c_jobs = $grouped_jobs->get($wallet->character_id, collect());

            $get_skill_level = function($id) use ($c_skills) {
                $s = $c_skills->where('skill_id', $id)->first();
                return $s ? $s->active_skill_level : 0;
            };

            $manu_total = 1 + $get_skill_level(3387) + $get_skill_level(24625);
            $manu_used = $c_jobs->where('activity_id', 1)->count();
            $wallet->manu_slots = $manu_used . ' / ' . $manu_total;
            $summary['manu_used'] += $manu_used;
            $summary['manu_total'] += $manu_total;

            $science_total = 1 + $get_skill_level(3406) + $get_skill_level(24624);
            $science_used = $c_jobs->whereIn('activity_id', [3, 4, 5, 8])->count();
            $wallet->science_slots = $science_used . ' / ' . $science_total;
            $summary['science_used'] += $science_used;
            $summary['science_total'] += $science_total;

            $reactions_total = 1 + $get_skill_level(45746) + $get_skill_level(45748);
            $reactions_used = $c_jobs->where('activity_id', 9)->count();
            $wallet->reactions_slots = $reactions_used . ' / ' . $reactions_total;
            $summary['reactions_used'] += $reactions_used;
            $summary['reactions_total'] += $reactions_total;
        }

        return view('seat-assets::dashboard.index', compact(
            'industry_jobs_totals', 
            'activity_mapping', 
            'wallet_balances', 
            'character_wallets',
            'corporations',
            'selected_corp_id',
            'wallet_division',
            'division_labels',
            'total_char_isk',
            'summary'
        ));
    }
}
