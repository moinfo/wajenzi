import 'kpi_common.dart';

/// Lightweight review row used in [KpiListScreen].
class KpiReviewListItem {
  final int id;
  final String reviewNumber;
  final KpiRef employee;
  final KpiRef template;
  final String periodLabel;
  final String status;
  final String statusLabel;
  final double totalSelfScore;
  final double totalSupervisorScore;
  final double totalOverallScore;
  final String gradeLabel;
  final bool canFill;
  final bool canReview;
  final bool canRecall;

  const KpiReviewListItem({
    required this.id,
    required this.reviewNumber,
    required this.employee,
    required this.template,
    required this.periodLabel,
    required this.status,
    required this.statusLabel,
    required this.totalSelfScore,
    required this.totalSupervisorScore,
    required this.totalOverallScore,
    required this.gradeLabel,
    required this.canFill,
    required this.canReview,
    required this.canRecall,
  });

  factory KpiReviewListItem.fromJson(Map<String, dynamic> json) {
    return KpiReviewListItem(
      id: kpiToInt(json['id']),
      reviewNumber: kpiToString(json['review_number']),
      employee: KpiRef.fromJson(json['employee'] as Map<String, dynamic>?),
      template: KpiRef.fromJson(json['template'] as Map<String, dynamic>?),
      periodLabel: kpiToString(json['period_label']),
      status: kpiToString(json['status']),
      statusLabel: kpiToString(json['status_label']),
      totalSelfScore: kpiToDouble(json['total_self_score']),
      totalSupervisorScore: kpiToDouble(json['total_supervisor_score']),
      totalOverallScore: kpiToDouble(json['total_overall_score']),
      gradeLabel: kpiToString(json['grade_label']),
      canFill: json['can_fill'] == true,
      canReview: json['can_review'] == true,
      canRecall: json['can_recall'] == true,
    );
  }
}

/// Counts shown on the list tabs.
class KpiCounts {
  final int mineOpen;
  final int awaiting;

  const KpiCounts({this.mineOpen = 0, this.awaiting = 0});

  factory KpiCounts.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const KpiCounts();
    return KpiCounts(
      mineOpen: kpiToInt(json['mine_open']),
      awaiting: kpiToInt(json['awaiting']),
    );
  }
}

/// Full response of `GET /performance`.
class KpiReviewListResponse {
  final List<KpiReviewListItem> reviews;
  final KpiCounts counts;
  final bool canSeeAll;
  final String tab;
  final int currentPage;
  final int lastPage;
  final int total;

  const KpiReviewListResponse({
    required this.reviews,
    required this.counts,
    required this.canSeeAll,
    required this.tab,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  factory KpiReviewListResponse.fromResponse(
    Map<String, dynamic> data,
    Map<String, dynamic>? meta,
  ) {
    final rawReviews = data['reviews'] as List? ?? [];
    return KpiReviewListResponse(
      reviews: rawReviews
          .map((e) => KpiReviewListItem.fromJson(e as Map<String, dynamic>))
          .toList(),
      counts: KpiCounts.fromJson(data['counts'] as Map<String, dynamic>?),
      canSeeAll: data['can_see_all'] == true,
      tab: kpiToString(data['tab']),
      currentPage: kpiToInt(meta?['current_page']),
      lastPage: kpiToInt(meta?['last_page']),
      total: kpiToInt(meta?['total']),
    );
  }
}
