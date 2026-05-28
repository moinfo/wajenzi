import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/kpi_api.dart';
import '../../data/models/kpi_create_info.dart';
import '../../data/models/kpi_review_detail.dart';
import '../../data/models/kpi_review_list_item.dart';

/// The KPI list tabs.
enum KpiTab { mine, awaiting, all }

extension KpiTabApi on KpiTab {
  String get apiValue => switch (this) {
        KpiTab.mine => 'mine',
        KpiTab.awaiting => 'awaiting',
        KpiTab.all => 'all',
      };

  String get label => switch (this) {
        KpiTab.mine => 'Mine',
        KpiTab.awaiting => 'Awaiting',
        KpiTab.all => 'All',
      };
}

/// Holds the review list for a single tab + refresh support.
final kpiListProvider = StateNotifierProvider.family<KpiListNotifier,
    AsyncValue<KpiReviewListResponse>, KpiTab>((ref, tab) {
  return KpiListNotifier(ref.watch(kpiApiProvider), tab);
});

class KpiListNotifier
    extends StateNotifier<AsyncValue<KpiReviewListResponse>> {
  final KpiApi _api;
  final KpiTab _tab;

  KpiListNotifier(this._api, this._tab) : super(const AsyncValue.loading()) {
    load();
  }

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final data = await _api.fetchReviews(tab: _tab.apiValue);
      if (mounted) state = AsyncValue.data(data);
    } catch (e, st) {
      if (mounted) state = AsyncValue.error(e, st);
    }
  }

  Future<void> refresh() => load();
}

/// create-info for the New Review screen.
final kpiCreateInfoProvider =
    FutureProvider.autoDispose<KpiCreateInfo>((ref) async {
  return ref.watch(kpiApiProvider).fetchCreateInfo();
});

/// Detail for a single review. Family keyed by review id.
final kpiReviewDetailProvider = StateNotifierProvider.family<
    KpiDetailNotifier, AsyncValue<KpiReviewDetail>, int>((ref, id) {
  return KpiDetailNotifier(ref.watch(kpiApiProvider), id);
});

class KpiDetailNotifier extends StateNotifier<AsyncValue<KpiReviewDetail>> {
  final KpiApi _api;
  final int _id;

  KpiDetailNotifier(this._api, this._id) : super(const AsyncValue.loading()) {
    load();
  }

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final data = await _api.fetchReview(_id);
      if (mounted) state = AsyncValue.data(data);
    } catch (e, st) {
      if (mounted) state = AsyncValue.error(e, st);
    }
  }

  Future<void> refresh() => load();
}
