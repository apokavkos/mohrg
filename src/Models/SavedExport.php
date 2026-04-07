<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class SavedExport extends Model
{
    protected $table = 'eic_saved_exports';

    protected $fillable = ['user_id', 'label', 'export_text'];

    public function items()
    {
        return $this->hasMany(SavedExportItem::class, 'export_id');
    }
}
