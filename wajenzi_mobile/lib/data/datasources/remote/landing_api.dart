import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../models/landing_about_model.dart';
import '../../models/landing_award_model.dart';
import '../../models/landing_poster_model.dart';
import '../../models/landing_project_model.dart';
import '../../models/landing_service_model.dart';
import '../../models/landing_stat_model.dart';

final landingApiProvider = Provider<LandingApi>((ref) {
  return LandingApi(ref.watch(apiClientProvider));
});

/// Public (unauthenticated) landing-content endpoints.
class LandingApi {
  final ApiClient _apiClient;

  LandingApi(this._apiClient);

  Future<List<LandingProjectModel>> fetchPortfolio({
    required String lang,
    required String deviceId,
  }) async {
    final response = await _apiClient.get(
      '/public/portfolio',
      queryParameters: {'lang': lang, 'device_id': deviceId},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => LandingProjectModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<LandingAwardModel>> fetchAwards({required String lang}) async {
    final response = await _apiClient.get(
      '/public/awards',
      queryParameters: {'lang': lang},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => LandingAwardModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<LandingServiceModel>> fetchServices({required String lang}) async {
    final response = await _apiClient.get(
      '/public/services',
      queryParameters: {'lang': lang},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => LandingServiceModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<LandingStatModel>> fetchStats({required String lang}) async {
    final response = await _apiClient.get(
      '/public/stats',
      queryParameters: {'lang': lang},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => LandingStatModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<LandingPosterModel>> fetchPosters({required String lang}) async {
    final response = await _apiClient.get(
      '/public/posters',
      queryParameters: {'lang': lang},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => LandingPosterModel.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<LandingAboutModel> fetchAbout({required String lang}) async {
    final response = await _apiClient.get(
      '/public/about',
      queryParameters: {'lang': lang},
    );
    return LandingAboutModel.fromJson(
      response.data['data'] as Map<String, dynamic>,
    );
  }

  /// Toggle a like for the current device. Returns the fresh state.
  Future<({bool liked, int likesCount})> toggleLike({
    required int id,
    required String deviceId,
  }) async {
    final response = await _apiClient.post(
      '/public/portfolio/$id/like',
      data: {'device_id': deviceId},
    );
    final data = response.data['data'] as Map<String, dynamic>? ?? {};
    return (
      liked: data['liked'] == true,
      likesCount: (data['likes_count'] as num?)?.toInt() ?? 0,
    );
  }
}
