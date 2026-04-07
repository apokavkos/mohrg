<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class Stockpile extends Model
{
    protected $table = 'eic_stockpiles';

    protected $fillable = ['user_id', 'name', 'location_id'];

    public function items()
    {
        return $this->hasMany(StockpileItem::class, 'stockpile_id');
    }
}
