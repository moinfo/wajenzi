import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'auth_provider.dart';

// Keys for SharedPreferences
const String _darkModeKey = 'isDarkMode';
const String _languageKey = 'appLanguage';
const String _legacyLanguageKey = 'isSwahili';
const String _notificationsKey = 'enableNotifications';

enum AppLanguage { english, swahili, french, arabic }

extension AppLanguageX on AppLanguage {
  String get storageValue => switch (this) {
    AppLanguage.english => 'english',
    AppLanguage.swahili => 'swahili',
    AppLanguage.french => 'french',
    AppLanguage.arabic => 'arabic',
  };

  String get code => switch (this) {
    AppLanguage.english => 'EN',
    AppLanguage.swahili => 'SW',
    AppLanguage.french => 'FR',
    AppLanguage.arabic => 'AR',
  };

  bool get isRtl => this == AppLanguage.arabic;
}

AppLanguage _languageFromStorage(String? value) {
  return switch (value) {
    'swahili' => AppLanguage.swahili,
    'french' => AppLanguage.french,
    'arabic' => AppLanguage.arabic,
    _ => AppLanguage.english,
  };
}

// Settings state
class SettingsState {
  final bool isDarkMode;
  final AppLanguage language;
  final bool enableNotifications;

  const SettingsState({
    this.isDarkMode = false,
    this.language = AppLanguage.english,
    this.enableNotifications = true,
  });

  bool get isSwahili => language == AppLanguage.swahili;

  SettingsState copyWith({
    bool? isDarkMode,
    AppLanguage? language,
    bool? enableNotifications,
  }) {
    return SettingsState(
      isDarkMode: isDarkMode ?? this.isDarkMode,
      language: language ?? this.language,
      enableNotifications: enableNotifications ?? this.enableNotifications,
    );
  }
}

// Settings notifier
class SettingsNotifier extends StateNotifier<SettingsState> {
  final SharedPreferences _prefs;

  SettingsNotifier(this._prefs) : super(const SettingsState()) {
    _loadSettings();
  }

  void _loadSettings() {
    final isDarkMode = _prefs.getBool(_darkModeKey) ?? false;
    final storedLanguage = _prefs.getString(_languageKey);
    final legacyIsSwahili = _prefs.getBool(_legacyLanguageKey);
    final enableNotifications = _prefs.getBool(_notificationsKey) ?? true;
    state = SettingsState(
      isDarkMode: isDarkMode,
      language: storedLanguage != null
          ? _languageFromStorage(storedLanguage)
          : (legacyIsSwahili == true
                ? AppLanguage.swahili
                : AppLanguage.english),
      enableNotifications: enableNotifications,
    );
  }

  Future<void> toggleDarkMode() async {
    final newValue = !state.isDarkMode;
    await _prefs.setBool(_darkModeKey, newValue);
    state = state.copyWith(isDarkMode: newValue);
  }

  Future<void> toggleLanguage() async {
    const languageOrder = [
      AppLanguage.english,
      AppLanguage.swahili,
      AppLanguage.french,
      AppLanguage.arabic,
    ];
    final currentIndex = languageOrder.indexOf(state.language);
    final nextLanguage =
        languageOrder[(currentIndex + 1) % languageOrder.length];
    await setLanguage(nextLanguage);
  }

  Future<void> setDarkMode(bool value) async {
    await _prefs.setBool(_darkModeKey, value);
    state = state.copyWith(isDarkMode: value);
  }

  Future<void> setLanguage(AppLanguage language) async {
    await _prefs.setString(_languageKey, language.storageValue);
    await _prefs.remove(_legacyLanguageKey);
    state = state.copyWith(language: language);
  }

  Future<void> setNotificationsEnabled(bool value) async {
    await _prefs.setBool(_notificationsKey, value);
    state = state.copyWith(enableNotifications: value);
  }
}

// SharedPreferences provider
final sharedPreferencesProvider = Provider<SharedPreferences>((ref) {
  throw UnimplementedError('SharedPreferences must be initialized before use');
});

// Settings provider
final settingsProvider = StateNotifierProvider<SettingsNotifier, SettingsState>(
  (ref) {
    final prefs = ref.watch(sharedPreferencesProvider);
    return SettingsNotifier(prefs);
  },
);

// Convenience providers
final isDarkModeProvider = Provider<bool>((ref) {
  return ref.watch(settingsProvider).isDarkMode;
});

final isSwahiliProvider = Provider<bool>((ref) {
  return ref.watch(settingsProvider).isSwahili;
});

final notificationsEnabledProvider = Provider<bool>((ref) {
  return ref.watch(settingsProvider).enableNotifications;
});

final currentLanguageProvider = Provider<AppLanguage>((ref) {
  return ref.watch(settingsProvider).language;
});

final isFrenchProvider = Provider<bool>((ref) {
  return ref.watch(currentLanguageProvider) == AppLanguage.french;
});

final isArabicProvider = Provider<bool>((ref) {
  return ref.watch(currentLanguageProvider) == AppLanguage.arabic;
});

/// Effective language provider that respects login context
/// Returns the saved language only for pre-login screens (landing, login, about, services, projects, awards)
/// Returns English ONLY for post-login screens (dashboard and all authenticated screens)
///
/// This ensures language settings only affect pre-login UI screens
/// and post-login dashboard screens always display in English
final effectiveLanguageProvider = Provider<AppLanguage>((ref) {
  // Import auth_provider to check authentication status
  final authState = ref.watch(authStateProvider);
  final isLoggedIn = authState.valueOrNull?.isAuthenticated ?? false;

  // If user is logged in (post-login screens), always use English
  if (isLoggedIn) {
    return AppLanguage.english;
  }

  // If user is NOT logged in (pre-login screens), use saved language preference
  return ref.watch(currentLanguageProvider);
});
