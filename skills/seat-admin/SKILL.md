---
name: seat-admin
description: Specialized instructions for maintaining SeAT v5 Docker environments and custom standalone plugins. Use when working on the seat-apok codebase to ensure server stability, correct ESI client usage, and proper sidebar configuration.
---

# SeAT 5 Administrative Workflows

## Server Stability (Mandatory)
- **Cache Clearing**: After ANY code change, you MUST clear all caches to avoid 500 errors:
  - `php artisan config:clear`
  - `php artisan route:clear`
  - `php artisan view:clear`
  - `php artisan cache:clear`
- **Provider Order**: Register custom ServiceProviders in `config/app.php` BEFORE `WebServiceProvider`.

## Sidebar & Routing
- **Sorting**: Use `000` prefix keys (e.g., `000nexus`) to force a menu to the top.
- **Collisions**: Use unique `route_segment` values (e.g., `nexus`) to prevent core SeAT overrides.
- **Namespacing**: Use `::` separator for route names (e.g., `seat-dashboard::index`) to match established project conventions.

## Technical Standards
- **PHP Injection**: Use single-quoted heredocs (`<<'EOF'`) for terminal-based file writes to prevent variable mangling.
- **ESI Client**: Resolve `Seat\Services\Contracts\EsiClient` from the container. NEVER use raw `Eseye` objects.
- **Location Resolution**: NPC stations (`StaStation`) lack a `solar_system` relationship. Resolve system names manually using `SolarSystem::where('system_id', $station->solarSystemID)->value('name')`.

## Troubleshooting SOPs
- **Logs**: `docker exec seat-front-1 tail -f /var/www/seat/storage/logs/laravel-$(date +%Y-%m-%d).log`
- **Stock Issues**: Check `AI_CONTEXT.md` for Fuzzwork CSV mapping rules (Vector B).
- **500 Errors**: Check for duplicate `use` statements (Vector G) and missing route names in `package.sidebar.php` (Vector I).
