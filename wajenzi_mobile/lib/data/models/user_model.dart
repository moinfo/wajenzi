import 'package:freezed_annotation/freezed_annotation.dart';

part 'user_model.freezed.dart';
part 'user_model.g.dart';

@freezed
sealed class UserModel with _$UserModel {
  const factory UserModel({
    required int id,
    required String name,
    required String email,
    String? employeeNumber,
    String? designation,
    String? department,
    String? profileUrl,
    String? signatureUrl,
    String? status,
    List<String>? roles,
    List<String>? permissions,
    DateTime? createdAt,
  }) = _UserModel;

  factory UserModel.fromJson(Map<String, dynamic> json) =>
      _$UserModelFromJson(json);
}

@freezed
sealed class AuthState with _$AuthState {
  const AuthState._();

  const factory AuthState({
    UserModel? user,
    String? token,
    @Default(false) bool isLoading,
    String? error,
  }) = _AuthState;

  bool get isAuthenticated => user != null && token != null;
}

@freezed
sealed class LoginRequest with _$LoginRequest {
  const factory LoginRequest({
    required String email,
    required String password,
    required String deviceName,
    String? deviceId,
  }) = _LoginRequest;

  factory LoginRequest.fromJson(Map<String, dynamic> json) =>
      _$LoginRequestFromJson(json);
}

@freezed
sealed class LoginResponse with _$LoginResponse {
  const factory LoginResponse({
    required bool success,
    required LoginData data,
    String? message,
  }) = _LoginResponse;

  factory LoginResponse.fromJson(Map<String, dynamic> json) =>
      _$LoginResponseFromJson(json);
}

@freezed
sealed class LoginData with _$LoginData {
  const factory LoginData({
    required UserModel user,
    required String token,
    List<String>? abilities,
  }) = _LoginData;

  factory LoginData.fromJson(Map<String, dynamic> json) =>
      _$LoginDataFromJson(json);
}
