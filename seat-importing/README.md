# seat-importing

A **SeAT 5** plugin for EVE Online market hub import analysis and stock monitoring, inspired by Goonmetrics. Import CSV market data dumps (Fuzzwork, Tycoon) and get instant insight into markup opportunities, low-stock alerts, and weekly profit estimates across any number of player-run market hubs.

---

## Features

- **Hub-based analysis** – manage multiple market hubs (e.g. Jita, Amarr, alliance staging), each with its own ISK/m³ freight cost
- **Four metric views**
  - *≥ Threshold Markup* – items with a profitable import spread
  - *Low Stock* – items running below target stock level
  - *Top Markup %* – highest percentage margin items
  - *Top Weekly Profit* – highest absolute ISK/week items
- **CSV import** – supports Fuzzwork aggregates CSV and Tycoon market CSV formats
- **Artisan command** – `seat:importing:import` for cron-driven or manual updates
- **Queue-based job** – `ProcessMarketImport` for async large imports
- **SDE integration** – resolves type names and volumes from SeAT's bundled SDE (`sde` DB connection)
- **Bootstrap 4/5 + Font Awesome** – inherits SeAT's styling with zero extra CSS dependencies

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ≥ 8.1 |
| SeAT | ^5.0 |
| Laravel | ^10.0 |

---

## Installation

### 1. Add to SeAT's `composer.json`

```bash
composer require apokavkos/seat-importing
```

Or, for local development, add a path repository to your SeAT root `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/seat-importing"
    }
]
```

Then run `composer require apokavkos/seat-importing:@dev`.

### 2. Run migrations

```bash
php artisan migrate
```

This creates four tables: `market_hubs`, `market_item_data`, `market_settings`, and `market_import_logs`.

### 3. Publish config (optional)

```bash
php artisan vendor:publish --tag=seat-importing-config
```

This copies `config/seat-importing.php` to your application's `config/` directory so you can customise defaults.

### 4. Assign permissions

In SeAT's Role management, assign the following permissions to roles:

| Permission | Description |
|---|---|
| `seat-importing.view` | View dashboards and metric tables |
| `seat-importing.manage` | Create/edit/delete hubs and change settings |
| `seat-importing.import` | Trigger CSV imports from the web UI |

---

## Configuration

All values can be set via `.env` or by publishing and editing `config/seat-importing.php`.

| `.env` key | Default | Description |
|---|---|---|
| `SEAT_IMPORTING_ISK_PER_M3` | `1000.0` | Global freight cost in ISK per m³ |
| `SEAT_IMPORTING_JITA_REGION` | `10000002` | Jita region ID (The Forge) |
| `SEAT_IMPORTING_JITA_STATION` | `60003760` | Jita 4-4 station ID |
| `SEAT_IMPORTING_MARKUP_THRESHOLD` | `25.0` | Minimum markup % to appear in the markup table |
| `SEAT_IMPORTING_STOCK_LOW_THRESHOLD` | `50.0` | Stock % below which an item appears in low-stock table |
| `SEAT_IMPORTING_CACHE_METRICS` | `600` | Seconds to cache hub metric results |
| `SEAT_IMPORTING_CACHE_HUBS` | `3600` | Seconds to cache hub list |
| `SEAT_IMPORTING_CACHE_PRICES` | `900` | Seconds to cache price data |
| `SEAT_IMPORTING_SOURCE` | `fuzzwork_csv` | Default import source (`fuzzwork_csv` or `tycoon_csv`) |
| `SEAT_IMPORTING_FUZZWORK_URL` | Fuzzwork aggregates URL | Base URL for Fuzzwork market data |
| `SEAT_IMPORTING_PATH` | `storage/app/seat-importing` | Local path where CSV files are stored |
| `SEAT_IMPORTING_BATCH` | `500` | DB upsert batch size |

---

## Usage

### Artisan Command

```bash
# Import all active hubs using the default source
php artisan seat:importing:import

# Import a specific hub (hub ID 1) from a local Fuzzwork CSV
php artisan seat:importing:import --hub=1 --source=fuzzwork_csv --file=/path/to/market.csv

# Validate a Tycoon CSV without writing to the database
php artisan seat:importing:import --source=tycoon_csv --file=/path/to/tycoon.csv --dry-run

# Override the Jita region for this run
php artisan seat:importing:import --jita-region=10000002
```

#### Fuzzwork CSV format

Column order expected:
```
typeID, buy_percentile, buy_max, buy_avg, buy_stddev, buy_median, buy_volume,
sell_percentile, sell_min, sell_avg, sell_stddev, sell_median, sell_volume,
buy_orders, sell_orders
```

Download from: `https://market.fuzzwork.co.uk/aggregates/?region=10000002&types=...`

#### Tycoon CSV format

Column order expected:
```
typeid, region_id, buy_max, buy_volume, sell_min, sell_volume, timestamp
```

### Web UI

Navigate to **Market Importing → Dashboard** in SeAT's sidebar. Select a hub from the dropdown to view its metric tables. Click any item name to open a detail modal with SDE information and import analysis.

To trigger an import from the UI (requires `seat-importing.import` permission), go to **Settings → Run Import**.

### Scheduled Import (cron)

Add to SeAT's `app/Console/Kernel.php` (or your scheduler):

```php
$schedule->command('seat:importing:import')->dailyAt('03:00');
```

---

## Market Maths

```
import_cost   = volume_m3 × isk_per_m3
markup_pct    = ((local_sell - jita_sell - import_cost) / jita_sell) × 100
weekly_profit = (local_sell - jita_sell - import_cost) × weekly_volume
stock_pct     = (current_stock / weekly_volume) × 100
days_supply   = current_stock / (weekly_volume / 7)
```

---

## Database Tables

| Table | Purpose |
|---|---|
| `market_hubs` | Hub definitions (name, region, freight cost) |
| `market_item_data` | Per-hub per-item metrics, refreshed on each import |
| `market_settings` | Key/value settings, optionally scoped to a hub |
| `market_import_logs` | Audit log of every import run |

---

## License

MIT — see [LICENSE](LICENSE).
