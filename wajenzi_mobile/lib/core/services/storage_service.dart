import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';
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

  Future<SharedPreferences> _prefs() => SharedPreferences.getInstance();

  // Token Management
  Future<void> saveToken(String token) async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.setString(AppConfig.tokenKey, token);
      return;
    }
    await _storage.write(key: AppConfig.tokenKey, value: token);
  }

  Future<String?> getToken() async {
    if (kIsWeb) {
      final prefs = await _prefs();
      return prefs.getString(AppConfig.tokenKey);
    }
    return _storage.read(key: AppConfig.tokenKey);
  }

  Future<void> deleteToken() async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.remove(AppConfig.tokenKey);
      return;
    }
    await _storage.delete(key: AppConfig.tokenKey);
  }

  // User Data
  Future<void> saveUser(Map<String, dynamic> user) async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.setString(AppConfig.userKey, jsonEncode(user));
      return;
    }
    await _storage.write(key: AppConfig.userKey, value: jsonEncode(user));
  }

  Future<Map<String, dynamic>?> getUser() async {
    final data = kIsWeb
        ? (await _prefs()).getString(AppConfig.userKey)
        : await _storage.read(key: AppConfig.userKey);
    if (data != null) {
      return jsonDecode(data) as Map<String, dynamic>;
    }
    return null;
  }

  Future<void> deleteUser() async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.remove(AppConfig.userKey);
      return;
    }
    await _storage.delete(key: AppConfig.userKey);
  }

  // User Type (staff or client)
  Future<void> saveUserType(String type) async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.setString(AppConfig.userTypeKey, type);
      return;
    }
    await _storage.write(key: AppConfig.userTypeKey, value: type);
  }

  Future<String?> getUserType() async {
    if (kIsWeb) {
      final prefs = await _prefs();
      return prefs.getString(AppConfig.userTypeKey);
    }
    return _storage.read(key: AppConfig.userTypeKey);
  }

  Future<void> deleteUserType() async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.remove(AppConfig.userTypeKey);
      return;
    }
    await _storage.delete(key: AppConfig.userTypeKey);
  }

  // Device ID
  Future<String> getDeviceId() async {
    var deviceId = kIsWeb
        ? (await _prefs()).getString(AppConfig.deviceIdKey)
        : await _storage.read(key: AppConfig.deviceIdKey);
    if (deviceId == null) {
      deviceId = const Uuid().v4();
      if (kIsWeb) {
        final prefs = await _prefs();
        await prefs.setString(AppConfig.deviceIdKey, deviceId);
      } else {
        await _storage.write(key: AppConfig.deviceIdKey, value: deviceId);
      }
    }
    return deviceId;
  }

  // Last Sync Time
  Future<void> saveLastSyncTime(DateTime time) async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.setString(AppConfig.lastSyncKey, time.toIso8601String());
      return;
    }
    await _storage.write(
      key: AppConfig.lastSyncKey,
      value: time.toIso8601String(),
    );
  }

  Future<DateTime?> getLastSyncTime() async {
    final data = kIsWeb
        ? (await _prefs()).getString(AppConfig.lastSyncKey)
        : await _storage.read(key: AppConfig.lastSyncKey);
    if (data != null) {
      return DateTime.parse(data);
    }
    return null;
  }

  // Generic key-value storage
  Future<void> write(String key, String value) async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.setString(key, value);
      return;
    }
    await _storage.write(key: key, value: value);
  }

  Future<String?> read(String key) async {
    if (kIsWeb) {
      final prefs = await _prefs();
      return prefs.getString(key);
    }
    return _storage.read(key: key);
  }

  Future<void> delete(String key) async {
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.remove(key);
      return;
    }
    await _storage.delete(key: key);
  }

  // Clear all data
  Future<void> clearAll() async {
    // Keep device ID
    final deviceId = await getDeviceId();
    if (kIsWeb) {
      final prefs = await _prefs();
      await prefs.clear();
      await prefs.setString(AppConfig.deviceIdKey, deviceId);
      return;
    }
    await _storage.deleteAll();
    await _storage.write(key: AppConfig.deviceIdKey, value: deviceId);
  }
}
