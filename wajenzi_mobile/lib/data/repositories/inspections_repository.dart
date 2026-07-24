import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../datasources/remote/inspections_remote_datasource.dart';
import '../models/procurement/inspection_models.dart';

/// Repository facade for the Material Inspections feature.
///
/// Thin pass-through to [InspectionsRemoteDataSource]; kept in its own layer so
/// caching / offline behaviour can be added later without touching screens.
class InspectionsRepository {
  final InspectionsRemoteDataSource _remote;
  InspectionsRepository(this._remote);

  Future<InspectionListResult> list({
    int? projectId,
    String? status,
    String? search,
    String? startDate,
    String? endDate,
    int page = 1,
    int perPage = 20,
  }) =>
      _remote.list(
        projectId: projectId,
        status: status,
        search: search,
        startDate: startDate,
        endDate: endDate,
        page: page,
        perPage: perPage,
      );

  Future<InspectionDto> show(int id) => _remote.show(id);

  Future<InspectionCreateData> createData(int receivingId) =>
      _remote.createData(receivingId);

  Future<InspectionDto> store(Map<String, dynamic> body) =>
      _remote.store(body);

  Future<InspectionDto> approve(int id, {String? comment}) =>
      _remote.approve(id, comment: comment);

  Future<InspectionDto> reject(int id, String reason) =>
      _remote.reject(id, reason);

  Future<InspectionDto> updateStock(int id) => _remote.updateStock(id);
}

final inspectionsRepositoryProvider = Provider<InspectionsRepository>((ref) {
  return InspectionsRepository(ref.watch(inspectionsRemoteDataSourceProvider));
});
