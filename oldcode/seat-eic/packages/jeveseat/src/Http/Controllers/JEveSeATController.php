<?php

namespace Apokavkos\JEveSeAT\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JEveSeATController extends Controller
{
    public function index()
    {
        $path = 'jeveassets/assets.json';
        $assets = [];

        if (Storage::disk('local')->exists($path)) {
            $json = Storage::disk('local')->get($path);
            $assets = json_decode($json, true);

            // Enrich with SeAT SDE
            $typeIds = collect($assets)->pluck('typeID')->unique();

            $types = DB::table('invTypes')
                ->join('invGroups', 'invTypes.groupID', '=', 'invGroups.groupID')
                ->whereIn('invTypes.typeID', $typeIds)
                ->select('invTypes.typeID', 'invTypes.typeName', 'invGroups.groupName')
                ->get()
                ->keyBy('typeID');

            foreach ($assets as &$asset) {
                $type = $types->get($asset['typeID']);
                $asset['typeName'] = $type->typeName ?? 'Unknown Item';
                $asset['groupName'] = $type->groupName ?? 'Unknown Group';
                // Value is usually in the JSON, but if not we can just keep it 0
                $asset['value'] = $asset['value'] ?? 0;
            }
        }

        return view('jeveseat::index', compact('assets'));
    }
}
