<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class SavedFit extends Model
{
    protected $table = 'eic_saved_fits';

    protected $fillable = ['user_id', 'group_id', 'name', 'label', 'reference_url', 'fit_text'];

    public function items()
    {
        return $this->hasMany(SavedFitItem::class, 'fit_id');
    }

    public function group()
    {
        return $this->belongsTo(SavedFitGroup::class, 'group_id');
    }
}
