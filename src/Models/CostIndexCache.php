<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class CostIndexCache extends Model
{
    protected $table = 'eic_cost_index_cache';
    public $timestamps = false;

    protected $fillable = [
        'solar_system_id', 'activity', 'cost_index', 'updated_at'
    ];
}
