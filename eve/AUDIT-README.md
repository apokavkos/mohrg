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
