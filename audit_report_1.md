# Architectural Audit & Refactoring Plan: `apokavkos/mohrg` (SeAT Asset Manager)

> **Audit Date:** April 6, 2026
> **Scope:** Full codebase review via `plugin_audit_context.txt` (7,577 lines / 333 KB)
> **Package Namespace:** `Apokavkos\SeatAssets`

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Fat Controllers — Identification & Service Extraction](#2-fat-controllers)
3. [Hardcoded Values — Config File Refactor](#3-hardcoded-values)
4. [Service Provider Review](#4-service-provider-review)
5. [Draft README.md for GitHub](#5-draft-readmemd)

---

## 1. Executive Summary

The plugin is a functional prototype for EVE Online industry management within SeAT 5.x. The Services layer is well-decomposed (8 dedicated service classes), which is a positive sign. However, the codebase has several architectural issues that need addressing before it's ready for public distribution:

**Critical Issues:**
- **Fat Controllers**: Controllers (particularly `ReactionController`, `StockpileController`, and the industry calculator controllers) contain business logic that should live in Services.
- **Hardcoded EVE IDs**: The Jita region ID (`10000002`), Fuzzwork API URLs, eve-industry.org URLs, cache key prefixes (`eic.*`), and reaction product names are scattered throughout the codebase.
- **Service Provider**: Likely missing proper route registration with middleware, config publishing, and migration loading from the package path.
- **No README**: The existing README is a placeholder with no installation or configuration instructions.

**What's Already Good:**
- Clean separation of calculator logic (`CostCalculatorService`, `ReactionCalculatorService`)
- Proper use of Laravel caching and DB facades
- DataTable extends SeAT's abstract classes correctly
- Market price caching with TTL and lock-based structure fetching

---

## 2. Fat Controllers

Based on the codebase context, the following controllers contain business logic that should be extracted into service classes. Below are the specific patterns found and the exact refactored code.

### 2.1 Problem Pattern: Controllers Querying for User Characters/Corporations

Multiple controllers repeat this logic to resolve the current user's characters and corporations. This pattern appears in `ReactionController`, `StockpileController`, `BlueprintController`, and others.

**Before (repeated in controllers):**
```php
// This pattern is duplicated across 4+ controllers
$user = auth()->user();
$characterIds = RefreshToken::where('user_id', $user->id)
    ->pluck('character_id')->toArray();
$corporationIds = CharacterAffiliation::whereIn('character_id', $characterIds)
    ->pluck('corporation_id')->unique()->toArray();
```

**After — New `UserResolverService`:**

```php
<?php
// src/Services/UserResolverService.php

namespace Apokavkos\SeatAssets\Services;

use Illuminate\Support\Facades\DB;
use Seat\Eveapi\Models\Character\CharacterInfo;
use Seat\Eveapi\Models\Character\CharacterAffiliation;
use Seat\Eveapi\Models\RefreshToken;

class UserResolverService
{
    /**
     * Get all character IDs linked to a SeAT user.
     */
    public function getCharacterIds(int $userId): array
    {
        return RefreshToken::where('user_id', $userId)
            ->pluck('character_id')
            ->toArray();
    }

    /**
     * Get all corporation IDs for a SeAT user's linked characters.
     */
    public function getCorporationIds(int $userId): array
    {
        $characterIds = $this->getCharacterIds($userId);

        return CharacterAffiliation::whereIn('character_id', $characterIds)
            ->pluck('corporation_id')
            ->unique()
            ->toArray();
    }

    /**
     * Get character names keyed by character_id.
     */
    public function getCharacterNames(int $userId): array
    {
        $characterIds = $this->getCharacterIds($userId);

        return CharacterInfo::whereIn('character_id', $characterIds)
            ->pluck('name', 'character_id')
            ->toArray();
    }

    /**
     * Get corporation names keyed by corporation_id.
     */
    public function getCorporationNames(int $userId): array
    {
        $corporationIds = $this->getCorporationIds($userId);

        return DB::table('corporation_infos')
            ->whereIn('corporation_id', $corporationIds)
            ->pluck('name', 'corporation_id')
            ->toArray();
    }

    /**
     * Resolve a location name from station or structure tables.
     */
    public function resolveLocationName(int $locationId): ?string
    {
        $name = DB::table('universe_stations')
            ->where('station_id', $locationId)
            ->value('name');

        if (! $name) {
            $name = DB::table('universe_structures')
                ->where('structure_id', $locationId)
                ->value('name');
        }

        return $name;
    }
}
```

**Controller after refactor (example):**
```php
class StockpileController extends Controller
{
    public function __construct(
        protected UserResolverService $userResolver,
        protected StockpileLogisticsService $logistics,
    ) {}

    public function logistics(int $stockpileId)
    {
        $stockpile = Stockpile::findOrFail($stockpileId);
        $report = $this->logistics->getLogisticsReport($stockpile);

        return view('seat-assets::stockpile.logistics', compact('report'));
    }
}
```

### 2.2 Problem Pattern: `MarketSyncService::syncRegion()` Has Hardcoded Jita Check

The `syncRegion` method only runs if `$hub->hub_id == 10000002`. This is a hardcoded business rule that should be configurable.

**Before:**
```php
protected function syncRegion(MarketHub $hub)
{
    if ($hub->hub_id == 10000002) {
        // ... only syncs Jita
    }
}
```

**After:**
```php
protected function syncRegion(MarketHub $hub)
{
    $typeIds = app(ReactionDataService::class)->getAllInvolvedTypeIds();
    app(MarketPriceService::class)->getPrices($typeIds, $hub->hub_id);

    $prices = DB::table('eic_market_price_cache')
        ->where('region_id', $hub->hub_id)
        ->get();

    DB::transaction(function () use ($hub, $prices) {
        MarketSnapshot::where('hub_id', $hub->hub_id)->update(['quantity' => 0]);

        foreach ($prices as $p) {
            MarketSnapshot::updateOrCreate(
                ['hub_id' => $hub->hub_id, 'type_id' => $p->type_id],
                ['quantity' => 0, 'lowest_sell' => $p->sell_price, 'updated_at' => Carbon::now()]
            );
        }
    });
}
```

### 2.3 Problem Pattern: `MarketSyncService::syncVolumeData()` Has Hardcoded Region

**Before:**
```php
public function syncVolumeData(array $typeIds)
{
    $regionId = 10000002; // Jita
    // ...
}
```

**After:**
```php
public function syncVolumeData(array $typeIds, ?int $regionId = null)
{
    $regionId = $regionId ?? config('seat-assets.defaults.market_region_id');
    // ...
}
```

### 2.4 Problem Pattern: `BlueprintImportService` Duplicates `UserResolverService` Logic

`BlueprintImportService` has its own `getCharactersForUser()` and `getCorporationsForUser()` methods that duplicate the pattern. After creating `UserResolverService`, these should delegate:

**After refactor:**
```php
class BlueprintImportService
{
    public function __construct(
        protected UserResolverService $userResolver
    ) {}

    public function getCharactersForUser(int $userId): array
    {
        return $this->userResolver->getCharacterNames($userId);
    }

    public function getCorporationsForUser(int $userId): array
    {
        return $this->userResolver->getCorporationNames($userId);
    }

    // ... rest of the service unchanged
}
```

### 2.5 Problem Pattern: `StockpileLogisticsService::getLogisticsReport()` Inlines User Resolution

The `getLogisticsReport` method calls `auth()->user()` directly inside a service — services should not access the auth facade directly. This makes the service untestable.

**Before:**
```php
public function getLogisticsReport(Stockpile $stockpile)
{
    $user = auth()->user();
    $characterIds = $user->associatedCharacterIds();
    $corporationIds = DB::table('character_affiliations')
        ->whereIn('character_id', $characterIds)
        ->pluck('corporation_id')->unique()->toArray();
    // ...
}
```

**After:**
```php
public function getLogisticsReport(Stockpile $stockpile, int $userId): array
{
    $userResolver = app(UserResolverService::class);
    $characterIds = $userResolver->getCharacterIds($userId);
    $corporationIds = $userResolver->getCorporationIds($userId);
    // ...
}
```

And in the controller:
```php
$report = $this->logistics->getLogisticsReport($stockpile, auth()->id());
```

---

## 3. Hardcoded Values

### 3.1 Inventory of All Hardcoded Values Found

| Value | Location(s) | Description |
|-------|------------|-------------|
| `10000002` | `MarketPriceService`, `MarketSyncService`, `EveIndustryApiService` | Jita region ID (The Forge) |
| `100000000` | `MarketPriceService::getPrices()` | Threshold to distinguish region IDs from structure IDs |
| `15` (minutes) | `MarketPriceService` (×2) | Market price cache TTL |
| `3600` (seconds) | `EveIndustryApiService` (×2), `MarketPriceService` | Cost index / adjusted price cache TTL |
| `600` (seconds) | `EveIndustryApiService` | Market price cache TTL (different from above!) |
| `86400` (seconds) | `ReactionDataService`, `MarketSyncService` | Daily cache TTL |
| `86400 * 7` | `ReactionDataService` | Weekly formula cache TTL |
| `'eic.*'` prefix | `MarketSyncService`, `ReactionDataService`, `MarketPriceService` | Cache key prefix (leftover from "EVE Industry Calculator"?) |
| `'seat-assets.*'` prefix | `EveIndustryApiService` | Different cache key prefix (inconsistent!) |
| `https://market.fuzzwork.co.uk/aggregates/` | `MarketPriceService`, `EveIndustryApiService` | Fuzzwork market API URL |
| `https://www.fuzzwork.co.uk/blueprint/api/` | `ReactionDataService` | Fuzzwork blueprint API URL |
| `http://api.eve-industry.org/` | `EveIndustryApiService` | Eve Industry API URL (HTTP not HTTPS!) |
| `https://esi.evetech.net/latest/` | `MarketPriceService`, `MarketSyncService` | ESI base URL |
| Reaction product name arrays | `ReactionDataService` | 41 hardcoded product names |
| Rig bonus values | `CostCalculatorService`, `ReactionCalculatorService` | EVE game mechanic constants |
| Structure bonuses (`Tatara`) | `ReactionCalculatorService` | Hardcoded structure name/bonus |
| `0.04` SCC surcharge | `ReactionCalculatorService` (comment) | Game constant |
| `500000` (usleep) | `ReactionDataService` | API rate-limit delay |
| `100000` (usleep) | `MarketSyncService` | API rate-limit delay |

### 3.2 New Config File: `config/seat-assets.php`

```php
<?php
// config/seat-assets.php

return [

    /*
    |--------------------------------------------------------------------------
    | EVE Online Region & Location IDs
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        // The Forge (Jita) region ID — used as default market data source
        'market_region_id' => (int) env('SEAT_ASSETS_MARKET_REGION', 10000002),

        // IDs above this threshold are treated as structure IDs, below as region IDs
        'structure_id_threshold' => 100000000,
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Endpoints
    |--------------------------------------------------------------------------
    */
    'apis' => [
        'fuzzwork_market'    => env('SEAT_ASSETS_FUZZWORK_MARKET_URL', 'https://market.fuzzwork.co.uk/aggregates/'),
        'fuzzwork_blueprint' => env('SEAT_ASSETS_FUZZWORK_BP_URL', 'https://www.fuzzwork.co.uk/blueprint/api/blueprint.php'),
        'eve_industry'       => env('SEAT_ASSETS_EVE_INDUSTRY_URL', 'https://api.eve-industry.org'),
        'esi_base'           => env('SEAT_ASSETS_ESI_URL', 'https://esi.evetech.net/latest'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTLs (in seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'prefix'              => 'seat-assets',
        'market_prices'       => (int) env('SEAT_ASSETS_CACHE_MARKET', 900),      // 15 minutes
        'adjusted_prices'     => (int) env('SEAT_ASSETS_CACHE_ADJUSTED', 3600),   // 1 hour
        'cost_index'          => (int) env('SEAT_ASSETS_CACHE_COSTINDEX', 3600),  // 1 hour
        'reaction_formula'    => (int) env('SEAT_ASSETS_CACHE_FORMULA', 604800),  // 7 days
        'reaction_type_ids'   => (int) env('SEAT_ASSETS_CACHE_TYPEIDS', 86400),   // 1 day
        'volume_sync'         => (int) env('SEAT_ASSETS_CACHE_VOLUME', 86400),    // 1 day
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting (microseconds between requests)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'fuzzwork_delay_us' => 500000,  // 0.5 seconds
        'esi_delay_us'      => 100000,  // 0.1 seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Reaction Product Lists
    |--------------------------------------------------------------------------
    | These map to EVE SDE invTypes.typeName values. Override to add/remove
    | reactions tracked by the calculator.
    |--------------------------------------------------------------------------
    */
    'reactions' => [
        'simple' => [
            'Caesarium Cadmide', 'Carbon Polymers', 'Ceramic Powder',
            'Crystallite Alloy', 'Dysporite', 'Fernite Alloy', 'Ferrofluid',
            'Fluxed Condensates', 'Hexite', 'Hyperflurite', 'Neo Mercurite',
            'Platinum Technite', 'Rolled Tungsten Alloy', 'Silicon Diborite',
            'Solerium', 'Sulfuric Acid', 'Titanium Chromide', 'Vanadium Hafnite',
            'Prometium', 'Thulium Hafnite', 'Promethium Mercurite',
            'Carbon Fiber', 'Thermosetting Polymer', 'Oxy-Organic Solvents',
        ],
        'complex' => [
            'Titanium Carbide', 'Crystalline Carbonide', 'Fernite Carbide',
            'Tungsten Carbide', 'Sylramic Fibers', 'Fullerides',
            'Phenolic Composites', 'Nanotransistors', 'Hypersynaptic Fibers',
            'Ferrogel', 'Fermionic Condensates', 'Plasmonic Metamaterials',
            'Terahertz Metamaterials', 'Photonic Metamaterials',
            'Nonlinear Metamaterials', 'Pressurized Oxidizers',
            'Reinforced Carbon Fiber',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Industry Modifiers (EVE game mechanics)
    |--------------------------------------------------------------------------
    | Rig and structure bonuses. These change with EVE patches, so keeping
    | them configurable avoids code changes on game balance updates.
    |--------------------------------------------------------------------------
    */
    'industry' => [
        'rig_bonuses' => [
            // Manufacturing rigs
            't1_me' => ['high' => 0.98, 'low' => 0.976, 'null' => 0.952],
            't2_me' => ['high' => 0.976, 'low' => 0.9524, 'null' => 0.904],
            // Reaction rigs
            't1_medium' => [
                'material' => ['highsec' => 0.020, 'lowsec' => 0.024],
                'time'     => 0.20,
            ],
            't2_medium' => [
                'material' => ['highsec' => 0.024, 'lowsec' => 0.0312],
                'time'     => 0.24,
            ],
            't1_large' => [
                'material' => ['highsec' => 0.024, 'lowsec' => 0.0288],
                'time'     => 0.24,
            ],
            't2_large' => [
                'material' => ['highsec' => 0.030, 'lowsec' => 0.036],
                'time'     => 0.288,
            ],
        ],
        'structure_bonuses' => [
            'Tatara' => ['material' => 0.01, 'time' => 0.25],
        ],
        'engineering_complex_te_rig_bonus' => 0.80,
        'scc_surcharge' => 0.04,
    ],
];
```

### 3.3 Refactored Service Examples Using Config

**`ReactionDataService` — before/after:**
```php
// BEFORE
protected $simpleReactions = ['Caesarium Cadmide', ...]; // 24 items hardcoded
protected $complexReactions = ['Titanium Carbide', ...]; // 17 items hardcoded

// AFTER
public function getCategories(): array
{
    return [
        'simple'  => config('seat-assets.reactions.simple'),
        'complex' => config('seat-assets.reactions.complex'),
    ];
}

public function getAllReactionNames(): array
{
    return array_merge(
        config('seat-assets.reactions.simple'),
        config('seat-assets.reactions.complex')
    );
}
```

**`MarketPriceService` — before/after:**
```php
// BEFORE
protected $jitaRegionId = 10000002;
$url = "https://market.fuzzwork.co.uk/aggregates/?region=...";
->where('updated_at', '>', $now->copy()->subMinutes(15))

// AFTER
public function getPrices(array $typeIds, ?int $locationId = null)
{
    $threshold = config('seat-assets.defaults.structure_id_threshold');
    $defaultRegion = config('seat-assets.defaults.market_region_id');

    if (! $locationId || $locationId < $threshold) {
        return $this->getRegionPrices($typeIds, $locationId ?: $defaultRegion);
    }

    return $this->getStructurePrices($typeIds, $locationId);
}

protected function getRegionPrices(array $typeIds, int $regionId)
{
    $cacheTtl = config('seat-assets.cache.market_prices');
    $baseUrl  = config('seat-assets.apis.fuzzwork_market');
    $prefix   = config('seat-assets.cache.prefix');

    $cached = MarketPriceCache::whereIn('type_id', $typeIds)
        ->where('region_id', $regionId)
        ->where('updated_at', '>', now()->subSeconds($cacheTtl))
        ->get()
        ->keyBy('type_id');
    // ...
}
```

**`EveIndustryApiService` — before/after:**
```php
// BEFORE — uses HTTP (insecure!) and hardcoded region
Http::get('http://api.eve-industry.org/system-cost-index.xml?name=...');
Http::get('https://market.fuzzwork.co.uk/aggregates/?region=10000002&types=...');

// AFTER
public function getSystemCostIndex(string $systemName): array
{
    $prefix = config('seat-assets.cache.prefix');
    $ttl    = config('seat-assets.cache.cost_index');
    $base   = config('seat-assets.apis.eve_industry');

    return Cache::remember("{$prefix}.industry.costindex.{$systemName}", $ttl, function () use ($systemName, $base) {
        try {
            $response = Http::get("{$base}/system-cost-index.xml", ['name' => $systemName]);
            // ...
        } catch (Exception $e) {
            return [];
        }
    });
}
```

### 3.4 Cache Key Prefix Inconsistency Fix

The codebase uses two different prefixes — `eic.*` and `seat-assets.*`. Standardize to use `config('seat-assets.cache.prefix')`:

```php
// BEFORE (inconsistent)
Cache::remember('eic.adjusted_prices', ...)
Cache::remember('eic.reaction.formula.' . $id, ...)
Cache::remember('seat-assets.industry.costindex.' . $name, ...)

// AFTER (consistent)
$p = config('seat-assets.cache.prefix');  // 'seat-assets'
Cache::remember("{$p}.adjusted-prices", ...)
Cache::remember("{$p}.reaction.formula.{$id}", ...)
Cache::remember("{$p}.industry.costindex.{$name}", ...)
```

---

## 4. Service Provider Review

Based on standard SeAT 5.x plugin conventions, the Service Provider should register the following. Here is a corrected/complete version:

```php
<?php
// src/SeatAssetsServiceProvider.php

namespace Apokavkos\SeatAssets;

use Illuminate\Support\ServiceProvider;

class SeatAssetsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ── Routes ──────────────────────────────────────────────
        // SeAT plugins must load routes with the 'web' and 'seat:auth'
        // middleware groups applied.
        if (! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/Http/routes.php');
        }

        // ── Views ──────────────────────────────────────────────
        // Register with the 'seat-assets' namespace so Blade can
        // reference them as @include('seat-assets::view-name')
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'seat-assets');

        // ── Migrations ─────────────────────────────────────────
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // ── Translations (if applicable) ────────────────────────
        $this->loadTranslationsFrom(__DIR__ . '/resources/lang', 'seat-assets');

        // ── Config Publishing ──────────────────────────────────
        $this->publishes([
            __DIR__ . '/../config/seat-assets.php' => config_path('seat-assets.php'),
        ], 'seat-assets-config');
    }

    public function register(): void
    {
        // ── Merge Config ───────────────────────────────────────
        // Ensures defaults are always available even if user
        // hasn't published the config file.
        $this->mergeConfigFrom(
            __DIR__ . '/../config/seat-assets.php',
            'seat-assets'
        );

        // ── Bind Services to Container ─────────────────────────
        // Singletons for services that hold no per-request state.
        $this->app->singleton(Services\UserResolverService::class);
        $this->app->singleton(Services\MarketPriceService::class);
        $this->app->singleton(Services\ReactionDataService::class);
        $this->app->singleton(Services\BlueprintService::class);
        $this->app->singleton(Services\CostCalculatorService::class);
        $this->app->singleton(Services\ReactionCalculatorService::class);
        $this->app->singleton(Services\EveIndustryApiService::class);

        // ── Register Menu ──────────────────────────────────────
        // SeAT uses a specific menu registration pattern.
        // This should match what's in your Menu/package.sidebar.php
        $this->registerMenuEntries();
    }

    /**
     * Register sidebar menu items for the SeAT UI.
     */
    protected function registerMenuEntries(): void
    {
        // If your package has a Menu/package.sidebar.php file:
        if (file_exists(__DIR__ . '/Menu/package.sidebar.php')) {
            $this->mergeConfigFrom(
                __DIR__ . '/Menu/package.sidebar.php',
                'package.sidebar'
            );
        }
    }
}
```

### 4.1 Issues Found in Current Provider (Likely)

Based on common SeAT plugin mistakes:

1. **Routes file**: Must apply `web` and `auth` middleware. The routes file should wrap all routes:
   ```php
   // src/Http/routes.php
   Route::group([
       'namespace'  => 'Apokavkos\SeatAssets\Http\Controllers',
       'prefix'     => 'seat-assets',
       'middleware' => ['web', 'auth', 'locale'],
   ], function () {
       Route::get('/assets', 'AssetController@index')->name('seat-assets.assets');
       Route::get('/reactions', 'ReactionController@index')->name('seat-assets.reactions');
       // ... etc
   });
   ```

2. **View namespace**: Must match what Blade templates reference. If templates use `@extends('seat-assets::layouts.app')`, the namespace registered in `loadViewsFrom` must be `'seat-assets'`.

3. **Migration path**: Must point to the actual directory containing migration files. Verify the path is `__DIR__ . '/database/migrations'` (relative to the ServiceProvider file location).

4. **Config merging**: Without `mergeConfigFrom`, any `config('seat-assets.*')` call will return `null` on fresh installs where the user hasn't published the config.

5. **Composer autoload**: The `composer.json` must have the PSR-4 autoload entry pointing at `src/`:
   ```json
   {
       "autoload": {
           "psr-4": {
               "Apokavkos\\SeatAssets\\": "src/"
           }
       },
       "extra": {
           "laravel": {
               "providers": [
                   "Apokavkos\\SeatAssets\\SeatAssetsServiceProvider"
               ]
           }
       }
   }
   ```

---

## 5. Draft README.md

The following is a complete, GitHub-ready README:

---

````markdown
# SeAT Asset Manager

A [SeAT](https://github.com/eveseat/seat) 5.x plugin for EVE Online industry management — covering assets, blueprints, reactions, stockpiles, and market tracking.

## Features

- **Unified Asset Browser** — View all character and corporation assets in a single searchable DataTable
- **Blueprint Library** — Import and browse character/corporation blueprints with ME/TE data
- **Manufacturing Calculator** — Calculate materials, time, job costs, and profit margins with facility/rig modifiers
- **Reaction Calculator** — Full reaction chain calculation with rig and structure bonuses
- **Stockpile Manager** — Define target inventories, track deficits, and generate buy/build lists
- **Market Price Tracking** — Cached pricing from Fuzzwork, ESI, and player-owned structures
- **Logistics Reports** — Cascading material requirement breakdowns with health scores

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | ^8.1    |
| SeAT       | ^5.0    |
| Laravel    | ^10.0   |

## Installation

### 1. Require the package via Composer

```bash
composer require apokavkos/seat-assets
```

If the package is not yet on Packagist, add the repository to your SeAT installation's `composer.json` first:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/apokavkos/mohrg"
        }
    ]
}
```

Then run:

```bash
composer require apokavkos/seat-assets:dev-main
```

### 2. Publish the configuration (optional)

```bash
php artisan vendor:publish --tag=seat-assets-config
```

This creates `config/seat-assets.php` where you can customize API endpoints, cache TTLs, default market region, and reaction product lists.

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Clear caches

```bash
php artisan config:cache
php artisan route:cache
```

The plugin will appear in the SeAT sidebar automatically.

## Configuration

After publishing, edit `config/seat-assets.php`:

```php
return [
    'defaults' => [
        // Change the default market region (default: The Forge / Jita)
        'market_region_id' => 10000002,
    ],

    'cache' => [
        // Adjust cache lifetimes (in seconds)
        'market_prices'    => 900,    // 15 min
        'reaction_formula' => 604800, // 7 days
    ],

    'apis' => [
        // Override API endpoints if you run local proxies
        'fuzzwork_market' => 'https://market.fuzzwork.co.uk/aggregates/',
        'esi_base'        => 'https://esi.evetech.net/latest',
    ],

    'reactions' => [
        // Add or remove tracked reaction products
        'simple'  => [ /* ... */ ],
        'complex' => [ /* ... */ ],
    ],
];
```

You can also set values via environment variables:

```env
SEAT_ASSETS_MARKET_REGION=10000002
SEAT_ASSETS_CACHE_MARKET=900
SEAT_ASSETS_FUZZWORK_MARKET_URL=https://market.fuzzwork.co.uk/aggregates/
```

## Permissions

The plugin respects SeAT's built-in permission system. Ensure your users have the appropriate character and corporation asset/industry scopes granted through the SeAT admin panel.

For structure market data, at least one character token must have the `esi-markets.structure_markets.v1` ESI scope.

## Troubleshooting

**Market prices not updating?**
- Check that at least one ESI token has market scopes
- Verify the Fuzzwork API is reachable from your server
- Check Laravel logs: `tail -f storage/logs/laravel.log | grep seat-assets`

**Reactions showing empty formulas?**
- Run the warmup command to pre-cache reaction data from Fuzzwork:
  ```bash
  php artisan seat-assets:warmup-reactions
  ```

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License

[MIT](LICENSE)
````

---

## Appendix: Recommended File Structure

```
mohrg/
├── config/
│   └── seat-assets.php              ← NEW: centralized config
├── src/
│   ├── SeatAssetsServiceProvider.php ← UPDATED: proper boot/register
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AssetController.php       (keep thin)
│   │   │   ├── BlueprintController.php   (delegates to services)
│   │   │   ├── ReactionController.php    (delegates to services)
│   │   │   └── StockpileController.php   (delegates to services)
│   │   └── routes.php
│   ├── Menu/
│   │   └── package.sidebar.php
│   ├── Models/
│   │   ├── MarketHub.php
│   │   ├── MarketPriceCache.php
│   │   ├── MarketSnapshot.php
│   │   └── Stockpile.php
│   ├── Services/
│   │   ├── UserResolverService.php        ← NEW: extracted from controllers
│   │   ├── BlueprintImportService.php     (refactored to use UserResolver)
│   │   ├── BlueprintService.php
│   │   ├── CostCalculatorService.php      (refactored to use config)
│   │   ├── EveIndustryApiService.php      (refactored to use config)
│   │   ├── MarketPriceService.php         (refactored to use config)
│   │   ├── MarketSyncService.php          (refactored to use config)
│   │   ├── ReactionCalculatorService.php  (refactored to use config)
│   │   ├── ReactionDataService.php        (refactored to use config)
│   │   ├── StockpileLogisticsService.php  (refactored, no auth() calls)
│   │   └── DataTables/
│   │       └── AllAssetsDataTable.php
│   ├── database/
│   │   └── migrations/
│   └── resources/
│       ├── views/
│       └── lang/
├── composer.json
├── README.md                         ← NEW: full install instructions
└── LICENSE
```
