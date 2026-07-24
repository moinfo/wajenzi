import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../models/procurement/inspection_models.dart';

/// Thin wrapper around [ApiClient] for the Material Inspections endpoints.
///
/// All paths are relative to the API base (which already includes `/api/v1`),
/// so they are prefixed with `/procurement/inspections`.
class InspectionsRemoteDataSource {
  final ApiClient _api;
  InspectionsRemoteDataSource(this._api);

  Future<InspectionListResult> list({
    int? projectId,
    String? status,
    String? search,
    String? startDate,
    String? endDate,
    int page = 1,
    int perPage = 20,
  }) async {
    final params = <String, dynamic>{'page': page, 'per_page': perPage};
    if (projectId != null) params['project_id'] = projectId;
    if (status != null && status.isNotEmpty) params['status'] = status;
    if (search != null && search.isNotEmpty) params['search'] = search;
    if (startDate != null && startDate.isNotEmpty) {
      params['start_date'] = startDate;
    }
    if (endDate != null && endDate.isNotEmpty) params['end_date'] = endDate;

    final res = await _api.get('/procurement/inspections',
        queryParameters: params);
    final body = res.data is Map ? Map<String, dynamic>.from(res.data) : const {};
    final data = body['data'] is Map
        ? Map<String, dynamic>.from(body['data'])
        : <String, dynamic>{};

    final inspections = ((data['data'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => InspectionDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();

    final pending = ((data['pending_receivings'] as List?) ?? const [])
        .whereType<Map>()
        .map((e) => PendingReceivingDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();

    final meta = data['meta'] is Map
        ? Map<String, dynamic>.from(data['meta'])
        : <String, dynamic>{};

    return InspectionListResult(
      inspections: inspections,
      pendingReceivings: pending,
      currentPage: (meta['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (meta['last_page'] as num?)?.toInt() ?? 1,
      total: (meta['total'] as num?)?.toInt() ?? inspections.length,
    );
  }

  Future<InspectionDto> show(int id) async {
    final res = await _api.get('/procurement/inspections/$id');
    return InspectionDto.fromJson(_data(res.data));
  }

  Future<InspectionCreateData> createData(int receivingId) async {
    final res = await _api.get(
        '/procurement/inspections/receivings/$receivingId/create-data');
    return InspectionCreateData.fromJson(_data(res.data));
  }

  Future<InspectionDto> store(Map<String, dynamic> body) async {
    final res = await _api.post('/procurement/inspections', data: body);
    return InspectionDto.fromJson(_data(res.data));
  }

  Future<InspectionDto> approve(int id, {String? comment}) async {
    final res = await _api.post('/procurement/inspections/$id/approve',
        data: {if (comment != null) 'comment': comment});
    return InspectionDto.fromJson(_data(res.data));
  }

  Future<InspectionDto> reject(int id, String reason) async {
    final res = await _api.post('/procurement/inspections/$id/reject',
        data: {'reason': reason});
    return InspectionDto.fromJson(_data(res.data));
  }

  Future<InspectionDto> updateStock(int id) async {
    final res = await _api.post('/procurement/inspections/$id/update-stock');
    return InspectionDto.fromJson(_data(res.data));
  }

  Map<String, dynamic> _data(dynamic raw) {
    if (raw is Map && raw['data'] is Map) {
      return Map<String, dynamic>.from(raw['data']);
    }
    if (raw is Map) return Map<String, dynamic>.from(raw);
    return <String, dynamic>{};
  }
}

final inspectionsRemoteDataSourceProvider =
    Provider<InspectionsRemoteDataSource>((ref) {
  return InspectionsRemoteDataSource(ref.watch(apiClientProvider));
});
