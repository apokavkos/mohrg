<?php

namespace Apokavkos\SeatAssets\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Apokavkos\SeatAssets\Models\Stockpile;
use Apokavkos\SeatAssets\Models\StockpileItem;
use Illuminate\Support\Facades\DB;
use Apokavkos\SeatAssets\Services\StockpileLogisticsService;
use Seat\Eveapi\Models\Assets\CharacterAsset;
use Seat\Eveapi\Models\Assets\CorporationAsset;

class StockpileController extends Controller
{
    protected $logisticsService;

    public function __construct(StockpileLogisticsService $logisticsService)
    {
        $this->logisticsService = $logisticsService;
    }

    public function index()
    {
        $user_id = auth()->user()->id;
        $characterIds = auth()->user()->associatedCharacterIds();
        $corporationIds = DB::table('character_affiliations')
            ->whereIn('character_id', $characterIds)
            ->pluck('corporation_id')
            ->unique()
            ->toArray();

        $stockpiles = Stockpile::where('user_id', $user_id)
            ->with(['items' => function($query) {
                $query->join('invTypes', 'eic_stockpile_items.type_id', '=', 'invTypes.typeID')
                      ->select('eic_stockpile_items.*', 'invTypes.typeName');
            }])->withCount('items')->get();

        // Attach inventory counts to each stockpile item
        foreach ($stockpiles as $stockpile) {
            foreach ($stockpile->items as $item) {
                $inventory = $this->logisticsService->getInventoryBreakdown($item->type_id, $characterIds, $corporationIds);
                $item->current_stock = $inventory['assets'];
                $item->in_flight = $inventory['in_flight'];
                
                // Determine which location to check for this specific entry
                $effectiveLocationId = $item->location_id ?: $stockpile->location_id;

                if ($effectiveLocationId) {
                    $item->local_stock = CharacterAsset::whereIn('character_id', $characterIds)
                        ->where('type_id', $item->type_id)
                        ->where('location_id', $effectiveLocationId)
                        ->sum('quantity') + 
                        CorporationAsset::whereIn('corporation_id', $corporationIds)
                        ->where('type_id', $item->type_id)
                        ->where('location_id', $effectiveLocationId)
                        ->sum('quantity');
                    
                    // Also get the location name for the UI
                    $name = DB::table('universe_stations')->where('station_id', $effectiveLocationId)->value('name');
                    if (!$name) {
                        $name = DB::table('universe_structures')->where('structure_id', $effectiveLocationId)->value('name');
                    }
                    $item->location_name = $name ?: 'Unknown Location';
                } else {
                    $item->local_stock = $item->current_stock; // Default to all if no location
                    $item->location_name = 'All Locations';
                }
            }
        }

        // Get available locations for the dropdown
        $charLocations = CharacterAsset::whereIn('character_id', $characterIds)
            ->select('location_id')
            ->distinct()
            ->pluck('location_id')
            ->toArray();
        $corpLocations = CorporationAsset::whereIn('corporation_id', $corporationIds)
            ->select('location_id')
            ->distinct()
            ->pluck('location_id')
            ->toArray();
        
        $locationIds = array_unique(array_merge($charLocations, $corpLocations));
        
        $locations = [];
        foreach ($locationIds as $lid) {
            $name = DB::table('universe_stations')->where('station_id', $lid)->value('name');
            if (!$name) {
                $name = DB::table('universe_structures')->where('structure_id', $lid)->value('name');
            }
            if ($name) {
                $locations[$lid] = $name;
            }
        }
        asort($locations);

        return view('seat-assets::stockpiles.index', compact('stockpiles', 'locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'multibuy' => 'required|string',
            'location_id' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($request) {
            $stockpile = Stockpile::create([
                'user_id' => auth()->user()->id,
                'name' => $request->name,
                'location_id' => $request->location_id,
            ]);

            $lines = explode("\n", $request->multibuy);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                $parts = explode("\t", $line);
                $itemName = trim($parts[0]);
                $quantity = 1;

                if (count($parts) > 1) {
                    $quantity = (int) preg_replace('/[^0-9]/', '', $parts[1]);
                } else {
                    if (preg_match('/^(.*)\s+x?(\d+)$/', $line, $matches)) {
                        $itemName = trim($matches[1]);
                        $quantity = (int) $matches[2];
                    }
                }

                if ($quantity <= 0) $quantity = 1;

                $type = DB::table('invTypes')->where('typeName', $itemName)->first();
                if ($type) {
                    StockpileItem::create([
                        'stockpile_id' => $stockpile->id,
                        'type_id' => $type->typeID,
                        'quantity' => $quantity,
                    ]);
                }
            }
        });

        if ($request->has('return_to_wizard')) {
            return redirect()->route('seat-assets::stockpiles.workflow', ['mode' => 'wizard', 'step' => 1])->with('success', 'Stockpile created. Now move to Step 2.');
        }

        return redirect()->route('seat-assets::stockpiles')->with('success', 'Stockpile created successfully.');
    }

    public function delete($id)
    {
        Stockpile::where('id', $id)->where('user_id', auth()->user()->id)->delete();
        return redirect()->route('seat-assets::stockpiles')->with('success', 'Stockpile deleted successfully.');
    }

    public function updateItemLocation(Request $request, $itemId)
    {
        $request->validate(['location_id' => 'nullable|integer']);
        
        $item = StockpileItem::findOrFail($itemId);
        // Verify ownership through the stockpile
        if ($item->stockpile->user_id !== auth()->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $item->update(['location_id' => $request->location_id]);

        return response()->json(['success' => true]);
    }

    public function industry($id)
    {
        $user_id = auth()->user()->id;
        $stockpile = Stockpile::where('id', $id)->where('user_id', $user_id)->firstOrFail();
        
        $report = $this->logisticsService->getLogisticsReport($stockpile);

        return view('seat-assets::stockpiles.industry', compact('stockpile', 'report'));
    }

    public function workflow()
    {
        $user_id = auth()->user()->id;
        $characterIds = auth()->user()->associatedCharacterIds();
        $corporationIds = DB::table('character_affiliations')
            ->whereIn('character_id', $characterIds)
            ->pluck('corporation_id')
            ->unique()
            ->toArray();

        $stockpiles = Stockpile::where('user_id', $user_id)
            ->withCount('items')->get();

        // Get available locations for the dropdown
        $charLocations = CharacterAsset::whereIn('character_id', $characterIds)
            ->select('location_id')
            ->distinct()
            ->pluck('location_id')
            ->toArray();
        $corpLocations = CorporationAsset::whereIn('corporation_id', $corporationIds)
            ->select('location_id')
            ->distinct()
            ->pluck('location_id')
            ->toArray();
        
        $locationIds = array_unique(array_merge($charLocations, $corpLocations));
        
        $locations = [];
        foreach ($locationIds as $lid) {
            $name = DB::table('universe_stations')->where('station_id', $lid)->value('name');
            if (!$name) {
                $name = DB::table('universe_structures')->where('structure_id', $lid)->value('name');
            }
            if ($name) {
                $locations[$lid] = $name;
            }
        }
        asort($locations);

        return view('seat-assets::stockpiles.workflow', compact('stockpiles', 'locations'));
    }

    public function searchLocations(Request $request)
    {
        $user_id = auth()->user()->id;
        $characterIds = auth()->user()->associatedCharacterIds();
        $corporationIds = DB::table('character_affiliations')
            ->whereIn('character_id', $characterIds)
            ->pluck('corporation_id')
            ->unique()
            ->toArray();

        $q = $request->get('q');
        
        if (empty($q)) {
            // Return existing locations (precached ones)
            $charLocations = CharacterAsset::whereIn('character_id', $characterIds)
                ->select('location_id')
                ->distinct()
                ->pluck('location_id')
                ->toArray();
            $corpLocations = CorporationAsset::whereIn('corporation_id', $corporationIds)
                ->select('location_id')
                ->distinct()
                ->pluck('location_id')
                ->toArray();
            
            $locationIds = array_unique(array_merge($charLocations, $corpLocations));
            
            $results = [];
            foreach ($locationIds as $lid) {
                $name = DB::table('universe_stations')->where('station_id', $lid)->value('name');
                if (!$name) {
                    $name = DB::table('universe_structures')->where('structure_id', $lid)->value('name');
                }
                if ($name) {
                    $results[] = ['id' => $lid, 'text' => $name];
                }
            }
            return response()->json(['results' => $results]);
        }

        // Search for systems matching query
        $systemIds = DB::table('solar_systems')
            ->where('name', 'like', '%' . $q . '%')
            ->limit(10)
            ->pluck('system_id')
            ->toArray();

        // Search stations
        $stations = DB::table('universe_stations')
            ->where(function($query) use ($q, $systemIds) {
                $query->where('name', 'like', '%' . $q . '%')
                      ->orWhereIn('solar_system_id', $systemIds);
            })
            ->limit(30)
            ->select('station_id as id', 'name as text')
            ->get();

        // Search structures
        $structures = DB::table('universe_structures')
            ->where(function($query) use ($q, $systemIds) {
                $query->where('name', 'like', '%' . $q . '%')
                      ->orWhereIn('solar_system_id', $systemIds);
            })
            ->limit(30)
            ->select('structure_id as id', 'name as text')
            ->get();

        $results = $stations->merge($structures)->unique('id')->values();

        return response()->json(['results' => $results]);
    }

    public function createFromRequirements(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'items' => 'required|array',
            'location_id' => 'nullable|integer',
        ]);

        DB::transaction(function () use ($request) {
            $stockpile = Stockpile::create([
                'user_id' => auth()->user()->id,
                'name' => $request->name,
                'location_id' => $request->location_id,
            ]);

            foreach ($request->items as $item) {
                StockpileItem::create([
                    'stockpile_id' => $stockpile->id,
                    'type_id' => $item['type_id'],
                    'quantity' => $item['quantity'],
                ]);
            }
        });

        if ($request->has('return_to_wizard')) {
            return redirect()->route('seat-assets::stockpiles.workflow', ['mode' => 'wizard', 'step' => 2])->with('success', 'Intermediate stockpile created.');
        }

        return redirect()->route('seat-assets::stockpiles')->with('success', 'New intermediate stockpile created.');
    }
}
