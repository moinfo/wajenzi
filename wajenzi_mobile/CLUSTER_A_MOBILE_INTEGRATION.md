# Cluster A — Mobile router integration

Cluster A owns 7 drawer menus (Currencies, Design Packages, Design Add-ons,
Special Structure Rates, Site Visit Locations, Design Pricing Calculator, Site
Visit Calculator). Because `lib/core/router/app_router.dart` is shared between
clusters, this cluster does **not** modify it directly. Apply the snippets
below during merge.

All new code lives under:
- `lib/presentation/screens/calculators/`
- `lib/data/repositories/calculators_repository.dart`
- `lib/data/datasources/remote/calculators_remote_datasource.dart`
- `lib/data/models/calculators/calculator_models.dart`

---

## 1. Imports to add (alongside the other settings imports near line ~36-90)

```dart
import '../../presentation/screens/calculators/currencies_screen.dart';
import '../../presentation/screens/calculators/design_packages_screen.dart';
import '../../presentation/screens/calculators/design_addons_screen.dart';
import '../../presentation/screens/calculators/special_structures_screen.dart';
import '../../presentation/screens/calculators/site_visit_locations_screen.dart';
import '../../presentation/screens/calculators/design_pricing_calculator_screen.dart';
import '../../presentation/screens/calculators/site_visit_calculator_screen.dart';
```

## 2. GoRoute entries to add inside the `ShellRoute.routes` list

Paste these alongside the other `/settings/...` GoRoutes (e.g. just below
`settings-permissions` around line ~1044). They use the `/settings/...` prefix
for catalog CRUD screens to match the existing convention and `/calculators/...`
for the two calculators.

```dart
// ── Cluster A: Calculators + design/site-visit catalogs ─────────────────
GoRoute(
  path: '/settings/currencies',
  name: 'settings-currencies',
  builder: (context, state) => const CurrenciesScreen(),
),
GoRoute(
  path: '/settings/design-packages',
  name: 'settings-design-packages',
  builder: (context, state) => const DesignPackagesScreen(),
),
GoRoute(
  path: '/settings/design-addons',
  name: 'settings-design-addons',
  builder: (context, state) => const DesignAddonsScreen(),
),
GoRoute(
  path: '/settings/design-special-structures',
  name: 'settings-design-special-structures',
  builder: (context, state) => const SpecialStructuresScreen(),
),
GoRoute(
  path: '/settings/site-visit-locations',
  name: 'settings-site-visit-locations',
  builder: (context, state) => const SiteVisitLocationsScreen(),
),
GoRoute(
  path: '/calculators/design-pricing',
  name: 'calculators-design-pricing',
  builder: (context, state) => const DesignPricingCalculatorScreen(),
),
GoRoute(
  path: '/calculators/site-visit',
  name: 'calculators-site-visit',
  builder: (context, state) => const SiteVisitCalculatorScreen(),
),
```

## 3. `_mapWebRoute` entries to add (~line 2345 in the `map` const)

These translate the Laravel route names / URL slugs that the drawer + portal
emit into the new mobile paths above. Add them inside the `const map = <String,
String>{ ... }` literal in `_mapWebRoute`.

```dart
// ── Cluster A ───────────────────────────────────────────────────────────
'hr_settings_currencies':                  '/settings/currencies',
'settings/currencies':                     '/settings/currencies',
'currencies':                              '/settings/currencies',

'hr_settings_design_packages':             '/settings/design-packages',
'settings/design_packages':                '/settings/design-packages',
'settings/design-packages':                '/settings/design-packages',

'hr_settings_design_addons':               '/settings/design-addons',
'settings/design_addons':                  '/settings/design-addons',
'settings/design-addons':                  '/settings/design-addons',

'hr_settings_design_special_structures':   '/settings/design-special-structures',
'settings/design_special_structures':      '/settings/design-special-structures',
'settings/design-special-structures':      '/settings/design-special-structures',

'hr_settings_site_visit_locations':        '/settings/site-visit-locations',
'settings/site_visit_locations':           '/settings/site-visit-locations',
'settings/site-visit-locations':           '/settings/site-visit-locations',

'calculators.design-pricing':              '/calculators/design-pricing',
'calculators/design-pricing':              '/calculators/design-pricing',
'calculators.design_pricing':              '/calculators/design-pricing',

'calculators.site-visit':                  '/calculators/site-visit',
'calculators/site-visit':                  '/calculators/site-visit',
'calculators.site_visit':                  '/calculators/site-visit',
```

## 4. Sanity check after integration

Run:

```bash
cd wajenzi_mobile && flutter analyze
```

The new screens add zero analyzer issues; any reported errors at integration
time will be pre-existing (settings/auth/database codegen issues that were
already in the tree on the cluster-A baseline).
