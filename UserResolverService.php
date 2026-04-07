<?php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\DB;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Character\CharacterAffiliation;
use Seat\Eveapi\Models\RefreshToken;

/**
 * Centralizes user → character → corporation resolution logic.
 *
 * Extracted from duplicated patterns found in:
 *   - BlueprintImportService::getCharactersForUser()
 *   - BlueprintImportService::getCorporationsForUser()
 *   - StockpileLogisticsService::getLogisticsReport()
 *   - Multiple controllers (ReactionController, StockpileController, etc.)
 */
class UserResolverService
{
    /**
     * Get all character IDs linked to a SeAT user via refresh tokens.
     *
     * @return int[]
     */
    public function getCharacterIds(int $userId): array
    {
        return RefreshToken::where('user_id', $userId)
            ->pluck('character_id')
            ->toArray();
    }

    /**
     * Get all corporation IDs for a SeAT user's linked characters.
     *
     * @return int[]
     */
    public function getCorporationIds(int $userId): array
    {
        $characterIds = $this->getCharacterIds($userId);

        return CharacterAffiliation::whereIn('character_id', $characterIds)
            ->pluck('corporation_id')
            ->unique()
            ->toArray();
    }

    /**
     * Get character names keyed by character_id.
     *
     * @return array<int, string>
     */
    public function getCharacterNames(int $userId): array
    {
        $characterIds = $this->getCharacterIds($userId);

        return CharacterInfo::whereIn('character_id', $characterIds)
            ->pluck('name', 'character_id')
            ->toArray();
    }

    /**
     * Get corporation names keyed by corporation_id.
     *
     * @return array<int, string>
     */
    public function getCorporationNames(int $userId): array
    {
        $corporationIds = $this->getCorporationIds($userId);

        return DB::table('corporation_infos')
            ->whereIn('corporation_id', $corporationIds)
            ->pluck('name', 'corporation_id')
            ->toArray();
    }

    /**
     * Resolve a location name from station or structure tables.
     */
    public function resolveLocationName(int $locationId): ?string
    {
        $name = DB::table('universe_stations')
            ->where('station_id', $locationId)
            ->value('name');

        if (! $name) {
            $name = DB::table('universe_structures')
                ->where('structure_id', $locationId)
                ->value('name');
        }

        return $name;
    }
}
