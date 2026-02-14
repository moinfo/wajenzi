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

  // ─── Project Detail API ──────────────────────────

  Future<ProjectOverviewData> fetchProjectDetail(int id) async {
    final url = '${AppConfig.clientBaseUrl}/projects/$id';
    final response = await _apiClient.get(url);
    final data = response.data['data'] as Map<String, dynamic>;
    return ProjectOverviewData.fromJson(data);
  }

  Future<List<ProjectBoq>> fetchProjectBoq(int id) async {
    final url = '${AppConfig.clientBaseUrl}/projects/$id/boq';
    final response = await _apiClient.get(url);
    final data = response.data['data'];
    final list = data is List
        ? data
        : (data is Map ? (data['data'] as List? ?? []) : []);
    return list
        .map((e) => ProjectBoq.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<ProjectScheduleData> fetchProjectSchedule(int id) async {
    final url = '${AppConfig.clientBaseUrl}/projects/$id/schedule';
    final response = await _apiClient.get(url);
    final data = response.data['data'] as Map<String, dynamic>;
    return ProjectScheduleData.fromJson(data);
  }

  Future<ProjectFinancials> fetchProjectFinancials(int id) async {
    final url = '${AppConfig.clientBaseUrl}/projects/$id/financials';
    final response = await _apiClient.get(url);
    final data = response.data['data'] as Map<String, dynamic>;
    return ProjectFinancials.fromJson(data);
  }

  Future<List<ProjectDesign>> fetchProjectDocuments(int id) async {
    final url = '${AppConfig.clientBaseUrl}/projects/$id/documents';
    final response = await _apiClient.get(url);
    final data = response.data['data'];
    return _parseList(data, ProjectDesign.fromJson);
  }

  Future<ProjectReportsData> fetchProjectReports(int id) async {
    final url = '${AppConfig.clientBaseUrl}/projects/$id/reports';
    final response = await _apiClient.get(url);
    final data = response.data['data'] as Map<String, dynamic>;
    return ProjectReportsData.fromJson(data);
  }

  Future<ProjectGalleryData> fetchProjectGallery(int id) async {
    final url = '${AppConfig.clientBaseUrl}/projects/$id/gallery';
    final response = await _apiClient.get(url);
    final data = response.data['data'] as Map<String, dynamic>;
    return ProjectGalleryData.fromJson(data);
  }

  String projectBillingPdfUrl(int projectId, int documentId) =>
      '${AppConfig.clientBaseUrl}/projects/$projectId/billing/$documentId/pdf';

  String siteVisitPdfUrl(int projectId, int visitId) =>
      '${AppConfig.clientBaseUrl}/projects/$projectId/site-visits/$visitId/pdf';
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

// ─── Project Detail Data Classes ─────────────────

class ProjectDetail {
  final int id;
  final String? documentNumber;
  final String projectName;
  final String? description;
  final String? status;
  final String? priority;
  final String? startDate;
  final String? expectedEndDate;
  final String? actualEndDate;
  final double contractValue;
  final String? projectType;
  final String? serviceType;
  final String? projectManager;
  final List<ConstructionPhase> phases;

  ProjectDetail({
    required this.id,
    this.documentNumber,
    required this.projectName,
    this.description,
    this.status,
    this.priority,
    this.startDate,
    this.expectedEndDate,
    this.actualEndDate,
    this.contractValue = 0,
    this.projectType,
    this.serviceType,
    this.projectManager,
    this.phases = const [],
  });

  factory ProjectDetail.fromJson(Map<String, dynamic> json) {
    return ProjectDetail(
      id: _toInt(json['id']),
      documentNumber: json['document_number'] as String?,
      projectName: json['project_name'] as String? ?? '',
      description: json['description'] as String?,
      status: json['status'] as String?,
      priority: json['priority'] as String?,
      startDate: json['start_date'] as String?,
      expectedEndDate: json['expected_end_date'] as String?,
      actualEndDate: json['actual_end_date'] as String?,
      contractValue: _toDouble(json['contract_value']),
      projectType: json['project_type'] as String?,
      serviceType: json['service_type'] as String?,
      projectManager: json['project_manager'] as String?,
      phases: _parseList(json['phases'], ConstructionPhase.fromJson),
    );
  }
}

class ProjectProgress {
  final int total;
  final int completed;
  final int inProgress;
  final int pending;
  final int overdue;
  final double percentage;

  ProjectProgress({
    this.total = 0,
    this.completed = 0,
    this.inProgress = 0,
    this.pending = 0,
    this.overdue = 0,
    this.percentage = 0,
  });

  factory ProjectProgress.fromJson(Map<String, dynamic> json) {
    return ProjectProgress(
      total: _toInt(json['total']),
      completed: _toInt(json['completed']),
      inProgress: _toInt(json['in_progress']),
      pending: _toInt(json['pending']),
      overdue: _toInt(json['overdue']),
      percentage: _toDouble(json['percentage']),
    );
  }
}

class ProjectOverviewData {
  final ProjectDetail project;
  final ProjectProgress? progress;
  final Map<String, dynamic> progressByPhase;

  ProjectOverviewData({
    required this.project,
    this.progress,
    this.progressByPhase = const {},
  });

  factory ProjectOverviewData.fromJson(Map<String, dynamic> json) {
    final progressData = json['progress'];
    return ProjectOverviewData(
      project: ProjectDetail.fromJson(json['project'] as Map<String, dynamic>),
      progress: progressData is Map<String, dynamic>
          ? ProjectProgress.fromJson(progressData)
          : null,
      progressByPhase: json['progress_by_phase'] is Map<String, dynamic>
          ? json['progress_by_phase'] as Map<String, dynamic>
          : {},
    );
  }
}

// ─── BOQ ─────────────────────────────────────────

class ProjectBoq {
  final int id;
  final String? version;
  final String? type;
  final double totalAmount;
  final String? status;
  final List<BoqSection> sections;
  final List<BoqItem> items;

  ProjectBoq({
    required this.id,
    this.version,
    this.type,
    this.totalAmount = 0,
    this.status,
    this.sections = const [],
    this.items = const [],
  });

  factory ProjectBoq.fromJson(Map<String, dynamic> json) {
    return ProjectBoq(
      id: _toInt(json['id']),
      version: json['version']?.toString(),
      type: json['type']?.toString(),
      totalAmount: _toDouble(json['total_amount']),
      status: json['status']?.toString(),
      sections: _parseList(json['sections'], BoqSection.fromJson),
      items: _parseList(json['items'], BoqItem.fromJson),
    );
  }
}

class BoqSection {
  final int id;
  final String name;
  final String? description;
  final int sortOrder;
  final List<BoqItem> items;
  final List<BoqSection> children;

  BoqSection({
    required this.id,
    required this.name,
    this.description,
    this.sortOrder = 0,
    this.items = const [],
    this.children = const [],
  });

  factory BoqSection.fromJson(Map<String, dynamic> json) {
    return BoqSection(
      id: _toInt(json['id']),
      name: json['name'] as String? ?? '',
      description: json['description'] as String?,
      sortOrder: _toInt(json['sort_order']),
      items: _parseList(json['items'], BoqItem.fromJson),
      children: _parseList(json['children'], BoqSection.fromJson),
    );
  }
}

class BoqItem {
  final int id;
  final String? itemCode;
  final String? description;
  final String? itemType;
  final String? specification;
  final double quantity;
  final String? unit;
  final double unitPrice;
  final double totalPrice;
  final int sortOrder;

  BoqItem({
    required this.id,
    this.itemCode,
    this.description,
    this.itemType,
    this.specification,
    this.quantity = 0,
    this.unit,
    this.unitPrice = 0,
    this.totalPrice = 0,
    this.sortOrder = 0,
  });

  factory BoqItem.fromJson(Map<String, dynamic> json) {
    return BoqItem(
      id: _toInt(json['id']),
      itemCode: json['item_code'] as String?,
      description: json['description'] as String?,
      itemType: json['item_type'] as String?,
      specification: json['specification'] as String?,
      quantity: _toDouble(json['quantity']),
      unit: json['unit'] as String?,
      unitPrice: _toDouble(json['unit_price']),
      totalPrice: _toDouble(json['total_price']),
      sortOrder: _toInt(json['sort_order']),
    );
  }
}

// ─── Schedule ────────────────────────────────────

class ConstructionPhase {
  final int id;
  final String phaseName;
  final String? startDate;
  final String? endDate;
  final String? status;

  ConstructionPhase({
    required this.id,
    required this.phaseName,
    this.startDate,
    this.endDate,
    this.status,
  });

  factory ConstructionPhase.fromJson(Map<String, dynamic> json) {
    return ConstructionPhase(
      id: _toInt(json['id']),
      phaseName: json['phase_name'] as String? ?? '',
      startDate: json['start_date'] as String?,
      endDate: json['end_date'] as String?,
      status: json['status'] as String?,
    );
  }
}

class ScheduleActivity {
  final int id;
  final String? activityCode;
  final String name;
  final String? phase;
  final String? discipline;
  final String? startDate;
  final String? endDate;
  final int durationDays;
  final String? status;
  final String? startedAt;
  final String? completedAt;
  final String? notes;

  ScheduleActivity({
    required this.id,
    this.activityCode,
    required this.name,
    this.phase,
    this.discipline,
    this.startDate,
    this.endDate,
    this.durationDays = 0,
    this.status,
    this.startedAt,
    this.completedAt,
    this.notes,
  });

  factory ScheduleActivity.fromJson(Map<String, dynamic> json) {
    return ScheduleActivity(
      id: _toInt(json['id']),
      activityCode: json['activity_code'] as String?,
      name: json['name'] as String? ?? '',
      phase: json['phase'] as String?,
      discipline: json['discipline'] as String?,
      startDate: json['start_date'] as String?,
      endDate: json['end_date'] as String?,
      durationDays: _toInt(json['duration_days']),
      status: json['status'] as String?,
      startedAt: json['started_at'] as String?,
      completedAt: json['completed_at'] as String?,
      notes: json['notes'] as String?,
    );
  }
}

class ProjectScheduleData {
  final List<ConstructionPhase> phases;
  final List<ScheduleActivity> activities;

  ProjectScheduleData({this.phases = const [], this.activities = const []});

  factory ProjectScheduleData.fromJson(Map<String, dynamic> json) {
    return ProjectScheduleData(
      phases: _parseList(json['phases'], ConstructionPhase.fromJson),
      activities: _parseList(json['activities'], ScheduleActivity.fromJson),
    );
  }
}

// ─── Financials ──────────────────────────────────

class ProjectFinancials {
  final Map<String, dynamic> summary;
  final List<BillingDocument> billingInvoices;
  final List<BillingDocument> billingQuotes;
  final List<BillingDocument> billingProformas;

  ProjectFinancials({
    required this.summary,
    this.billingInvoices = const [],
    this.billingQuotes = const [],
    this.billingProformas = const [],
  });

  factory ProjectFinancials.fromJson(Map<String, dynamic> json) {
    return ProjectFinancials(
      summary: json['summary'] as Map<String, dynamic>? ?? {},
      billingInvoices:
          _parseList(json['billing_invoices'], BillingDocument.fromJson),
      billingQuotes:
          _parseList(json['billing_quotes'], BillingDocument.fromJson),
      billingProformas:
          _parseList(json['billing_proformas'], BillingDocument.fromJson),
    );
  }

  double get contractValue => _toDouble(summary['contract_value']);
  double get totalInvoiced => _toDouble(summary['total_invoiced']);
  double get totalPaid => _toDouble(summary['total_paid']);
  double get balanceDue => _toDouble(summary['balance_due']);
}

// ─── Documents ───────────────────────────────────

class ProjectDesign {
  final int id;
  final String? designType;
  final String? version;
  final String? fileUrl;
  final String? status;
  final String? clientFeedback;

  ProjectDesign({
    required this.id,
    this.designType,
    this.version,
    this.fileUrl,
    this.status,
    this.clientFeedback,
  });

  factory ProjectDesign.fromJson(Map<String, dynamic> json) {
    return ProjectDesign(
      id: _toInt(json['id']),
      designType: json['design_type']?.toString(),
      version: json['version']?.toString(),
      fileUrl: json['file_url'] as String?,
      status: json['status']?.toString(),
      clientFeedback: json['client_feedback'] as String?,
    );
  }
}

// ─── Reports ─────────────────────────────────────

class DailyReport {
  final int id;
  final String? reportDate;
  final String? weatherConditions;
  final String? workCompleted;
  final String? materialsUsed;
  final String? laborHours;
  final String? issuesFaced;
  final String? supervisor;

  DailyReport({
    required this.id,
    this.reportDate,
    this.weatherConditions,
    this.workCompleted,
    this.materialsUsed,
    this.laborHours,
    this.issuesFaced,
    this.supervisor,
  });

  factory DailyReport.fromJson(Map<String, dynamic> json) {
    return DailyReport(
      id: _toInt(json['id']),
      reportDate: json['report_date'] as String?,
      weatherConditions: json['weather_conditions'] as String?,
      workCompleted: json['work_completed'] as String?,
      materialsUsed: json['materials_used'] as String?,
      laborHours: json['labor_hours']?.toString(),
      issuesFaced: json['issues_faced'] as String?,
      supervisor: json['supervisor'] as String?,
    );
  }
}

class SiteVisit {
  final int id;
  final String? documentNumber;
  final String? visitDate;
  final String? status;
  final String? location;
  final String? description;
  final String? findings;
  final String? recommendations;
  final String? inspector;

  SiteVisit({
    required this.id,
    this.documentNumber,
    this.visitDate,
    this.status,
    this.location,
    this.description,
    this.findings,
    this.recommendations,
    this.inspector,
  });

  factory SiteVisit.fromJson(Map<String, dynamic> json) {
    return SiteVisit(
      id: _toInt(json['id']),
      documentNumber: json['document_number'] as String?,
      visitDate: json['visit_date'] as String?,
      status: json['status'] as String?,
      location: json['location'] as String?,
      description: json['description'] as String?,
      findings: json['findings'] as String?,
      recommendations: json['recommendations'] as String?,
      inspector: json['inspector'] as String?,
    );
  }
}

class ProjectReportsData {
  final List<DailyReport> dailyReports;
  final List<SiteVisit> siteVisits;

  ProjectReportsData({this.dailyReports = const [], this.siteVisits = const []});

  factory ProjectReportsData.fromJson(Map<String, dynamic> json) {
    return ProjectReportsData(
      dailyReports: _parseList(json['daily_reports'], DailyReport.fromJson),
      siteVisits: _parseList(json['site_visits'], SiteVisit.fromJson),
    );
  }
}

// ─── Gallery ─────────────────────────────────────

class ProgressImage {
  final int id;
  final String? title;
  final String? description;
  final String? imageUrl;
  final String? takenAt;
  final String? phase;

  ProgressImage({
    required this.id,
    this.title,
    this.description,
    this.imageUrl,
    this.takenAt,
    this.phase,
  });

  factory ProgressImage.fromJson(Map<String, dynamic> json) {
    return ProgressImage(
      id: _toInt(json['id']),
      title: json['title'] as String?,
      description: json['description'] as String?,
      imageUrl: json['image_url'] as String?,
      takenAt: json['taken_at'] as String?,
      phase: json['phase'] as String?,
    );
  }
}

class ProjectGalleryData {
  final List<ProgressImage> images;
  final List<ConstructionPhase> phases;

  ProjectGalleryData({this.images = const [], this.phases = const []});

  factory ProjectGalleryData.fromJson(Map<String, dynamic> json) {
    return ProjectGalleryData(
      images: _parseList(json['images'], ProgressImage.fromJson),
      phases: _parseList(json['phases'], ConstructionPhase.fromJson),
    );
  }
}
