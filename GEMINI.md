# SeAT 5 AI Operational Context (SeAT-Apok)

This file provides mandatory instructions for AI agents maintaining this SeAT v5 installation and its custom plugins.

## 1. Critical Operational Rules (Server Stability)
- **Cache Management**: After ANY change to plugins, routes, or sidebars, you MUST run:
  `php artisan config:clear`, `route:clear`, `view:clear`, and `cache:clear`.
  *Failure to do this results in immediate 500 Internal Server Errors.*
- **Provider Loading Order**: Custom providers (Dashboard & Importing) **MUST** be registered in `config/app.php` **BEFORE** the `WebServiceProvider`. This ensures permissions are correctly injected into the Laravel Gate.
- **Sidebar Validation**: Every sidebar menu and entry MUST have a `label` key. If `label` is missing, the sidebar renderer will silently fail to display the menu.

## 2. Living Documentation & Evolution (Mandatory)
- **Knowledge Capture**: If you (the AI) discover a new server-specific quirk, fix a persistent 500 error, or identify a new dependency, you **MUST** append that knowledge to this file or `AI_CONTEXT.md` before ending the session.
- **Root Cause Tracking**: Document "Why" a failure happened (e.g., "Fuzzwork CSV format changed to pipe-delimited") to prevent future agents from repeating the same investigation.

## 3. Technical Standards
- **Shell File Edits**: Always use single-quoted heredocs (`<<'EOF'`) when writing PHP files via the terminal to prevent mangling PHP variables.
- **ESI Client**: Always resolve `Seat\Services\Contracts\EsiClient` from the container. Do not use raw `Eseye` objects.
- **Database Precision**: Use `double` for all market metrics to prevent out-of-range errors.

## 4. Plugin Logic
- **Background Cache Warming**: Dashboard tables are pre-calculated. Re-run `php artisan seat:importing:import --download --simulate` if data is stale.
- **Hub Discovery**: Import engine dynamically finds Region IDs via `solar_system_id` or `structure_id`.

## 5. Standard Operating Procedures (SOPs)
- **Log Monitoring**: To see real-time errors, run:
  `docker exec seat-front-1 tail -f /var/www/seat/storage/logs/laravel-$(date +%Y-%m-%d).log`
- **Worker Management**: If imports get stuck, restart the background workers:
  `docker exec seat-front-1 php artisan horizon:terminate`
- **Permission Repair**: If custom menus are missing for users, force-sync permissions:
  `docker exec seat-front-1 php artisan seat:admin:assign:all`
- **Namespace Updates**: After adding new classes or changing PSR-4 paths:
  `docker exec seat-front-1 composer dump-autoload`
