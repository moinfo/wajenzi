import 'dart:convert';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';
import '../config/app_config.dart';

final storageServiceProvider = Provider<StorageService>((ref) {
  return StorageService();
});

class StorageService {
  final FlutterSecureStorage _storage = const FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
    iOptions: IOSOptions(accessibility: KeychainAccessibility.first_unlock),
  );

  // Token Management
  Future<void> saveToken(String token) async {
    await _storage.write(key: AppConfig.tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return _storage.read(key: AppConfig.tokenKey);
  }

  Future<void> deleteToken() async {
    await _storage.delete(key: AppConfig.tokenKey);
  }

  // User Data
  Future<void> saveUser(Map<String, dynamic> user) async {
    await _storage.write(key: AppConfig.userKey, value: jsonEncode(user));
  }

  Future<Map<String, dynamic>?> getUser() async {
    final data = await _storage.read(key: AppConfig.userKey);
    if (data != null) {
      return jsonDecode(data) as Map<String, dynamic>;
    }
    return null;
  }

  Future<void> deleteUser() async {
    await _storage.delete(key: AppConfig.userKey);
  }

  // User Type (staff or client)
  Future<void> saveUserType(String type) async {
    await _storage.write(key: AppConfig.userTypeKey, value: type);
  }

  Future<String?> getUserType() async {
    return _storage.read(key: AppConfig.userTypeKey);
  }

  Future<void> deleteUserType() async {
    await _storage.delete(key: AppConfig.userTypeKey);
  }

  // Device ID
  Future<String> getDeviceId() async {
    var deviceId = await _storage.read(key: AppConfig.deviceIdKey);
    if (deviceId == null) {
      deviceId = const Uuid().v4();
      await _storage.write(key: AppConfig.deviceIdKey, value: deviceId);
    }
    return deviceId;
  }

  // Last Sync Time
  Future<void> saveLastSyncTime(DateTime time) async {
    await _storage.write(
      key: AppConfig.lastSyncKey,
      value: time.toIso8601String(),
    );
  }

  Future<DateTime?> getLastSyncTime() async {
    final data = await _storage.read(key: AppConfig.lastSyncKey);
    if (data != null) {
      return DateTime.parse(data);
    }
    return null;
  }

  // Generic key-value storage
  Future<void> write(String key, String value) async {
    await _storage.write(key: key, value: value);
  }

  Future<String?> read(String key) async {
    return _storage.read(key: key);
  }

  Future<void> delete(String key) async {
    await _storage.delete(key: key);
  }

  // Clear all data
  Future<void> clearAll() async {
    // Keep device ID
    final deviceId = await getDeviceId();
    await _storage.deleteAll();
    await _storage.write(key: AppConfig.deviceIdKey, value: deviceId);
  }
}
