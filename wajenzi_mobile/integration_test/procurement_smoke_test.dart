// Smoke test: log in against the local API and open each new Procurement
// screen, asserting it renders. Run on a booted device/emulator with:
//   flutter test integration_test/procurement_smoke_test.dart -d <deviceId> \
//     --dart-define=API_BASE_URL=http://localhost:8010/api/v1
//
// Requires the local Laravel API running on the given base URL and the test
// user credentials below to be valid.
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:wajenzi_mobile/core/config/app_config.dart';
import 'package:wajenzi_mobile/core/router/app_router.dart';
import 'package:wajenzi_mobile/main.dart';
import 'package:wajenzi_mobile/presentation/providers/auth_provider.dart';
import 'package:wajenzi_mobile/presentation/providers/settings_provider.dart';

// Credentials come from the environment so no real login lands in the repo:
//   --dart-define=SMOKE_EMAIL=... --dart-define=SMOKE_PASSWORD=...
const _email = String.fromEnvironment('SMOKE_EMAIL');
const _password = String.fromEnvironment('SMOKE_PASSWORD');

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('login and browse procurement screens', (tester) async {
    await AppConfig.initialize();
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();

    final scope = ProviderScope(
      overrides: [sharedPreferencesProvider.overrideWithValue(prefs)],
      child: const WajenziApp(),
    );
    await tester.pumpWidget(scope);
    await tester.pumpAndSettle(const Duration(seconds: 2));

    // ── Log in against the live API (bypasses the landing → login UI) ─────
    final container = ProviderScope.containerOf(
      tester.element(find.byType(MaterialApp)),
    );
    final ok = await container
        .read(authStateProvider.notifier)
        .login(_email, _password);
    expect(ok, isTrue, reason: 'login should succeed against the local API');
    await tester.pumpAndSettle(const Duration(seconds: 2));

    // ── Navigate to each procurement screen via the router ────────────────
    final router = container.read(routerProvider);

    Future<void> visit(String path, String expectedTitle) async {
      router.go(path);
      for (var i = 0; i < 20; i++) {
        await tester.pump(const Duration(milliseconds: 500));
        if (find.text(expectedTitle).evaluate().isNotEmpty) break;
      }
      await tester.pumpAndSettle(const Duration(seconds: 1));
      expect(find.text(expectedTitle), findsWidgets,
          reason: '$path should render "$expectedTitle"');
      // Hold on-screen so an external screenshot can capture it.
      await tester.pump(const Duration(seconds: 3));
    }

    await visit('/material-requests', 'Material Requests');
    await visit('/supplier-quotations', 'Supplier Quotations');
    await visit('/purchase-orders', 'Purchase Orders');
    await visit('/material-inspections', 'Material Inspections');
  });
}
