import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/landing_api.dart';
import '../../data/models/landing_poster_model.dart';
import '../../data/models/landing_stat_model.dart';
import 'settings_provider.dart';

extension _HomeContentLangCode on AppLanguage {
  String get apiCode => switch (this) {
    AppLanguage.swahili => 'sw',
    AppLanguage.french => 'fr',
    AppLanguage.arabic => 'ar',
    AppLanguage.english => 'en',
  };
}

/// Live hero stats from the portal CMS. Refetches when the language changes.
final statsProvider = FutureProvider<List<LandingStatModel>>((ref) async {
  final lang = ref.watch(currentLanguageProvider).apiCode;
  return ref.watch(landingApiProvider).fetchStats(lang: lang);
});

/// Live home-banner posters from the portal CMS.
final postersProvider = FutureProvider<List<LandingPosterModel>>((ref) async {
  final lang = ref.watch(currentLanguageProvider).apiCode;
  return ref.watch(landingApiProvider).fetchPosters(lang: lang);
});
