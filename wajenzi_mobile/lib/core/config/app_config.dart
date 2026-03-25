import 'package:flutter/foundation.dart';

class AppConfig {
  static const String appName = 'Wajenzi';
  static const String appVersion = '1.0.0';
  static const String _defaultPortalBaseUrl = 'https://wajenziprosystem.co.tz';
  static const String _apiPath = '/api/v1';
  static const String _clientApiPath = '/api/client';
  static const String _defaultClientApiBaseUrl =
      '$_defaultPortalBaseUrl$_clientApiPath';

  // API Configuration
  static const String apiBaseUrl = '$_defaultPortalBaseUrl$_apiPath';
  static const String devApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: apiBaseUrl,
  );
  static const String portalBaseUrl = String.fromEnvironment(
    'PORTAL_BASE_URL',
    defaultValue: _defaultPortalBaseUrl,
  );
  static const String clientApiBaseUrl = String.fromEnvironment(
    'CLIENT_API_BASE_URL',
    defaultValue: _defaultClientApiBaseUrl,
  );
  static const String supportEmail = String.fromEnvironment(
    'SUPPORT_EMAIL',
    defaultValue: 'support@wajenzi.com',
  );
  static const String companyPhoneNumber = String.fromEnvironment(
    'COMPANY_PHONE_NUMBER',
    defaultValue: '',
  );
  static const String whatsAppNumber = String.fromEnvironment(
    'WHATSAPP_NUMBER',
    defaultValue: '',
  );

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
  static const bool isRelease = bool.fromEnvironment('dart.vm.product');
  static const bool isProfile = bool.fromEnvironment('dart.vm.profile');
  static bool get isDebug => !isRelease && !isProfile;

  static String _normalizeBaseUrl(String url) =>
      url.replaceFirst(RegExp(r'\/+$'), '');

  static String _ensureApiBaseUrl(String url) {
    final normalized = _normalizeBaseUrl(url);

    if (normalized.endsWith(_apiPath)) {
      return normalized;
    }

    if (normalized.endsWith(_clientApiPath)) {
      return normalized.replaceFirst(RegExp('$_clientApiPath\$'), _apiPath);
    }

    if (normalized.contains('/api/')) {
      return normalized;
    }

    return '$normalized$_apiPath';
  }

  static String _ensureClientApiBaseUrl(String url) {
    final normalized = _normalizeBaseUrl(url);

    if (normalized.endsWith(_clientApiPath)) {
      return normalized;
    }

    if (normalized.endsWith(_apiPath)) {
      return normalized.replaceFirst(RegExp('$_apiPath\$'), _clientApiPath);
    }

    if (normalized.contains('/api/')) {
      return _deriveClientBaseUrl(normalized);
    }

    return '$normalized$_clientApiPath';
  }

  static String get _runtimeOrigin {
    if (!kIsWeb) {
      return _normalizeBaseUrl(portalBaseUrl);
    }

    final origin = Uri.base.origin;
    if (origin.isEmpty || origin == 'null') {
      return portalBaseUrl;
    }

    return _normalizeBaseUrl(origin);
  }

  // Override debug endpoints with --dart-define for local development.
  static String get baseUrl {
    if (kIsWeb) {
      return '$_runtimeOrigin$_apiPath';
    }

    return _ensureApiBaseUrl(isDebug ? devApiBaseUrl : apiBaseUrl);
  }

  static String _deriveClientBaseUrl(String apiUrl) {
    if (apiUrl.endsWith('/api/v1')) {
      return '${apiUrl.substring(0, apiUrl.length - '/api/v1'.length)}/api/client';
    }

    if (apiUrl.contains('/api/')) {
      return apiUrl.replaceFirst(RegExp(r'/api/[^/]+$'), '/api/client');
    }

    return _defaultClientApiBaseUrl;
  }

  static String get devClientApiBaseUrl {
    const explicitClientBaseUrl = String.fromEnvironment(
      'DEV_CLIENT_API_BASE_URL',
      defaultValue: '',
    );
    if (explicitClientBaseUrl.isNotEmpty) {
      return _ensureClientApiBaseUrl(explicitClientBaseUrl);
    }

    return _deriveClientBaseUrl(devApiBaseUrl);
  }

  static String get clientBaseUrl => kIsWeb
      ? '$_runtimeOrigin$_clientApiPath'
      : _ensureClientApiBaseUrl(isDebug ? devClientApiBaseUrl : clientApiBaseUrl);

  static String clientUrl(String path) {
    final normalizedPath = path.startsWith('/') ? path : '/$path';
    return '$clientBaseUrl$normalizedPath';
  }

  static String apiUrl(String path) {
    final normalizedPath = path.startsWith('/') ? path : '/$path';
    return '$baseUrl$normalizedPath';
  }

  static String portalUrl(String path) {
    final normalizedPath = path.startsWith('/') ? path : '/$path';
    final origin = kIsWeb ? _runtimeOrigin : _normalizeBaseUrl(portalBaseUrl);
    return '$origin$normalizedPath';
  }

  static String get webBaseUrl {
    return kIsWeb ? _runtimeOrigin : portalBaseUrl;
  }
}
