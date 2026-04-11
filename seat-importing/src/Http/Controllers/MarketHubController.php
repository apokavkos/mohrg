<?php

namespace Apokavkos\SeatImporting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller;
use Apokavkos\SeatImporting\Models\MarketHub;
use Apokavkos\SeatImporting\Models\MarketItemData;
use Apokavkos\SeatImporting\Models\MarketSetting;
use Apokavkos\SeatImporting\Models\MarketImportLog;
use Apokavkos\SeatImporting\Services\MarketMetricsService;
use Apokavkos\SeatImporting\Jobs\ProcessMarketImport;

class MarketHubController extends Controller
{
    public function __construct(private readonly MarketMetricsService $metrics) {}

    /**
     * Redirect to the first active hub's dashboard, or show an empty state.
     */
    public function index(Request $request): mixed
    {
        $hub = MarketHub::where('is_active', true)->orderBy('name')->first();

        if ($hub) {
            return redirect()->route('seat-importing.hub.show', $hub);
        }

        $hubs = collect();

        return view('seat-importing::dashboard', [
            'hubs'           => $hubs,
            'selectedHub'    => null,
            'markupItems'    => collect(),
            'lowStockItems'  => collect(),
            'topMarkupItems' => collect(),
            'topTotalItems'  => collect(),
        ]);
    }

    /**
     * Show full metric dashboard for a specific market hub.
     */
    public function show(MarketHub $hub): mixed
    {
        $hubs = Cache::remember(
            config('seat-importing.cache.prefix') . ':hub_list',
            config('seat-importing.cache.hub_list'),
            fn () => MarketHub::where('is_active', true)->orderBy('name')->get()
        );

        $metricsData = $this->metrics->getHubMetrics($hub);

        return view('seat-importing::dashboard', [
            'hubs'           => $hubs,
            'selectedHub'    => $hub,
            'markupItems'    => $metricsData['markup'],
            'lowStockItems'  => $metricsData['low_stock'],
            'topMarkupItems' => $metricsData['top_markup'],
            'topTotalItems'  => $metricsData['top_total'],
            'lastImport'     => $metricsData['last_import'],
        ]);
    }

    /**
     * Create a new market hub.
     * Requires seat-importing.manage permission.
     */
    public function storeHub(Request $request): RedirectResponse
    {
        $this->authorize('seat-importing.manage');

        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'solar_system_id' => 'nullable|integer|min:1',
            'structure_id'    => 'nullable|integer|min:1',
            'region_id'       => 'nullable|integer|min:1',
            'isk_per_m3'      => 'required|numeric|min:0',
            'is_active'       => 'boolean',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        MarketHub::create($validated);

        Cache::forget(config('seat-importing.cache.prefix') . ':hub_list');

        return redirect()->route('seat-importing.settings')
            ->with('success', 'Hub created successfully.');
    }

    /**
     * Update an existing market hub.
     * Requires seat-importing.manage permission.
     */
    public function updateHub(Request $request, MarketHub $hub): RedirectResponse
    {
        $this->authorize('seat-importing.manage');

        $validated = $request->validate([
            'name'            => 'required|string|max:100',
            'solar_system_id' => 'nullable|integer|min:1',
            'structure_id'    => 'nullable|integer|min:1',
            'region_id'       => 'nullable|integer|min:1',
            'isk_per_m3'      => 'required|numeric|min:0',
            'is_active'       => 'boolean',
            'notes'           => 'nullable|string|max:2000',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $hub->update($validated);

        Cache::forget(config('seat-importing.cache.prefix') . ':hub_list');
        $this->metrics->flushHubCache($hub->id);

        return redirect()->route('seat-importing.settings')
            ->with('success', 'Hub updated successfully.');
    }

    /**
     * Delete a market hub (cascades to item data + settings).
     * Requires seat-importing.manage permission.
     */
    public function destroyHub(MarketHub $hub): RedirectResponse
    {
        $this->authorize('seat-importing.manage');

        $this->metrics->flushHubCache($hub->id);
        $hub->delete();

        Cache::forget(config('seat-importing.cache.prefix') . ':hub_list');

        return redirect()->route('seat-importing.settings')
            ->with('success', 'Hub deleted.');
    }

    /**
     * Show global + per-hub settings form.
     */
    public function settings(): mixed
    {
        $hubs = MarketHub::orderBy('name')->get();

        $globalSettings = [
            'isk_per_m3'           => MarketSetting::get('isk_per_m3', null, config('seat-importing.default_isk_per_m3')),
            'markup_threshold_pct' => MarketSetting::get('markup_threshold_pct', null, config('seat-importing.markup_threshold_pct')),
            'stock_low_threshold'  => MarketSetting::get('stock_low_threshold_pct', null, config('seat-importing.stock_low_threshold_pct')),
        ];

        $recentLogs = MarketImportLog::latest()->limit(10)->get();

        return view('seat-importing::settings', compact('hubs', 'globalSettings', 'recentLogs'));
    }

    /**
     * Persist global settings (ISK/m3, thresholds).
     * Requires seat-importing.manage permission.
     */
    public function saveSettings(Request $request): RedirectResponse
    {
        $this->authorize('seat-importing.manage');

        $validated = $request->validate([
            'isk_per_m3'           => 'required|numeric|min:0',
            'markup_threshold_pct' => 'required|numeric|min:0|max:10000',
            'stock_low_threshold'  => 'required|numeric|min:0|max:10000',
        ]);

        MarketSetting::setValue('isk_per_m3', $validated['isk_per_m3']);
        MarketSetting::setValue('markup_threshold_pct', $validated['markup_threshold_pct']);
        MarketSetting::setValue('stock_low_threshold_pct', $validated['stock_low_threshold']);

        // Flush all hub metric caches so recalculations pick up the new thresholds
        MarketHub::all()->each(fn (MarketHub $hub) => $this->metrics->flushHubCache($hub->id));

        return redirect()->route('seat-importing.settings')
            ->with('success', 'Settings saved.');
    }

    /**
     * Return JSON item details for the AJAX modal.
     * Pulls type info from the SDE `invTypes` / `invGroups` / `invCategories` tables.
     */
    public function itemDetail(int $typeId): JsonResponse
    {
        // Try SDE connection first; fall back to default connection gracefully
        try {
            $sde = DB::connection('sde');
            $type = $sde->table('invTypes as t')
                ->leftJoin('invGroups as g', 'g.groupID', '=', 't.groupID')
                ->leftJoin('invCategories as c', 'c.categoryID', '=', 'g.categoryID')
                ->select(
                    't.typeID',
                    't.typeName',
                    't.description',
                    't.volume',
                    't.mass',
                    't.groupID',
                    'g.groupName',
                    'c.categoryID',
                    'c.categoryName'
                )
                ->where('t.typeID', $typeId)
                ->first();
        } catch (\Exception) {
            // SDE connection unavailable — fall back silently
            $type = null;
        }

        // Grab latest price snapshot for any active hub
        $priceRow = MarketItemData::where('type_id', $typeId)
            ->latest('data_date')
            ->first();

        if (! $type && ! $priceRow) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        return response()->json([
            'type_id'       => $typeId,
            'type_name'     => $type?->typeName ?? $priceRow?->type_name ?? "Type #{$typeId}",
            'group_name'    => $type?->groupName ?? '—',
            'category_name' => $type?->categoryName ?? '—',
            'volume_m3'     => $type?->volume ?? $priceRow?->volume_m3 ?? 0,
            'description'   => $type?->description ?? '',
            'jita_sell'     => $priceRow?->jita_sell_price ?? 0,
            'jita_buy'      => $priceRow?->jita_buy_price ?? 0,
            'local_sell'    => $priceRow?->local_sell_price ?? 0,
            'local_buy'     => $priceRow?->local_buy_price ?? 0,
            'import_cost'   => $priceRow?->import_cost ?? 0,
            'markup_pct'    => $priceRow?->markup_pct ?? 0,
            'weekly_profit' => $priceRow?->weekly_profit ?? 0,
            'weekly_volume' => $priceRow?->weekly_volume ?? 0,
            'current_stock' => $priceRow?->current_stock ?? 0,
            'data_date'     => $priceRow?->data_date,
        ]);
    }

    /**
     * Dispatch a ProcessMarketImport job from the web UI.
     * Requires seat-importing.import permission.
     */
    public function triggerImport(Request $request): JsonResponse
    {
        $this->authorize('seat-importing.import');

        $validated = $request->validate([
            'hub_id' => 'nullable|integer|exists:market_hubs,id',
            'source' => 'nullable|string|in:fuzzwork_csv,tycoon_csv',
        ]);

        $hubId   = $validated['hub_id'] ?? null;
        $source  = $validated['source'] ?? config('seat-importing.import.default_source');

        ProcessMarketImport::dispatch($hubId, $source, null);

        return response()->json(['message' => 'Import job dispatched.']);
    }

    // -------------------------------------------------------------------------
    // API endpoints (JSON — for future integrations / dashboards)
    // -------------------------------------------------------------------------

    public function apiHubs(): JsonResponse
    {
        $hubs = Cache::remember(
            config('seat-importing.cache.prefix') . ':hub_list',
            config('seat-importing.cache.hub_list'),
            fn () => MarketHub::where('is_active', true)->orderBy('name')->get()
        );

        return response()->json($hubs);
    }

    public function apiMetrics(MarketHub $hub): JsonResponse
    {
        $metrics = $this->metrics->getHubMetrics($hub);

        return response()->json([
            'hub'            => $hub,
            'markup_count'   => $metrics['markup']->count(),
            'low_stock_count'=> $metrics['low_stock']->count(),
            'last_import'    => $metrics['last_import'],
        ]);
    }

    public function apiItems(MarketHub $hub): JsonResponse
    {
        $items = MarketItemData::where('hub_id', $hub->id)
            ->latest('data_date')
            ->paginate(100);

        return response()->json($items);
    }
}
