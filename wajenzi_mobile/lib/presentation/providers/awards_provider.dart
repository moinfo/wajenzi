import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/landing_api.dart';
import '../../data/models/landing_award_model.dart';
import 'settings_provider.dart';

extension _AwardsLangCode on AppLanguage {
  String get apiCode => switch (this) {
    AppLanguage.swahili => 'sw',
    AppLanguage.french => 'fr',
    AppLanguage.arabic => 'ar',
    AppLanguage.english => 'en',
  };
}

/// Live awards from the portal CMS. Refetches when the language changes.
final awardsProvider = FutureProvider<List<LandingAwardModel>>((ref) async {
  final lang = ref.watch(currentLanguageProvider).apiCode;
  final api = ref.watch(landingApiProvider);
  return api.fetchAwards(lang: lang);
});
