<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class SavedFitGroup extends Model
{
    protected $table = 'eic_fit_groups';

    protected $fillable = ['user_id', 'name'];

    public function fits()
    {
        return $this->hasMany(SavedFit::class, 'group_id');
    }
}
