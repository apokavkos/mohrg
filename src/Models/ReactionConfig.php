<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class ReactionConfig extends Model
{
    protected $table = 'eic_reaction_configs';

    protected $fillable = [
        'user_id', 'name', 'structure_type', 'reactor_type', 'rig_1', 'rig_2', 'rig_3',
        'space_type', 'solar_system_id', 'input_method', 'output_method', 'skill_level',
        'system_name', 'runs', 'fuel_block_type_id', 'facility_tax', 'is_default'
    ];
}
