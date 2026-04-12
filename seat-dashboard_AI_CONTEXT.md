
## 8. Dashboard Troubleshooting: "The 500 Crisis"
If the dashboard or nexus pages return 500 errors after an update:

### Vector F: Relationship Name Changes (v5)
- **Symptom**: `Call to undefined relationship [system] on model [CharacterLocation]`.
- **Cause**: Relationship names in SeAT v5 often differ from legacy versions.
- **Fix**: Use `solar_system` instead of `system` when eager-loading or accessing location data from `CharacterLocation` or `CharacterJumpClone`.

### Vector G: PHP Namespace Collisions
- **Symptom**: `FatalError: Cannot use CharacterInfo as CharacterInfo because the name is already in use`.
- **Cause**: Overwriting a controller and adding multiple `use` statements for the same class (often happens during rapid code insertion).
- **Fix**: Systematically check the top of the Controller for duplicate `use` declarations. Every model should be imported exactly once.

### Vector H: Preserving Logic Density
- **Rule**: The `DashboardController` is "Logic-Heavy." It handles wallets, corp filters, and industry slots simultaneously.
- **Danger**: Do NOT use destructive `cat` or `sed` commands that overwrite the entire file without including the original logic. Always merge new methods into the existing class structure.
- **Verification**: Always test BOTH the new page and the original "Overview" page after a change to ensure no logic was dropped.

### Vector I: The Global Sidebar Crash (Critical)
- **Symptom**: The entire site returns a 500 error on every page, including the login screen.
- **Cause**: The `package.sidebar.php` file references a Laravel route name that does not exist or has a typo (e.g., using `.` instead of `::`). Since SeAT renders the sidebar globally, any error in this config brings down the entire application.
- **Fix**: Verify that every `route` key in `package.sidebar.php` has a corresponding entry in `routes.php` with the exact same name.
- **Rule**: Never update the sidebar without a matching route update. If the site crashes, check the `package.sidebar.php` for recent route changes.

### Vector J: Safe Controller Injection
- **Rule**: When adding features to the `DashboardController`, DO NOT overwrite the file. It contains irreplaceable logic for wallets, corporation balance tracking, and industry slots.
- **Procedure**:
    1. Restore from `/root/seat-apok/seat-dashboard/src/Http/Controllers/DashboardController.php` if corrupted.
    2. Use `sed` to inject new `use` statements at the top.
    3. Use `sed` to remove the final closing brace `}` and then `cat >>` to append new methods before re-closing the class.
    4. This ensures existing variables like `$character_ids` and `$selected_corp_id` are never lost.

### Vector K: SDE Model Relationship Gaps
- **Symptom**: `Call to undefined relationship [solar_system] on model [StaStation]`.
- **Cause**: Unlike character models, some SDE models (like `StaStation`) lack built-in Eloquent relationships to other tables.
- **Fix**: Manually resolve the Solar System name by querying the `SolarSystem` model using the `solarSystemID` (note the casing) from the station record.
- **Example**: `SolarSystem::where('system_id', $station->solarSystemID)->value('name')`.
