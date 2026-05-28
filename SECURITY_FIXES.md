# Security fixes — status

Recorded by the fan-out integration on 2026-05-28. All fixes below are now applied on branch `feat/native-mobile-fanout-integration` unless marked otherwise.

## ✅ Applied

### Route-level admin guards
- **Cluster A catalog mutations** (`currencies`, `design-service-packages`, `design-service-addons`, `design-special-structures`, `site-visit-locations`) wrapped in `Route::middleware('role:System Administrator|Admin')`. Calculator compute endpoints stay open to all auth users.
- **Cluster D landing-admin** entire prefix group wrapped in `Route::middleware('role:System Administrator|Admin')`. All CMS writes + reads are admin-only.

### Sanitize verbose exception messages — Cluster A controllers
Applied to **CurrencyApiController, DesignServicePackageApiController, DesignServiceAddonApiController, DesignSpecialStructureApiController, SiteVisitLocationApiController**. In every `store()` / `update()`:
- Stripped `': ' . $e->getMessage()` from the user-facing `'message'` JSON. The full exception still goes to `Log::error()` server-side.
- Added an early `if ($e instanceof \Illuminate\Validation\ValidationException) throw $e;` inside each Throwable catch so framework returns 422 with field-level validation errors instead of a generic message.

### IDOR — engineer ownership on design controllers (Cluster C)
**StructuralDesignApiController, ServiceDesignApiController**: added `MANAGER_ROLES` const + `isManager()` + `ensureCanAccessDesign()` helpers. Called from `show()`, `update()`, `destroy()`, `updateStage()`, `submitStage()`. Engineer-only sees own designs; managers see all.
- `show()` and `destroy()` gained a `Request $request` first argument so the helper can read the auth user. Route binding is unaffected.
- `update()`: strips `assigned_engineer_id` from the validated input for non-managers (engineers cannot reassign).
- `destroy()`: managers-only (engineers cannot delete their own design rows).

### IDOR — ownership/role on Cluster B controllers
**ContentCreatorApiController**: added `MANAGER_ROLES` + `isManager()` + `ensureCanEditTask()` (assignee OR creator OR manager). Called from `updateTask()`, `destroyTask()`. `approveTask()` is manager-only.

**FieldMarketingApiController**: leveraged existing `isFieldOfficer()`; added complementary `isMarketingManager()`. `updateSession()` + `destroySession()` enforce session.officer_id ownership when caller is a field officer; `updateSession()` strips `officer_id` from validated input for field officers. `destroyVisit()` enforces visit.created_by ownership for field officers. `storeTarget()` is manager-only.

**WhatsAppMarketingApiController**: added `MANAGER_ROLES` + `ensureCanEditContact()` (assignee/creator/manager) and `ensureCanEditCampaign()` (creator/manager). Called from `updateContact()`, `destroyContact()`, `destroyCampaign()`, `closeCampaign()`.

## ⚠️ Still pending (your call)

### Role names — confirm match Spatie roles table
The integration uses these role strings as the manager set:

- Catalog routes: `System Administrator|Admin`
- Landing-admin routes: `System Administrator|Admin`
- Design controllers (Cluster C): `Managing Director | CEO | Chief Executive Officer | System Administrator | Admin`
- Content Creator (Cluster B): add `Content Manager | Marketing Manager` to the design set
- WhatsApp Marketing (Cluster B): add `Marketing Manager` to the design set
- Field Marketing (Cluster B): leverages existing `isFieldOfficer()` helper which uses role IDs `[1, 2, 7, 9, 12, 14, 15, 16]` for managers and `13` for field officer — confirm those IDs match production data.

Quick check:
```sh
php artisan tinker --execute='dump(\Spatie\Permission\Models\Role::pluck("name","id"));'
```

If a role name differs (e.g., `SysAdmin` not `System Administrator`), update the middleware string in `routes/api/v1.php` and the `MANAGER_ROLES` consts in the 5 IDOR-fixed controllers.

### Optional: convert inline guards to Policies
The current inline `abort_unless(...)` calls work but a `ProjectStructuralDesignPolicy`, `ContentCreatorTaskPolicy`, etc. registered in `AuthServiceProvider` would be cleaner long-term. The fix patches above can be replaced with `$this->authorize('update', $design)` style calls once policies exist.
