import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'core/config/app_config.dart';
import 'core/config/theme_config.dart';
import 'core/router/app_router.dart';
import 'core/services/export_service.dart';
import 'presentation/providers/auth_provider.dart';
import 'presentation/providers/settings_provider.dart';
import 'l10n/app_localizations.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize AppConfig for API URLs (local_config.json or defaults)
  await AppConfig.initialize();

  // Initialize SharedPreferences for settings persistence
  final sharedPreferences = await SharedPreferences.getInstance();

  // "Download complete" notifications for exported documents
  await initDownloadNotifications();

  // Initialize Firebase (uncomment when configured)
  // await Firebase.initializeApp();

  runApp(
    ProviderScope(
      overrides: [
        sharedPreferencesProvider.overrideWithValue(sharedPreferences),
      ],
      child: const WajenziApp(),
    ),
  );
}

class WajenziApp extends ConsumerStatefulWidget {
  const WajenziApp({super.key});

  @override
  ConsumerState<WajenziApp> createState() => _WajenziAppState();
}

class _WajenziAppState extends ConsumerState<WajenziApp>
    with WidgetsBindingObserver {
  DateTime? _lastRefresh;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// Roles/permissions are managed on the web; re-read them whenever the app
  /// comes back to the foreground so both sides always agree.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) return;
    final now = DateTime.now();
    final last = _lastRefresh;
    if (last != null && now.difference(last) < const Duration(seconds: 30)) {
      return;
    }
    _lastRefresh = now;
    ref.read(authStateProvider.notifier).refreshUser();
  }

  Locale _getLocaleFromLanguage(AppLanguage language) {
    return switch (language) {
      AppLanguage.english => const Locale('en'),
      AppLanguage.swahili => const Locale('sw'),
      AppLanguage.french => const Locale('fr'),
      AppLanguage.arabic => const Locale('ar'),
    };
  }

  @override
  Widget build(BuildContext context) {
    final router = ref.watch(routerProvider);
    final isDarkMode = ref.watch(isDarkModeProvider);
    // Use effectiveLanguageProvider to respect login context
    // Pre-login screens will use saved language preference
    // Post-login screens will always use English
    final language = ref.watch(effectiveLanguageProvider);

    return MaterialApp.router(
      title: AppConfig.appName,
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      themeMode: isDarkMode ? ThemeMode.dark : ThemeMode.light,
      routerConfig: router,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      locale: _getLocaleFromLanguage(language),
      builder: (context, child) {
        return Directionality(
          textDirection: language.isRtl ? TextDirection.rtl : TextDirection.ltr,
          child: child ?? const SizedBox.shrink(),
        );
      },
    );
  }
}
