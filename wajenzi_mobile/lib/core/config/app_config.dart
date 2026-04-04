import 'dart:convert';
import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';

class AppConfig {
  static const String appName = 'Wajenzi';
  static const String appVersion = '1.0.0';
  static const String _defaultPortalBaseUrl = 'https://wajenziprosystem.co.tz';
  static const String _apiPath = '/api/v1';
  static const String _clientApiPath = '/api/client';
  static const String _defaultClientApiBaseUrl =
      '$_defaultPortalBaseUrl$_clientApiPath';
  static const String _explicitApiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: '',
  );
  static const String _explicitPortalBaseUrl = String.fromEnvironment(
    'PORTAL_BASE_URL',
    defaultValue: '',
  );
  static const String _explicitClientApiBaseUrl = String.fromEnvironment(
    'CLIENT_API_BASE_URL',
    defaultValue: '',
  );

  static Map<String, String>? _localConfig;
  static bool _initialized = false;

  static Future<void> initialize() async {
    if (_initialized) return;
    _initialized = true;
    try {
      final jsonString = await rootBundle.loadString(
        'assets/local_config.json',
      );
      final json = jsonDecode(jsonString) as Map<String, dynamic>;
      _localConfig = json.map((k, v) => MapEntry(k, v.toString()));
    } catch (_) {
      _localConfig = {};
    }
  }

  static String _getLocalApiUrl() {
    if (_localConfig == null) return '';
    return _localConfig!['API_BASE_URL'] ?? '';
  }

  static String _getLocalPortalUrl() {
    if (_localConfig == null) return '';
    return _localConfig!['PORTAL_BASE_URL'] ?? '';
  }

  static const String apiBaseUrl = '$_defaultPortalBaseUrl$_apiPath';

  static String get devApiBaseUrl {
    if (_explicitApiBaseUrl.isNotEmpty) return _explicitApiBaseUrl;
    final localUrl = _getLocalApiUrl();
    if (localUrl.isNotEmpty) return localUrl;
    return apiBaseUrl;
  }

  static String get portalBaseUrl {
    if (_explicitPortalBaseUrl.isNotEmpty) return _explicitPortalBaseUrl;
    final localUrl = _getLocalPortalUrl();
    if (localUrl.isNotEmpty) return localUrl;
    return _defaultPortalBaseUrl;
  }

  static String get clientApiBaseUrl {
    if (_explicitClientApiBaseUrl.isNotEmpty) return _explicitClientApiBaseUrl;
    return _defaultClientApiBaseUrl;
  }

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

  static const Duration connectionTimeout = Duration(seconds: 30);
  static const Duration receiveTimeout = Duration(seconds: 30);

  static const Duration syncInterval = Duration(minutes: 15);
  static const int maxSyncRetries = 3;

  static const Duration cacheExpiry = Duration(hours: 24);

  static const int defaultPageSize = 20;

  static const String tokenKey = 'auth_token';
  static const String userKey = 'user_data';
  static const String userTypeKey = 'user_type';
  static const String lastSyncKey = 'last_sync_time';
  static const String deviceIdKey = 'device_id';

  static const bool enableOfflineMode = true;
  static const bool enablePushNotifications = true;
  static const bool enableBiometricAuth = false;

  static const bool isRelease = bool.fromEnvironment('dart.vm.product');
  static const bool isProfile = bool.fromEnvironment('dart.vm.profile');
  static bool get isDebug => !isRelease && !isProfile;

  static String _normalizeBaseUrl(String url) =>
      url.replaceFirst(RegExp(r'\/+$'), '');

  static String _rewriteLocalhostForDevice(String url) {
    if (kIsWeb) return url;

    final normalized = _normalizeBaseUrl(url);
    final uri = Uri.tryParse(normalized);
    if (uri == null || uri.host.isEmpty) return normalized;

    final isLocalHost = uri.host == '127.0.0.1' || uri.host == 'localhost';
    if (!isLocalHost) return normalized;

    if (Platform.isAndroid) {
      return uri.replace(host: '10.0.2.2').toString();
    }
    return normalized;
  }

  static String _ensureApiBaseUrl(String url) {
    final normalized = _rewriteLocalhostForDevice(url);
    if (normalized.endsWith(_apiPath)) return normalized;
    if (normalized.endsWith(_clientApiPath)) {
      return normalized.replaceFirst(RegExp('$_clientApiPath\$'), _apiPath);
    }
    if (normalized.contains('/api/')) return normalized;
    return '$normalized$_apiPath';
  }

  static String _ensureClientApiBaseUrl(String url) {
    final normalized = _rewriteLocalhostForDevice(url);
    if (normalized.endsWith(_clientApiPath)) return normalized;
    if (normalized.endsWith(_apiPath)) {
      return normalized.replaceFirst(RegExp('$_apiPath\$'), _clientApiPath);
    }
    if (normalized.contains('/api/')) return _deriveClientBaseUrl(normalized);
    return '$normalized$_clientApiPath';
  }

  static String get _runtimeOrigin {
    if (!kIsWeb) return _rewriteLocalhostForDevice(portalBaseUrl);
    final origin = Uri.base.origin;
    if (origin.isEmpty || origin == 'null') return portalBaseUrl;
    return _normalizeBaseUrl(origin);
  }

  static String get baseUrl {
    if (kIsWeb) return '$_runtimeOrigin$_apiPath';
    if (_explicitApiBaseUrl.isNotEmpty)
      return _ensureApiBaseUrl(_explicitApiBaseUrl);
    if (_explicitPortalBaseUrl.isNotEmpty)
      return _ensureApiBaseUrl(_explicitPortalBaseUrl);
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
    if (_explicitClientApiBaseUrl.isNotEmpty) {
      return _ensureClientApiBaseUrl(_explicitClientApiBaseUrl);
    }
    return _deriveClientBaseUrl(devApiBaseUrl);
  }

  static String get clientBaseUrl {
    if (kIsWeb) return '$_runtimeOrigin$_clientApiPath';
    if (_explicitClientApiBaseUrl.isNotEmpty) {
      return _ensureClientApiBaseUrl(_explicitClientApiBaseUrl);
    }
    if (_explicitPortalBaseUrl.isNotEmpty) {
      return _ensureClientApiBaseUrl(_explicitPortalBaseUrl);
    }
    return _ensureClientApiBaseUrl(
      isDebug ? devClientApiBaseUrl : clientApiBaseUrl,
    );
  }

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
    final origin = kIsWeb
        ? _runtimeOrigin
        : _rewriteLocalhostForDevice(portalBaseUrl);
    return '$origin$normalizedPath';
  }

  static String get webBaseUrl => kIsWeb ? _runtimeOrigin : portalBaseUrl;

  static String get activePortalBaseUrl {
    return kIsWeb ? _runtimeOrigin : _rewriteLocalhostForDevice(portalBaseUrl);
  }

  static String get canonicalPortalBaseUrl => _defaultPortalBaseUrl;

  static String? resolvePortalMediaUrl(String? path) {
    if (path == null || path.isEmpty) return null;
    final uri = Uri.tryParse(path);
    if (uri != null && uri.hasScheme) {
      return normalizeExternalUrl(path);
    }
    final normalizedPath = path.startsWith('/') ? path : '/$path';
    return '$activePortalBaseUrl$normalizedPath';
  }

  static String? normalizeExternalUrl(String? url) {
    if (url == null || url.isEmpty) return null;
    final parsed = Uri.tryParse(url);
    if (parsed == null) return url;
    if (!parsed.hasScheme) return portalUrl(url);
    final isLoopbackHost =
        parsed.host == '127.0.0.1' || parsed.host == 'localhost';
    if (isLoopbackHost) {
      final normalizedPath = parsed.path.startsWith('/')
          ? parsed.path
          : '/${parsed.path}';
      final query = parsed.hasQuery ? '?${parsed.query}' : '';
      final fragment = parsed.hasFragment ? '#${parsed.fragment}' : '';
      return portalUrl('$normalizedPath$query$fragment');
    }
    return _rewriteLocalhostForDevice(url);
  }
}
