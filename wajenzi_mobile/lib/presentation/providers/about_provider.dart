import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/landing_api.dart';
import '../../data/models/landing_about_model.dart';
import 'settings_provider.dart';

extension _AboutLangCode on AppLanguage {
  String get apiCode => switch (this) {
    AppLanguage.swahili => 'sw',
    AppLanguage.french => 'fr',
    AppLanguage.arabic => 'ar',
    AppLanguage.english => 'en',
  };
}

/// Live "About" content from the portal CMS. Refetches when the language
/// changes. Returns null-able so the screen can fall back to bundled content
/// while loading or if nothing has been published.
final aboutProvider = FutureProvider<LandingAboutModel?>((ref) async {
  final lang = ref.watch(currentLanguageProvider).apiCode;
  final api = ref.watch(landingApiProvider);
  return api.fetchAbout(lang: lang);
});
