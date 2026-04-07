<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class StockpileItem extends Model
{
    protected $table = 'eic_stockpile_items';

    protected $fillable = ['stockpile_id', 'type_id', 'quantity', 'location_id'];

    public function stockpile()
    {
        return $this->belongsTo(Stockpile::class, 'stockpile_id');
    }
}
