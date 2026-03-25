import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../config/app_config.dart';
import '../services/storage_service.dart';

final authInvalidationProvider = StateProvider<int>((ref) => 0);

final apiClientProvider = Provider<ApiClient>((ref) {
  final storageService = ref.watch(storageServiceProvider);
  return ApiClient(storageService, ref);
});

class ApiClient {
  late final Dio _dio;
  final StorageService _storageService;
  final Ref _ref;

  ApiClient(this._storageService, this._ref) {
    _dio = Dio(
      BaseOptions(
        baseUrl: AppConfig.baseUrl,
        connectTimeout: AppConfig.connectionTimeout,
        receiveTimeout: AppConfig.receiveTimeout,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
      ),
    );

    _dio.interceptors.addAll([
      _AuthInterceptor(_storageService, _ref),
      _LoggingInterceptor(),
    ]);
  }

  Dio get dio => _dio;

  // GET request
  Future<Response<T>> get<T>(
    String path, {
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.get<T>(
      path,
      queryParameters: queryParameters,
      options: options,
    );
  }

  // POST request
  Future<Response<T>> post<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.post<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
    );
  }

  // PUT request
  Future<Response<T>> put<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.put<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
    );
  }

  // DELETE request
  Future<Response<T>> delete<T>(
    String path, {
    dynamic data,
    Map<String, dynamic>? queryParameters,
    Options? options,
  }) async {
    return _dio.delete<T>(
      path,
      data: data,
      queryParameters: queryParameters,
      options: options,
    );
  }

  // Multipart request for file uploads
  Future<Response<T>> uploadFile<T>(
    String path, {
    required FormData data,
    Options? options,
    ProgressCallback? onSendProgress,
  }) async {
    return _dio.post<T>(
      path,
      data: data,
      options: options,
      onSendProgress: onSendProgress,
    );
  }
}

class _AuthInterceptor extends Interceptor {
  final StorageService _storageService;
  final Ref _ref;

  _AuthInterceptor(this._storageService, this._ref);

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _storageService.getToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    if (err.response?.statusCode == 401) {
      // Token expired or invalid - clear storage and redirect to login
      await _storageService.clearAll();
      _ref.read(authInvalidationProvider.notifier).state++;
    }
    handler.next(err);
  }
}

class _LoggingInterceptor extends Interceptor {
  static const Set<String> _sensitiveKeys = {
    'authorization',
    'password',
    'current_password',
    'new_password',
    'new_password_confirmation',
    'token',
    'auth_token',
    'access_token',
    'refresh_token',
    'device_id',
  };

  Map<String, dynamic> _redactHeaders(Map<String, dynamic> headers) {
    final sanitized = Map<String, dynamic>.from(headers);
    for (final key in sanitized.keys.toList()) {
      if (_sensitiveKeys.contains(key.toLowerCase())) {
        sanitized[key] = '[REDACTED]';
      }
    }
    return sanitized;
  }

  dynamic _redactData(dynamic data) {
    if (data is Map) {
      return data.map((key, value) {
        final normalizedKey = key.toString().toLowerCase();
        if (_sensitiveKeys.contains(normalizedKey)) {
          return MapEntry(key, '[REDACTED]');
        }
        return MapEntry(key, _redactData(value));
      });
    }

    if (data is List) {
      return data.map(_redactData).toList();
    }

    return data;
  }

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    if (!AppConfig.isRelease) {
      debugPrint('REQUEST[${options.method}] => PATH: ${options.path}');
      debugPrint('Headers: ${_redactHeaders(options.headers)}');
      if (options.data != null) {
        debugPrint('Data: ${_redactData(options.data)}');
      }
    }
    handler.next(options);
  }

  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    if (!AppConfig.isRelease) {
      debugPrint(
        'RESPONSE[${response.statusCode}] => PATH: ${response.requestOptions.path}',
      );
    }
    handler.next(response);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (!AppConfig.isRelease) {
      debugPrint(
        'ERROR[${err.response?.statusCode}] => PATH: ${err.requestOptions.path}',
      );
      debugPrint('Message: ${err.message}');
    }
    handler.next(err);
  }
}
