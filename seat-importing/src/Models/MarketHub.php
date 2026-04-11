<?php

namespace Apokavkos\SeatImporting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a player-run market hub (e.g. an alliance staging Fortizar).
 * Each hub has its own ISK-per-m³ freight cost that feeds into markup calculations.
 *
 * @property int         $id
 * @property string      $name
 * @property int|null    $solar_system_id
 * @property int|null    $structure_id
 * @property int|null    $region_id
 * @property float       $isk_per_m3
 * @property bool        $is_active
 * @property string|null $notes
 */
class MarketHub extends Model
{
    protected $table = 'market_hubs';

    protected $fillable = [
        'name',
        'solar_system_id',
        'structure_id',
        'region_id',
        'isk_per_m3',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'solar_system_id' => 'integer',
        'structure_id'    => 'integer',
        'region_id'       => 'integer',
        'isk_per_m3'      => 'float',
        'is_active'       => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function itemData(): HasMany
    {
        return $this->hasMany(MarketItemData::class, 'hub_id');
    }

    public function settings(): HasMany
    {
        return $this->hasMany(MarketSetting::class, 'hub_id');
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(MarketImportLog::class, 'hub_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve the effective ISK/m³ for this hub, with fallback to global setting
     * then to the config default.
     */
    public function effectiveIskPerM3(): float
    {
        $hubOverride = MarketSetting::get('isk_per_m3', $this->id);

        if ($hubOverride !== null) {
            return (float) $hubOverride;
        }

        $global = MarketSetting::get('isk_per_m3');

        return $global !== null
            ? (float) $global
            : (float) config('seat-importing.default_isk_per_m3', 1000.0);
    }
}
