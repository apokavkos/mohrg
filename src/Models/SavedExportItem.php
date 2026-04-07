<?php

namespace Apokavkos\SeatAssets\Models;

use Illuminate\Database\Eloquent\Model;

class SavedExportItem extends Model
{
    protected $table = 'eic_saved_export_items';

    protected $fillable = ['export_id', 'type_id', 'quantity'];

    public function export()
    {
        return $this->belongsTo(SavedExport::class, 'export_id');
    }
}
