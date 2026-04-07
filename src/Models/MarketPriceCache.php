<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPriceCache extends Model
{
    protected $table = 'eic_market_price_cache';
    public $timestamps = false;

    protected $fillable = [
        'type_id', 'region_id', 'buy_price', 'sell_price', 'adjusted_price', 'updated_at'
    ];
}
