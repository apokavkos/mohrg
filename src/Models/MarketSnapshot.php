<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class MarketSnapshot extends Model
{
    protected $table = 'eic_market_snapshots';

    protected $fillable = ['hub_id', 'type_id', 'quantity', 'lowest_sell'];
}
