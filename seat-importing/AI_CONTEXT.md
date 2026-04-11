# AI Operational Context: `seat-importing` Plugin

This document provides the necessary architectural and operational context for an AI agent to troubleshoot, maintain, and extend this SeAT 5 plugin.

## 1. Architectural Overview
- **Type**: SeAT 5 Plugin (Laravel 10+)
- **Base Class**: `Seat\Services\AbstractSeatPlugin`
- **Namespace**: `Apokavkos\SeatImporting`
- **Primary Entry Point**: `SeatImportingServiceProvider.php`

## 2. Core Logic & Formulas
The plugin calculates EVE Online market metrics using the following logic:
- **Import Cost**: `volume_m3 * isk_per_m3` (Freight cost from Jita to Hub).
- **Markup %**: `((local_sell - jita_sell - import_cost) / jita_sell) * 100`.
- **Weekly Profit**: `(local_sell - jita_sell - import_cost) * weekly_volume`.
- **Stock %**: `(current_stock / weekly_volume) * 100`.

## 3. High-Performance Caching (Critical)
To ensure near-instant dashboard loads, this plugin uses **Background Cache Warming**:
- **Mechanism**: The `MarketMetricsService::warmHubCache($hubId)` method pre-calculates the four dashboard tables (Markup, Low Stock, Top Profits, Top Weekly).
- **Trigger**: Automatically called at the end of every `seat:importing:import` command and `ProcessMarketImport` job.
- **Cache Key**: `seat-importing:hub_metrics:{hubId}`.
- **Troubleshooting**: If dashboard data looks "stale" or is 0, run `php artisan seat:importing:import --download` to force a re-calculation and cache update.

## 4. Dependencies
- **External API**: Fuzzwork Aggregates (`https://market.fuzzwork.co.uk/aggregates/region/{id}.csv`).
- **SeAT Core**: 
    - `Seat\Services\Contracts\EsiClient`: Used for all ESI calls (fetching system cost indexes).
    - `Seat\Eveapi\Models\Sde`: Used for System/Region/Structure lookups.
- **JS Libraries**: Select2 (for searchable dropdowns), DataTables (for dashboard tables).

## 5. Permissions (ACL)
- `market.import`: Required to view the dashboard and trigger manual imports.
- `market.settings`: Required to create/edit hubs and change global ISK/m3 values.
- **Requirement**: Custom providers **must** load before `WebServiceProvider` in `config/app.php` to ensure permissions are registered in the Laravel Gate.

## 6. Common Troubleshooting Vectors
- **500 Error on Sidebar**: Usually a missing `label` key in `package.sidebar.php` or a permission registration failure.
- **Empty Hub Dropdown**: Ensure `is_active` is true in the `market_hubs` table.
- **0 ISK Prices**: Check `MarketImportLog` for failures. Ensure the Fuzzwork CSV was downloaded correctly to `storage/app/seat-importing/`.
- **Maintenance Command**:
  ```bash
  php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear
  ```
