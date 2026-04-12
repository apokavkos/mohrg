# Project Context: Eve Intelligence Center (EIC)

## 1. Overview
EIC is an industrial logistics engine for EVE Online, implemented as a SeAT plugin. Its primary goal is to optimize continuous-throughput manufacturing pipelines based on the "Stockpile Churn" philosophy, maintaining threshold levels across intermediate and final products.

## 2. Infrastructure & Environment
- **Host:** Hetzner Cloud (Ubuntu 24.04, 16GB RAM)
- **Primary Domain:** `apokavkos.com`
- **Architecture:** Containerized using Docker and `docker-compose`.
- **SeAT (`seat-docker`):** Core EVE API manager using MariaDB.
- **jEveSeAT:** SeAT plugin providing a bridge to the jEveAssets Java engine for high-density inventory tracking.
- **EVE SDE (`eve-sde`):** Standalone MariaDB instance for Static Data Export.
- **Intelligence Bridge (MCP Server):** Python-based FastMCP server for direct SQL queries against SeAT and SDE databases, exposed at `evemcp.apokavkos.com/sse`.
- **Security:** Authelia (`sso.apokavkos.com`) provides OIDC for the MCP server.

## 3. Core Mandate & Logic
- **Stockpile Churn:** Focus on maintaining inventory thresholds rather than one-off batch profits.
- **Industry Calculator:**
    - `Effective Inventory = Current Assets + In-Flight Industry Job Outputs`.
    - **Material Cascading:** Recursive fetching of requirements (assumes ME 10 / TE 20).
    - **Data Sourcing:** Fuzzwork API (Blueprints), Market Price Cache (Prices), ESI (System Index).
- **Logistics Engine:**
    - `Status = GREEN` if `Effective Inventory >= Target Threshold`.
    - `Status = RED` if `Effective Inventory < Target Threshold`.
    - **Cascading Deficits:** High-tier deficits propagate to component thresholds.

## 4. Technical Stack
- **Plugin Type:** SeAT Plugin (Laravel-based).
- **jEveSeAT Integration:** Integrated high-density inventory reporting via AG Grid.
- **PHP Namespace:** `Apokavkos\EveIntelligenceCenter`.
- **Composer Package:** `apokavkos/eve-intelligence-center`.
- **Data Persistence:** EIC-specific tables (`eic_stockpiles`, `eic_stockpile_items`) + SeAT core tables.

## 5. Key Workflows
- **BUY Action:** Summarize raw material deficits.
- **BUILD Action:** Summarize buildable component deficits.
- **Bottlenecks:** Identify raw materials stalling multiple manufacturing jobs.
- **Pipeline Health:** `(Green Stockpiles / Total Stockpiles) * 100`.
