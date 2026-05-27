import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/services/storage_service.dart';
import '../../data/datasources/remote/landing_api.dart';
import '../../data/models/landing_project_model.dart';
import 'settings_provider.dart';

/// Maps the app language to the API `lang` code used by the landing endpoints.
extension _AppLanguageApiCode on AppLanguage {
  String get apiCode => switch (this) {
    AppLanguage.swahili => 'sw',
    AppLanguage.french => 'fr',
    AppLanguage.arabic => 'ar',
    AppLanguage.english => 'en',
  };
}

final portfolioProvider =
    StateNotifierProvider<
      PortfolioNotifier,
      AsyncValue<List<LandingProjectModel>>
    >((ref) {
      return PortfolioNotifier(ref);
    });

class PortfolioNotifier
    extends StateNotifier<AsyncValue<List<LandingProjectModel>>> {
  final Ref _ref;

  PortfolioNotifier(this._ref) : super(const AsyncValue.loading()) {
    // Re-fetch with localized content whenever the language changes.
    _ref.listen<AppLanguage>(currentLanguageProvider, (_, _) => load());
    load();
  }

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final api = _ref.read(landingApiProvider);
      final lang = _ref.read(currentLanguageProvider).apiCode;
      final deviceId = await _ref.read(storageServiceProvider).getDeviceId();
      final projects = await api.fetchPortfolio(lang: lang, deviceId: deviceId);
      if (mounted) state = AsyncValue.data(projects);
    } catch (e, st) {
      if (mounted) state = AsyncValue.error(e, st);
    }
  }

  /// Optimistically toggle a like, then reconcile with the server result.
  Future<void> toggleLike(int id) async {
    final current = state.valueOrNull;
    if (current == null) return;

    final index = current.indexWhere((p) => p.id == id);
    if (index < 0) return;
    final original = current[index];

    LandingProjectModel apply(List<LandingProjectModel> list, int i,
        LandingProjectModel value) {
      list[i] = value;
      return value;
    }

    // Optimistic update.
    final optimistic = [...current];
    apply(
      optimistic,
      index,
      original.copyWith(
        liked: !original.liked,
        likesCount: original.likesCount + (original.liked ? -1 : 1),
      ),
    );
    state = AsyncValue.data(optimistic);

    try {
      final api = _ref.read(landingApiProvider);
      final deviceId = await _ref.read(storageServiceProvider).getDeviceId();
      final result = await api.toggleLike(id: id, deviceId: deviceId);

      final list = [...?state.valueOrNull];
      final i = list.indexWhere((p) => p.id == id);
      if (i >= 0) {
        apply(list, i,
            original.copyWith(liked: result.liked, likesCount: result.likesCount));
        state = AsyncValue.data(list);
      }
    } catch (_) {
      // Revert on failure.
      final list = [...?state.valueOrNull];
      final i = list.indexWhere((p) => p.id == id);
      if (i >= 0) {
        apply(list, i, original);
        state = AsyncValue.data(list);
      }
    }
  }
}
