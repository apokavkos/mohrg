<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class MarketHub extends Model
{
    protected $table = 'eic_market_hubs';

    protected $fillable = ['hub_id', 'name', 'type', 'is_enabled'];
}
