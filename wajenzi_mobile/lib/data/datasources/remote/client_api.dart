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

  Future<ClientBillingData> fetchBilling() async {
    final url = '${AppConfig.clientBaseUrl}/billing';
    final response = await _apiClient.get(url);
    final data = response.data['data'] as Map<String, dynamic>;
    return ClientBillingData.fromJson(data);
  }

  String billingPdfUrl(int documentId) =>
      '${AppConfig.clientBaseUrl}/billing/$documentId/pdf';
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

// ─── Billing Data Classes ────────────────────────

List<T> _parseList<T>(dynamic raw, T Function(Map<String, dynamic>) fromJson) {
  final List items = raw is List
      ? raw
      : (raw is Map ? (raw['data'] as List? ?? []) : []);
  return items.map((e) => fromJson(e as Map<String, dynamic>)).toList();
}

class ClientBillingData {
  final Map<String, dynamic> summary;
  final List<BillingDocument> invoices;
  final List<BillingDocument> quotes;
  final List<BillingDocument> proformas;
  final List<BillingDocument> creditNotes;

  ClientBillingData({
    required this.summary,
    required this.invoices,
    required this.quotes,
    required this.proformas,
    required this.creditNotes,
  });

  factory ClientBillingData.fromJson(Map<String, dynamic> json) {
    return ClientBillingData(
      summary: json['summary'] as Map<String, dynamic>? ?? {},
      invoices: _parseList(json['invoices'], BillingDocument.fromJson),
      quotes: _parseList(json['quotes'], BillingDocument.fromJson),
      proformas: _parseList(json['proformas'], BillingDocument.fromJson),
      creditNotes: _parseList(json['credit_notes'], BillingDocument.fromJson),
    );
  }

  double get totalInvoiced => _toDouble(summary['total_invoiced']);
  double get totalPaid => _toDouble(summary['total_paid']);
  double get balanceDue => _toDouble(summary['balance_due']);
  int get overdueCount => _toInt(summary['overdue_count']);

  /// All payments flattened from all invoices
  List<BillingPayment> get allPayments =>
      invoices.expand((inv) => inv.payments).toList();
}

class BillingDocument {
  final int id;
  final String documentType;
  final String? documentNumber;
  final String? projectName;
  final int? projectId;
  final String? issueDate;
  final String? dueDate;
  final String? validUntilDate;
  final double totalAmount;
  final double paidAmount;
  final double balanceAmount;
  final String? status;
  final String? paymentTerms;
  final List<BillingPayment> payments;

  BillingDocument({
    required this.id,
    required this.documentType,
    this.documentNumber,
    this.projectName,
    this.projectId,
    this.issueDate,
    this.dueDate,
    this.validUntilDate,
    this.totalAmount = 0,
    this.paidAmount = 0,
    this.balanceAmount = 0,
    this.status,
    this.paymentTerms,
    this.payments = const [],
  });

  factory BillingDocument.fromJson(Map<String, dynamic> json) {
    final project = json['project'];
    String? projName;
    if (project is Map<String, dynamic>) {
      projName = project['project_name'] as String?;
    }

    return BillingDocument(
      id: _toInt(json['id']),
      documentType: json['document_type'] as String? ?? 'invoice',
      documentNumber: json['document_number'] as String?,
      projectName: projName,
      projectId: json['project_id'] as int?,
      issueDate: json['issue_date'] as String?,
      dueDate: json['due_date'] as String?,
      validUntilDate: json['valid_until_date'] as String?,
      totalAmount: _toDouble(json['total_amount']),
      paidAmount: _toDouble(json['paid_amount']),
      balanceAmount: _toDouble(json['balance_amount']),
      status: json['status'] as String?,
      paymentTerms: json['payment_terms'] as String?,
      payments: _parseList(
        json['payments'],
        BillingPayment.fromJson,
      ),
    );
  }

  bool get isOverdue {
    if (dueDate == null || status == 'paid') return false;
    try {
      return DateTime.parse(dueDate!).isBefore(DateTime.now());
    } catch (_) {
      return false;
    }
  }
}

class BillingPayment {
  final int id;
  final String? paymentNumber;
  final String? invoiceNumber;
  final String? paymentDate;
  final double amount;
  final String? paymentMethod;
  final String? referenceNumber;
  final String? status;

  BillingPayment({
    required this.id,
    this.paymentNumber,
    this.invoiceNumber,
    this.paymentDate,
    this.amount = 0,
    this.paymentMethod,
    this.referenceNumber,
    this.status,
  });

  factory BillingPayment.fromJson(Map<String, dynamic> json) {
    final doc = json['document'];
    String? invNum;
    if (doc is Map<String, dynamic>) {
      invNum = doc['document_number'] as String?;
    }

    return BillingPayment(
      id: _toInt(json['id']),
      paymentNumber: json['payment_number'] as String?,
      invoiceNumber: invNum,
      paymentDate: json['payment_date'] as String?,
      amount: _toDouble(json['amount']),
      paymentMethod: json['payment_method'] as String?,
      referenceNumber: json['reference_number'] as String?,
      status: json['status'] as String?,
    );
  }
}
