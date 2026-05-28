# Cluster C — Mobile Router Integration

This document instructs the integrator how to wire the Engineering Design
screens (Structural Design + Service Design) into
`wajenzi_mobile/lib/core/router/app_router.dart`.

> Cluster C MUST NOT touch `app_router.dart` directly (multi-agent conflict
> prevention). All edits below should be applied by the merge agent.

---

## 1. Imports

Add these imports near the other `presentation/screens/...` imports in
`app_router.dart` (alphabetical block near the kpi/landing/etc imports):

```dart
import '../../presentation/screens/engineering/structural_design_screen.dart';
import '../../presentation/screens/engineering/service_design_screen.dart';
```

---

## 2. GoRoutes

Add these two `GoRoute` entries inside the `ShellRoute.routes:` list (alongside
the other staff routes — e.g. next to the `/performance` block at the end):

```dart
// ── Engineering Design ────────────────────────────────────────
GoRoute(
  path: '/structural-design',
  name: 'structural-design',
  builder: (context, state) => const StructuralDesignScreen(),
),
GoRoute(
  path: '/service-design',
  name: 'service-design',
  builder: (context, state) => const ServiceDesignScreen(),
),
```

---

## 3. `_mapWebRoute` entries

Add these mappings inside the `const map = <String, String>{ … }` literal in
`_mapWebRoute` (around line 2158). They cover the Laravel route names emitted
by the drawer menu (e.g. `structural_design.index`) as well as URL slugs.

```dart
// Engineering Design — Structural
'structural_design': '/structural-design',
'structural-design': '/structural-design',
'structural_design.index': '/structural-design',
'structural_design_index': '/structural-design',

// Engineering Design — Service
'service_design': '/service-design',
'service-design': '/service-design',
'service_design.index': '/service-design',
'service_design_index': '/service-design',
```

---

## 4. (Optional) Drawer / nav highlighting

If the staff nav `_resolveStaffTabIndex` is extended to highlight a drawer
section for engineering, add to the appropriate `if (location.startsWith(...))`
chain:

```dart
location.startsWith('/structural-design') ||
location.startsWith('/service-design') ||
```

Currently both should fall under tab index `2` (Other / drawer-managed
sections) — matching the existing engineering items like `/boqs`.

---

## Files added by Cluster C (do NOT modify in this PR)

- `wajenzi_mobile/lib/presentation/screens/engineering/engineering_design_shared.dart`
- `wajenzi_mobile/lib/presentation/screens/engineering/design_list_screen.dart`
- `wajenzi_mobile/lib/presentation/screens/engineering/structural_design_screen.dart`
- `wajenzi_mobile/lib/presentation/screens/engineering/service_design_screen.dart`

## Backend (Laravel) artifacts

- `app/Http/Controllers/Api/V1/StructuralDesignApiController.php`
- `app/Http/Controllers/Api/V1/ServiceDesignApiController.php`
- `routes/api/v1.php` (added imports + 2 prefixed route groups)
