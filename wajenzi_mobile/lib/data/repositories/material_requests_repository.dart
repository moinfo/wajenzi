import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../datasources/remote/material_requests_remote_datasource.dart';
import '../models/procurement/material_request_models.dart';

/// Repository facade over [MaterialRequestsRemoteDataSource].
///
/// Thin pass-through today; isolates screen code from the network layer.
class MaterialRequestsRepository {
  final MaterialRequestsRemoteDataSource _remote;
  MaterialRequestsRepository(this._remote);

  Future<MaterialRequestPage> list({
    int? projectId,
    String? status,
    String? search,
    bool myRequests = false,
    int page = 1,
    int perPage = 20,
  }) =>
      _remote.list(
        projectId: projectId,
        status: status,
        search: search,
        myRequests: myRequests,
        page: page,
        perPage: perPage,
      );

  Future<MaterialRequestDto> show(int id) => _remote.show(id);

  Future<MrReferenceData> referenceData({int? projectId}) =>
      _remote.referenceData(projectId: projectId);

  Future<MaterialRequestDto> createBulk(Map<String, dynamic> body) =>
      _remote.createBulk(body);

  Future<MaterialRequestDto> updateQuantities(
    int id,
    Map<String, dynamic> quantities,
  ) =>
      _remote.updateQuantities(id, quantities);

  Future<void> delete(int id) => _remote.delete(id);

  Future<void> submit(int id) => _remote.submit(id);

  Future<void> approve(int id, {String? comment}) =>
      _remote.approve(id, comment: comment);

  Future<void> reject(int id, String reason) => _remote.reject(id, reason);
}

final materialRequestsRepositoryProvider =
    Provider<MaterialRequestsRepository>((ref) {
  return MaterialRequestsRepository(
    ref.watch(materialRequestsRemoteDataSourceProvider),
  );
});
