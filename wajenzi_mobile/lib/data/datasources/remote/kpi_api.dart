import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';
import '../../models/kpi_create_info.dart';
import '../../models/kpi_review_detail.dart';
import '../../models/kpi_review_list_item.dart';

final kpiApiProvider = Provider<KpiApi>((ref) {
  return KpiApi(ref.watch(apiClientProvider));
});

/// Authenticated KPI / Performance endpoints (base path `/performance`).
/// The auth token is injected automatically by the [ApiClient] interceptor.
class KpiApi {
  final ApiClient _apiClient;

  KpiApi(this._apiClient);

  /// GET /performance?tab=mine|awaiting|all
  Future<KpiReviewListResponse> fetchReviews({
    String tab = 'mine',
    int page = 1,
  }) async {
    final response = await _apiClient.get(
      '/performance',
      queryParameters: {'tab': tab, 'page': page},
    );
    final body = response.data as Map<String, dynamic>;
    final data = body['data'] as Map<String, dynamic>? ?? {};
    final meta = body['meta'] as Map<String, dynamic>?;
    return KpiReviewListResponse.fromResponse(data, meta);
  }

  /// GET /performance/create-info
  Future<KpiCreateInfo> fetchCreateInfo() async {
    final response = await _apiClient.get('/performance/create-info');
    final data = response.data['data'] as Map<String, dynamic>? ?? {};
    return KpiCreateInfo.fromJson(data);
  }

  /// POST /performance — returns the new review id.
  Future<int> createReview({
    required int kpiTemplateId,
    required String periodLabel,
    required String periodStart,
    required String periodEnd,
  }) async {
    final response = await _apiClient.post(
      '/performance',
      data: {
        'kpi_template_id': kpiTemplateId,
        'period_label': periodLabel,
        'period_start': periodStart,
        'period_end': periodEnd,
      },
    );
    final data = response.data['data'] as Map<String, dynamic>? ?? {};
    return (data['id'] as num?)?.toInt() ?? 0;
  }

  /// GET /performance/{id}
  Future<KpiReviewDetail> fetchReview(int id) async {
    final response = await _apiClient.get('/performance/$id');
    final data = response.data['data'] as Map<String, dynamic>? ?? {};
    return KpiReviewDetail.fromJson(data);
  }

  /// PATCH /performance/{id}/self
  Future<void> saveSelfAssessment(
    int id, {
    required List<Map<String, dynamic>> ratings,
    required String achievements,
    required String areasOfImprovement,
    required String trainingNeeds,
    required String employeeComments,
    required String action, // save | submit
  }) async {
    await _apiClient.patch(
      '/performance/$id/self',
      data: {
        'ratings': ratings,
        'achievements': achievements,
        'areas_of_improvement': areasOfImprovement,
        'training_needs': trainingNeeds,
        'employee_comments': employeeComments,
        'action': action,
      },
    );
  }

  /// POST /performance/{id}/submit
  Future<void> submit(int id) async {
    await _apiClient.post('/performance/$id/submit');
  }

  /// POST /performance/{id}/recall
  Future<void> recall(int id) async {
    await _apiClient.post('/performance/$id/recall');
  }

  /// PATCH /performance/{id}/review
  Future<void> review(
    int id, {
    required List<Map<String, dynamic>> ratings,
    required String stageComment,
    required String action, // save | approve | reject | return
    String? reason,
  }) async {
    final data = <String, dynamic>{
      'ratings': ratings,
      'stage_comment': stageComment,
      'action': action,
    };
    if (reason != null && reason.isNotEmpty) {
      data['reason'] = reason;
    }
    await _apiClient.patch('/performance/$id/review', data: data);
  }
}

extension on ApiClient {
  /// Convenience PATCH wrapper using the underlying Dio instance.
  Future patch(String path, {dynamic data}) {
    return dio.patch(_normalize(path), data: data);
  }

  String _normalize(String path) =>
      path.trim().replaceFirst(RegExp(r'^/+'), '');
}
