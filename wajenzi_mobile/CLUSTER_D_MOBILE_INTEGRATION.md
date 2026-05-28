# Cluster D — Website Content CMS (mobile admin): integration manifest

Apply these edits to `wajenzi_mobile/lib/core/router/app_router.dart` during merge.

## ⚠️ Partial delivery
Controllers and routes exist for all 8 landing categories, but only **5 of 8** admin screens were written before the agent hit a socket error:

| Category | Controller | Admin screen |
|---|---|---|
| Portfolio | ✅ `LandingPortfolioAdminController` | ❌ MISSING |
| Awards | ✅ `LandingAwardAdminController` | ✅ `awards_admin_screen.dart` |
| Services | ✅ `LandingServiceAdminController` | ✅ `services_admin_screen.dart` |
| Posters | ✅ `LandingPosterAdminController` | ✅ `posters_admin_screen.dart` |
| Stats | ✅ `LandingStatAdminController` | ✅ `stats_admin_screen.dart` |
| About | ✅ `LandingAboutAdminController` (singleton) | ❌ MISSING |
| Values | ✅ `LandingValueAdminController` | ✅ `values_admin_screen.dart` |
| Team | ✅ `LandingTeamAdminController` | ❌ MISSING |

Until Portfolio/About/Team admin screens are added, those three menu entries will continue to fall through to the web URL.

## 1. Imports to add (near the other screen imports, ~line 130)

```dart
import '../../presentation/screens/landing_admin/awards_admin_screen.dart';
import '../../presentation/screens/landing_admin/services_admin_screen.dart';
import '../../presentation/screens/landing_admin/posters_admin_screen.dart';
import '../../presentation/screens/landing_admin/stats_admin_screen.dart';
import '../../presentation/screens/landing_admin/values_admin_screen.dart';
// TODO: add when these exist
// import '../../presentation/screens/landing_admin/portfolio_admin_screen.dart';
// import '../../presentation/screens/landing_admin/about_admin_screen.dart';
// import '../../presentation/screens/landing_admin/team_admin_screen.dart';
```

## 2. GoRoutes to add (inside the ShellRoute child route list, after the KPI block ~line 1359)

```dart
GoRoute(
  path: '/landing-admin/awards',
  name: 'landing-admin-awards',
  builder: (context, state) => const AwardsAdminScreen(),
),
GoRoute(
  path: '/landing-admin/services',
  name: 'landing-admin-services',
  builder: (context, state) => const ServicesAdminScreen(),
),
GoRoute(
  path: '/landing-admin/posters',
  name: 'landing-admin-posters',
  builder: (context, state) => const PostersAdminScreen(),
),
GoRoute(
  path: '/landing-admin/stats',
  name: 'landing-admin-stats',
  builder: (context, state) => const StatsAdminScreen(),
),
GoRoute(
  path: '/landing-admin/values',
  name: 'landing-admin-values',
  builder: (context, state) => const ValuesAdminScreen(),
),
// TODO: add when these exist
// GoRoute(path: '/landing-admin/portfolio', name: 'landing-admin-portfolio',
//   builder: (context, state) => const PortfolioAdminScreen()),
// GoRoute(path: '/landing-admin/about', name: 'landing-admin-about',
//   builder: (context, state) => const AboutAdminScreen()),
// GoRoute(path: '/landing-admin/team', name: 'landing-admin-team',
//   builder: (context, state) => const TeamAdminScreen()),
```

## 3. `_mapWebRoute` entries to add (inside the const map ~line 2885, before the closing brace)

```dart
'landing_awards': '/landing-admin/awards',
'landing_services': '/landing-admin/services',
'landing_posters': '/landing-admin/posters',
'landing_stats': '/landing-admin/stats',
'landing_values': '/landing-admin/values',
// landing_portfolio, landing_about, landing_team intentionally NOT mapped
// until their admin screens exist.
```

## Test
After applying:
```sh
cd wajenzi_mobile && flutter analyze
```
