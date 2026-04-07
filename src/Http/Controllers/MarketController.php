<?php

namespace Apokavkos\SeatAssets\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Apokavkos\SeatAssets\Models\MarketHub;
use Apokavkos\SeatAssets\Models\MarketSnapshot;
use Apokavkos\SeatAssets\Models\SavedFit;
use Apokavkos\SeatAssets\Models\SavedFitItem;
use Apokavkos\SeatAssets\Models\SavedFitGroup;
use Apokavkos\SeatAssets\Models\SavedExport;
use Apokavkos\SeatAssets\Models\SavedExportItem;
use Apokavkos\SeatAssets\Services\MarketPriceService;
use Apokavkos\SeatAssets\Services\MarketSyncService;
use Apokavkos\SeatAssets\Services\ReactionDataService;
use Illuminate\Support\Facades\DB;
use Seat\Services\Models\UserSetting;

class MarketController extends Controller
{
    public function searchHubs(Request $request)
    {
        $q = $request->get('q');
        if (empty($q)) return response()->json(['results' => []]);

        $results = [];

        // Regions (e.g., Jita, Amarr)
        $regions = DB::table('solar_systems')
            ->join('constellations', 'solar_systems.constellation_id', '=', 'constellations.constellation_id')
            ->join('regions', 'constellations.region_id', '=', 'regions.region_id')
            ->where('regions.name', 'like', '%' . $q . '%')
            ->select('regions.region_id as id', 'regions.name as text')
            ->distinct()
            ->limit(5)
            ->get();
        foreach ($regions as $r) {
            $results[] = ['id' => $r->id, 'text' => $r->text . ' (Region)', 'type' => 'region'];
        }

        // Stations
        $stations = DB::table('universe_stations')
            ->where('name', 'like', '%' . $q . '%')
            ->select('station_id as id', 'name as text')
            ->limit(10)
            ->get();
        foreach ($stations as $s) {
            $results[] = ['id' => $s->id, 'text' => $s->text . ' (Station)', 'type' => 'structure'];
        }

        // Structures
        $structures = DB::table('universe_structures')
            ->where('name', 'like', '%' . $q . '%')
            ->select('structure_id as id', 'name as text')
            ->limit(10)
            ->get();
        foreach ($structures as $s) {
            $results[] = ['id' => $s->id, 'text' => $s->text . ' (Structure)', 'type' => 'structure'];
        }

        // Specific request: K-IYNW - G F more like gobbins fled
        // Hardcoding the known ID for this specific structure to ensure it can be added
        $targetName = 'k-iynw - g f more like gobbins fled';
        if (str_contains($targetName, strtolower($q))) {
            $results[] = [
                'id' => 1042730030282, 
                'text' => 'K-IYNW - G F more like gobbins fled', 
                'type' => 'structure'
            ];
        }

        return response()->json(['results' => $results]);
    }

    protected function ensureKeepstarExists()
    {
        MarketHub::firstOrCreate(
            ['hub_id' => 1042730030282],
            [
                'name' => 'K-IYNW - G F more like gobbins fled', 
                'type' => 'structure', 
                'is_enabled' => true
            ]
        );
    }

    public function addHub(Request $request)
    {
        $request->validate([
            'hub_id' => 'required|numeric',
            'name' => 'required|string',
            'type' => 'required|in:region,structure'
        ]);

        $hub = MarketHub::updateOrCreate(
            ['hub_id' => $request->hub_id],
            ['name' => $request->name, 'type' => $request->type, 'is_enabled' => true]
        );

        app(MarketSyncService::class)->syncAllHubs();

        return response()->json(['success' => true, 'hub' => $hub]);
    }

    public function markup(Request $request)
    {
        $this->ensureKeepstarExists();
        $user_id = auth()->user()->id;
        if ($request->has('hub_id')) {
            $setting = UserSetting::where('user_id', $user_id)->where('name', 'eic_selected_market_hub')->first();
            if (!$setting) $setting = new UserSetting();
            $setting->user_id = $user_id;
            $setting->name = 'eic_selected_market_hub';
            $setting->value = $request->get('hub_id');
            $setting->save();
        }

        $hubs = MarketHub::where('is_enabled', true)->get();
        $selectedHubId = UserSetting::where('user_id', $user_id)->where('name', 'eic_selected_market_hub')->value('value') ?: 1049588174021;
        
        $snapshots = MarketSnapshot::where('hub_id', $selectedHubId)
            ->join('invTypes', 'eic_market_snapshots.type_id', '=', 'invTypes.typeID')
            ->select('eic_market_snapshots.*', 'invTypes.typeName')
            ->get();

        $jitaPrices = DB::table('eic_market_price_cache')->where('region_id', 10000002)->get()->keyBy('type_id');

        $report = [];
        foreach ($snapshots as $s) {
            $jita = $jitaPrices->get($s->type_id);
            if (!$jita || $jita->sell_price == 0 || $s->lowest_sell == 0) continue;
            $markup = (($s->lowest_sell / $jita->sell_price) - 1) * 100;
            $report[] = (object)['type_id' => $s->type_id, 'name' => $s->typeName, 'qty' => $s->quantity, 'local_price' => $s->lowest_sell, 'jita_price' => $jita->sell_price, 'markup' => $markup];
        }
        usort($report, fn($a, $b) => $b->markup <=> $a->markup);

        return view('seat-assets::market.markup', compact('hubs', 'selectedHubId', 'report'));
    }

    public function stock(Request $request)
    {
        $this->ensureKeepstarExists();
        $user_id = auth()->user()->id;
        $hubs = MarketHub::where('is_enabled', true)->get();
        $selectedHubId = UserSetting::where('user_id', $user_id)->where('name', 'eic_selected_market_hub')->value('value') ?: 1049588174021;

        $report = DB::table('eic_market_snapshots')
            ->where('hub_id', $selectedHubId)
            ->join('invTypes', 'eic_market_snapshots.type_id', '=', 'invTypes.typeID')
            ->leftJoin('eic_market_volume', 'eic_market_snapshots.type_id', '=', 'eic_market_volume.type_id')
            ->select('eic_market_snapshots.*', 'invTypes.typeName', 'eic_market_volume.weekly_volume')
            ->get();

        foreach ($report as $row) {
            $row->stock_health = $row->weekly_volume > 0 ? ($row->quantity / $row->weekly_volume) * 100 : 100;
        }

        return view('seat-assets::market.stock', compact('hubs', 'selectedHubId', 'report'));
    }

    public function doctrineDashboard()
    {
        $this->ensureKeepstarExists();
        $user_id = auth()->user()->id;
        $hubs = MarketHub::where('is_enabled', true)->get();
        $selectedHubId = UserSetting::where('user_id', $user_id)->where('name', 'eic_selected_market_hub')->value('value') ?: 1049588174021;

        $lastSync = MarketSnapshot::where('hub_id', $selectedHubId)->max('updated_at');

        $savedFits = SavedFit::where('user_id', $user_id)->withCount('items')->with('group')->get();
        $savedGroups = SavedFitGroup::where('user_id', $user_id)->orderBy('name')->get();
        $savedExports = SavedExport::where('user_id', $user_id)->get();

        return view('seat-assets::market.doctrine', compact('hubs', 'selectedHubId', 'savedFits', 'savedGroups', 'savedExports', 'lastSync'));
    }

    public function fittings(Request $request)
    {
        $this->ensureKeepstarExists();
        $user_id = auth()->user()->id;
        $hubs = MarketHub::where('is_enabled', true)->get();
        $selectedHubId = UserSetting::where('user_id', $user_id)->where('name', 'eic_selected_market_hub')->value('value') ?: 1049588174021;

        $results = null;
        $lastSync = MarketSnapshot::where('hub_id', $selectedHubId)->max('updated_at');

        if ($request->has('fit_text')) {
            // Check if hub needs sync
            if (!$lastSync || \Carbon\Carbon::parse($lastSync)->lt(now()->subMinutes(15))) {
                $hub = MarketHub::where('hub_id', $selectedHubId)->first();
                if ($hub) {
                    app(MarketSyncService::class)->syncHub($hub);
                    $lastSync = MarketSnapshot::where('hub_id', $selectedHubId)->max('updated_at');
                }
            }
            $results = $this->parseFit($request->get('fit_text'), $selectedHubId);
        }

        $savedFits = SavedFit::where('user_id', $user_id)->withCount('items')->with('group')->get();
        $savedGroups = SavedFitGroup::where('user_id', $user_id)->orderBy('name')->get();
        $savedExports = SavedExport::where('user_id', $user_id)->get();

        return view('seat-assets::market.fittings', compact('hubs', 'selectedHubId', 'results', 'savedFits', 'savedGroups', 'savedExports', 'lastSync'));
    }

    public function syncHub($hubId)
    {
        $hub = MarketHub::where('hub_id', $hubId)->firstOrFail();
        app(MarketSyncService::class)->syncHub($hub);
        return response()->json(['success' => true, 'updated_at' => MarketSnapshot::where('hub_id', $hubId)->max('updated_at')]);
    }

    public function batchRestock(Request $request)
    {
        $user_id = auth()->user()->id;
        $fitIds = $request->input('fit_ids', []);
        $percent = (int)$request->input('percent', 100);
        $hubId = UserSetting::where('user_id', $user_id)->where('name', 'eic_selected_market_hub')->value('value') ?: 1049588174021;

        if (empty($fitIds)) return response()->json(['error' => 'No fits selected'], 400);

        $items = SavedFitItem::whereIn('fit_id', $fitIds)->select('type_id', DB::raw('SUM(quantity) as total_needed'))->groupBy('type_id')->get();
        $shoppingList = "";
        foreach ($items as $item) {
            $needed = ceil($item->total_needed * ($percent / 100));
            $snapshot = MarketSnapshot::where('hub_id', $hubId)->where('type_id', $item->type_id)->first();
            $available = $snapshot->quantity ?? 0;
            $toBuy = (int)max(0, $needed - $available);
            if ($toBuy > 0) {
                $typeName = DB::table('invTypes')->where('typeID', $item->type_id)->value('typeName');
                $shoppingList .= $typeName . " " . $toBuy . "\n";
            }
        }
        return response()->json(['text' => $shoppingList]);
    }

    public function saveGroup(Request $request)
    {
        $user_id = auth()->user()->id;
        $request->validate(['name' => 'required|string|max:255']);

        if ($request->has('id') && $request->id) {
            $group = SavedFitGroup::where('id', $request->id)->where('user_id', $user_id)->firstOrFail();
            $group->update(['name' => $request->name]);
        } else {
            $group = SavedFitGroup::create(['user_id' => $user_id, 'name' => $request->name]);
        }

        if ($request->has('fit_ids')) {
            SavedFit::whereIn('id', $request->fit_ids)->where('user_id', $user_id)->update(['group_id' => $group->id]);
        }

        return response()->json(['success' => true, 'group' => $group]);
    }

    public function deleteGroup($id)
    {
        SavedFitGroup::where('id', $id)->where('user_id', auth()->user()->id)->delete();
        return response()->json(['success' => true]);
    }

    public function saveFit(Request $request)
    {
        $user_id = auth()->user()->id;
        $request->validate(['fit_text' => 'required|string', 'name' => 'required|string|max:255', 'label' => 'nullable|string|max:255', 'reference_url' => 'nullable|url|max:255']);
        
        if ($request->has('id') && $request->id) {
            $fit = SavedFit::where('id', $request->id)->where('user_id', $user_id)->firstOrFail();
            $fit->update([
                'name' => $request->name,
                'label' => $request->label,
                'reference_url' => $request->reference_url,
                'fit_text' => $request->fit_text,
                'group_id' => $request->group_id
            ]);
            SavedFitItem::where('fit_id', $fit->id)->delete();
        } else {
            $fit = SavedFit::create([
                'user_id' => $user_id,
                'name' => $request->name,
                'label' => $request->label,
                'reference_url' => $request->reference_url,
                'fit_text' => $request->fit_text,
                'group_id' => $request->group_id
            ]);
        }

        $items = $this->extractItemsFromFit($request->fit_text);
        foreach ($items as $typeId => $qty) {
            SavedFitItem::create(['fit_id' => $fit->id, 'type_id' => $typeId, 'quantity' => $qty]);
        }
        return redirect()->back()->with('success', 'Fit saved successfully.');
    }

    public function deleteFit($id)
    {
        SavedFit::where('id', $id)->where('user_id', auth()->user()->id)->delete();
        return redirect()->back()->with('success', 'Fit deleted.');
    }

    public function saveExport(Request $request)
    {
        $user_id = auth()->user()->id;
        if ($request->has('replace_id') && $request->replace_id) {
            SavedExport::where('id', $request->replace_id)->where('user_id', $user_id)->update(['export_text' => $request->export_text, 'label' => $request->label]);
            $exportId = $request->replace_id;
            SavedExportItem::where('export_id', $exportId)->delete();
        } else {
            $export = SavedExport::create(['user_id' => $user_id, 'label' => $request->label, 'export_text' => $request->export_text]);
            $exportId = $export->id;
        }
        $lines = explode("\n", $request->export_text);
        foreach ($lines as $line) {
            if (preg_match('/^(.*)\s+(\d+)$/', trim($line), $matches)) {
                $type = DB::table('invTypes')->where('typeName', trim($matches[1]))->first();
                if ($type) {
                    SavedExportItem::create(['export_id' => $exportId, 'type_id' => $type->typeID, 'quantity' => (int)$matches[2]]);
                }
            }
        }
        return response()->json(['success' => true]);
    }

    public function deleteExport($id)
    {
        SavedExport::where('id', $id)->where('user_id', auth()->user()->id)->delete();
        return redirect()->back()->with('success', 'Export deleted.');
    }

    public function dedupeExports(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) return response()->json(['text' => '']);
        $items = SavedExportItem::whereIn('export_id', $ids)->join('invTypes', 'eic_saved_export_items.type_id', '=', 'invTypes.typeID')->select('invTypes.typeName', DB::raw('SUM(quantity) as total_qty'))->groupBy('invTypes.typeName')->get();
        $text = "";
        foreach ($items as $item) $text .= $item->typeName . " " . $item->total_qty . "\n";
        return response()->json(['text' => $text]);
    }

    protected function parseFit($text, $hubId)
    {
        $items = $this->extractItemsFromFit($text);
        $report = [];
        foreach ($items as $id => $qty) {
            $snapshot = MarketSnapshot::where('hub_id', $hubId)->where('type_id', $id)->first();
            $jita = DB::table('eic_market_price_cache')->where('region_id', 10000002)->where('type_id', $id)->first();
            $typeName = DB::table('invTypes')->where('typeID', $id)->value('typeName');
            $report[] = (object)['type_id' => $id, 'name' => $typeName, 'required' => $qty, 'available' => $snapshot->quantity ?? 0, 'local_price' => $snapshot->lowest_sell ?? 0, 'jita_price' => $jita->sell_price ?? 0];
        }
        return $report;
    }

    protected function extractItemsFromFit($text)
    {
        $lines = explode("\n", $text);
        $items = [];
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip headers, empty lines, empty slots
            if (empty($line) || str_starts_with($line, '[') || str_contains($line, '/') || strtolower($line) == '[empty low slot]') continue;
            if (str_contains(strtolower($line), 'empty') && str_contains($line, 'slot')) continue;

            $qty = 1; 
            $name = $line;

            // Handle "Item Name x2" or "Item Name 2" (some exports)
            if (preg_match('/^(.*?)\s+x?(\d+)$/', $line, $matches)) {
                $name = trim($matches[1]); 
                $qty = (int)$matches[2];
            }

            $type = DB::table('invTypes')->where('typeName', $name)->first();
            if ($type) {
                if (!isset($items[$type->typeID])) $items[$type->typeID] = 0;
                $items[$type->typeID] += $qty;
            }
        }
        return $items;
    }
}
