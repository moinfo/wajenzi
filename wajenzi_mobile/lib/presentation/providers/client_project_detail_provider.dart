import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/client_api.dart';

/// Overview — fetched when screen opens.
final projectOverviewProvider =
    FutureProvider.family<ProjectOverviewData, int>((ref, id) {
  final api = ref.watch(clientApiProvider);
  return api.fetchProjectDetail(id);
});

/// BOQ — lazy, fetched when tab selected.
final projectBoqProvider =
    FutureProvider.family<List<ProjectBoq>, int>((ref, id) {
  final api = ref.watch(clientApiProvider);
  return api.fetchProjectBoq(id);
});

/// Schedule — lazy.
final projectScheduleProvider =
    FutureProvider.family<ProjectScheduleData, int>((ref, id) {
  final api = ref.watch(clientApiProvider);
  return api.fetchProjectSchedule(id);
});

/// Financials — lazy.
final projectFinancialsProvider =
    FutureProvider.family<ProjectFinancials, int>((ref, id) {
  final api = ref.watch(clientApiProvider);
  return api.fetchProjectFinancials(id);
});

/// Documents — lazy.
final projectDocumentsProvider =
    FutureProvider.family<List<ProjectDesign>, int>((ref, id) {
  final api = ref.watch(clientApiProvider);
  return api.fetchProjectDocuments(id);
});

/// Reports — lazy.
final projectReportsProvider =
    FutureProvider.family<ProjectReportsData, int>((ref, id) {
  final api = ref.watch(clientApiProvider);
  return api.fetchProjectReports(id);
});

/// Gallery — lazy.
final projectGalleryProvider =
    FutureProvider.family<ProjectGalleryData, int>((ref, id) {
  final api = ref.watch(clientApiProvider);
  return api.fetchProjectGallery(id);
});
