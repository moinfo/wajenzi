import 'dart:io';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/services/storage_service.dart';
import '../../data/datasources/remote/auth_api.dart';
import '../../data/models/user_model.dart';

final authStateProvider = StateNotifierProvider<AuthNotifier, AsyncValue<AuthState>>((ref) {
  final authApi = ref.watch(authApiProvider);
  final storageService = ref.watch(storageServiceProvider);
  return AuthNotifier(authApi, storageService);
});

class AuthNotifier extends StateNotifier<AsyncValue<AuthState>> {
  final AuthApi _authApi;
  final StorageService _storageService;

  AuthNotifier(this._authApi, this._storageService) : super(const AsyncValue.loading()) {
    _init();
  }

  Future<void> _init() async {
    try {
      final token = await _storageService.getToken();
      final userData = await _storageService.getUser();

      if (token != null && userData != null) {
        final user = UserModel.fromJson(userData);
        state = AsyncValue.data(AuthState(user: user, token: token));
      } else {
        state = const AsyncValue.data(AuthState());
      }
    } catch (e) {
      state = const AsyncValue.data(AuthState());
    }
  }

  Future<bool> login(String email, String password) async {
    state = const AsyncValue.loading();

    try {
      final deviceId = await _storageService.getDeviceId();
      final deviceName = Platform.isIOS ? 'iOS Device' : 'Android Device';

      final response = await _authApi.login(
        email: email,
        password: password,
        deviceName: deviceName,
        deviceId: deviceId,
      );

      if (response.success) {
        await _storageService.saveToken(response.data.token);
        await _storageService.saveUser(response.data.user.toJson());

        state = AsyncValue.data(AuthState(
          user: response.data.user,
          token: response.data.token,
        ));
        return true;
      } else {
        state = AsyncValue.data(AuthState(error: response.message ?? 'Login failed'));
        return false;
      }
    } catch (e) {
      state = AsyncValue.data(AuthState(error: e.toString()));
      return false;
    }
  }

  Future<void> logout() async {
    try {
      await _authApi.logout();
    } catch (_) {
      // Continue with local logout even if API fails
    }

    await _storageService.clearAll();
    state = const AsyncValue.data(AuthState());
  }

  Future<void> refreshUser() async {
    try {
      final user = await _authApi.getUser();
      await _storageService.saveUser(user.toJson());

      final token = await _storageService.getToken();
      state = AsyncValue.data(AuthState(user: user, token: token));
    } catch (e) {
      // Keep current state if refresh fails
    }
  }

  void clearError() {
    final currentState = state.valueOrNull;
    if (currentState != null) {
      state = AsyncValue.data(AuthState(
        user: currentState.user,
        token: currentState.token,
      ));
    }
  }
}
