import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/client_api.dart';

final clientDashboardProvider =
    StateNotifierProvider<ClientDashboardNotifier, AsyncValue<ClientDashboardData>>(
  (ref) {
    final clientApi = ref.watch(clientApiProvider);
    return ClientDashboardNotifier(clientApi);
  },
);

class ClientDashboardNotifier extends StateNotifier<AsyncValue<ClientDashboardData>> {
  final ClientApi _clientApi;

  ClientDashboardNotifier(this._clientApi) : super(const AsyncValue.loading());

  Future<void> fetchDashboard() async {
    state = const AsyncValue.loading();
    try {
      final data = await _clientApi.fetchDashboard();
      state = AsyncValue.data(data);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }
}
