class AppConfig {
  static const String appName = 'Wajenzi';
  static const String appVersion = '1.0.0';

  // API Configuration
  static const String apiBaseUrl = 'https://wajenziprosystem.co.tz/api/v1';
  static const String devApiBaseUrl = 'http://localhost:8000/api/v1';

  // Timeout durations
  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);

  // Sync Configuration
  static const Duration syncInterval = Duration(minutes: 15);
  static const int maxSyncRetries = 3;

  // Cache Configuration
  static const Duration cacheExpiry = Duration(hours: 24);

  // Pagination
  static const int defaultPageSize = 20;

  // Storage Keys
  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String userTypeKey = 'user_type';
  static const String lastSyncKey = 'last_sync_time';
  static const String deviceIdKey = 'device_id';

  // Feature Flags
  static const bool enableOfflineMode = true;
  static const bool enablePushNotifications = true;
  static const bool enableBiometricAuth = false;

  // Environment
  static bool get isProduction => const bool.fromEnvironment('dart.vm.product');

  static String get baseUrl => isProduction ? apiBaseUrl : devApiBaseUrl;

  static String get clientBaseUrl => baseUrl.replaceAll('/api/v1', '/api/client');
}
