<?php

namespace Apokavkos\SeatImporting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Key/value settings store for the seat-importing plugin.
 * hub_id = null means the setting is global; a specific hub_id scopes it to that hub.
 *
 * Lookup precedence (highest to lowest):
 *   1. Hub-specific setting
 *   2. Global setting (hub_id IS NULL)
 *   3. $default argument
 *   4. Config value
 *
 * @property int         $id
 * @property int|null    $hub_id
 * @property string      $key
 * @property string|null $value
 */
class MarketSetting extends Model
{
    protected $table = 'market_settings';

    protected $fillable = [
        'hub_id',
        'key',
        'value',
    ];

    protected $casts = [
        'hub_id' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function hub(): BelongsTo
    {
        return $this->belongsTo(MarketHub::class, 'hub_id');
    }

    // -------------------------------------------------------------------------
    // Static Helpers
    // -------------------------------------------------------------------------

    /**
     * Retrieve a setting value.
     *
     * @param  string      $key
     * @param  int|null    $hubId   Specific hub ID, or null for global lookup
     * @param  mixed       $default Returned when no record is found
     * @return mixed
     */
    public static function get(string $key, ?int $hubId = null, mixed $default = null): mixed
    {
        $query = static::where('key', $key);

        if ($hubId !== null) {
            $query->where('hub_id', $hubId);
        } else {
            $query->whereNull('hub_id');
        }

        $record = $query->first();

        return $record !== null ? $record->value : $default;
    }

    /**
     * Upsert a setting value (insert or update by hub_id + key).
     *
     * @param  string   $key
     * @param  mixed    $value
     * @param  int|null $hubId  null = global setting
     */
    public static function setValue(string $key, mixed $value, ?int $hubId = null): void
    {
        static::updateOrCreate(
            ['hub_id' => $hubId, 'key' => $key],
            ['value'  => (string) $value]
        );
    }
}
