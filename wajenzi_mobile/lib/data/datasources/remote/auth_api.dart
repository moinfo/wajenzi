import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/app_config.dart';
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

  Future<LoginResponse> clientLogin({
    required String login,
    required String password,
    required String deviceName,
  }) async {
    final url = '${AppConfig.clientBaseUrl}/auth/login';
    final response = await _apiClient.post(
      url,
      data: {
        'login': login,
        'password': password,
        'device_name': deviceName,
      },
    );

    final responseData = response.data as Map<String, dynamic>;
    final data = responseData['data'] as Map<String, dynamic>;
    final client = data['client'] as Map<String, dynamic>;

    final user = UserModel(
      id: client['id'] as int,
      name: client['full_name'] as String,
      email: (client['email'] as String?) ?? '',
    );

    return LoginResponse(
      success: responseData['success'] as bool,
      data: LoginData(user: user, token: data['token'] as String),
      message: responseData['message'] as String?,
    );
  }

  Future<void> logout() async {
    await _apiClient.post('/auth/logout');
  }

  Future<void> clientLogout() async {
    final url = '${AppConfig.clientBaseUrl}/auth/logout';
    await _apiClient.post(url);
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
