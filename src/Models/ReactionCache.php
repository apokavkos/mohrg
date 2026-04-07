<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class ReactionCache extends Model
{
    protected $table = 'eic_reaction_cache';

    protected $fillable = [
        'reaction_type_id', 'config_id', 'input_cost', 'fuel_cost', 
        'tax_cost', 'output_value', 'profit', 'profit_percent', 'calculated_at'
    ];

    public function config()
    {
        return $this->belongsTo(ReactionConfig::class, 'config_id');
    }
}
