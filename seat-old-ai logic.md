# SeAT 5 Project Context

## Critical Operational Rules
- **Cache Management**: Always run `php artisan config:clear`, `route:clear`, `view:clear`, and `cache:clear` after modifying plugins, routes, or sidebar configurations. Failure to do so results in a server-wide 500 error.
- **Sidebar Menu Builder**: 
    - Every sidebar entry must contain a `route_segment` key.
    - Top-level menus without `entries` are prone to validation failures in `AbstractMenu.php`.
- **Permission Sync**: Permissions defined in `registerPermissions()` only merge into the config. They must be explicitly created in the `permissions` table and linked to a `Role` via the `permission_role` pivot table to be effective.
- **Shell Code Injection**: When using `cat` or `sed` to write PHP files, use the `<<'EOF'` syntax to prevent the host shell from mangling PHP variables (e.g., `$request` becoming empty).

## Plugin Inventory
- **SeatDashboard**: `/opt/seat/packages/seat-dashboard` (Namespace: `Apokavkos\SeatDashboard`)
