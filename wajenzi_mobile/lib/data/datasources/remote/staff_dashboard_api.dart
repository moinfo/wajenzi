import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/network/api_client.dart';

final staffDashboardApiProvider = Provider<StaffDashboardApi>((ref) {
  final apiClient = ref.watch(apiClientProvider);
  return StaffDashboardApi(apiClient);
});

class StaffDashboardApi {
  final ApiClient _apiClient;

  StaffDashboardApi(this._apiClient);

  Future<StaffDashboardData> fetchDashboard() async {
    final response = await _apiClient.get('/dashboard');
    final data = response.data['data'] as Map<String, dynamic>;
    return StaffDashboardData.fromJson(data);
  }

  Future<List<DashboardActivity>> fetchActivities({int page = 1}) async {
    final response = await _apiClient.get(
      '/dashboard/activities',
      queryParameters: {'page': page},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => DashboardActivity.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<DashboardInvoice>> fetchInvoices({int page = 1}) async {
    final response = await _apiClient.get(
      '/dashboard/invoices',
      queryParameters: {'page': page},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => DashboardInvoice.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<DashboardFollowup>> fetchFollowups({int page = 1}) async {
    final response = await _apiClient.get(
      '/dashboard/followups',
      queryParameters: {'page': page},
    );
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => DashboardFollowup.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<RecentActivity>> fetchRecentActivities() async {
    final response = await _apiClient.get('/dashboard/recent-activities');
    final list = response.data['data'] as List? ?? [];
    return list
        .map((e) => RecentActivity.fromJson(e as Map<String, dynamic>))
        .toList();
  }
}

// ─── Helper conversions ─────────────────────────

double _toDouble(dynamic v) {
  if (v == null) return 0;
  if (v is num) return v.toDouble();
  if (v is String) return double.tryParse(v) ?? 0;
  return 0;
}

int _toInt(dynamic v) {
  if (v == null) return 0;
  if (v is int) return v;
  if (v is num) return v.toInt();
  if (v is String) return int.tryParse(v) ?? 0;
  return 0;
}

// ─── Data Models ────────────────────────────────

class StaffDashboardData {
  final DashboardStats stats;
  final PendingApprovals pendingApprovals;
  final StatusSummary followupSummary;
  final ActivitiesSummary activitiesSummary;
  final InvoicesSummary invoicesSummary;
  final ProjectProgressData projectProgress;

  StaffDashboardData({
    required this.stats,
    required this.pendingApprovals,
    required this.followupSummary,
    required this.activitiesSummary,
    required this.invoicesSummary,
    required this.projectProgress,
  });

  factory StaffDashboardData.fromJson(Map<String, dynamic> json) {
    return StaffDashboardData(
      stats: DashboardStats.fromJson(
          json['stats'] as Map<String, dynamic>? ?? {}),
      pendingApprovals: PendingApprovals.fromJson(
          json['pending_approvals'] as Map<String, dynamic>? ?? {}),
      followupSummary: StatusSummary.fromJson(
          json['followup_summary'] as Map<String, dynamic>? ?? {}),
      activitiesSummary: ActivitiesSummary.fromJson(
          json['activities_summary'] as Map<String, dynamic>? ?? {}),
      invoicesSummary: InvoicesSummary.fromJson(
          json['invoices_summary'] as Map<String, dynamic>? ?? {}),
      projectProgress: ProjectProgressData.fromJson(
          json['project_progress'] as Map<String, dynamic>? ?? {}),
    );
  }
}

class DashboardStats {
  final double totalRevenue;
  final double revenueChangePercent;
  final int activeProjects;
  final int newProjectsThisMonth;
  final TeamMembers teamMembers;
  final BudgetUtilization budgetUtilization;

  DashboardStats({
    this.totalRevenue = 0,
    this.revenueChangePercent = 0,
    this.activeProjects = 0,
    this.newProjectsThisMonth = 0,
    required this.teamMembers,
    required this.budgetUtilization,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    return DashboardStats(
      totalRevenue: _toDouble(json['total_revenue']),
      revenueChangePercent: _toDouble(json['revenue_change_percent']),
      activeProjects: _toInt(json['active_projects']),
      newProjectsThisMonth: _toInt(json['new_projects_this_month']),
      teamMembers: TeamMembers.fromJson(
          json['team_members'] as Map<String, dynamic>? ?? {}),
      budgetUtilization: BudgetUtilization.fromJson(
          json['budget_utilization'] as Map<String, dynamic>? ?? {}),
    );
  }
}

class TeamMembers {
  final int total;
  final int male;
  final int female;

  TeamMembers({this.total = 0, this.male = 0, this.female = 0});

  factory TeamMembers.fromJson(Map<String, dynamic> json) {
    return TeamMembers(
      total: _toInt(json['total']),
      male: _toInt(json['male']),
      female: _toInt(json['female']),
    );
  }
}

class BudgetUtilization {
  final double totalBudget;
  final double totalSpent;
  final int percentage;

  BudgetUtilization({
    this.totalBudget = 0,
    this.totalSpent = 0,
    this.percentage = 0,
  });

  factory BudgetUtilization.fromJson(Map<String, dynamic> json) {
    return BudgetUtilization(
      totalBudget: _toDouble(json['total_budget']),
      totalSpent: _toDouble(json['total_spent']),
      percentage: _toInt(json['percentage']),
    );
  }
}

class PendingApprovals {
  final int total;
  final List<ApprovalItem> items;

  PendingApprovals({this.total = 0, this.items = const []});

  factory PendingApprovals.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'] as List? ?? [];
    return PendingApprovals(
      total: _toInt(json['total']),
      items: rawItems
          .map((e) => ApprovalItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class ApprovalItem {
  final String type;
  final String label;
  final int count;
  final String icon;

  ApprovalItem({
    required this.type,
    required this.label,
    this.count = 0,
    this.icon = '',
  });

  factory ApprovalItem.fromJson(Map<String, dynamic> json) {
    return ApprovalItem(
      type: json['type'] as String? ?? '',
      label: json['label'] as String? ?? '',
      count: _toInt(json['count']),
      icon: json['icon'] as String? ?? '',
    );
  }
}

class StatusSummary {
  final int overdue;
  final int today;
  final int upcoming;
  final int completedThisMonth;

  StatusSummary({
    this.overdue = 0,
    this.today = 0,
    this.upcoming = 0,
    this.completedThisMonth = 0,
  });

  factory StatusSummary.fromJson(Map<String, dynamic> json) {
    return StatusSummary(
      overdue: _toInt(json['overdue']),
      today: _toInt(json['today']),
      upcoming: _toInt(json['upcoming']),
      completedThisMonth: _toInt(json['completed_this_month']),
    );
  }
}

class ActivitiesSummary {
  final int overdue;
  final int dueToday;
  final int pending;
  final int inProgress;

  ActivitiesSummary({
    this.overdue = 0,
    this.dueToday = 0,
    this.pending = 0,
    this.inProgress = 0,
  });

  factory ActivitiesSummary.fromJson(Map<String, dynamic> json) {
    return ActivitiesSummary(
      overdue: _toInt(json['overdue']),
      dueToday: _toInt(json['due_today']),
      pending: _toInt(json['pending']),
      inProgress: _toInt(json['in_progress']),
    );
  }
}

class InvoicesSummary {
  final int overdue;
  final int dueToday;
  final int upcoming;
  final int paidThisMonth;

  InvoicesSummary({
    this.overdue = 0,
    this.dueToday = 0,
    this.upcoming = 0,
    this.paidThisMonth = 0,
  });

  factory InvoicesSummary.fromJson(Map<String, dynamic> json) {
    return InvoicesSummary(
      overdue: _toInt(json['overdue']),
      dueToday: _toInt(json['due_today']),
      upcoming: _toInt(json['upcoming']),
      paidThisMonth: _toInt(json['paid_this_month']),
    );
  }
}

class ProjectProgressData {
  final double overallPercentage;
  final int totalActivities;
  final int completed;
  final int inProgress;
  final int pending;
  final int overdue;
  final List<ProjectProgressItem> projects;

  ProjectProgressData({
    this.overallPercentage = 0,
    this.totalActivities = 0,
    this.completed = 0,
    this.inProgress = 0,
    this.pending = 0,
    this.overdue = 0,
    this.projects = const [],
  });

  factory ProjectProgressData.fromJson(Map<String, dynamic> json) {
    final rawProjects = json['projects'] as List? ?? [];
    return ProjectProgressData(
      overallPercentage: _toDouble(json['overall_percentage']),
      totalActivities: _toInt(json['total_activities']),
      completed: _toInt(json['completed']),
      inProgress: _toInt(json['in_progress']),
      pending: _toInt(json['pending']),
      overdue: _toInt(json['overdue']),
      projects: rawProjects
          .map((e) => ProjectProgressItem.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

class ProjectProgressItem {
  final int id;
  final String? name;
  final String? leadName;
  final double percentage;
  final int completed;
  final int inProgress;
  final int pending;
  final int overdue;

  ProjectProgressItem({
    required this.id,
    this.name,
    this.leadName,
    this.percentage = 0,
    this.completed = 0,
    this.inProgress = 0,
    this.pending = 0,
    this.overdue = 0,
  });

  factory ProjectProgressItem.fromJson(Map<String, dynamic> json) {
    return ProjectProgressItem(
      id: _toInt(json['id']),
      name: json['name'] as String?,
      leadName: json['lead_name'] as String?,
      percentage: _toDouble(json['percentage']),
      completed: _toInt(json['completed']),
      inProgress: _toInt(json['in_progress']),
      pending: _toInt(json['pending']),
      overdue: _toInt(json['overdue']),
    );
  }
}

// ─── Detail list models ─────────────────────────

class DashboardActivity {
  final int id;
  final String? activityCode;
  final String? name;
  final String? phase;
  final String? assignedTo;
  final String? startDate;
  final String? endDate;
  final int durationDays;
  final String? status;
  final bool isOverdue;
  final String? projectName;

  DashboardActivity({
    required this.id,
    this.activityCode,
    this.name,
    this.phase,
    this.assignedTo,
    this.startDate,
    this.endDate,
    this.durationDays = 0,
    this.status,
    this.isOverdue = false,
    this.projectName,
  });

  factory DashboardActivity.fromJson(Map<String, dynamic> json) {
    return DashboardActivity(
      id: _toInt(json['id']),
      activityCode: json['activity_code'] as String?,
      name: json['name'] as String?,
      phase: json['phase'] as String?,
      assignedTo: json['assigned_to'] as String?,
      startDate: json['start_date'] as String?,
      endDate: json['end_date'] as String?,
      durationDays: _toInt(json['duration_days']),
      status: json['status'] as String?,
      isOverdue: json['is_overdue'] == true,
      projectName: json['project_name'] as String?,
    );
  }
}

class DashboardInvoice {
  final int id;
  final String? documentNumber;
  final String? clientName;
  final String? projectName;
  final String? dueDate;
  final double totalAmount;
  final double balanceAmount;
  final String? status;
  final bool isOverdue;
  final int daysOverdue;

  DashboardInvoice({
    required this.id,
    this.documentNumber,
    this.clientName,
    this.projectName,
    this.dueDate,
    this.totalAmount = 0,
    this.balanceAmount = 0,
    this.status,
    this.isOverdue = false,
    this.daysOverdue = 0,
  });

  factory DashboardInvoice.fromJson(Map<String, dynamic> json) {
    return DashboardInvoice(
      id: _toInt(json['id']),
      documentNumber: json['document_number'] as String?,
      clientName: json['client_name'] as String?,
      projectName: json['project_name'] as String?,
      dueDate: json['due_date'] as String?,
      totalAmount: _toDouble(json['total_amount']),
      balanceAmount: _toDouble(json['balance_amount']),
      status: json['status'] as String?,
      isOverdue: json['is_overdue'] == true,
      daysOverdue: _toInt(json['days_overdue']),
    );
  }
}

class DashboardFollowup {
  final int id;
  final String? leadName;
  final String? clientName;
  final String? followupDate;
  final String? details;
  final String? nextStep;
  final String? status;
  final bool isOverdue;

  DashboardFollowup({
    required this.id,
    this.leadName,
    this.clientName,
    this.followupDate,
    this.details,
    this.nextStep,
    this.status,
    this.isOverdue = false,
  });

  factory DashboardFollowup.fromJson(Map<String, dynamic> json) {
    return DashboardFollowup(
      id: _toInt(json['id']),
      leadName: json['lead_name'] as String?,
      clientName: json['client_name'] as String?,
      followupDate: json['followup_date'] as String?,
      details: json['details'] as String?,
      nextStep: json['next_step'] as String?,
      status: json['status'] as String?,
      isOverdue: json['is_overdue'] == true,
    );
  }
}

class RecentActivity {
  final String type;
  final String icon;
  final String color;
  final String message;
  final String? timestamp;
  final String? timeAgo;

  RecentActivity({
    required this.type,
    this.icon = '',
    this.color = '',
    this.message = '',
    this.timestamp,
    this.timeAgo,
  });

  factory RecentActivity.fromJson(Map<String, dynamic> json) {
    return RecentActivity(
      type: json['type'] as String? ?? '',
      icon: json['icon'] as String? ?? '',
      color: json['color'] as String? ?? '',
      message: json['message'] as String? ?? '',
      timestamp: json['timestamp'] as String?,
      timeAgo: json['time_ago'] as String?,
    );
  }
}
