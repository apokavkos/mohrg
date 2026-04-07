# Eve Intelligence Center (EIC) - AI Context Reference

This document provides technical context for an AI assistant to understand the logic and data processing of the Eve Intelligence Center (EIC) plugin for SeAT.

## Core Mandate
EIC is an industrial logistics engine. Its primary goal is to optimize continuous-throughput manufacturing pipelines based on the "Stockpile Churn" philosophy. It does not focus on one-off batch profit, but on maintaining threshold levels across intermediate and final products.

## 1. Industry Calculator Logic
- **Effective Inventory:** Calculated as `Current Assets + In-Flight Industry Job Outputs`.
- **Material Cascading:** When "Build sub-components" is enabled, the calculator recursively fetches requirements. It assumes a standard ME 10 / TE 20 for sub-components.
- **Data Sourcing:**
    - **Blueprints:** Fetched from Fuzzwork API using Product TypeID.
    - **Prices:** Fetched from Market Price Cache (SeAT) or EveMarketer/ESI.
    - **System Index:** Dynamic lookup from ESI via `EveIndustryApiService`.

## 2. Stockpile Logistics Engine
- **Philosophy:** "Green vs. Red" logic.
- **Rules:**
    1. If `Effective Inventory >= Target Threshold`, status is **GREEN**.
    2. If `Effective Inventory < Target Threshold`, status is **RED**.
    3. **Cascading Deficits:** If a high-tier product (e.g., Ishtar) is RED, the engine calculates the deficit for all components (e.g., Construction Components). These deficits are added to the component thresholds before their own RED/GREEN status is evaluated.
- **Location Scoping:** Inventory checks can be scoped globally, to a specific Solar System, or to a specific Structure/Station TypeID.

## 3. Data Schema References (High-Level)
- `eic_stockpiles`: (id, user_id, name, location_id)
- `eic_stockpile_items`: (id, stockpile_id, type_id, quantity, location_id)
- `character_blueprints`: (item_id, type_id, material_efficiency, time_efficiency, runs, quantity)
- `character_assets` / `corporation_assets`: Used for inventory comparison.
- `industry_jobs`: Used to calculate "In-Flight" inventory.

## 4. Key Workflows for AI Analysis
- **Action Required: BUY:** Summarize raw material deficits that cannot be built.
- **Action Required: BUILD:** Summarize buildable component deficits.
- **Bottlenecks:** Identify raw materials that are stalling multiple upper-tier manufacturing jobs.
- **Pipeline Health:** `(Green Stockpiles / Total Stockpiles) * 100`.

## 5. UI Capabilities
- **Export Formats:** Supports EVE Online Multi-buy text format (Item Name [tab/space] Quantity).
- **Persistence:** All calculator settings (system, facility, rigs, tax) are saved in browser local storage.
- **SDE Focus:** Automated search field focusing for rapid entry.
