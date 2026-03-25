import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

// Keys for SharedPreferences
const String _darkModeKey = 'isDarkMode';
const String _languageKey = 'isSwahili';
const String _notificationsKey = 'enableNotifications';

// Settings state
class SettingsState {
  final bool isDarkMode;
  final bool isSwahili;
  final bool enableNotifications;

  const SettingsState({
    this.isDarkMode = false,
    this.isSwahili = false,
    this.enableNotifications = true,
  });

  SettingsState copyWith({
    bool? isDarkMode,
    bool? isSwahili,
    bool? enableNotifications,
  }) {
    return SettingsState(
      isDarkMode: isDarkMode ?? this.isDarkMode,
      isSwahili: isSwahili ?? this.isSwahili,
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
    final isSwahili = _prefs.getBool(_languageKey) ?? false;
    final enableNotifications = _prefs.getBool(_notificationsKey) ?? true;
    state = SettingsState(
      isDarkMode: isDarkMode,
      isSwahili: isSwahili,
      enableNotifications: enableNotifications,
    );
  }

  Future<void> toggleDarkMode() async {
    final newValue = !state.isDarkMode;
    await _prefs.setBool(_darkModeKey, newValue);
    state = state.copyWith(isDarkMode: newValue);
  }

  Future<void> toggleLanguage() async {
    final newValue = !state.isSwahili;
    await _prefs.setBool(_languageKey, newValue);
    state = state.copyWith(isSwahili: newValue);
  }

  Future<void> setDarkMode(bool value) async {
    await _prefs.setBool(_darkModeKey, value);
    state = state.copyWith(isDarkMode: value);
  }

  Future<void> setLanguage(bool isSwahili) async {
    await _prefs.setBool(_languageKey, isSwahili);
    state = state.copyWith(isSwahili: isSwahili);
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
final settingsProvider = StateNotifierProvider<SettingsNotifier, SettingsState>((ref) {
  final prefs = ref.watch(sharedPreferencesProvider);
  return SettingsNotifier(prefs);
});

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
