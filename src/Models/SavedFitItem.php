<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class SavedFitItem extends Model
{
    protected $table = 'eic_saved_fit_items';

    protected $fillable = ['fit_id', 'type_id', 'quantity'];

    public function fit()
    {
        return $this->belongsTo(SavedFit::class, 'fit_id');
    }
}
