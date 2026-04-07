<?php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\DB;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Character\CharacterAffiliation;
use Seat\Eveapi\Models\RefreshToken;

class BlueprintImportService
{
    public function getCharactersForUser(int $userId)
    {
        $characterIds = RefreshToken::where('user_id', $userId)->pluck('character_id')->toArray();
        return CharacterInfo::whereIn('character_id', $characterIds)->pluck('name', 'character_id')->toArray();
    }

    public function getCorporationsForUser(int $userId)
    {
        $characterIds = RefreshToken::where('user_id', $userId)->pluck('character_id')->toArray();
        $corporationIds = CharacterAffiliation::whereIn('character_id', $characterIds)->pluck('corporation_id')->unique()->toArray();
        
        return DB::table('corporation_infos')->whereIn('corporation_id', $corporationIds)->pluck('name', 'corporation_id')->toArray();
    }

    public function getCharacterBlueprints(int $characterId, ?string $search = null)
    {
        $query = DB::table('character_blueprints')
            ->join('invTypes', 'character_blueprints.type_id', '=', 'invTypes.typeID')
            ->where('character_blueprints.character_id', $characterId)
            ->select('character_blueprints.item_id', 'character_blueprints.type_id', 'character_blueprints.character_id', 'character_blueprints.material_efficiency', 'character_blueprints.time_efficiency', 'character_blueprints.quantity', 'character_blueprints.runs', 'invTypes.typeName');

        if ($search) {
            $query->where('invTypes.typeName', 'like', '%' . $search . '%');
        }

        return $query->get();
    }

    public function getCorporationBlueprints(int $corporationId, ?string $search = null)
    {
        $query = DB::table('corporation_blueprints')
            ->join('invTypes', 'corporation_blueprints.type_id', '=', 'invTypes.typeID')
            ->where('corporation_blueprints.corporation_id', $corporationId)
            ->select('corporation_blueprints.item_id', 'corporation_blueprints.type_id', 'corporation_blueprints.corporation_id', 'corporation_blueprints.material_efficiency', 'corporation_blueprints.time_efficiency', 'corporation_blueprints.quantity', 'corporation_blueprints.runs', 'invTypes.typeName');

        if ($search) {
            $query->where('invTypes.typeName', 'like', '%' . $search . '%');
        }

        return $query->get();
    }

    public function getAllBlueprintsForUser(int $userId, ?string $search = null)
    {
        $characterIds = RefreshToken::where('user_id', $userId)->pluck('character_id')->toArray();
        $characters = CharacterInfo::whereIn('character_id', $characterIds)->pluck('name', 'character_id')->toArray();
        
        $corporationIds = CharacterAffiliation::whereIn('character_id', $characterIds)->pluck('corporation_id')->unique()->toArray();
        $corporations = DB::table('corporation_infos')->whereIn('corporation_id', $corporationIds)->pluck('name', 'corporation_id')->toArray();

        $result = [
            'characters' => [],
            'corporations' => []
        ];

        if (!empty($characters)) {
            $charBpsQuery = DB::table('character_blueprints')
                ->join('invTypes', 'character_blueprints.type_id', '=', 'invTypes.typeID')
                ->whereIn('character_blueprints.character_id', array_keys($characters))
                ->select('character_blueprints.item_id', 'character_blueprints.type_id', 'character_blueprints.character_id', 'character_blueprints.material_efficiency', 'character_blueprints.time_efficiency', 'character_blueprints.quantity', 'character_blueprints.runs', 'invTypes.typeName');

            if ($search) {
                $charBpsQuery->where('invTypes.typeName', 'like', '%' . $search . '%');
            }

            $allCharBps = $charBpsQuery->get()->groupBy('character_id');

            foreach ($characters as $charId => $charName) {
                if (isset($allCharBps[$charId])) {
                    $result['characters'][] = [
                        'character_id' => $charId,
                        'character_name' => $charName,
                        'blueprints' => $allCharBps[$charId]
                    ];
                }
            }
        }

        if (!empty($corporations)) {
            $corpBpsQuery = DB::table('corporation_blueprints')
                ->join('invTypes', 'corporation_blueprints.type_id', '=', 'invTypes.typeID')
                ->whereIn('corporation_blueprints.corporation_id', array_keys($corporations))
                ->select('corporation_blueprints.item_id', 'corporation_blueprints.type_id', 'corporation_blueprints.corporation_id', 'corporation_blueprints.material_efficiency', 'corporation_blueprints.time_efficiency', 'corporation_blueprints.quantity', 'corporation_blueprints.runs', 'invTypes.typeName');

            if ($search) {
                $corpBpsQuery->where('invTypes.typeName', 'like', '%' . $search . '%');
            }

            $allCorpBps = $corpBpsQuery->get()->groupBy('corporation_id');

            foreach ($corporations as $corpId => $corpName) {
                if (isset($allCorpBps[$corpId])) {
                    $result['corporations'][] = [
                        'corporation_id' => $corpId,
                        'corporation_name' => $corpName,
                        'blueprints' => $allCorpBps[$corpId]
                    ];
                }
            }
        }

        return $result;
    }

    public function getBlueprintByItemId(int $itemId, int $userId)
    {
        $characterIds = RefreshToken::where('user_id', $userId)->pluck('character_id')->toArray();
        
        $bp = DB::table('character_blueprints')
            ->where('item_id', $itemId)
            ->whereIn('character_id', $characterIds)
            ->join('invTypes', 'character_blueprints.type_id', '=', 'invTypes.typeID')
            ->select('character_blueprints.*', 'invTypes.typeName')
            ->first();

        if ($bp) {
            $bp->ownerType = 'character';
            $bp->ownerName = CharacterInfo::where('character_id', $bp->character_id)->value('name');
            return $bp;
        }

        $corporationIds = CharacterAffiliation::whereIn('character_id', $characterIds)->pluck('corporation_id')->unique()->toArray();
        $bp = DB::table('corporation_blueprints')
            ->where('item_id', $itemId)
            ->whereIn('corporation_id', $corporationIds)
            ->join('invTypes', 'corporation_blueprints.type_id', '=', 'invTypes.typeID')
            ->select('corporation_blueprints.*', 'invTypes.typeName')
            ->first();

        if ($bp) {
            $bp->ownerType = 'corporation';
            $bp->ownerName = DB::table('corporation_infos')->where('corporation_id', $bp->corporation_id)->value('name');
            return $bp;
        }

        return null;
    }
}
