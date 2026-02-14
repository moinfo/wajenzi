import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/staff_dashboard_api.dart';

final staffDashboardProvider =
    StateNotifierProvider<StaffDashboardNotifier, AsyncValue<StaffDashboardData>>(
  (ref) {
    final api = ref.watch(staffDashboardApiProvider);
    return StaffDashboardNotifier(api);
  },
);

class StaffDashboardNotifier extends StateNotifier<AsyncValue<StaffDashboardData>> {
  final StaffDashboardApi _api;

  StaffDashboardNotifier(this._api) : super(const AsyncValue.loading());

  Future<void> fetchDashboard() async {
    state = const AsyncValue.loading();
    try {
      final data = await _api.fetchDashboard();
      state = AsyncValue.data(data);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }
}
