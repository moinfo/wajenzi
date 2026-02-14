import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/config/app_config.dart';
import '../../../core/network/api_client.dart';

final clientApiProvider = Provider<ClientApi>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return ClientApi(apiClient);
});

class ClientApi {
  final ApiClient _apiClient;

  ClientApi(this._apiClient);

  Future<ClientDashboardData> fetchDashboard() async {
    final url = '${AppConfig.clientBaseUrl}/dashboard';
    final response = await _apiClient.get(url);
    final data = response.data['data'] as Map<String, dynamic>;
    return ClientDashboardData.fromJson(data);
  }
}

// Safely parse a value that may be num or String to double.
double _toDouble(dynamic value) {
  if (value == null) return 0;
  if (value is num) return value.toDouble();
  if (value is String) return double.tryParse(value) ?? 0;
  return 0;
}

int _toInt(dynamic value) {
  if (value == null) return 0;
  if (value is int) return value;
  if (value is num) return value.toInt();
  if (value is String) return int.tryParse(value) ?? 0;
  return 0;
}

class ClientDashboardData {
  final Map<String, dynamic> stats;
  final List<ClientProject> projects;

  ClientDashboardData({required this.stats, required this.projects});

  factory ClientDashboardData.fromJson(Map<String, dynamic> json) {
    final rawProjects = json['projects'];
    // Resource collections may be a List directly or wrapped in {"data": [...]}
    final List projectList = rawProjects is List
        ? rawProjects
        : (rawProjects is Map ? (rawProjects['data'] as List? ?? []) : []);
    final projectsList = projectList
        .map((p) => ClientProject.fromJson(p as Map<String, dynamic>))
        .toList();
    return ClientDashboardData(
      stats: json['stats'] as Map<String, dynamic>,
      projects: projectsList,
    );
  }

  int get totalProjects => _toInt(stats['total_projects']);
  int get activeProjects => _toInt(stats['active_projects']);
  double get totalContractValue => _toDouble(stats['total_contract_value']);
  double get totalInvoiced => _toDouble(stats['total_invoiced']);
}

class ClientProject {
  final int id;
  final String? documentNumber;
  final String projectName;
  final String? status;
  final String? startDate;
  final String? expectedEndDate;
  final double contractValue;
  final int boqsCount;
  final int invoicesCount;
  final int dailyReportsCount;

  ClientProject({
    required this.id,
    this.documentNumber,
    required this.projectName,
    this.status,
    this.startDate,
    this.expectedEndDate,
    this.contractValue = 0,
    this.boqsCount = 0,
    this.invoicesCount = 0,
    this.dailyReportsCount = 0,
  });

  factory ClientProject.fromJson(Map<String, dynamic> json) {
    return ClientProject(
      id: _toInt(json['id']),
      documentNumber: json['document_number'] as String?,
      projectName: json['project_name'] as String? ?? '',
      status: json['status'] as String?,
      startDate: json['start_date'] as String?,
      expectedEndDate: json['expected_end_date'] as String?,
      contractValue: _toDouble(json['contract_value']),
      boqsCount: _toInt(json['boqs_count']),
      invoicesCount: _toInt(json['invoices_count']),
      dailyReportsCount: _toInt(json['daily_reports_count']),
    );
  }
}
