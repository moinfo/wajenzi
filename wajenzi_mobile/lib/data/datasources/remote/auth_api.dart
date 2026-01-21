import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../models/user_model.dart';

final authApiProvider = Provider<AuthApi>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return AuthApi(apiClient);
});

class AuthApi {
  final ApiClient _apiClient;

  AuthApi(this._apiClient);

  Future<LoginResponse> login({
    required String email,
    required String password,
    required String deviceName,
    String? deviceId,
  }) async {
    final response = await _apiClient.post(
      '/auth/login',
      data: {
        'email': email,
        'password': password,
        'device_name': deviceName,
        'device_id': deviceId,
      },
    );

    return LoginResponse.fromJson(response.data);
  }

  Future<void> logout() async {
    await _apiClient.post('/auth/logout');
  }

  Future<UserModel> getUser() async {
    final response = await _apiClient.get('/auth/user');
    return UserModel.fromJson(response.data['data']);
  }

  Future<UserModel> updateProfile({
    String? name,
    String? address,
  }) async {
    final response = await _apiClient.put(
      '/auth/profile',
      data: {
        if (name != null) 'name': name,
        if (address != null) 'address': address,
      },
    );
    return UserModel.fromJson(response.data['data']);
  }

  Future<void> registerDeviceToken({
    required String deviceId,
    required String fcmToken,
    required String platform,
  }) async {
    await _apiClient.post(
      '/auth/device-token',
      data: {
        'device_id': deviceId,
        'fcm_token': fcmToken,
        'platform': platform,
      },
    );
  }
}
