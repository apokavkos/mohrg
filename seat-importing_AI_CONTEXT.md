
## 7. Deep Troubleshooting: "The All-Red Dashboard"
If the dashboard shows all items as "Missing" (Red) and stock levels are 0 across all hubs:

### Vector A: Numeric Out-of-Range (Database)
- **Symptom**: Importer crashes or logs `SQLSTATE[22003]: Numeric value out of range`.
- **Cause**: Some items (especially Blueprint Originals) have near-zero Jita prices, resulting in astronomical Markup % values that exceed standard database column sizes.
- **Fix**: Widen the `markup_pct`, `weekly_profit`, and `import_cost` columns to `double` in the `market_item_data` table.
- **Maintenance**: Truncate the table and re-run the import to clear corrupt records.

### Vector B: Fuzzwork CSV Mapping (Stock & Prices)
- **Symptom**: Parsed rows are 0 or stock is always 0.
- **Rule 1**: Fuzzwork's `aggregatecsv` uses a pipe-delimited header (`region|type|isBuy`).
- **Rule 2**: `isBuy` column: `true` = BUY orders, `false` = SELL orders. We MUST filter for `false` to get stock and sell prices.
- **Rule 3**: **Stock Mapping**: Map `current_stock` to the `volume` column (index 6), NOT `ordercount` (index 7). Index 7 is the number of people selling; Index 6 is the total number of items available.

### Vector C: Token Migration (Fresh Installs)
- **Symptom**: `invalid_grant` or `Refresh token missing` errors.
- **Cause**: Standard ESI calls for structure markets require valid refresh tokens. If tokens are blank after a DB restore, you must re-SSO the characters to repopulate the `token` column in `refresh_tokens`.

### Vector D: Sidebar Menu Ordering
- **Symptom**: A new menu appears below others despite intended priority.
- **Cause**: SeAT merges `package.sidebar` configs and sorts them alphabetically by their top-level keys.
- **Fix**: Use numeric prefixes for force-sorting. For example, `000nexus` will always beat `00market` because `0` comes before `m`.
- **Rule**: If a menu must be at the very top, use the `000` prefix.

### Vector E: Route Segment Collisions
- **Symptom**: Custom menu entries disappear or are overridden by core SeAT pages.
- **Cause**: Using a `route_segment` that already exists in the core (like `seat-dashboard`) can cause the Laravel breadcrumb or active-menu logic to hide the custom entry.
- **Fix**: Always use a unique, descriptive string for the `route_segment` (e.g., `nexus` instead of `seat-dashboard`). This ensures the menu remains visible and correctly "active" when clicked.
