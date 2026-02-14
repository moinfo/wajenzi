import 'dart:io';
import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/services/storage_service.dart';
import '../../data/datasources/remote/auth_api.dart';
import '../../data/models/user_model.dart';

final authStateProvider = StateNotifierProvider<AuthNotifier, AsyncValue<AuthState>>((ref) {
  final authApi = ref.watch(authApiProvider);
  final storageService = ref.watch(storageServiceProvider);
  return AuthNotifier(authApi, storageService);
});

final userTypeProvider = Provider<String?>((ref) {
  return ref.watch(authStateProvider.notifier).userType;
});

class AuthNotifier extends StateNotifier<AsyncValue<AuthState>> {
  final AuthApi _authApi;
  final StorageService _storageService;
  String? _userType;

  String? get userType => _userType;

  AuthNotifier(this._authApi, this._storageService) : super(const AsyncValue.loading()) {
    _init();
  }

  Future<void> _init() async {
    try {
      final token = await _storageService.getToken();
      final userData = await _storageService.getUser();
      _userType = await _storageService.getUserType();

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

  Future<bool> login(String login, String password) async {
    state = const AsyncValue.loading();

    try {
      final deviceId = await _storageService.getDeviceId();
      final deviceName = Platform.isIOS ? 'iOS Device' : 'Android Device';

      // Try staff login first
      LoginResponse? response;
      String resolvedUserType = 'staff';

      try {
        response = await _authApi.login(
          email: login,
          password: password,
          deviceName: deviceName,
          deviceId: deviceId,
        );
      } on DioException catch (e) {
        final statusCode = e.response?.statusCode;
        if (statusCode == 401 || statusCode == 422) {
          // Staff login failed — try client login
          response = await _authApi.clientLogin(
            login: login,
            password: password,
            deviceName: deviceName,
          );
          resolvedUserType = 'client';
        } else {
          rethrow;
        }
      }

      if (response.success) {
        _userType = resolvedUserType;
        await _storageService.saveToken(response.data.token);
        await _storageService.saveUser(response.data.user.toJson());
        await _storageService.saveUserType(resolvedUserType);

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
      String errorMessage = 'Login failed';
      if (e is DioException) {
        final data = e.response?.data;
        if (data is Map<String, dynamic> && data['message'] != null) {
          errorMessage = data['message'];
        }
      }
      state = AsyncValue.data(AuthState(error: errorMessage));
      return false;
    }
  }

  Future<void> logout() async {
    try {
      if (_userType == 'client') {
        await _authApi.clientLogout();
      } else {
        await _authApi.logout();
      }
    } catch (_) {
      // Continue with local logout even if API fails
    }

    _userType = null;
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
