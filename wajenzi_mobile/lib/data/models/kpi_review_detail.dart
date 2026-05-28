import 'kpi_common.dart';

/// A single KPI line item (rating row).
class KpiRating {
  final int id;
  final String kpa;
  final String measure;
  final String target;
  final double weight;
  final String sectionCode;
  final double? actualAchieved;
  final double? selfRate;
  final double? supervisorRate;
  final double? overallRate;
  final String? comment;
  final int sortOrder;

  const KpiRating({
    required this.id,
    required this.kpa,
    required this.measure,
    required this.target,
    required this.weight,
    required this.sectionCode,
    this.actualAchieved,
    this.selfRate,
    this.supervisorRate,
    this.overallRate,
    this.comment,
    required this.sortOrder,
  });

  factory KpiRating.fromJson(Map<String, dynamic> json) {
    return KpiRating(
      id: kpiToInt(json['id']),
      kpa: kpiToString(json['kpa']),
      measure: kpiToString(json['measure']),
      target: kpiToString(json['target']),
      weight: kpiToDouble(json['weight']),
      sectionCode: kpiToString(json['section_code']),
      actualAchieved: kpiToDoubleOrNull(json['actual_achieved']),
      selfRate: kpiToDoubleOrNull(json['self_rate']),
      supervisorRate: kpiToDoubleOrNull(json['supervisor_rate']),
      overallRate: kpiToDoubleOrNull(json['overall_rate']),
      comment: kpiToStringOrNull(json['comment']),
      sortOrder: kpiToInt(json['sort_order']),
    );
  }
}

/// A grouped section of ratings.
class KpiSection {
  final String code;
  final String title;
  final double weightTotal;
  final List<KpiRating> ratings;

  const KpiSection({
    required this.code,
    required this.title,
    required this.weightTotal,
    required this.ratings,
  });

  factory KpiSection.fromJson(Map<String, dynamic> json) {
    final rawRatings = json['ratings'] as List? ?? [];
    return KpiSection(
      code: kpiToString(json['code']),
      title: kpiToString(json['title']),
      weightTotal: kpiToDouble(json['weight_total']),
      ratings: rawRatings
          .map((e) => KpiRating.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

/// Narrative footer fields.
class KpiFooter {
  final String achievements;
  final String areasOfImprovement;
  final String trainingNeeds;
  final String employeeComments;
  final String supervisorComments;
  final String mdComments;
  final String ceoComments;

  const KpiFooter({
    this.achievements = '',
    this.areasOfImprovement = '',
    this.trainingNeeds = '',
    this.employeeComments = '',
    this.supervisorComments = '',
    this.mdComments = '',
    this.ceoComments = '',
  });

  factory KpiFooter.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const KpiFooter();
    return KpiFooter(
      achievements: kpiToString(json['achievements']),
      areasOfImprovement: kpiToString(json['areas_of_improvement']),
      trainingNeeds: kpiToString(json['training_needs']),
      employeeComments: kpiToString(json['employee_comments']),
      supervisorComments: kpiToString(json['supervisor_comments']),
      mdComments: kpiToString(json['md_comments']),
      ceoComments: kpiToString(json['ceo_comments']),
    );
  }
}

class KpiTimestamps {
  final String? selfSubmittedAt;
  final String? supervisorReviewedAt;
  final String? mdReviewedAt;
  final String? completedAt;

  const KpiTimestamps({
    this.selfSubmittedAt,
    this.supervisorReviewedAt,
    this.mdReviewedAt,
    this.completedAt,
  });

  factory KpiTimestamps.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const KpiTimestamps();
    return KpiTimestamps(
      selfSubmittedAt: kpiToStringOrNull(json['self_submitted_at']),
      supervisorReviewedAt: kpiToStringOrNull(json['supervisor_reviewed_at']),
      mdReviewedAt: kpiToStringOrNull(json['md_reviewed_at']),
      completedAt: kpiToStringOrNull(json['completed_at']),
    );
  }
}

class KpiPermissions {
  final bool canFill;
  final bool canRecall;
  final bool canReview;

  /// One of: supervisor, md, ceo (or empty).
  final String reviewStage;

  const KpiPermissions({
    this.canFill = false,
    this.canRecall = false,
    this.canReview = false,
    this.reviewStage = '',
  });

  factory KpiPermissions.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const KpiPermissions();
    return KpiPermissions(
      canFill: json['can_fill'] == true,
      canRecall: json['can_recall'] == true,
      canReview: json['can_review'] == true,
      reviewStage: kpiToString(json['review_stage']),
    );
  }
}

/// Detailed employee reference (may include department).
class KpiEmployee {
  final int id;
  final String name;
  final String? department;

  const KpiEmployee({required this.id, required this.name, this.department});

  factory KpiEmployee.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const KpiEmployee(id: 0, name: '');
    return KpiEmployee(
      id: kpiToInt(json['id']),
      name: kpiToString(json['name']),
      department: kpiToStringOrNull(json['department']),
    );
  }
}

/// Full response of `GET /performance/{id}`.
class KpiReviewDetail {
  final int id;
  final String reviewNumber;
  final String status;
  final String statusLabel;
  final String periodLabel;
  final String? periodStart;
  final String? periodEnd;
  final KpiEmployee employee;
  final KpiRef? supervisor;
  final KpiRef template;
  final double totalSelfScore;
  final double totalSupervisorScore;
  final double totalOverallScore;
  final String gradeLabel;
  final KpiFooter footer;
  final KpiTimestamps timestamps;
  final List<KpiSection> sections;
  final KpiPermissions permissions;
  final String? pdfUrl;

  const KpiReviewDetail({
    required this.id,
    required this.reviewNumber,
    required this.status,
    required this.statusLabel,
    required this.periodLabel,
    this.periodStart,
    this.periodEnd,
    required this.employee,
    this.supervisor,
    required this.template,
    required this.totalSelfScore,
    required this.totalSupervisorScore,
    required this.totalOverallScore,
    required this.gradeLabel,
    required this.footer,
    required this.timestamps,
    required this.sections,
    required this.permissions,
    this.pdfUrl,
  });

  /// Convenience: flatten all ratings across sections.
  List<KpiRating> get allRatings =>
      [for (final s in sections) ...s.ratings];

  factory KpiReviewDetail.fromJson(Map<String, dynamic> json) {
    final rawSections = json['sections'] as List? ?? [];
    final supervisorRaw = json['supervisor'];
    return KpiReviewDetail(
      id: kpiToInt(json['id']),
      reviewNumber: kpiToString(json['review_number']),
      status: kpiToString(json['status']),
      statusLabel: kpiToString(json['status_label']),
      periodLabel: kpiToString(json['period_label']),
      periodStart: kpiToStringOrNull(json['period_start']),
      periodEnd: kpiToStringOrNull(json['period_end']),
      employee: KpiEmployee.fromJson(json['employee'] as Map<String, dynamic>?),
      supervisor: supervisorRaw is Map<String, dynamic>
          ? KpiRef.fromJson(supervisorRaw)
          : null,
      template: KpiRef.fromJson(json['template'] as Map<String, dynamic>?),
      totalSelfScore: kpiToDouble(json['total_self_score']),
      totalSupervisorScore: kpiToDouble(json['total_supervisor_score']),
      totalOverallScore: kpiToDouble(json['total_overall_score']),
      gradeLabel: kpiToString(json['grade_label']),
      footer: KpiFooter.fromJson(json['footer'] as Map<String, dynamic>?),
      timestamps:
          KpiTimestamps.fromJson(json['timestamps'] as Map<String, dynamic>?),
      sections: rawSections
          .map((e) => KpiSection.fromJson(e as Map<String, dynamic>))
          .toList(),
      permissions:
          KpiPermissions.fromJson(json['permissions'] as Map<String, dynamic>?),
      pdfUrl: kpiToStringOrNull(json['pdf_url']),
    );
  }
}

/// Live weighted-score helper: Σ (rate/100 * weight) across the given rates.
/// Mirrors the server scoring formula.
double kpiWeightedScore(Iterable<({double weight, double? rate})> rows) {
  double total = 0;
  for (final r in rows) {
    final rate = r.rate;
    if (rate == null) continue;
    total += (rate / 100.0) * r.weight;
  }
  return total;
}
