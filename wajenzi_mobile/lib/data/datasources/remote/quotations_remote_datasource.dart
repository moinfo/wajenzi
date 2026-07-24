import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/network/api_client.dart';
import '../../models/procurement/quotation_models.dart';

/// Wraps [ApiClient] for the Supplier Quotations + Quotation Comparisons
/// endpoints. Base URL already includes `/api/v1`, so paths are relative.
class QuotationsRemoteDataSource {
  final ApiClient _api;
  QuotationsRemoteDataSource(this._api);

  // ── Supplier quotations ────────────────────────────────────────────────
  Future<List<SupplierQuotationDto>> listQuotations({
    String? search,
    String? status,
    int? materialRequestId,
    int perPage = 200,
  }) async {
    final qp = <String, dynamic>{'per_page': perPage};
    if (search != null && search.isNotEmpty) qp['search'] = search;
    if (status != null && status.isNotEmpty) qp['status'] = status;
    if (materialRequestId != null) qp['material_request_id'] = materialRequestId;
    final res = await _api.get('/procurement/supplier-quotations', queryParameters: qp);
    return _listItems(res.data)
        .map((e) => SupplierQuotationDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<SupplierQuotationDto> showQuotation(int id) async {
    final res = await _api.get('/procurement/supplier-quotations/$id');
    final data = (res.data as Map)['data'];
    return SupplierQuotationDto.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<QuotationReferenceData> referenceData() async {
    final res = await _api.get('/procurement/supplier-quotations/reference-data');
    final data = (res.data as Map)['data'];
    return QuotationReferenceData.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<List<SupplierRefDto>> availableSuppliers(int materialRequestId) async {
    final res = await _api
        .get('/procurement/supplier-quotations/available-suppliers/$materialRequestId');
    final data = (res.data as Map)['data'];
    final list = data is Map ? data['suppliers'] : null;
    if (list is! List) return const [];
    return list
        .whereType<Map>()
        .map((e) => SupplierRefDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<void> createQuotation(Map<String, dynamic> body, {String? filePath}) async {
    final formData = _buildQuotationFormData(body, filePath);
    await _api.uploadFile('/procurement/supplier-quotations', data: formData);
  }

  Future<void> updateQuotation(int id, Map<String, dynamic> body, {String? filePath}) async {
    // The update route is PUT, but multipart bodies are only parsed by PHP on
    // POST — so we POST with Laravel method spoofing (`_method=PUT`), which the
    // framework rewrites to the existing PUT route before dispatch.
    final formData = _buildQuotationFormData({...body, '_method': 'PUT'}, filePath);
    await _api.uploadFile('/procurement/supplier-quotations/$id', data: formData);
  }

  Future<void> deleteQuotation(int id) =>
      _api.delete('/procurement/supplier-quotations/$id');

  FormData _buildQuotationFormData(Map<String, dynamic> body, String? filePath) {
    // Copy scalars; nested `items` (List<Map>) is flattened by Dio into
    // items[i][field] bracket notation, which Laravel parses into an array.
    final map = <String, dynamic>{};
    body.forEach((key, value) {
      if (value == null) return;
      map[key] = value;
    });
    final formData = FormData.fromMap(map);
    if (filePath != null && filePath.isNotEmpty) {
      formData.files.add(
        MapEntry('file', MultipartFile.fromFileSync(filePath,
            filename: filePath.split('/').last)),
      );
    }
    return formData;
  }

  // ── Quotation comparisons ──────────────────────────────────────────────
  Future<List<ComparisonListItemDto>> listComparisons({
    String? search,
    String? status,
    int perPage = 200,
  }) async {
    final qp = <String, dynamic>{'per_page': perPage};
    if (search != null && search.isNotEmpty) qp['search'] = search;
    if (status != null && status.isNotEmpty) qp['status'] = status;
    final res = await _api.get('/procurement/quotation-comparisons', queryParameters: qp);
    return _listItems(res.data)
        .map((e) => ComparisonListItemDto.fromJson(Map<String, dynamic>.from(e)))
        .toList();
  }

  Future<ComparisonDetailDto> showComparison(int id) async {
    final res = await _api.get('/procurement/quotation-comparisons/$id');
    final data = (res.data as Map)['data'];
    return ComparisonDetailDto.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<ComparisonEligibilityDto> createEligibility(int materialRequestId) async {
    final res = await _api.get(
        '/procurement/quotation-comparisons/create-eligibility/$materialRequestId');
    final data = (res.data as Map)['data'];
    return ComparisonEligibilityDto.fromJson(Map<String, dynamic>.from(data as Map));
  }

  Future<void> createComparison({
    required int materialRequestId,
    required int selectedQuotationId,
    required String recommendationReason,
  }) async {
    await _api.post('/procurement/quotation-comparisons', data: {
      'material_request_id': materialRequestId,
      'selected_quotation_id': selectedQuotationId,
      'recommendation_reason': recommendationReason,
    });
  }

  Future<void> approveComparison(int id, {String? comment}) async {
    await _api.post('/procurement/quotation-comparisons/$id/approve',
        data: {'comment': comment ?? ''});
  }

  Future<void> rejectComparison(int id, String reason) async {
    await _api.post('/procurement/quotation-comparisons/$id/reject',
        data: {'reason': reason});
  }

  Future<Map<String, dynamic>> createPurchase(int id) async {
    final res = await _api.post('/procurement/quotation-comparisons/$id/create-purchase');
    final data = (res.data as Map)['data'];
    return data is Map ? Map<String, dynamic>.from(data) : const {};
  }

  /// Extracts a list from either `{data:[...]}` or `{data:{data:[...]}}`.
  List<Map> _listItems(dynamic raw) {
    if (raw is! Map) return const [];
    final data = raw['data'];
    if (data is List) return data.whereType<Map>().toList();
    if (data is Map) {
      final inner = data['data'];
      if (inner is List) return inner.whereType<Map>().toList();
    }
    return const [];
  }
}

final quotationsRemoteDataSourceProvider =
    Provider<QuotationsRemoteDataSource>((ref) {
  return QuotationsRemoteDataSource(ref.watch(apiClientProvider));
});
