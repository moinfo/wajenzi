import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/landing_api.dart';
import '../../data/models/landing_service_model.dart';
import 'settings_provider.dart';

extension _ServicesLangCode on AppLanguage {
  String get apiCode => switch (this) {
    AppLanguage.swahili => 'sw',
    AppLanguage.french => 'fr',
    AppLanguage.arabic => 'ar',
    AppLanguage.english => 'en',
  };
}

/// Live services from the portal CMS. Refetches when the language changes.
final servicesProvider = FutureProvider<List<LandingServiceModel>>((ref) async {
  final lang = ref.watch(currentLanguageProvider).apiCode;
  final api = ref.watch(landingApiProvider);
  return api.fetchServices(lang: lang);
});
