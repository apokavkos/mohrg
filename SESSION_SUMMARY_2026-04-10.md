# Session Summary: 2026-04-10

## Major Milestone: Rebuild & Restoration of SeAT v5
This session focused on moving away from Alliance Auth due to performance and plugin compatibility issues, resulting in a fresh, stable SeAT v5.0.x environment.

### 1. SeAT Framework & Infrastructure
- **Fresh Install**: Deployed SeAT v5 using the official Docker bootstrap method.
- **Data Restoration**: Migrated original `APP_KEY`, `DB` credentials, and `EVE_CLIENT_ID/SECRET`. Restored characters and tokens from backup.
- **SSL Fix**: Resolved Traefik `acme.json` permission issues (600) and verified Let's Encrypt certificate issuance.
- **Core Patch**: Patched the SeAT Socialite EVE Provider to prevent 500 errors during no-scope signups.

### 2. Standalone Plugin Development: `seat-dashboard`
- **Architecture**: Created a clean, standalone plugin in `packages/seat-dashboard`.
- **Features**:
    - **Legacy Dashboard**: Restored "Industrial Slots," "ISK Summary," and "Corp Wallet" views from EIC backups.
    - **System Cost Indexes**: Integrated live ESI lookup for manufacturing, research, and reaction indexes.
    - **Solar System Search**: Implemented a 3-character Ajax search with auto-focus for tracking specific systems.
- **Optimization**: Updated controller logic to ensure all linked characters appear even before their first wallet sync.

### 3. Engineering Standards Established
Created a project-wide `GEMINI.md` (and stored in AI memory) to prevent recurring 500 errors:
- **Strict Cache Rules**: Mandatory `config:clear`, `route:clear`, `view:clear` after any change.
- **Menu Validation**: Rules for `route_segment` and `entries` structure to prevent menu builder crashes.
- **Auto-Healing**: Service Provider updated to automatically create roles and link permissions on boot.

### Current Status
- **Environment**: Stable and Production-Ready.
- **Site**: [https://seat.apokavkos.com](https://seat.apokavkos.com)
- **User**: `admin` account owns all characters; `Administrator` role is correctly configured.
