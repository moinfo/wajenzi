import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'core/config/app_config.dart';
import 'core/config/theme_config.dart';
import 'core/router/app_router.dart';
import 'presentation/providers/settings_provider.dart';
import 'l10n/app_localizations.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Initialize AppConfig for API URLs (local_config.json or defaults)
  await AppConfig.initialize();

  // Initialize SharedPreferences for settings persistence
  final sharedPreferences = await SharedPreferences.getInstance();

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

class WajenziApp extends ConsumerWidget {
  const WajenziApp({super.key});

  Locale _getLocaleFromLanguage(AppLanguage language) {
    return switch (language) {
      AppLanguage.english => const Locale('en'),
      AppLanguage.swahili => const Locale('sw'),
      AppLanguage.french => const Locale('fr'),
      AppLanguage.arabic => const Locale('ar'),
    };
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
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
