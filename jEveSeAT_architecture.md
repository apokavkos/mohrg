# jEveSeAT: SeAT + Headless jEveAssets Integration

## Project Overview
jEveSeAT is a specialized Laravel plugin for the SeAT EVE Online management system. It bridges the gap between SeAT's enterprise-grade ESI token management and the advanced industrial calculations provided by the jEveAssets Java engine.

## Strategic Architecture

### 1. The Token Bridge
SeAT acts as the **source of truth** for ESI refresh tokens. A custom Artisan command (`php artisan jeveseat:sync-tokens`) extracts these tokens and formats them into the `accounts.xml` format required by jEveAssets.
- **Path:** `storage/app/jeveassets/.jeveassets/accounts.xml`
- **Logic:** Aggregates all character refresh tokens linked to SeAT users and prepares them for the Java engine.

### 2. Headless Math Engine
A headless jEveAssets container (Docker) runs the `.jar` using the Java headless flag. It mounts the SeAT storage volume to access the synced tokens and output its data exports.
- **Engine:** `java -Djava.awt.headless=true -jar jeveassets.jar`
- **Input:** `accounts.xml`
- **Output:** `assets.json` (Custom export format or converted from CSV)

### 3. SDE Enrichment & Presentation
The SeAT controller reads the `assets.json` output. To maintain data integrity and reduce payload size in the export, jEveAssets only provides `TypeIDs`. The plugin's controller uses SeAT's **local SDE database** (specifically the `invTypes` and `invGroups` tables) to resolve human-readable names and groups before passing data to the frontend.

### 4. High-Density Frontend (AG Grid)
The presentation layer uses **AG Grid Community Edition** with the `ag-theme-quartz-dark` theme.
- **Density:** `rowHeight: 28`, `headerHeight: 32` for maximum data visibility.
- **Features:** Client-side sorting, filtering, and pagination.
- **Formatting:** Custom ISK currency formatter for asset values.

## Deployment Steps
1. Install the plugin via composer (local path).
2. Configure a Docker container for jEveAssets mounting `storage/app/jeveassets`.
3. Schedule `php artisan jeveseat:sync-tokens` and the jEveAssets update command via CRON.
4. Access the inventory via `/jeveseat`.
