import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/kpi_api.dart';
import '../../data/models/kpi_template.dart';

/// The templates list (+ available roles for a new template).
final kpiTemplatesProvider = StateNotifierProvider<KpiTemplatesNotifier,
    AsyncValue<KpiTemplatesResponse>>((ref) {
  return KpiTemplatesNotifier(ref.watch(kpiApiProvider));
});

class KpiTemplatesNotifier
    extends StateNotifier<AsyncValue<KpiTemplatesResponse>> {
  final KpiApi _api;

  KpiTemplatesNotifier(this._api) : super(const AsyncValue.loading()) {
    load();
  }

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final data = await _api.fetchTemplates();
      if (mounted) state = AsyncValue.data(data);
    } catch (e, st) {
      if (mounted) state = AsyncValue.error(e, st);
    }
  }

  Future<void> refresh() => load();
}

/// A single template's full detail. Family keyed by template id.
final kpiTemplateDetailProvider = StateNotifierProvider.family<
    KpiTemplateDetailNotifier, AsyncValue<KpiTemplateDetail>, int>((ref, id) {
  return KpiTemplateDetailNotifier(ref.watch(kpiApiProvider), id);
});

class KpiTemplateDetailNotifier
    extends StateNotifier<AsyncValue<KpiTemplateDetail>> {
  final KpiApi _api;
  final int _id;

  KpiTemplateDetailNotifier(this._api, this._id)
      : super(const AsyncValue.loading()) {
    load();
  }

  Future<void> load() async {
    state = const AsyncValue.loading();
    try {
      final data = await _api.fetchTemplate(_id);
      if (mounted) state = AsyncValue.data(data);
    } catch (e, st) {
      if (mounted) state = AsyncValue.error(e, st);
    }
  }

  Future<void> refresh() => load();
}
