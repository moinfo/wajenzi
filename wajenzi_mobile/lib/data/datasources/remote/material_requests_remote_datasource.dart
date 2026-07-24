import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../models/procurement/material_request_models.dart';

/// Thin wrapper around [ApiClient] for the material-requests endpoints.
///
/// Base URL already includes `/api/v1`, so paths are relative.
class MaterialRequestsRemoteDataSource {
  final ApiClient _api;
  MaterialRequestsRemoteDataSource(this._api);

  Future<MaterialRequestPage> list({
    int? projectId,
    String? status,
    String? search,
    bool myRequests = false,
    int page = 1,
    int perPage = 20,
  }) async {
    final qp = <String, dynamic>{'page': page, 'per_page': perPage};
    if (projectId != null) qp['project_id'] = projectId;
    if (status != null && status.isNotEmpty) qp['status'] = status;
    if (search != null && search.isNotEmpty) qp['search'] = search;
    if (myRequests) qp['my_requests'] = 1;

    final res = await _api.get('/material-requests', queryParameters: qp);
    return MaterialRequestPage.fromEnvelope(res.data);
  }

  Future<MaterialRequestDto> show(int id) async {
    final res = await _api.get('/material-requests/$id');
    final data = (res.data is Map ? res.data['data'] : null);
    return MaterialRequestDto.fromJson(
      Map<String, dynamic>.from(data as Map? ?? const {}),
    );
  }

  Future<MrReferenceData> referenceData({int? projectId}) async {
    final qp = <String, dynamic>{};
    if (projectId != null) qp['project_id'] = projectId;
    final res = await _api.get(
      '/material-requests/reference-data',
      queryParameters: qp.isEmpty ? null : qp,
    );
    final data = (res.data is Map ? res.data['data'] : null);
    return MrReferenceData.fromJson(
      Map<String, dynamic>.from(data as Map? ?? const {}),
    );
  }

  Future<MaterialRequestDto> createBulk(Map<String, dynamic> body) async {
    final res = await _api.post('/material-requests/bulk', data: body);
    final data = (res.data is Map ? res.data['data'] : null);
    return MaterialRequestDto.fromJson(
      Map<String, dynamic>.from(data as Map? ?? const {}),
    );
  }

  Future<MaterialRequestDto> updateQuantities(
    int id,
    Map<String, dynamic> quantities,
  ) async {
    final res = await _api.post(
      '/material-requests/$id/quantities',
      data: {'quantities': quantities},
    );
    final data = (res.data is Map ? res.data['data'] : null);
    return MaterialRequestDto.fromJson(
      Map<String, dynamic>.from(data as Map? ?? const {}),
    );
  }

  Future<void> delete(int id) => _api.delete('/material-requests/$id');

  Future<void> submit(int id) => _api.post('/material-requests/$id/submit');

  Future<void> approve(int id, {String? comment}) => _api.post(
        '/material-requests/$id/approve',
        data: {if (comment != null) 'comment': comment},
      );

  Future<void> reject(int id, String reason) => _api.post(
        '/material-requests/$id/reject',
        data: {'reason': reason},
      );
}

final materialRequestsRemoteDataSourceProvider =
    Provider<MaterialRequestsRemoteDataSource>((ref) {
  return MaterialRequestsRemoteDataSource(ref.watch(apiClientProvider));
});
