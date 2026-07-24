import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final supervisorAssignmentApiProvider = Provider<SupervisorAssignmentApi>((ref) {
  return SupervisorAssignmentApi(ref.watch(apiClientProvider));
});

/// A single active-staff row in the KPI reviewer matrix.
class SupervisorAssignmentUser {
  final int id;
  final String name;
  final String? email;
  final int? supervisorId;
  final int? departmentId;
  final String? supervisorName;

  const SupervisorAssignmentUser({
    required this.id,
    required this.name,
    this.email,
    this.supervisorId,
    this.departmentId,
    this.supervisorName,
  });

  factory SupervisorAssignmentUser.fromJson(Map<String, dynamic> json) {
    final sup = json['supervisor'] as Map<String, dynamic>?;
    return SupervisorAssignmentUser(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?) ?? '',
      email: json['email'] as String?,
      supervisorId: (json['supervisor_id'] as num?)?.toInt(),
      departmentId: (json['department_id'] as num?)?.toInt(),
      supervisorName: sup == null ? null : sup['name'] as String?,
    );
  }
}

/// A pickable supervisor candidate.
class SupervisorCandidate {
  final int id;
  final String name;

  const SupervisorCandidate({required this.id, required this.name});

  factory SupervisorCandidate.fromJson(Map<String, dynamic> json) {
    return SupervisorCandidate(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] as String?) ?? '',
    );
  }
}

/// Assigned / missing counts for the header.
class SupervisorAssignmentStats {
  final int total;
  final int assigned;
  final int missing;

  const SupervisorAssignmentStats({
    required this.total,
    required this.assigned,
    required this.missing,
  });

  factory SupervisorAssignmentStats.fromJson(Map<String, dynamic> json) {
    return SupervisorAssignmentStats(
      total: (json['total'] as num?)?.toInt() ?? 0,
      assigned: (json['assigned'] as num?)?.toInt() ?? 0,
      missing: (json['missing'] as num?)?.toInt() ?? 0,
    );
  }
}

/// The full matrix payload.
class SupervisorAssignmentData {
  final List<SupervisorAssignmentUser> users;
  final List<SupervisorCandidate> candidates;
  final SupervisorAssignmentStats stats;

  const SupervisorAssignmentData({
    required this.users,
    required this.candidates,
    required this.stats,
  });

  factory SupervisorAssignmentData.fromJson(Map<String, dynamic> json) {
    return SupervisorAssignmentData(
      users: ((json['users'] as List?) ?? [])
          .map((e) => SupervisorAssignmentUser.fromJson(
              e as Map<String, dynamic>))
          .toList(),
      candidates: ((json['candidates'] as List?) ?? [])
          .map((e) =>
              SupervisorCandidate.fromJson(e as Map<String, dynamic>))
          .toList(),
      stats: SupervisorAssignmentStats.fromJson(
          (json['stats'] as Map<String, dynamic>?) ?? const {}),
    );
  }
}

/// Authenticated KPI reviewer-matrix endpoints
/// (base path `/performance/supervisor-assignments`).
/// The auth token is injected automatically by the [ApiClient] interceptor.
class SupervisorAssignmentApi {
  final ApiClient _apiClient;

  SupervisorAssignmentApi(this._apiClient);

  /// GET /performance/supervisor-assignments
  Future<SupervisorAssignmentData> fetchAssignments() async {
    final response =
        await _apiClient.get('/performance/supervisor-assignments');
    final data = response.data['data'] as Map<String, dynamic>? ?? {};
    return SupervisorAssignmentData.fromJson(data);
  }

  /// PATCH /performance/supervisor-assignments
  ///
  /// [assignments] is a map keyed by userId → supervisorId (null clears the
  /// assignment). Serialized as `assignments[<userId>][supervisor_id]` to
  /// mirror the web form shape. Returns the number of rows changed.
  Future<int> saveAssignments(Map<int, int?> assignments) async {
    final payload = <String, dynamic>{};
    assignments.forEach((userId, supervisorId) {
      payload['$userId'] = {'supervisor_id': supervisorId};
    });

    final response = await _apiClient.patch(
      '/performance/supervisor-assignments',
      data: {'assignments': payload},
    );
    final body = response.data as Map<String, dynamic>;
    return (body['changed_count'] as num?)?.toInt() ?? 0;
  }
}
