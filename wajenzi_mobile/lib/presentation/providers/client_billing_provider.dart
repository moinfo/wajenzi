import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../data/datasources/remote/client_api.dart';

final clientBillingProvider =
    StateNotifierProvider<ClientBillingNotifier, AsyncValue<ClientBillingData>>(
  (ref) {
    final clientApi = ref.watch(clientApiProvider);
    return ClientBillingNotifier(clientApi);
  },
);

class ClientBillingNotifier extends StateNotifier<AsyncValue<ClientBillingData>> {
  final ClientApi _clientApi;

  ClientBillingNotifier(this._clientApi) : super(const AsyncValue.loading());

  Future<void> fetchBilling() async {
    state = const AsyncValue.loading();
    try {
      final data = await _clientApi.fetchBilling();
      state = AsyncValue.data(data);
    } catch (e, st) {
      state = AsyncValue.error(e, st);
    }
  }
}
