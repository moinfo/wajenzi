# Cluster E — Mobile Router Integration

Hand-off notes for whoever owns `wajenzi_mobile/lib/core/router/app_router.dart`.
Cluster E did NOT touch that file (per scope rules). Apply the three edits below
to wire up the three new native screens.

Branch: `feat/cluster-e-finance-leftovers`

---

## 1. Imports (add near the other `screens/...` imports)

```dart
import '../../presentation/screens/finance/finance_dashboard_screen.dart';
import '../../presentation/screens/finance/expenditure_dashboard_screen.dart';
import '../../presentation/screens/procurement/material_transfers_screen.dart';
```

---

## 2. GoRoutes (add inside the `ShellRoute` `routes: [...]` block, alongside the other `/accounting`, `/procurement`, `/expenses` routes)

```dart
GoRoute(
  path: '/finance',
  name: 'finance',
  builder: (context, state) => const FinanceDashboardScreen(),
),
GoRoute(
  path: '/finance/expenditure-dashboard',
  name: 'finance-expenditure-dashboard',
  builder: (context, state) => const ExpenditureDashboardScreen(),
),
GoRoute(
  path: '/material-transfers',
  name: 'material-transfers',
  builder: (context, state) => const MaterialTransfersScreen(),
),
```

---

## 3. `_mapWebRoute` entries (add inside the `map` literal, anywhere)

These are the drawer slugs that currently fall through to the web URL fallback.

```dart
// Finance landing (parent drawer entry).
'finance': '/finance',
'finance.dashboard': '/finance',
'finance/dashboard': '/finance',
'finance_dashboard': '/finance',

// Expenditure dashboard.
'finance.expenditure_dashboard': '/finance/expenditure-dashboard',
'finance/expenditure_dashboard': '/finance/expenditure-dashboard',
'finance/expenditure-dashboard': '/finance/expenditure-dashboard',
'expenditure_dashboard': '/finance/expenditure-dashboard',
'expenditure-dashboard': '/finance/expenditure-dashboard',

// Material transfers (under Procurement parent in the drawer).
'material_transfers': '/material-transfers',
'material-transfers': '/material-transfers',
'material_transfer': '/material-transfers',
'material-transfer': '/material-transfers',
'procurement/material_transfers': '/material-transfers',
'procurement/material-transfers': '/material-transfers',
```

---

## API endpoints used (already wired in `routes/api/v1.php`)

```
GET    /api/v1/finance/dashboard
GET    /api/v1/finance/expenditure-dashboard
GET    /api/v1/material-transfers
GET    /api/v1/material-transfers/reference-data
POST   /api/v1/material-transfers
GET    /api/v1/material-transfers/{id}
DELETE /api/v1/material-transfers/{id}
POST   /api/v1/material-transfers/{id}/approve
POST   /api/v1/material-transfers/{id}/reject
```
