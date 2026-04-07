<?php

namespace Apokavkos\SeatAssets\Services\DataTables;

use Seat\Web\Http\DataTables\Common\Intel\AbstractAssetDataTable;
use Seat\Web\Http\DataTables\Common\IColumn;
use Seat\Eveapi\Models\Assets\CharacterAsset;
use Illuminate\Database\Eloquent\Builder;
use Seat\Web\Http\DataTables\Character\Intel\Assets\Columns\LocationFlag;
use Seat\Web\Http\DataTables\Character\Intel\Assets\Columns\Station;
use Seat\Web\Http\DataTables\Character\Intel\Assets\Columns\Owner;

class AllAssetsDataTable extends AbstractAssetDataTable
{
    public function query()
    {
        $characterIds = auth()->user()->associatedCharacterIds();
        
        return CharacterAsset::with('type', 'type.group', 'content', 'content.type', 'content.content', 'content.content.type', 'character')
            ->whereIn('character_id', $characterIds);
    }

    protected function getLocationFlagColumn($table): IColumn
    {
        return new LocationFlag($table);
    }

    protected function getStationColumn($table): IColumn
    {
        return new Station($table);
    }

    protected function extraColumns(): array
    {
        return [
            'character.name' => new Owner($this)
        ];
    }

    public function getColumns()
    {
        $columns = parent::getColumns();
        // Add Owner column at the beginning
        array_unshift($columns, ['data' => 'character.name', 'title' => 'Owner']);
        return $columns;
    }
}
