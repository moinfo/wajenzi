// Smoke test: log in against the local API and open each Labor Procurement
// screen, asserting it renders. Run on a booted device/emulator with:
//   flutter test integration_test/labor_smoke_test.dart -d <deviceId> \
//     --dart-define=API_BASE_URL=http://localhost:8010/api/v1 \
//     --dart-define=SMOKE_EMAIL=... --dart-define=SMOKE_PASSWORD=...
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

const _email = String.fromEnvironment('SMOKE_EMAIL');
const _password = String.fromEnvironment('SMOKE_PASSWORD');

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('login and browse labor procurement screens', (tester) async {
    await AppConfig.initialize();
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();

    await tester.pumpWidget(ProviderScope(
      overrides: [sharedPreferencesProvider.overrideWithValue(prefs)],
      child: const WajenziApp(),
    ));
    await tester.pumpAndSettle(const Duration(seconds: 2));

    final container = ProviderScope.containerOf(
      tester.element(find.byType(MaterialApp)),
    );
    final ok = await container
        .read(authStateProvider.notifier)
        .login(_email, _password);
    expect(ok, isTrue, reason: 'login should succeed against the local API');
    await tester.pumpAndSettle(const Duration(seconds: 2));

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
      await tester.pump(const Duration(seconds: 2));
    }

    await visit('/labor-dashboard', 'Labor Dashboard');
    await visit('/labor-requests', 'Labor Requests');
    await visit('/labor-contracts', 'Labor Contracts');
    await visit('/labor-logs', 'Work Logs');
    await visit('/labor-inspections', 'Labor Inspections');
    await visit('/labor-payments', 'Labor Payments');
  });
}
